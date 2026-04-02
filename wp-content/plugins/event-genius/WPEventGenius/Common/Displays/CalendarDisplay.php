<?php

namespace WPEventGenius\Common\Displays;

use WPEventGenius\Common\Utils\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class CalendarDisplay {
    /**
     * @var string The calendar ID
     */
    protected $calendar_id;

    /**
     * @var array The default calendar settings
     */
    protected $settings;

    /**
     * @var array The event query
     */
    protected $event_query;

    protected $events;

    /**
     * @var \WPEventGenius\Common\Calendars\CalendarBuilder The calendar builder instance
     */
    protected $calendar_builder;

    /**
     * @var array Allowed taxonomies
     */
    protected $allowed_taxonomies = [
        'evge_event_category',
        'evge_event_tag'
    ];

    /**
     * @var bool Whether there is a search term
     */
    protected $has_search = false;

    /**
     * Constructor
     * 
     * @param string $calendar_id The calendar ID
     */
    public function __construct($calendar_id) {
        $this->calendar_id = $calendar_id;
        $this->settings = $this->base_settings();
    }

    public function get_settings() {
        return $this->settings;
    }

    public function get_calendar_id() {
        return $this->calendar_id;
    }

    public function get_event_query() {
        return $this->event_query;
    }

    public function get_events() {
        return $this->events;
    }

    /**
     * Get the calendar data from the CalendarBuilder instance
     * 
     * @return array|null The calendar data or null if no builder is set
     */
    public function get_calendar_data() {
        if (!$this->calendar_builder) {
            return null;
        }
        return $this->calendar_builder->get_calendar_data();
    }

    public function get_calendar_events() {
        if (!$this->calendar_builder) {
            return null;
        }
        return $this->calendar_builder->get_events();
    }

    /**
     * Set the calendar builder instance
     * 
     * @param \WPEventGenius\Common\Calendars\CalendarBuilder $builder The calendar builder instance
     * @return void
     */
    public function set_calendar_builder($builder) {
        $this->calendar_builder = $builder;
    }

    public function has_search() {
        return $this->has_search;
    }

    /**
     * Default filter bar option keys and their enabled-by-default state.
     *
     * @return array<string, bool>
     */
    public function get_default_filter_bar_options() {
        return [
            'search'       => true,
            'venue'        => true,
            'category'     => false,
            'tag'          => false,
            'time_filter'  => true,
            'display'      => true,
        ];
    }

    /**
     * Whether to show a given filter bar item (search, venue, category, tag, time_filter, display).
     * Encapsulates settings check and, where needed, data availability (e.g. 2+ venues, terms exist).
     *
     * @param string $item_key One of: search, venue, category, tag, time_filter, display.
     * @return bool
     */
    public function should_show_filter_bar_item( $item_key ) {
        $options = $this->settings['filter_bar_options'] ?? null;
        if ( ! is_array( $options ) ) {
            $options = $this->get_default_filter_bar_options();
        } else {
            $options = array_merge( $this->get_default_filter_bar_options(), $options );
        }

        $enabled = ! empty( $options[ $item_key ] );
        if ( ! $enabled ) {
            return false;
        }

        switch ( $item_key ) {
            case 'venue':
                $venue_query = new \WPEventGenius\Common\Queries\VenueQuery();
                $venue_query->add_wp_query();
                $venues = $venue_query->get_venues();
                $venue_count = is_array( $venues ) ? count( $venues ) : 0;
                return $venue_count >= 2;
            case 'category':
                $terms = get_terms( [
                    'taxonomy'   => EVGE_EVENT_CATEGORY_TYPE,
                    'hide_empty' => false,
                ] );
                return ! is_wp_error( $terms ) && ! empty( $terms );
            case 'tag':
                $terms = get_terms( [
                    'taxonomy'   => EVGE_EVENT_TAG_TYPE,
                    'hide_empty' => false,
                ] );
                return ! is_wp_error( $terms ) && ! empty( $terms );
            case 'search':
            case 'time_filter':
            case 'display':
                return true;
            default:
                return false;
        }
    }

    /**
     * Derive category IDs that should be hidden from the toolbar dropdown
     * based on the calendar builder's "Event Filters" configuration.
     *
     * For the current UI/UX we only hide categories for the common case where
     * filter_relationship is AND (exclude makes those categories impossible).
     *
     * @return int[] Array of category term IDs.
     */
    public function get_excluded_category_ids_for_filter_bar() {
        $excluded_category_ids = [];

        $filter_relationship = $this->settings['filter_relationship'] ?? 'AND';
        if ( 'AND' !== $filter_relationship ) {
            return [];
        }

        $filters = $this->settings['filters'] ?? [];
        if ( empty( $filters ) || ! is_array( $filters ) ) {
            return [];
        }

        foreach ( $filters as $filter ) {
            if ( empty( $filter['type'] ) || empty( $filter['action'] ) ) {
                continue;
            }

            if ( 'category' !== $filter['type'] || 'exclude' !== $filter['action'] ) {
                continue;
            }

            $terms = $filter['terms'] ?? [];
            if ( is_array( $terms ) ) {
                $excluded_category_ids = array_merge(
                    $excluded_category_ids,
                    array_map( 'absint', $terms )
                );
            }
        }

        $excluded_category_ids = array_values(
            array_unique(
                array_filter( $excluded_category_ids )
            )
        );

        return $excluded_category_ids;
    }

    /**
     * Build get_terms() args for the toolbar category dropdown
     * based on calendar builder "Event Filters" include/exclude settings.
     *
     * Note: this UI pruning is only applied for filter_relationship = 'AND'
     * (same assumption as the rest of the filtering UI logic).
     *
     * @return array Arguments to merge into get_terms(), e.g. ['include' => [...]] or ['exclude' => [...]].
     */
    public function get_filter_bar_category_terms_args() {
        return $this->get_filter_bar_terms_args( 'category' );
    }

    /**
     * Build get_terms() args for the toolbar tag dropdown
     * based on calendar builder "Event Filters" include/exclude settings.
     *
     * @return array Arguments to merge into get_terms(), e.g. ['include' => [...]] or ['exclude' => [...]].
     */
    public function get_filter_bar_tag_terms_args() {
        return $this->get_filter_bar_terms_args( 'tag' );
    }

    /**
     * @param string $filter_type 'category' or 'tag'
     * @return array
     */
    private function get_filter_bar_terms_args( $filter_type ) {
        $filter_relationship = $this->settings['filter_relationship'] ?? 'AND';

        $filters = $this->settings['filters'] ?? [];
        if ( empty( $filters ) || ! is_array( $filters ) ) {
            return [];
        }

        $include_terms = [];
        $exclude_terms = [];

        foreach ( $filters as $filter ) {
            if ( empty( $filter['type'] ) || $filter_type !== $filter['type'] ) {
                continue;
            }

            $action = $filter['action'] ?? 'include';
            $terms = isset( $filter['terms'] ) ? (array) $filter['terms'] : [];
            $terms = array_values( array_unique( array_map( 'absint', $terms ) ) );

            if ( empty( $terms ) ) {
                continue;
            }

            if ( 'include' === $action ) {
                $include_terms = array_merge( $include_terms, $terms );
            } elseif ( 'exclude' === $action ) {
                $exclude_terms = array_merge( $exclude_terms, $terms );
            }
        }

        $include_terms = array_values( array_unique( array_filter( $include_terms ) ) );
        $exclude_terms = array_values( array_unique( array_filter( $exclude_terms ) ) );

        // If the builder has include filters, show only those terms (minus any excluded terms).
        if ( ! empty( $include_terms ) ) {
            if ( ! empty( $exclude_terms ) ) {
                $include_terms = array_values( array_diff( $include_terms, $exclude_terms ) );
            }
            return [ 'include' => $include_terms ];
        }

        // Otherwise, show everything except excluded terms.
        if ( ! empty( $exclude_terms ) ) {
            return [ 'exclude' => $exclude_terms ];
        }

        return [];
    }

    /**
     * Extra class for the filter bar container when total visible filters exceed 4.
     * When the display dropdown is not shown there is more room, so we only apply
     * the wrapping/spacing layout when the combined count (first group + display) is > 4.
     *
     * @return string Either ' evge-filter-bar-many' or ''.
     */
    public function get_filter_bar_container_class() {
        $first_group_keys = [ 'search', 'venue', 'category', 'tag', 'time_filter' ];
        $count = 0;
        foreach ( $first_group_keys as $key ) {
            if ( $this->should_show_filter_bar_item( $key ) ) {
                $count++;
            }
        }
        if ( $this->should_show_filter_bar_item( 'display' ) ) {
            $count++;
        }
        return $count > 4 ? ' evge-filter-bar-many' : '';
    }

    public function add_event_query( $event_query ) {
        $this->event_query = $event_query;
    }

    public function build( $event_query ) {
        $this->calculate_settings();
        $this->calculate_target_date();
        $this->event_query = $event_query;
        $this->event_query->set_params($this->settings);
        $this->event_query->apply_params();
        $this->events = $this->event_query->get_events();
        $this->calculate_pagination();
        
        // Set search flag
        $this->has_search = !empty($this->settings['search']);
    }

    /**
     * Get the calendar settings with URL parameters applied
     * 
     * @return array The merged settings
     */
    public function base_settings() {
        $settings = $this->defaults();

        if ($this->calendar_id === 'default' || empty($this->calendar_id)) {
            $settings = Settings::get('default_calendar_settings');
            if (!empty($settings) && is_string($settings)) {
                $settings = json_decode($settings, true);
            }
        } else {
            $settings = get_term_meta($this->calendar_id, 'evge_calendar_settings', true);
        }
        if ( ! is_array($settings) ) {
            $settings = $this->defaults();
        }

        // Ensure taxonomies array exists
        if (!isset($settings['taxonomies'])) {
            $settings['taxonomies'] = [];
        }

        if (!isset($settings['paged'])) {
            $settings['paged'] = 1;
        }

        if (!isset($settings['view'])) {
            $settings['view'] = 'month';
        }

        if (!isset($settings['posts_per_page'])) {
            if (isset($settings['num'])) {
                $settings['posts_per_page'] = $settings['num'];
            } else {
                $settings['posts_per_page'] = 12;
            }
        }

        return $settings;
    }

    public function apply_overrides($overrides) {
        // Apply overrides from URL parameters
        foreach ($overrides as $key => $value) {
            $this->update_setting($key, $value);
        }
    }

    public function defaults() {
        $defaults = [            
            'calendar_id' => 'default',
            'preview' => '',
            'current_date' => wp_date('Y-m'), // Use WordPress timezone
            'view' => 'month',
            'month' => '',
            'search' => '',
            'venue_id' => 0,
            'category_id' => 0,
            'tag_id' => 0,
            'paged' => 1,
            'num' => 10,
            'per_page' => 10,
            'events_per_day' => 3,
            'preview' => '',
            'date' => '',
            'event_id' => 0,
            'time_filter' => 'upcoming',
            'direction' => '',
            'selectedDate' => '',
            'filters' => [],
            'taxonomies' => [],
            'pagination' => []
        ];

        return $defaults;
    }

    /**
     * Validate and sanitize URL parameters
     * 
     * @param string $key The key to validate
     * @param mixed $value The value to validate
     * @return mixed The validated value
     * 
     */
    public function validate_and_sanitize_url_param($key, $value) {
        switch ($key) {
            case 'evge_view':
                return in_array($value, ['month', 'grid', 'list']) ? $value : 'month';
            case 'evge_time_filter':
                return in_array($value, ['upcoming', 'past', 'all']) ? $value : 'upcoming';
            case 'evge_num':
            case 'evge_paged':
                return max(1, absint($value));
            case 'evge_search':
            case 's':
                return sanitize_text_field($value);
            case 'evge_venue_id':
                return max(0, absint($value));
            case 'evge_category_id':
                return max(0, absint($value));
            case 'evge_tag_id':
                return max(0, absint($value));
            case 'evge_month':
                return sanitize_key($value);
        }
    }

    public function validate_and_sanitize_settings() {
        foreach ($this->settings as $key => $value) {
            $this->settings[$key] = $this->validate_and_sanitize_setting($key, $value);
        }

        if (empty($this->settings['month']) && !empty($this->settings['current_date'])) {
            $this->settings['month'] = $this->settings['current_date'];
        }

        return $this->settings;
    }

    public function validate_and_sanitize_setting($key, $value) {

        // Define allowed values for specific fields
        $allowed_views = ['month', 'grid', 'list'];
        $allowed_directions = ['prev', 'next', ''];
        $allowed_time_filters = ['upcoming', 'past', 'all']; // Add allowed values for time_filter

        $sanitized_value = $value;
        $defaults = $this->defaults();

        switch ($key) {
            case 'direction':
                $sanitized_value = in_array($value, $allowed_directions) ? $value : '';
                break;

            case 'view':
                $sanitized_value = in_array($value, $allowed_views) ? $value : 'month';
                break;

            case 'time_filter':
                $sanitized_value = in_array($value, $allowed_time_filters) ? $value : 'upcoming';
                break;

            case 'current_date':
            case 'month':
                $sanitized_value = '';
                // Validate YYYY-MM format
                if (preg_match('/^\d{4}-\d{2}$/', $value)) {
                    $parts = explode('-', $value);
                    if (checkdate($parts[1], 1, $parts[0])) {
                        $sanitized_value = $value;
                    } else {
                        $sanitized_value = $defaults['current_date'];
                    }
                }
                break;

            case 'search':
                $sanitized_value = sanitize_text_field($value);
                break;

            case 'venue_id':
            case 'category_id':
            case 'tag_id':
            case 'event_id':
                $sanitized_value = max(0, absint($value));
                break;
            case 'calendar_id':
                $sanitized_value = sanitize_key($value);
                break;
            case 'paged':
                $sanitized_value = max(1, absint($value));
                break;

            case 'num':
            case 'per_page':
            case 'events_per_day':
                $sanitized_value = max(1, min(100, absint($value)));
                break;

            case 'preview':
                $sanitized_value = sanitize_text_field($value);
                if ( empty($sanitized_value) ) {
                    $sanitized_value = false;
                }
                break;

            case 'date':
                // Validate date format (YYYY-MM-DD)
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    $parts = explode('-', $value);
                    if (checkdate($parts[1], $parts[2], $parts[0])) {
                        $sanitized_value = sanitize_text_field($value);
                    } else {
                        $sanitized_value = wp_date('Y-m-d'); // Use WordPress timezone
                    }
                } else {
                    $sanitized_value = wp_date('Y-m-d'); // Use WordPress timezone
                }
                break;

            case 'selectedDate':
                $sanitized_value = sanitize_text_field($value);
                break;

            case 'taxonomies':
                $sanitized_value = $this->validate_taxonomies($value);
                break;  
            default:
                $sanitized_value = isset($defaults[$key]) ? $defaults[$key] : '';
                break;
        }

        return $sanitized_value;
    }

    public function calculate_settings() {

        $is_preview = ! empty($this->settings['preview']);

        // Handle preview mode
        if ($is_preview) {
            $preview_settings = get_transient('evge_calendar_preview_' . $this->settings['preview']);
            if ($preview_settings) {
                foreach ($preview_settings as $key => $value) {
                    $this->update_setting($key, $value);
                }
            }
        }

        // Ensure view type is valid
        $this->settings['view'] = in_array($this->settings['view'], ['month', 'week', 'grid', 'list']) 
            ? $this->settings['view'] 
            : 'month';

        if ( ! isset($this->settings['num']) ) {
            if (isset($this->settings['per_page'])) {
                $this->settings['num'] = $this->settings['per_page'];
            }
            if (isset($this->settings['posts_per_page'])) {
                $this->settings['num'] = $this->settings['posts_per_page'];
            }
        }


        // Ensure pagination values are valid
        $this->settings['num'] = max(1, min(100, absint($this->settings['num'])));
        $this->settings['paged'] = max(1, absint($this->settings['paged']));

        if ( empty($this->settings['search']) ) {
            $this->settings['search'] = ! empty($this->settings['s']) ? sanitize_text_field($this->settings['s']) : '';
        }

    }

      /**
     * Get the expected URL parameter keys for the calendar
     * 
     * @return array The expected URL parameter keys
     */
    public function get_expected_url_params_keys() {
        return [
            'evge_view',
            'evge_month',
            'evge_paged',
            'evge_num',
            'evge_search',
            's',
            'evge_venue_id',
            'evge_category_id',
            'evge_tag_id',
            'evge_time_filter'
        ];
    }

    public function get_expected_form_input_keys() {
        return [
            'direction',
            'current_date',
            'view',
            'month',
            'search',
            'venue_id',
            'category_id',
            'tag_id',
            'calendar_id',
            'paged',
            'num',
            'events_per_day',
            'preview',
            'date',
            'event_id',
            'selectedDate',
            'time_filter'
        ];
    }


    /**
     * Update a single setting by key and value
     * 
     * @param string $key The setting key to update
     * @param mixed $value The new value for the setting
     * @return bool Whether the setting was updated
     */
    public function update_setting($key, $value) {
        $this->settings[$key] = $value;
        return true;
    }

    public function get_formatted_date() {
        return $this->calendar_builder->get_formatted_date();
    }

    protected function calculate_target_date() {

        // Create DateTime object from current date
        // Handle navigation direction

        if (!empty($this->settings['direction'])) {
            $date = new \DateTime($this->settings['current_date'] . '-01');
            if ($this->settings['direction'] === 'prev') {
                $date->modify('-1 month');
            } else {
                $date->modify('+1 month');
            }
        } elseif (!empty($this->settings['selectedDate'])) {
            $date = new \DateTime($this->settings['selectedDate'] . '-01');
        } elseif (!empty($this->settings['current_date'])) {
            $date = new \DateTime($this->settings['current_date'] . '-01');
        } else {
            if (empty($this->settings['month'])) {
                $month = wp_date('Y-m');
            } else {
                $month = $this->settings['month'];
            }
            $date = new \DateTime($month . '-01');
        }

        // Return formatted date
        $this->settings['month'] = $date->format('Y-m');
    }

    protected function calculate_pagination() {
        if (empty($this->settings['paged'])) {
            return;
        }

        $this->settings['pagination'] = [
            'total' => $this->event_query->get_max_num_pages(),
            'paged' => $this->settings['paged'],
            'per_page' => $this->settings['num']
        ];
    }

    /**
     * Check if bulk registration is enabled for this calendar
     * 
     * @return bool True if bulk registration is enabled, false otherwise
     */
    public function is_bulk_registration_enabled() {
        // Check if Premium tier is active
        if ( ! function_exists( 'evge_is_premium_tier' ) || ! evge_is_premium_tier() ) {
            return false;
        }

        // Get base settings which include calendar-specific settings from calendar ID
        $base_settings = $this->base_settings();
        
        // Check if bulk registration is explicitly enabled in calendar settings
        $bulk_registration_enabled = isset( $base_settings['bulk_registration_enabled'] ) 
            && $base_settings['bulk_registration_enabled'] === 'enabled';
        
        if ( ! $bulk_registration_enabled ) {
            return false;
        }

        // Enqueue dependencies first
        EVGE()->script_service()->enqueue_script( 'evge_common' );
        EVGE()->script_service()->enqueue_script( 'evge_bulk_registration' );
        
        // Enqueue styles
        EVGE()->style_service()->enqueue_style( 'evge_common' );
        EVGE()->style_service()->enqueue_style( 'evge_bulk_registration' );

        return true;
    }
}
