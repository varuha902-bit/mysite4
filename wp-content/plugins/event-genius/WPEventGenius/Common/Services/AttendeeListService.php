<?php
namespace WPEventGenius\Common\Services;

use WPEventGenius\Common\Database;
use WPEventGenius\Common\Event\Event;
use WPEventGenius\Common\Event\EventPost;
use WPEventGenius\Common\Calendars\AttendeeList;
use WPEventGenius\Common\Utils\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class AttendeeListService {
    
    public function init_hooks() {
        // AJAX handler removed - all attendees are now loaded upfront and revealed via JavaScript
        // This eliminates security issues with AJAX requests
        
        // Register shortcode
        add_shortcode('event_genius_attendees', array($this, 'render_attendee_shortcode'));
    }

    /**
     * Determine the appropriate template layout based on enabled fields
     * 
     * Rules:
     * - If only first and/or last name is included → use "simple" layout
     * - If only a single field is included → use "simple" layout
     * - All other configurations → use "full" layout
     * 
     * @param Event $event The event object
     * @return string 'simple' or 'full'
     */
    protected function determine_template(Event $event) {
        $form = $event->get_form();
        $all_fields = $form->get_fields();
        
        // Get display fields (filtered by show_in_attendee_list)
        $display_fields = array_filter($all_fields, function($field) {
            // Exclude hidden field types
            if ($field->get_type() === 'hidden') {
                return false;
            }
            
            // Exclude admin-only fields
            if (method_exists($field, 'get_admin_only')) {
                if ($field->get_admin_only()) {
                    return false;
                }
            }
            
            // Exclude fields that are not enabled for attendee lists
            if (method_exists($field, 'show_in_attendee_list')) {
                if (!$field->show_in_attendee_list()) {
                    return false;
                }
            }
            
            return true;
        });
        
        // Re-index array after filtering
        $display_fields = array_values($display_fields);
        
        $field_count = count($display_fields);
        
        // If no fields or more than 2 fields, use full layout
        if ($field_count > 2) {
            return 'full';
        }
        
        $slugs = array();
        foreach ($display_fields as $field) {
            $slugs[] = $field->get_slug();
        }
        
        // Check if both fields are first and/or last
        $has_first = in_array('first', $slugs, true);
        $has_last = in_array('last', $slugs, true);
        
        // If only first and/or last are included, use simple layout
        if (($has_first || $has_last) && count(array_diff($slugs, array('first', 'last'))) === 0) {
            return 'simple';
        }
        
        // Default to full layout for all other cases
        return 'full';
    }

    /**
     * Render the attendee list shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_attendee_shortcode($atts) {
        EVGE()->script_service()->enqueue_script('evge_common');
        EVGE()->script_service()->enqueue_script('evge_attendee_list');
        EVGE()->style_service()->enqueue_style('evge_common');
        EVGE()->style_service()->enqueue_style('evge_attendee_list');
        EVGE()->style_service()->enqueue_style('evge_single_post');

        $atts = shortcode_atts(array(
            'event' => 0,
            'template' => 'auto',
            'status' => 'confirmed',
            'per_load' => 20,
            'orderby' => 'registration_date',
            'order' => 'DESC',
            'fields' => '', // Comma-separated list of field slugs
            'format' => '' // Format string for simple template (e.g., "{first} {last}")
        ), $atts, 'event-genius-attendees');

        // Validate event ID
        $event_id = absint($atts['event']);
        if (empty($event_id)) {
            // If no event_id specified, check if we're on a single event page
            if (is_singular(EVGE_EVENT_POST_TYPE)) {
                $event_id = get_the_ID();
            } else {
                // Fallback: try to get it from the current post (for other contexts)
                $event_id = get_the_ID();
            }
        }

        if (empty($event_id)) {
            return Utils::get_attendee_list_not_available_message( 0 );
        }
        
        // Verify that the event_id is actually an event post type
        if (get_post_type($event_id) !== EVGE_EVENT_POST_TYPE) {
            return Utils::get_attendee_list_not_available_message( $event_id );
        }
        
        // Check if the event is visible to the current user
        if ( ! Utils::is_event_visible_to_user( $event_id ) ) {
            return Utils::get_event_visibility_error_message( 'attendee_list' );
        }
        
        // Check if attendee list is enabled for this event
        $event_post = new EventPost($event_id);
        if ( $event_post->get_show_attendee_list() !== 'enabled' ) {
            return Utils::get_attendee_list_not_available_message( $event_id ) ;
        }
        
        // Create event object
        $event = new Event($event_id);

        // Parse selected fields
        $selected_fields = array();
        if (!empty($atts['fields'])) {
            $fields_array = explode(',', $atts['fields']);
            $selected_fields = array_map('trim', $fields_array);
            $selected_fields = array_filter($selected_fields); // Remove empty values
        }

        // Determine template - if "auto", determine based on enabled fields
        $template = sanitize_key($atts['template']);
        $from_auto = false;
        if ($template === 'auto') {
            $template = $this->determine_template($event);
            $from_auto = true;
        }

        // Parse format for simple template
        $simple_format = '';
        if ($template === 'simple' && !empty($atts['format'])) {
            $simple_format = sanitize_text_field($atts['format']);
        }

        // Create attendee list using factory
        $factory = new RegistrationObjectFactory();
        $attendee_list = $factory->create_attendee_list(
            new Database(),
            $event,
            array(
                'event_id' => $event_id,
                'template' => $template,
                'status' => sanitize_key($atts['status']),
                'per_load' => absint($atts['per_load']),
                'orderby' => sanitize_sql_orderby($atts['orderby']),
                'order' => in_array(strtoupper($atts['order']), array('ASC', 'DESC')) ? strtoupper($atts['order']) : 'DESC',
                'selected_fields' => $selected_fields,
                'simple_format' => $simple_format,
                'from_auto' => $from_auto
            )
        );

        return $attendee_list->get_list();
    }

    public function load_more_attendees() {
        //phpcs:ignore WordPress.Security.NonceVerification.Missing
        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        
        // Check if the event is visible to the current user
        if ( ! Utils::is_event_visible_to_user( $event_id ) ) {
            $error_message = Utils::get_event_visibility_error_message( 'load_more' );
            wp_send_json_error( array(
                'html' => wp_kses_post( $error_message ),
                'count' => 0,
                'has_more' => false,
                'remaining' => 0
            ) );
            return;
        }
        
        // Check if attendee list is enabled for this event
        $event_post = new EventPost($event_id);
        if ( $event_post->get_show_attendee_list() !== 'enabled' ) {
            $message = '<div class="evge-attendee-list-message" role="status" aria-live="polite">' . 
                       esc_html__( 'The attendee list is not available for this event.', 'event-genius' ) . 
                       '</div>';
            wp_send_json_success( array(
                'html' => wp_kses_post( $message ),
                'count' => 0,
                'has_more' => false,
                'remaining' => 0
            ) );
            return;
        }
        
        $event = new Event($event_id);

        //phpcs:ignore WordPress.Security.NonceVerification.Missing
        $template = isset($_POST['template']) ? sanitize_key($_POST['template']) : '';
        
        // Determine template - if "auto", determine based on enabled fields
        $from_auto = false;
        if ($template === 'auto') {
            $template = $this->determine_template($event);
            $from_auto = true;
        }
        
        // Only allow load more for "simple" layout
        if ( $template !== 'simple' ) {
            wp_send_json_error( array(
                'html' => '',
                'count' => 0,
                'has_more' => false,
                'remaining' => 0
            ) );
            return;
        }
        //phpcs:ignore WordPress.Security.NonceVerification.Missing
        $status = isset($_POST['status']) ? sanitize_key($_POST['status']) : '';
        //phpcs:ignore WordPress.Security.NonceVerification.Missing
        $offset = isset($_POST['offset']) ? absint($_POST['offset']) : 0;
        //phpcs:ignore WordPress.Security.NonceVerification.Missing
        $simple_format = isset($_POST['simple_format']) ? sanitize_text_field($_POST['simple_format']) : '';
        
        $factory = new RegistrationObjectFactory();
        $attendee_list = $factory->create_attendee_list(
            new Database(),
            $event,
            array(
                'event_id' => $event_id,
                'template' => $template,
                'status' => $status,
                'offset' => $offset,
                'is_ajax' => true,
                'simple_format' => $simple_format,
                'from_auto' => $from_auto
            )
        );

        $html = $attendee_list->get_list();
        $total = $attendee_list->get_total_registrations();
        
        // Get the actual count of attendees returned from the database (more reliable than counting HTML)
        $count = $attendee_list->get_current_batch_count();
        
        // Calculate the new offset after loading these items
        $new_offset = $offset + $count;
        
        // Calculate remaining: total registrations minus the new offset
        $remaining = $total - $new_offset;
        
        // has_more should be true if there are more items beyond what we've loaded
        $has_more = $remaining > 0;

        wp_send_json_success(array(
            'html' => $html,
            'count' => $count,
            'has_more' => $has_more,
            'remaining' => max(0, $remaining)
        ));
    }
} 