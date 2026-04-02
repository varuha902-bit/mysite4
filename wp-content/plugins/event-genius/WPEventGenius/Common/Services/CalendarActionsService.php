<?php

namespace WPEventGenius\Common\Services;

use WPEventGenius\Common\Calendars\CalendarBuilder;
use WPEventGenius\Common\Event\EventPost;
use WPEventGenius\Common\Event\EventQuery;
use WPEventGenius\Common\Utils\Icon;
use WPEventGenius\Common\Utils\Settings;
use WPEventGenius\Common\Utils\Utils;
use WPEventGenius\Common\Displays\CalendarDisplay;
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class CalendarActionsService {
    
    
    public function __construct() {
    }

    public function init_hooks() {
        add_action('wp_ajax_evge_calendar_navigation', array($this, 'handle_navigation'));
	    add_action('wp_ajax_nopriv_evge_calendar_navigation', array($this, 'handle_navigation'));
	    add_action('wp_ajax_evge_get_event_details', array($this, 'get_event_details'));
        add_action('wp_ajax_nopriv_evge_get_event_details', array($this, 'get_event_details'));
	    add_action('wp_ajax_evge_get_day_events', array($this, 'get_day_events'));
	    add_action('wp_ajax_nopriv_evge_get_day_events', array($this, 'get_day_events'));

        add_shortcode('event_genius_calendar', array($this, 'render_calendar_shortcode'));

        // Add REST API endpoint
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }

    /**
     * Calculate settings by merging calendar settings with navigation and filter inputs
     *
     * @param array $form_data Validated form data
     * @param bool $is_preview Whether this is a preview request
     * @return array Calculated settings
     */
    protected function calculate_settings($form_data, $is_preview = false) {
        // Initialize base settings
        $settings = [
            'view' => $form_data['view'],
            'month' => $form_data['month'],
            'preview' => $is_preview,
            'num' => $form_data['num'],
            'paged' => $form_data['paged'],
            'direction' => $form_data['direction'],
            'current_date' => $form_data['current_date'],
            'time_filter' => $form_data['time_filter']
        ];

        // Get settings if calendar_id exists
        if (!empty($form_data['calendar_id'])) {
            $calendar_settings = get_term_meta($form_data['calendar_id'], 'evge_calendar_settings', true);
            if ($calendar_settings) {
                // Merge calendar settings with base settings
                $settings = array_merge($calendar_settings, $settings);
                $settings['calendar_id'] = $form_data['calendar_id'];
            }
        }

        // Handle preview mode
        if ($is_preview && !empty($form_data['preview'])) {
            $preview_settings = get_transient('evge_calendar_preview_' . $form_data['preview']);
            if ($preview_settings) {
                // Merge preview settings, keeping navigation values
                $settings = array_merge($preview_settings, [
                    'month' => $form_data['month'],
                    'paged' => 1,
                    'id' => 0,
                    'direction' => $form_data['direction'],
                    'current_date' => $form_data['current_date'],
                    'time_filter' => $form_data['time_filter']
                ]);
            }
        }

        // Apply navigation and filter overrides
        if (!empty($form_data['search'])) {
            $settings['search'] = $form_data['search'];
        }

        if (!empty($form_data['selectedDate'])) {
            $settings['selectedDate'] = $form_data['selectedDate'];
        }

        if (!empty($form_data['venue_id'])) {
            $settings['venue_id'] = $form_data['venue_id'];
        }

        if (!empty($form_data['category_id'])) {
            $settings['category_id'] = $form_data['category_id'];
        }

        if (!empty($form_data['tag_id'])) {
            $settings['tag_id'] = $form_data['tag_id'];
        }

        // Ensure view type is valid
        $settings['view'] = in_array($settings['view'], ['month', 'week', 'grid', 'list']) 
            ? $settings['view'] 
            : 'month';

        // Ensure pagination values are valid
        $settings['num'] = max(1, min(100, absint($settings['num'])));
        $settings['paged'] = max(1, absint($settings['paged']));

        return $settings;
    }

    public function handle_navigation() {
        // no checks as it's used in the frontend
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $calendar_id = isset($_POST['calendar_id']) ? sanitize_key($_POST['calendar_id']) : 'default';

        $calendar_display = new CalendarDisplay($calendar_id);

        $form_inputs = [];
        foreach ($calendar_display->get_expected_form_input_keys() as $key) {
            if (isset($_POST[$key])) {
                $calendar_display->update_setting($key, $calendar_display->validate_and_sanitize_setting($key, $_POST[$key]));
            }
        }
        
        $calendar_display->build(new EventQuery($calendar_display->get_settings()));
        $calendar_events = $calendar_display->get_events();
        $calendar_settings = $calendar_display->get_settings();

        $html = $this->calendar_part($calendar_settings['view'],  $calendar_events, $calendar_settings);

        wp_send_json_success([
            'html' => $html,
            'current_date' => $calendar_settings['month'],
            'current_date_display' => wp_date('F Y', strtotime($calendar_settings['month'] . '-01')),
            'settings' => $calendar_settings,
        ]);
    }

    public function get_event_details() {
        // no checks as it's used in the frontend
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        
        // Check if the event is visible to the current user
        if ( ! Utils::is_event_visible_to_user( $event_id ) ) {
            $error_message = Utils::get_event_visibility_error_message( 'event_details' );
            wp_send_json_error( array(
                'html' => wp_kses_post( $error_message )
            ) );
            return;
        }
        
        // Get calendar_id to determine bulk registration status
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $calendar_id = isset($_POST['calendar_id']) ? absint($_POST['calendar_id']) : 0;
        $calendar_display = new CalendarDisplay($calendar_id);
        
        // Check if bulk registration is enabled (from calendar settings)
        $bulk_registration_enabled = $calendar_display->is_bulk_registration_enabled();
        
        $event_post = new \WPEventGenius\Common\Event\EventPost($event_id);
        $event_post->set_registration_counter( new \WPEventGenius\Common\Event\RegistrationCounter( $event_id, new \WPEventGenius\Common\Database() ) );
        $image_below = false;

        $html = EVGE()->template_manager()->get_template('events/calendars/partials/grid-item.php', [
            'event_post' => $event_post,
            'image_below' => $image_below,
            'bulk_registration_enabled' => $bulk_registration_enabled
        ], false);

        wp_send_json_success([
            'html' => '<div class="evge-event-tooltip" role="tooltip">' . $html . '</div>'
        ]);
    }

    /**
     * Render the calendar shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string Calendar HTML
     */
    public function render_calendar_shortcode($atts = []) {
        EVGE()->script_service()->enqueue_script('evge_common');
        EVGE()->script_service()->enqueue_script('evge_calendar');
        EVGE()->script_service()->enqueue_script('evge_registration_form');
        
        EVGE()->style_service()->enqueue_style('evge_common');
        EVGE()->style_service()->enqueue_style('evge_calendar');
        EVGE()->style_service()->enqueue_style('evge_single_post');
        EVGE()->style_service()->enqueue_style('evge_registration_form');
        
        // Request modal since calendars display events that can have modal triggers
        EVGE()->modal_service()->request_modal();

        // Set default values
        $defaults = [
            'id' => null,
            'view' => null,
            'month' => null,
            'preview' => null,
            'num' => null,
            'paged' => null,
            'filters' => null,
            'time_filter' => null,
            'show_toolbar' => null
        ];

        // Parse and validate attributes
        $atts = shortcode_atts($defaults, $atts);

        // Check if the calendar exists if an ID is provided
        if ( !empty($atts['id']) && $atts['id'] !== 'default' ) {
            $calendar_term = get_term( $atts['id'], 'evge_calendar' );
            if ( !$calendar_term || is_wp_error($calendar_term) ) {
                // CAPABILITY INCONSISTENCY: Using WordPress core 'edit_posts' instead of plugin-specific 'edit_evge_events'
                if ( current_user_can('edit_evge_events') ) {
                    return '<div class="evge-warning-message">' . esc_html__( 'Error: Only visible to admins. The calendar ID you entered does not exist. Please check the calendar ID and try again.', 'event-genius' ) . '</div>';
                }
                return '';
            }
        }

        // Create calendar display instance
        $calendar_display = new CalendarDisplay($atts['id']);

        // Update settings from shortcode attributes
        foreach ($atts as $key => $value) {
            if ($value !== null) {
                $calendar_display->update_setting($key, $calendar_display->validate_and_sanitize_setting($key, $value));
            }
        }

        // apply query params
        $expected_params = $calendar_display->get_expected_url_params_keys();
        foreach ($expected_params as $param) {
            if (isset($_GET[$param])) {
                $calendar_display->update_setting(str_replace('evge_', '', $param), $calendar_display->validate_and_sanitize_url_param($param, $_GET[$param]));
            }
        }

        // Build the query and get events
        $calendar_display->build(new EventQuery());
        $events = $calendar_display->get_events();
        $calendar_settings = $calendar_display->get_settings();
        $calendar_settings['id'] = $calendar_display->get_calendar_id();

        // Check if any events have additional guests enabled and enqueue scripts if needed
        $this->maybe_enqueue_additional_guests_scripts($events);

        // Render calendar with events
        return $this->calendar($events, $calendar_settings);
    }

    /**
     * Validate month format (YYYY-MM)
     *
     * @param string $month Month in YYYY-MM format
     * @return bool Whether the month is valid
     */
    private function validate_month($month) {
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            return false;
        }
        
        $parts = explode('-', $month);
        return checkdate($parts[1], 1, $parts[0]);
    }

    public function calendar($events, $settings) {
        $view = in_array($settings['view'], ['month', 'week', 'grid', 'list']) ? $settings['view'] : 'month';
        $current_month = $settings['month'];
        
        $timezone = $settings['timezone'] ?? 'UTC';

        // Process events and build calendar data
        $category = isset($settings['category']) ? absint($settings['category']) : '';
        $timezone = wp_timezone_string();
        $pagination = isset($settings['pagination']) ? $settings['pagination'] : null;
        $calendar_id = isset($settings['id']) ? $settings['id'] : 0;

        // Create CalendarDisplay instance
        $calendar_display = new \WPEventGenius\Common\Displays\CalendarDisplay($calendar_id);
        
        // Override settings with passed settings
        foreach ($settings as $key => $value) {
            $calendar_display->update_setting($key, $value);
        }

        // Create and set CalendarBuilder
        $calendar_builder = new \WPEventGenius\Common\Calendars\CalendarBuilder($events, $current_month, $timezone);
        $calendar_display->set_calendar_builder($calendar_builder);

        // Return template content
        return EVGE()->template_manager()->get_template('events/calendars/calendar.php', [
            'calendar_display' => $calendar_display
        ], false);
    }

    public function calendar_part($view, $events, $atts) {
        // Set up template variables
        $view = in_array($atts['view'], ['month', 'week', 'grid', 'list']) ? $atts['view'] : 'month';
        $current_month = $atts['month'];
        $pagination = $atts['pagination'];
        $calendar_id = isset($atts['calendar_id']) ? $atts['calendar_id'] : 'default';

        $timezone = wp_timezone_string();

        // Create CalendarDisplay instance
        $calendar_display = new \WPEventGenius\Common\Displays\CalendarDisplay($calendar_id);
        
        // Override settings with passed attributes
        foreach ($atts as $key => $value) {
            $calendar_display->update_setting($key, $value);
        }

        // Create and set CalendarBuilder
        $calendar_builder = new \WPEventGenius\Common\Calendars\CalendarBuilder($events, $current_month, $timezone);
        $calendar_display->set_calendar_builder($calendar_builder);

        // Return template content based on view type
        if ($view === 'month') {
            return EVGE()->template_manager()->get_template('events/calendars/calendar-month.php', [
                'calendar_display' => $calendar_display
            ], false);
        } else {
            if (empty($events)) {
                return EVGE()->template_manager()->get_template('events/calendars/partials/none-found.php', [
                    'has_search' => $calendar_display->has_search()
                ], false);
            } else {
                return EVGE()->template_manager()->get_template('events/calendars/calendar-feed.php', [
                    'calendar_display' => $calendar_display
                ], false);
            }
        }
    }

    public function get_day_events() {
        // no checks as it's used in the frontend
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $calendar_id = isset($_POST['calendar_id']) ? sanitize_key($_POST['calendar_id']) : 'default';

        $calendar_display = new CalendarDisplay($calendar_id);

        // Update settings from form inputs
        foreach ($calendar_display->get_expected_form_input_keys() as $key) {
            if (isset($_POST[$key])) {
                $calendar_display->update_setting($key, $calendar_display->validate_and_sanitize_setting($key, $_POST[$key]));
            }
        }
        
        // no checks as it's used in the frontend
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
	    $date_string = isset($_POST['date']) ? sanitize_text_field(wp_unslash($_POST['date'])) : wp_date('Y-m');
	    // phpcs:ignore WordPress.Security.NonceVerification.Missing
	    $calendar_id = isset($_POST['calendar_id']) ? absint($_POST['calendar_id']) : 0;

	    // Get the target date based on input
	    $date = new \DateTime($date_string);

	    // Get events for the specified month
	    $start_date = $date->format('Y-m-d');
	    $end_date = $date->modify('+1 day')->format('Y-m-d');
        $calendar_display->update_setting('start_date', $start_date);   
        $calendar_display->update_setting('end_date', $end_date);
        $calendar_display->build(new EventQuery($calendar_display->get_settings()));
        $events = $calendar_display->get_event_query()->get_events_for_day($start_date);
        $calendar_settings = $calendar_display->get_settings();
        
        // Check if bulk registration is enabled
        $bulk_registration_enabled = $calendar_display->is_bulk_registration_enabled();
        
        ob_start();
        foreach ($events as $event_post) {
            ?>
            <div class="evge-day-event-item">
                <div class="evge-event-list-details">
                    <h3><a href="<?php echo esc_url( $event_post->get_the_permalink() ); ?>"><?php echo esc_html( $event_post->get_the_title() ); ?></a></h3>
                    <div class="evge-event-list-meta-wrap">
                        <div class="evge-event-list-date evge-event-meta-item">
                            <?php 
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo Icon::get( 'list-outline' ); ?>
                            <?php 
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo $event_post->recurrence_display() . esc_html( $event_post->get_the_date_summary() ); ?>
                        </div>
                        <div class="evge-event-list-cost evge-event-meta-item">
                         <?php 
                            $venue_title = $event_post->get_the_venue_title();
                            $cost_display = $event_post->get_the_cost_display();
                            $separator = ! empty( $venue_title ) && ! empty( $cost_display ) ? ' | ' : '';
                            if (!empty($venue_title)) {
                                echo Icon::get( 'location-outline' );
                            }
                            ?>
                            <span>
                                <?php
                                echo wp_kses_post($venue_title);
                                echo $separator;
                                echo wp_kses_post( $event_post->get_the_cost_display() );
                                ?>
                            </span>
                        </div>
                    </div>
                    <?php if ( !empty( $event_post->get_the_summary() ) ) : ?>
                        <p class="evge-event-list-summary">
                            <?php echo wp_kses_post( $event_post->get_the_summary() ); ?>
                        </p>
                    <?php endif; ?>

                    <?php 
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo $event_post->get_the_listing_cta( array( 'bulk_registration' => $bulk_registration_enabled ) ); 
                    ?>
                </div>
            </div>
            <?php
        }
        $html = ob_get_clean();

        wp_send_json_success(array(
            'html' => $html,
            'events' => $events,
            'settings' => $calendar_settings
        ));
    }

    /**
     * Generate WP_Query arguments based on calendar settings
     *
     * @param array $settings Calendar settings
     * @param array $args Additional arguments to merge (optional)
     * @return array WP_Query arguments
     */
    protected function get_calendar_query_args($settings, $args = []) {
        // Base query args
        $query_args = [
            'post_type' => EVGE_EVENT_POST_TYPE,
            'post_status' => 'publish',
            'meta_query' => [
                'relation' => 'AND',
                'start_date' => [
                    'key' => 'evge_start_date',
                    'type' => 'DATE'
                ]
            ]
        ];

        // Handle different view types
        if (($settings['view'] ?? 'month') === 'month') {
            // For month view, get events within the specified month
            $start_date = ($settings['month'] ?? wp_date('Y-m')) . '-01';
            $end_date = wp_date('Y-m-t', strtotime($start_date));
            
            $query_args['meta_query']['start_date']['value'] = [$start_date, $end_date];
            $query_args['meta_query']['start_date']['compare'] = 'BETWEEN';
            $query_args['posts_per_page'] = -1; // Get all events for the month
            $query_args['num'] = -1; // Get all events for the month
        } else {
            // For list view, get upcoming events from the specified month or current date
            $start_date = isset($settings['month']) ? $settings['month'] . '-01' : wp_date('Y-m-d');
            
            $query_args['meta_query']['start_date']['value'] = $start_date;
            $query_args['meta_query']['start_date']['compare'] = '>=';
            $query_args['posts_per_page'] = $settings['num'] ?? 10;
            $query_args['paged'] = $settings['paged'] ?? 1;
            $query_args['orderby'] = [
                'meta_value' => 'ASC', // Order by start date
                'title' => 'ASC'      // Then by title
            ];
            $query_args['meta_key'] = 'evge_start_date';
        }

        // Apply filters from settings
        if (!empty($settings['filters']) && is_array($settings['filters'])) {
            $tax_query = [];
            
            foreach ($settings['filters'] as $filter) {
                if (empty($filter['terms'])) {
                    continue;
                }

                $taxonomy = $filter['type'] === 'category' ? 'evge_event_category' : 'evge_event_tag';
                
                $tax_query[] = [
                    'taxonomy' => $taxonomy,
                    'field' => 'term_id',
                    'terms' => $filter['terms'],
                    'operator' => $filter['action'] === 'include' ? 'IN' : 'NOT IN'
                ];
            }

            if (!empty($tax_query)) {
                $query_args['tax_query'] = [
                    'relation' => $settings['filter_relationship'] ?? 'AND',
                    ...$tax_query
                ];
            }
        } elseif (!empty($settings['category'])) {
            // Legacy category support
            $query_args['tax_query'] = [
                [
                    'taxonomy' => 'evge_event_category',
                    'field' => 'term_id',
                    'terms' => absint($settings['category'])
                ]
            ];
        }

        if (!empty($args['search'])) {
            $query_args['s'] = $args['search'];
        }

        // Add venue filter
        if (!empty($args['venue_id'])) {
            $query_args['meta_query'][] = [
                'key' => EVGE_VENUE_POST_TYPE,
                'value' => $args['venue_id'],
                'compare' => '='
            ];
        }

        // Merge with additional arguments
        return wp_parse_args($args, $query_args);
    }

    public function get_calendar_events($calendar_id, $params = []) {
        $calendar_display = new CalendarDisplay($calendar_id);
        
        // Update settings from params
        foreach ($params as $key => $value) {
            $calendar_display->update_setting($key, $calendar_display->validate_and_sanitize_setting($key, $value));
        }

        // Build the query and get events
        $calendar_display->build(new EventQuery($calendar_display->get_settings()));
        return $calendar_display->get_events();
    }

    public function register_rest_routes() {
        register_rest_route('evge/v1', '/upcoming-events', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_upcoming_events'),
            'permission_callback' => function() {
                // Allow both edit_evge_events and edit_posts for block editor compatibility
                return current_user_can('edit_evge_events') || current_user_can('edit_posts');
            }
        ));
    }

    public function get_upcoming_events(\WP_REST_Request $request) {
        $search_query = $request->get_param('search');
        $is_date = false;
        $search_date = null;
        $search_title = null;

        // Parse search query if exists
        if ($search_query) {
            // Try to parse as date (check for common date formats)
            $potential_date = strtotime($search_query);
            if ($potential_date && $potential_date !== false) {
                // Check if it's a reasonable date (not too far in the past or future)
                $today = strtotime('today');
                $max_future = strtotime('+10 years');
                if ($potential_date >= $today && $potential_date <= $max_future) {
                    $is_date = true;
                    $search_date = wp_date('Y-m-d', $potential_date);
                }
            }

            // If not a date or if there are words, use as title search
            if (!$is_date) {
                $search_title = sanitize_text_field($search_query);
            }
        }

        // Build query parameters - default to upcoming events
        $query_params = [
            'posts_per_page' => 20,
            'qtype' => 'upcoming', // Only get upcoming events
        ];

        // Add date search if provided
        if ($search_date) {
            $query_params['qtype'] = 'custom';
            $query_params['start_date'] = $search_date;
        }

        // Add title search if provided
        if ($search_title) {
            $query_params['search'] = $search_title; // EventQuery supports 'search' parameter
        }

        $event_query = new \WPEventGenius\Common\Event\EventQuery($query_params);
        $event_query->apply_params(); // Process query parameters (qtype, search, etc.)
        $events = $event_query->get_events();

        $formatted_events = array_map(function($event) {
            return [
                'id' => $event->get_the_id(),
                'title' => get_post_field('post_title', $event->get_the_id()),
                'date' => $event->get_the_date_summary(),
                'startDate' => $event->get_the_start_date(),
                'endDate' => $event->get_the_end_date(),
                'venue' => $event->get_the_venue_title(),
                'permalink' => $event->get_the_permalink()
            ];
        }, $events);

        return new \WP_REST_Response($formatted_events, 200);
    }

    /**
     * Validate and sanitize form inputs
     *
     * @param array $data Raw form data to process
     * @return array Processed and validated data
     */
    protected function validate_form_inputs($data) {
        // Define default values and validation rules
        $defaults = [
            'direction' => '',
            'current_date' => wp_date('Y-m'), // Use WordPress timezone
            'view' => 'month',
            'month' => '',
            'search' => '',
            'venue_id' => 0,
            'calendar_id' => 'default',
            'paged' => 1,
            'num' => 10,
            'preview' => '',
            'date' => '',
            'event_id' => 0,
            'selectedDate' => '',
            'time_filter' => 'upcoming'
        ];

        // Define allowed values for specific fields
        $allowed_views = ['month', 'week', 'grid', 'list'];
        $allowed_directions = ['prev', 'next', ''];
        $allowed_time_filters = ['upcoming', 'past']; // Add allowed values for time_filter

        // Initialize processed data with defaults
        $processed = [];

        // Process each field with appropriate validation and sanitization
        foreach ($defaults as $field => $default) {
            $value = $data[$field] ?? $default;

            switch ($field) {
                case 'direction':
                    $processed[$field] = in_array($value, $allowed_directions) ? $value : '';
                    break;

                case 'view':
                    $processed[$field] = in_array($value, $allowed_views) ? $value : 'month';
                    break;

                case 'time_filter':
                    $processed[$field] = in_array($value, $allowed_time_filters) ? $value : 'upcoming';
                    break;

                case 'current_date':
                case 'month':
                    // Validate YYYY-MM format
                    if (preg_match('/^\d{4}-\d{2}$/', $value)) {
                        $parts = explode('-', $value);
                        if (checkdate($parts[1], 1, $parts[0])) {
                            $processed[$field] = $value;
                        } else {
                            $processed[$field] = $defaults['current_date'];
                        }
                    }
                    break;

                case 'search':
                    $processed[$field] = sanitize_text_field($value);
                    break;

                case 'venue_id':
                case 'event_id':
                    $processed[$field] = max(0, absint($value));
                    break;
                case 'calendar_id':
                    $processed[$field] = sanitize_key($value);
                    break;
                case 'paged':
                    $processed[$field] = max(1, absint($value));
                    break;

                case 'num':
                    $processed[$field] = max(1, min(100, absint($value)));
                    break;

                case 'preview':
                    $processed[$field] = sanitize_text_field($value);
                    break;

                case 'date':
                    // Validate date format (YYYY-MM-DD)
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                        $parts = explode('-', $value);
                        if (checkdate($parts[1], $parts[2], $parts[0])) {
                            $processed[$field] = sanitize_text_field($value);
                        } else {
                            $processed[$field] = wp_date('Y-m-d'); // Use WordPress timezone
                        }
                    } else {
                        $processed[$field] = wp_date('Y-m-d'); // Use WordPress timezone
                    }
                    break;

                case 'selectedDate':
                    $processed[$field] = sanitize_text_field($value);
                    break;
            }

            if (empty($processed['month']) && !empty($processed['current_date'])) {
                $processed['month'] = $processed['current_date'];
            }
        }

        return $processed;
    }

    /**
     * Check if any events in the calendar have additional guests enabled
     * and enqueue the necessary scripts/styles
     * 
     * @param array $events Array of event post objects
     * @return void
     */
    protected function maybe_enqueue_additional_guests_scripts($events) {
        if (empty($events) || !is_array($events)) {
            return;
        }

        // Check if Standard tier is available (additional guests is a Standard feature)
        if (!function_exists('evge_is_standard_tier') || !evge_is_standard_tier()) {
            return;
        }

        EVGE()->script_service()->enqueue_script('evge_additional_guests');
            
        EVGE()->style_service()->enqueue_style('evge_additional_guests');
    }
} 