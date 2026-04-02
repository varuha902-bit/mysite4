<?php

namespace WPEventGenius\Common\Event;

use WPEventGenius\Common\Event\EventPost;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class EventQuery {
    protected $params;
    protected $args = [];
    protected $calendar_settings = [];
    protected $max_num_pages = 1;

    /**
     * Initialize query with optional calendar ID and additional parameters
     *
     * @param int|null $calendar_id Optional calendar ID to get settings from
     * @param array $params Optional parameters to override calendar settings
     */
    public function __construct($params = []) {
        $this->args['post_type'] = EVGE_EVENT_POST_TYPE;
        // Default to 'publish' status to exclude scheduled/future posts unless explicitly overridden
        if (!isset($params['post_status'])) {
            $this->args['post_status'] = 'publish';
        } else {
            $this->args['post_status'] = $params['post_status'];
        }
        $this->params = $params;
    }

    public function set_params($params) {
        $this->params = $params;
    }

    /**
     * Apply additional parameters to query
     */
    public function apply_params() {

        if (isset($this->params['view']) && $this->params['view'] === 'month') {
            $this->apply_month_params();
        } else {
            $this->apply_list_params();
        }

        $this->apply_filter_params();
    }

    /**
     * Build tax_query for event category and tag filters.
     * Uses existing filters format; filter bar category_id/tag_id are folded in.
     * Calendar "filters" support include/exclude actions and AND/OR relationships.
     * Works across taxonomies (e.g. one category + one tag).
     *
     * @return array WP_Query tax_query format, or empty array if nothing to filter.
     */
    protected function build_tax_query_for_filters() {
        $builder_clauses = [];
        $filter_bar_clauses = [];

        // Builder "Event Filters" can be AND/OR'd amongst themselves.
        $builder_relation = 'AND';
        if ( ! empty( $this->params['filters'] ) && is_array( $this->params['filters'] ) ) {
            $candidate_relation = $this->params['filter_relationship'] ?? 'AND';
            $builder_relation = in_array( $candidate_relation, [ 'AND', 'OR' ], true ) ? $candidate_relation : 'AND';
        }

        // Calendar builder "Event Filters" (include/exclude).
        if ( ! empty( $this->params['filters'] ) && is_array( $this->params['filters'] ) ) {
            foreach ( $this->params['filters'] as $filter ) {
                if ( empty( $filter['terms'] ) ) {
                    continue;
                }

                $taxonomy = ( isset( $filter['type'] ) && $filter['type'] === 'tag' )
                    ? EVGE_EVENT_TAG_TYPE
                    : EVGE_EVENT_CATEGORY_TYPE;

                $action = $filter['action'] ?? 'include';
                $operator = $action === 'exclude' ? 'NOT IN' : 'IN';

                $builder_clauses[] = [
                    'taxonomy' => $taxonomy,
                    'field'    => 'term_id',
                    'terms'    => array_map( 'absint', (array) $filter['terms'] ),
                    'operator' => $operator,
                ];
            }
        }

        // Filter bar selections should always narrow results (AND with the builder filter group).
        if ( ! empty( $this->params['category_id'] ) ) {
            $filter_bar_clauses[] = [
                'taxonomy' => EVGE_EVENT_CATEGORY_TYPE,
                'field'    => 'term_id',
                'terms'    => absint( $this->params['category_id'] ),
                'operator' => 'IN',
            ];
        }

        if ( ! empty( $this->params['tag_id'] ) ) {
            $filter_bar_clauses[] = [
                'taxonomy' => EVGE_EVENT_TAG_TYPE,
                'field'    => 'term_id',
                'terms'    => absint( $this->params['tag_id'] ),
                'operator' => 'IN',
            ];
        }

        if ( empty( $builder_clauses ) && empty( $filter_bar_clauses ) ) {
            return [];
        }

        // Only builder filters
        if ( empty( $filter_bar_clauses ) ) {
            return [
                'relation' => $builder_relation,
                ...$builder_clauses,
            ];
        }

        // Only filter bar filters
        if ( empty( $builder_clauses ) ) {
            return [
                'relation' => 'AND',
                ...$filter_bar_clauses,
            ];
        }

        // Builder filters group combined with filter bar using AND.
        return [
            'relation' => 'AND',
            array_merge(
                [ 'relation' => $builder_relation ],
                $builder_clauses
            ),
            ...$filter_bar_clauses,
        ];
    }

    /**
     * Get the final query arguments
     */
    public function get_args() {
        return $this->args;
    }

    /**
     * Execute the query and return results
     */
    public function get_results() {
        $wp_query = new \WP_Query($this->args);
        
        // Calculate max_num_pages
        if (isset( $this->args['posts_per_page'] ) && $this->args['posts_per_page'] === -1 ) {
            $this->max_num_pages = 1;
        } elseif (isset( $this->args['posts_per_page'] ) && $this->args['posts_per_page'] > 0 ) {
            $this->max_num_pages = ceil($wp_query->found_posts / $this->args['posts_per_page']);
        } else {
            $this->max_num_pages = 1;
        }
        
        return $wp_query;
    }

    /**
     * Get the maximum number of pages for the current query
     *
     * @return int The maximum number of pages
     */
    public function get_max_num_pages() {
        return $this->max_num_pages;
    }

    /**
     * Get events based on query arguments
     *
     * @param array $args Query arguments
     * @return array Array of EventPost objects
     */
    public function get_events() {
        $wp_query = $this->get_results();
        $posts = $wp_query->posts;

        return array_map(function($post) {
            return new EventPost($post->ID);
        }, $posts);
    }

    public function get_total_count() {
        $wp_query = $this->get_results();
        return $wp_query->found_posts;
    }

    /**
     * Get all events that overlap with a specific calendar day
     *
     * @param string $date Date in Y-m-d format
     * @return array Array of EventPost objects
     */
    public function get_events_for_day($date) {
        // Store original query args
        $original_args = $this->args;

        // Set up meta query for date range
        $this->args['meta_query'] = [
            'relation' => 'AND',
            'date_range' => [
                'relation' => 'AND',
                [
                    'key' => 'evge_start_date',
                    'value' => $date,
                    'compare' => '<=',
                    'type' => 'DATE'
                ],
                [
                    'key' => 'evge_end_date',
                    'value' => $date,
                    'compare' => '>=',
                    'type' => 'DATE'
                ]
            ]
        ];
        $this->apply_filter_params();

        // Get all events for the day
        $this->args['posts_per_page'] = 100;
        $this->args['paged'] = 1;

        // Get the events
        $events = $this->get_events();

        // Restore original query args
        $this->args = $original_args;

        return $events;
    }

    protected function apply_filter_params() {

        // Build a single tax_query from filters + filter bar category/tag (all include, relation AND).
        $tax_query = $this->build_tax_query_for_filters();

        if ( ! empty( $tax_query ) ) {
            $this->args['tax_query'] = $tax_query;
        }

        // Handle search
        if (!empty($this->params['search'])) {
            $this->args['s'] = sanitize_text_field($this->params['search']);
        }

        // Handle taxonomy overrides
        if (!empty($this->params['tax_query'])) {
            $this->args['tax_query'] = $this->params['tax_query'];
        }

        // Handle organizer filter
        if (!empty($this->params['organizer_id'])) {
            $this->args['meta_query'][] = [
                'key' => 'evge_organizer',
                'value' => absint($this->params['organizer_id']),
                'compare' => '='
            ];
        }

        // Handle venue filter
        if (!empty($this->params['venue_id'])) {
            $this->args['meta_query'][] = [
                'key' => 'evge_venue',
                'value' => absint($this->params['venue_id']),
                'compare' => '='
            ];
        }

        // Handle series filter
        if (!empty($this->params['series_id'])) {
            $this->args['meta_query'][] = [
                'key' => 'evge_series_id',
                'value' => absint($this->params['series_id']),
                'compare' => '='
            ];
        }
    }

    protected function apply_list_params() {
        $time_filter = $this->params['time_filter'] ?? $this->params['qtype'] ?? 'upcoming';
        // Handle time filter (past/upcoming)
        $current_date = wp_date('Y-m-d H:i:s');
    

        if ($time_filter === 'all') {
            $this->args['meta_query'] = [
                'evge_end_date' => [
                    'key' => 'evge_end_date',
                    'compare' => 'EXISTS'
                ]
            ];
            // show all events in descending order
            $this->args['orderby'] = [
                'evge_end_date' => 'DESC',
                'title' => 'ASC'
            ];
        } elseif ($time_filter === 'past') {
            // For past events, we want events that ended before today
            $this->args['meta_query'] = [
                'relation' => 'AND',
                'evge_end_date' => [
                    'key' => 'evge_end_date',
                    'value' => $current_date,
                    'compare' => '<',
                    'type' => 'DATETIME'
                ],
                'evge_start_date' => [
                    'key' => 'evge_start_date',
                    'compare' => 'EXISTS'
                ]
            ];

            $this->args['orderby'] = [
                'evge_start_date' => 'DESC',
                'title' => 'ASC'
            ];
                    } elseif ($time_filter === 'custom') {
            // For custom date filter (After Custom Date), events that start after the specified date
            // Same logic as admin area - uses 'start' parameter
            $start_date = isset($this->params['start']) ? wp_date('Y-m-d H:i:s', strtotime($this->params['start'])) : $current_date;
            $this->args['meta_query'] = [
                [
                    'key' => 'evge_start_date',
                    'value' => $start_date,
                    'compare' => '>',
                    'type' => 'DATETIME'
                ]
            ];

            $this->args['orderby'] = [
                'evge_start_date' => 'ASC',
                'title' => 'ASC'
            ];
        } else {
            // For upcoming events, we want events that end after today
            $this->args['meta_query'] = [
                'relation' => 'AND',
                'evge_end_date' => [
                    'key' => 'evge_end_date',
                    'value' => $current_date,
                    'compare' => '>=',
                    'type' => 'DATETIME'
                ],
                'evge_start_date' => [
                    'key' => 'evge_start_date',
                    'compare' => 'EXISTS'
                ]
            ];

            $this->args['orderby'] = [
                'evge_start_date' => 'ASC',
                'title' => 'ASC'
            ];
        }

        if (!empty($this->params['num'])) {
            if ($this->params['num'] === 'all' || $this->params['num'] === -1) {
                $this->args['posts_per_page'] = -1;
            } else {
                $this->args['posts_per_page'] = absint($this->params['num']);
            }
        }

        // Handle pagination
        if (isset($this->params['paged'])) {
            $this->args['paged'] = max(1, absint($this->params['paged']));
        }

    }

    protected function apply_month_params() {
        // always show all events for month view
        $this->args['posts_per_page'] = -1;

        // Get the first and last day of the month
        $month_start = new \DateTime(($this->params['month'] ?? wp_date('Y-m')) . '-01');
        $month_end = clone $month_start;
        $month_end->modify('last day of this month');

        // Adjust start date to beginning of week
        $week_start = clone $month_start;
        $week_start->modify('monday this week');

        // Adjust end date to end of week
        $week_end = clone $month_end;
        $week_end->modify('sunday this week');

        // If the month starts on a Monday, use the first of the month
        if ($month_start->format('N') === '1') {
            $start_date = $month_start->format('Y-m-d');
        } else {
            $start_date = $week_start->format('Y-m-d');
        }

        // If the month ends on a Sunday, use the last of the month
        if ($month_end->format('N') === '7') {
            $end_date = $month_end->format('Y-m-d');
        } else {
            $end_date = $week_end->format('Y-m-d');
        }
            
        $this->args['meta_query']['start_date']['value'] = [$start_date, $end_date];
        $this->args['meta_query']['start_date']['compare'] = 'BETWEEN';
    }
}