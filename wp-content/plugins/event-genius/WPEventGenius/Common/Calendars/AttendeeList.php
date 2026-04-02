<?php
namespace WPEventGenius\Common\Calendars;

use WPEventGenius\Common\Database;
use WPEventGenius\Common\Event\Event;
use WPEventGenius\Common\Registration\Registration\BaseRegistration;


if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class AttendeeList {
    /**
     * @var Database
     */
    protected $database;

    /**
     * @var Event
     */
    protected $event;

    /**
     * @var array
     */
    protected $args;

    /**
     * @var int
     */
    protected $current_batch_count = 0;

    /**
     * Default arguments for the attendee list
     * 
     * @var array
     */
    protected $default_args = array(
        'event_id' => 0,
        'status' => array('confirmed'),
        'per_load' => 20,
        'offset' => 0,
        'template' => 'simple',
        'orderby' => 'registration_date',
        'order' => 'DESC',
        'is_ajax' => false,
        'selected_fields' => array(), // Array of field slugs to include
        'simple_format' => '', // Format string for simple template
        'from_auto' => false // Track if template was determined from "auto" mode
    );

    /**
     * @param Database $database
     * @param Event $event
     * @param array $args
     */
    public function __construct(Database $database, Event $event, $args = array()) {
        $this->database = $database;
        $this->event = $event;
        $this->args = wp_parse_args($args, $this->default_args);
    }

    /**
     * Get the attendee list HTML
     * 
     * @return string
     */
    public function get_list() {
        $registrations = $this->get_registrations();
        
        if (empty($registrations)) {
            // Pass event data to the no-attendees template
            // Create EventPost instance to access registration status methods
            $event_post = new \WPEventGenius\Common\Event\EventPost($this->event->get_post_id());
            $template_data = array(
                'event' => $this->event,
                'event_post' => $event_post,
            );
            return $this->get_template('partials/no-attendees', $template_data);
        }

        $display_fields = $this->get_display_fields($this->event->get_form()->get_fields());

        $attendees = array();
        foreach ($registrations as $registration_data) {
            $registration = new BaseRegistration($this->database);
            $registration->set_entry_id($registration_data['id']);
            $registration->build($registration_data);
            
            $placeholders = $this->create_placeholders($registration, $this->event, 'list');
            $attendees[] = array(
                'registration' => $registration,
                'placeholders' => $placeholders
            );
        }

        // Store the count of attendees for this batch
        $this->current_batch_count = count($attendees);

        // AJAX requests are no longer used - all attendees are loaded upfront
        // Keeping this check for backwards compatibility but it should never be reached
        if ($this->args['is_ajax']) {
            return '';
        }

        $total_registrations = $this->get_total_registrations();
        
        // has_more is based on whether we have more than per_load
        // All attendees are loaded, but we'll hide the ones beyond per_load
        $has_more = ($total_registrations > $this->args['per_load']);

        // For initial load, return the full wrapper with items
        $template_data = array(
            'attendees' => $attendees,
            'args' => $this->args,
            'has_more' => $has_more,
            'total_registrations' => $total_registrations,
            'form' => $this->event->get_form(),
            'fields' => $display_fields
        );
        
        // Add simple_format to template data if provided
        if (!empty($this->args['simple_format'])) {
            $template_data['simple_format'] = $this->args['simple_format'];
        }
        
        return $this->get_template($this->args['template'], $template_data);
    }

    /**
     * Get registrations from database
     * 
     * @return array
     */
    protected function get_registrations() {
        $where = array(
            array(
                'column' => 'event_id',
                'value' => $this->args['event_id'],
                'compare' => '=',
                'type' => 'int'
            )
        );
        if (!empty($this->args['status'])) {
            $status = is_array($this->args['status']) ? $this->args['status'][0] : $this->args['status'];
            $where[] = array(
                'column' => 'status',
                'value' => $status,
                'compare' => '=',
                'type' => 'string'
            );
        }
        // Load all attendees initially (not paginated) - we'll hide/show them with JavaScript
        // This eliminates the need for AJAX requests which can create security issues
        $limit = 10000; // Use a very large number to get all attendees
        $offset = 0;

        return $this->database->registration_query(
            $where,
            $this->args['orderby'] . ' ' . $this->args['order'],
            $limit,
            $offset
        );
    }

    /**
     * Get total number of registrations
     * 
     * @return int
     */
    public function get_total_registrations() {
        $where = array(
            array(
                'column' => 'event_id',
                'value' => $this->args['event_id'],
                'compare' => '=',
                'type' => 'int'
            )
        );

        if (!empty($this->args['status'])) {
            $status = is_array($this->args['status']) ? $this->args['status'][0] : $this->args['status'];
            $where[] = array(
                'column' => 'status',
                'value' => $status,
                'compare' => '=',
                'type' => 'string'
            );
        }

        return $this->database->registration_count_query($where);
    }

    /**
     * Get the count of attendees in the current batch
     * 
     * @return int
     */
    public function get_current_batch_count() {
        return $this->current_batch_count;
    }

    /**
     * Create placeholders object using factory
     * 
     * @param Registration $registration
     * @param Event $event
     * @param string $context
     * @param bool $show_admin_placeholders
     * @return \WPEventGenius\Common\Utils\Placeholders
     */
    protected function create_placeholders( $registration, $event, $context = 'email', $show_admin_placeholders = false ) {
        $factory = new \WPEventGenius\Common\Services\RegistrationObjectFactory();
        return $factory->create_placeholders( $registration, $event, $context, $show_admin_placeholders );
    }
    /**
     * Get display fields for attendee list
     * 
     * Filters out hidden and admin-only fields, then applies the evge_attendee_list_fields 
     * filter to allow customization of which fields are displayed in the attendee list.
     * 
     * @param array $all_fields Array of all form field objects
     * @return array Filtered array of field objects to display
     */
    protected function get_display_fields($all_fields) {
        // Check if specific fields are selected - if so, skip show_in_attendee_list check
        $has_selected_fields = !empty($this->args['selected_fields']) && is_array($this->args['selected_fields']);
        
        // Check if template came from "auto" mode
        $from_auto = !empty($this->args['from_auto']);
        
        // For "full" template, if no fields are selected, show all fields (excluding hidden and admin-only)
        // BUT: if template came from "auto" mode, still respect show_in_attendee_list
        $is_full_template = ($this->args['template'] === 'full');
        $show_all_fields = $is_full_template && !$has_selected_fields && !$from_auto;
        
        // Filter out hidden and admin-only fields, and fields not enabled for attendee lists
        $filtered_fields = array_filter($all_fields, function($field) use ($has_selected_fields, $show_all_fields) {
            // Exclude hidden field types
            if ($field->get_type() === 'hidden') {
                return false;
            }
            
            // Exclude admin-only fields (check if field has admin_only property)
            if (method_exists($field, 'get_admin_only')) {
                if ($field->get_admin_only()) {
                    return false;
                }
            }
            
            // Only check show_in_attendee_list if no specific fields are selected AND not showing all fields
            // When fields are explicitly selected (e.g., in "full" template), allow all selected fields
            // When using "full" template with no fields selected, show all fields
            if (!$has_selected_fields && !$show_all_fields) {
                // Exclude fields that are not enabled for attendee lists
                if (method_exists($field, 'show_in_attendee_list')) {
                    if (!$field->show_in_attendee_list()) {
                        return false;
                    }
                }
            }
            
            return true;
        });
        
        // Re-index array after filtering
        $filtered_fields = array_values($filtered_fields);
        
        // If specific fields are selected, filter to only those
        if ($has_selected_fields) {
            $selected_slugs = array_map('strval', $this->args['selected_fields']);
            $filtered_fields = array_filter($filtered_fields, function($field) use ($selected_slugs) {
                $slug = method_exists($field, 'get_slug') ? $field->get_slug() : '';
                return in_array($slug, $selected_slugs, true);
            });
            $filtered_fields = array_values($filtered_fields);
        }
        
        return apply_filters('evge_attendee_list_fields', $filtered_fields, $this->args);
    }

    /**
     * Get template HTML
     * 
     * @param string $template
     * @param array $data
     * @return string
     */
    protected function get_template($template, $data = array()) {
        $template_path = EVGE_PLUGIN_PATH . 'templates/event-genius/registration/attendee-lists/' . $template . '.php';
        if (!file_exists($template_path)) {
            return '';
        }

        // Extract data to make variables available in template
        if (!empty($data)) {
            extract($data);
        }

        // Start output buffering
        ob_start();
        
        // Include the template file
        include $template_path;
        
        // Get and clean the output buffer
        return ob_get_clean();
    }
} 