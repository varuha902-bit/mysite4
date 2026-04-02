<?php
namespace WPEventGenius\Admin\CustomPostTypes\QuickCreate;

use WPEventGenius\Common\Utils\DateFormatter;
use WPEventGenius\Common\Utils\EvgeDateTime;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class EventCreate extends BaseCreate {
    protected const POST_TYPE = EVGE_EVENT_POST_TYPE;
    protected const SLUG = 'event';
    
    protected $db;
    
    public function __construct($args) {
        parent::__construct($args);
        $this->db = new \WPEventGenius\Common\Database();
    }

    public function expected_input_names() {
        return array(
            'evge_start_date',
            'evge_end_date',
	        'evge_start_date_utc',
	        'evge_end_date_utc',
            'evge_timezone',
            'evge_all_day',
            'evge_summary',
            'evge_venue',
            'evge_organizer',
            'evge_currency_symbol',
            'evge_cost_amount',
            'evge_cost_display',
            'evge_allow_registration',
            'evge_unlimited_capacity',
            'evge_capacity',
            'evge_capacity_per_registration',
            'evge_show_attendee_list',
            'evge_who_can_see_attendee_list',
            'evge_open_type',
            'evge_relative_open_offset',
            'evge_relative_open_offset_type',
            'evge_open_date',
            'evge_close_type',
            'evge_relative_close_offset',
            'evge_relative_close_offset_type',
            'evge_close_date',
            'evge_recurrence_type',
            'evge_recurrence_end_date'
        );
    }

    public function insert_new_post() {
        $post_id = parent::insert_new_post();
        
        if (!$post_id) {
            return false;
        }

        // Process post meta if it exists
        if (!empty($this->args['post_meta'])) {
            $skip_fields = array(
                'evge_series_created',
            );

            foreach ($this->args['post_meta'] as $meta_key => $values) {
                if (!in_array($meta_key, $skip_fields, true)) {
                    if (is_array($values)) {
                        foreach ($values as $value) {
                            add_post_meta($post_id, $meta_key, $value);
                        }
                    }
                }
            }
        }

        $start_date = new EvgeDateTime(new \DateTime($this->args['start_date'], DateFormatter::timezone_object($this->args['timezone'])));
        $end_date = new EvgeDateTime(new \DateTime($this->args['end_date'], DateFormatter::timezone_object($this->args['timezone'])));

        // Sync with events table
        $this->db->sync_event_timing(
            $post_id,
            $start_date,
            $end_date,
            $this->args['timezone']
        );

        return $post_id;
    }

    /**
     * Sanitize event data from raw post data
     * 
     * @param array $data Raw post data
     * @return array Sanitized event data
     */
    public static function sanitize_event_data($data) {
        $sanitized = array();
        
        // Timezone
        $sanitized['evge_timezone'] = empty($data['evge_timezone']) || 'default' === $data['evge_timezone'] 
            ? wp_timezone_string() 
            : sanitize_text_field($data['evge_timezone']);
        
        // All day setting
        $sanitized['evge_all_day'] = !empty($data['evge_all_day']) && $data['evge_all_day'] === 'enabled' 
            ? 'enabled' 
            : 'disabled';
        
        // Get timezone for date processing
        $timezone = $sanitized['evge_timezone'];
        
        // Process dates
        if (!empty($data['evge_start_date']) && !empty($data['evge_end_date'])) {
            // Create date objects
            if ($sanitized['evge_all_day'] === 'enabled') {
                $start_date = new EvgeDateTime(new \DateTime(sanitize_text_field($data['evge_start_date']), DateFormatter::timezone_object($timezone)));
                $end_date = new EvgeDateTime(new \DateTime(sanitize_text_field($data['evge_end_date']), DateFormatter::timezone_object($timezone)));
                $start_date->set_time(0, 0);
                $end_date->set_time(23, 59);
            } else {
                $start_date = new EvgeDateTime(new \DateTime(sanitize_text_field($data['evge_start_date']), DateFormatter::timezone_object($timezone)));
                $end_date = new EvgeDateTime(new \DateTime(sanitize_text_field($data['evge_end_date']), DateFormatter::timezone_object($timezone)));
            }
            
            // Store formatted dates
            $sanitized['evge_start_date'] = $start_date->format('Y-m-d H:i:s');
            $sanitized['evge_end_date'] = $end_date->format('Y-m-d H:i:s');
            $sanitized['evge_start_date_utc'] = gmdate('Y-m-d H:i:s', $start_date->utc_timestamp());
            $sanitized['evge_end_date_utc'] = gmdate('Y-m-d H:i:s', $end_date->utc_timestamp());
            
            // Store date objects for later use
            $sanitized['_start_date_obj'] = $start_date;
            $sanitized['_end_date_obj'] = $end_date;
        }
        
        // Summary
        if (isset($data['evge_summary'])) {
            $sanitized['evge_summary'] = sanitize_textarea_field($data['evge_summary']);
        }
        
        // Venue IDs
        if (isset($data['evge_venue'])) {
            $sanitized['evge_venue'] = array_map('absint', (array)$data['evge_venue']);
        }
        
        // Organizer IDs
        if (isset($data['evge_organizer'])) {
            $sanitized['evge_organizer'] = array_map('absint', (array)$data['evge_organizer']);
        }
        
        // Cost settings
        if (isset($data['evge_currency_symbol'])) {
            $sanitized['evge_currency_symbol'] = sanitize_text_field($data['evge_currency_symbol']);
        }
        if (isset($data['evge_cost_amount'])) {
            $sanitized['evge_cost_amount'] = sanitize_text_field($data['evge_cost_amount']);
        }
        if (isset($data['evge_cost_display'])) {
            $sanitized['evge_cost_display'] = wp_kses_post($data['evge_cost_display']);
        }
        
        // Registration settings
        if (isset($data['evge_allow_registration'])) {
            $sanitized['evge_allow_registration'] = !empty($data['evge_allow_registration']) && 
                sanitize_key($data['evge_allow_registration']) === 'enabled' ? 'enabled' : 'disabled';
        }
        
        // Capacity settings
        if (isset($data['evge_unlimited_capacity'])) {
            $sanitized['evge_unlimited_capacity'] = !empty($data['evge_unlimited_capacity']) && 
                sanitize_key($data['evge_unlimited_capacity']) === 'enabled' ? 'enabled' : 'disabled';
        }
        if (isset($data['evge_capacity'])) {
            $sanitized['evge_capacity'] = absint($data['evge_capacity']);
        }
        if (isset($data['evge_capacity_per_registration'])) {
            $sanitized['evge_capacity_per_registration'] = max(1, absint($data['evge_capacity_per_registration']));
        }
        
        // Attendee list settings
        if (isset($data['evge_show_attendee_list'])) {
            $sanitized['evge_show_attendee_list'] = !empty($data['evge_show_attendee_list']) && 
                sanitize_key($data['evge_show_attendee_list']) === 'enabled' ? 'enabled' : 'disabled';
        }
        if (isset($data['evge_who_can_see_attendee_list'])) {
            $sanitized['evge_who_can_see_attendee_list'] = sanitize_text_field($data['evge_who_can_see_attendee_list']);
        }
        
        // Timeline settings
        if (isset($data['evge_open_type'])) {
            $sanitized['evge_open_type'] = sanitize_text_field($data['evge_open_type']);
        }
        if (isset($data['evge_relative_open_offset'])) {
            $sanitized['evge_relative_open_offset'] = absint($data['evge_relative_open_offset']);
        }
        if (isset($data['evge_relative_open_offset_type'])) {
            $sanitized['evge_relative_open_offset_type'] = sanitize_text_field($data['evge_relative_open_offset_type']);
        }
        if (isset($data['evge_close_type'])) {
            $sanitized['evge_close_type'] = sanitize_text_field($data['evge_close_type']);
        }
        if (isset($data['evge_relative_close_offset'])) {
            $sanitized['evge_relative_close_offset'] = absint($data['evge_relative_close_offset']);
        }
        if (isset($data['evge_relative_close_offset_type'])) {
            $sanitized['evge_relative_close_offset_type'] = sanitize_text_field($data['evge_relative_close_offset_type']);
        }
        
        // Open and close dates
        if (isset($data['evge_open_date']) && !empty($data['evge_open_date'])) {
            $open_date = new EvgeDateTime(new \DateTime(sanitize_text_field($data['evge_open_date']), DateFormatter::timezone_object($timezone)));
            $sanitized['evge_open_date'] = $open_date->format('Y-m-d H:i:s');
            $sanitized['evge_open_date_utc'] = gmdate('Y-m-d H:i:s', $open_date->utc_timestamp());
        }
        if (isset($data['evge_close_date']) && !empty($data['evge_close_date'])) {
            $close_date = new EvgeDateTime(new \DateTime(sanitize_text_field($data['evge_close_date']), DateFormatter::timezone_object($timezone)));
            $sanitized['evge_close_date'] = $close_date->format('Y-m-d H:i:s');
            $sanitized['evge_close_utc'] = gmdate('Y-m-d H:i:s', $close_date->utc_timestamp());
        }
        
        // Recurrence settings
        if (isset($data['evge_recurrence_type'])) {
            $sanitized['evge_recurrence_type'] = sanitize_key($data['evge_recurrence_type']);
        }
        
        if (isset($data['evge_recurrence_end_date']) && !empty($data['evge_recurrence_end_date'])) {
            $recurrence_end_date = new EvgeDateTime(new \DateTime(sanitize_text_field($data['evge_recurrence_end_date']), DateFormatter::timezone_object($timezone)));
            $sanitized['evge_recurrence_end_date'] = $recurrence_end_date->format('Y-m-d');
        } elseif (isset($sanitized['_start_date_obj'])) {
            // Default to 2 months from start date if not provided
            $recurrence_end_date = new \DateTime($sanitized['_start_date_obj']->format('Y-m-d'));
            $recurrence_end_date->modify('+2 months');
            $sanitized['evge_recurrence_end_date'] = $recurrence_end_date->format('Y-m-d');
        }
        
        // Remove temporary objects
        unset($sanitized['_start_date_obj']);
        unset($sanitized['_end_date_obj']);
        
        return $sanitized;
    }

    public function insert_meta($meta_key_value_pairs) {
        // This method can be implemented if needed
    }

    /**
     * Prepare event data from a template event
     */
    public static function prepare_from_template($template_post_id, \DateTime $start_date) {
        $template_post = get_post($template_post_id);
        if (!$template_post) {
            return false;
        }

        // Get all meta
        $meta = get_post_meta($template_post_id);

        // Calculate duration from template event (using UTC dates)
        $template_start = new \DateTime($meta['evge_start_date'][0], new \DateTimeZone('UTC'));
        $template_end = new \DateTime($meta['evge_end_date'][0], new \DateTimeZone('UTC'));
        $duration = $template_start->diff($template_end);
        $timezone_meta = $meta['evge_timezone'][0] ?? wp_timezone_string();

        // Get timezone from template or default
        $timezone = DateFormatter::timezone_object( $timezone_meta );
        
        // Calculate new dates
        $start_date_utc = new \DateTime($start_date->format('Y-m-d H:i:s'), $timezone);
        $start_date_utc->setTimezone(new \DateTimeZone('UTC'));

        $end_date = new \DateTime($start_date->format('Y-m-d H:i:s'), $timezone);
        $end_date->add($duration);

        // Calculate end dates by adding duration
        $end_date_utc = new \DateTime($start_date->format('Y-m-d H:i:s'), $timezone);
	    $end_date_utc->setTimezone(new \DateTimeZone('UTC'));
	    $end_date_utc->add($duration);

        // Start with basic post data
        $args = array(
            'post_title' => $template_post->post_title,
            'post_content' => $template_post->post_content,
            'post_meta' => $meta,
            'start_date' => $start_date->format('Y-m-d H:i:s'),
            'end_date' => $end_date->format('Y-m-d H:i:s'),
            'start_date_utc' => $start_date_utc->format('Y-m-d H:i:s'),
            'end_date_utc' => $end_date_utc->format('Y-m-d H:i:s'),
            'timezone' => $timezone_meta,
            'evge_start_date' => $start_date->format('Y-m-d H:i:s'),
            'evge_end_date' => $end_date->format('Y-m-d H:i:s'),
            'evge_start_date_utc' => $start_date_utc->format('Y-m-d H:i:s'),
            'evge_end_date_utc' => $end_date_utc->format('Y-m-d H:i:s'),
        );

		$args['post_meta']['evge_start_date'] = array($args['start_date']);
		$args['post_meta']['evge_end_date'] = array($args['end_date']);
		$args['post_meta']['evge_start_date_utc'] = array($args['start_date_utc']);
		$args['post_meta']['evge_end_date_utc'] = array($args['end_date_utc']);

		return $args;
	}

	public function sync_event_timing($event_id, $start_date, $end_date, $timezone) {
		$this->db->sync_event_timing($event_id,
			$start_date,
			$end_date,
			$timezone
		);
	}

    /**
     * Update post meta with sanitized event data
     * 
     * @param int $post_id The post ID to update
     * @param array $sanitized The sanitized event data
     */
    public static function update_event_meta($post_id, $sanitized) {
        // Save all day setting
        update_post_meta($post_id, 'evge_all_day', $sanitized['evge_all_day']);
        
        // Save dates
        update_post_meta($post_id, 'evge_start_date', $sanitized['evge_start_date']);
        update_post_meta($post_id, 'evge_end_date', $sanitized['evge_end_date']);
        update_post_meta($post_id, 'evge_start_date_utc', $sanitized['evge_start_date_utc']);
        update_post_meta($post_id, 'evge_end_date_utc', $sanitized['evge_end_date_utc']);
        update_post_meta($post_id, 'evge_timezone', $sanitized['evge_timezone']);
        
        // Save summary
        if (isset($sanitized['evge_summary'])) {
            update_post_meta($post_id, 'evge_summary', $sanitized['evge_summary']);
        }

        // Save cost settings
        if (isset($sanitized['evge_currency_symbol'])) {
            update_post_meta($post_id, 'evge_currency_symbol', $sanitized['evge_currency_symbol']);
        }
        if (isset($sanitized['evge_cost_amount'])) {
            update_post_meta($post_id, 'evge_cost_amount', $sanitized['evge_cost_amount']);
        }
        if (isset($sanitized['evge_cost_display'])) {
            update_post_meta($post_id, 'evge_cost_display', $sanitized['evge_cost_display']);
        }

        // Save registration timeline settings
        if (isset($sanitized['evge_open_type'])) {
            update_post_meta($post_id, 'evge_open_type', $sanitized['evge_open_type']);
        }
        if (isset($sanitized['evge_relative_open_offset'])) {
            update_post_meta($post_id, 'evge_relative_open_offset', $sanitized['evge_relative_open_offset']);
        }
        if (isset($sanitized['evge_relative_open_offset_type'])) {
            update_post_meta($post_id, 'evge_relative_open_offset_type', $sanitized['evge_relative_open_offset_type']);
        }
        if (isset($sanitized['evge_close_type'])) {
            update_post_meta($post_id, 'evge_close_type', $sanitized['evge_close_type']);
        }
        if (isset($sanitized['evge_relative_close_offset'])) {
            update_post_meta($post_id, 'evge_relative_close_offset', $sanitized['evge_relative_close_offset']);
        }
        if (isset($sanitized['evge_relative_close_offset_type'])) {
            update_post_meta($post_id, 'evge_relative_close_offset_type', $sanitized['evge_relative_close_offset_type']);
        }

        // Save open and close dates
        if (isset($sanitized['evge_open_date'])) {
            update_post_meta($post_id, 'evge_open_date', $sanitized['evge_open_date']);
        }
        if (isset($sanitized['evge_open_date_utc'])) {
            update_post_meta($post_id, 'evge_open_date_utc', $sanitized['evge_open_date_utc']);
        }
        if (isset($sanitized['evge_close_date'])) {
            update_post_meta($post_id, 'evge_close_date', $sanitized['evge_close_date']);
        }
        if (isset($sanitized['evge_close_utc'])) {
            update_post_meta($post_id, 'evge_close_utc', $sanitized['evge_close_utc']);
        }

        // Save registration settings
        if (isset($sanitized['evge_allow_registration'])) {
            update_post_meta($post_id, 'evge_allow_registration', $sanitized['evge_allow_registration']);
        }

        // Save capacity settings
        if (isset($sanitized['evge_unlimited_capacity'])) {
            update_post_meta($post_id, 'evge_unlimited_capacity', $sanitized['evge_unlimited_capacity']);
        }
        if (isset($sanitized['evge_capacity'])) {
            update_post_meta($post_id, 'evge_capacity', $sanitized['evge_capacity']);
        }
        if (isset($sanitized['evge_capacity_per_registration'])) {
            update_post_meta($post_id, 'evge_capacity_per_registration', $sanitized['evge_capacity_per_registration']);
        }
        // Save attendee list settings
        if (isset($sanitized['evge_show_attendee_list'])) {
            update_post_meta($post_id, 'evge_show_attendee_list', $sanitized['evge_show_attendee_list']);
        }
        if (isset($sanitized['evge_who_can_see_attendee_list'])) {
            update_post_meta($post_id, 'evge_who_can_see_attendee_list', $sanitized['evge_who_can_see_attendee_list']);
        }
        
        // Save recurrence settings
        if (isset($sanitized['evge_recurrence_type'])) {
            update_post_meta($post_id, 'evge_recurrence_type', $sanitized['evge_recurrence_type']);
        }
        
        if (isset($sanitized['evge_recurrence_end_date'])) {
            update_post_meta($post_id, 'evge_recurrence_end_date', $sanitized['evge_recurrence_end_date']);
        }
    }

    /**
     * Copy an event from a template, including post data and meta
     * 
     * @param int $template_post_id The source/template post ID
     * @param int $target_post_id The target post ID to copy to
     * @param array $exclude_fields Optional array of meta keys to exclude from copying
     * @return bool Success status
     */
    public static function copy_event($template_post_id, $target_post_id, $exclude_fields = array()) {
        // Get the template post
        $template_post = get_post($template_post_id);
        if (!$template_post || $template_post->post_type !== EVGE_EVENT_POST_TYPE) {
            return false;
        }
        
        // Update the target post with content from template
        $post_data = array(
            'ID' => $target_post_id,
            'post_content' => $template_post->post_content,
            'post_title' => $template_post->post_title,
            'post_excerpt' => $template_post->post_excerpt,
            'post_status' => $template_post->post_status,
            'comment_status' => $template_post->comment_status,
            'ping_status' => $template_post->ping_status,
            'post_password' => $template_post->post_password,
            'menu_order' => $template_post->menu_order
        );
        
        // Update the post
        $result = wp_update_post($post_data, true);
        if (is_wp_error($result)) {
            return false;
        }
        
        // Default fields to exclude (scheduling-related)
        $default_exclude = array(
            'evge_start_date',
            'evge_end_date',
            'evge_start_date_utc',
            'evge_end_date_utc',
            'evge_timezone',
            'evge_series_created',
            'evge_series_parent',
            'evge_series_child',
            'evge_recurrence_type',
            'evge_recurrence_end_date',
            'evge_pending_recurrence_changes',
            'evge_registrations_count',
            'evge_last_updated'
        );
        
        // Allow filtering of excluded fields
        $default_exclude = apply_filters('evge_copy_event_exclude_fields', $default_exclude, $template_post_id, $target_post_id);
        
        // Merge default exclusions with any additional ones
        $exclude_fields = array_merge($default_exclude, $exclude_fields);
        
        // Get all meta from template post
        $template_meta = get_post_meta($template_post_id);
        if (empty($template_meta)) {
            return false;
        }
        
        // Copy each meta field that's not excluded
        foreach ($template_meta as $meta_key => $meta_values) {
            // Skip excluded fields
            if (in_array($meta_key, $exclude_fields)) {
                continue;
            }
            
            // Skip WordPress internal meta fields
            if (strpos($meta_key, '_') === 0) {
                continue;
            }
            
            // For each value of this meta key
            foreach ($meta_values as $meta_value) {
                // Check if this is a serialized array
                if (is_serialized($meta_value)) {
                    $meta_value = maybe_unserialize($meta_value);
                }
                
                // Allow filtering of meta values before copying
                $filtered_value = apply_filters('evge_copy_event_meta_value', $meta_value, $meta_key, $template_post_id, $target_post_id);
                
                // Skip if filter returns false (indicating this should not be copied)
                if ($filtered_value === false) {
                    continue;
                }
                
                // Update the meta on the target post
                update_post_meta($target_post_id, $meta_key, $filtered_value);
            }
        }
        
        // Copy taxonomies
        $taxonomies = get_object_taxonomies(EVGE_EVENT_POST_TYPE);
        foreach ($taxonomies as $taxonomy) {
            $terms = wp_get_object_terms($template_post_id, $taxonomy, array('fields' => 'ids'));
            if (!is_wp_error($terms) && !empty($terms)) {
                wp_set_object_terms($target_post_id, $terms, $taxonomy);
            }
        }
        
        // Copy venue and organizer relationships
        $venue_ids = get_post_meta($template_post_id, 'evge_venue');
        if (!empty($venue_ids)) {
            delete_post_meta($target_post_id, 'evge_venue');
            foreach ($venue_ids as $venue_id) {
                add_post_meta($target_post_id, 'evge_venue', $venue_id);
            }
            
            // Copy venue order if it exists
            $venue_order = get_post_meta($template_post_id, 'evge_venue_order', true);
            if (!empty($venue_order)) {
                update_post_meta($target_post_id, 'evge_venue_order', $venue_order);
            }
        }
        
        $organizer_ids = get_post_meta($template_post_id, 'evge_organizer');
        if (!empty($organizer_ids)) {
            delete_post_meta($target_post_id, 'evge_organizer');
            foreach ($organizer_ids as $organizer_id) {
                add_post_meta($target_post_id, 'evge_organizer', $organizer_id);
            }
            
            // Copy organizer order if it exists
            $organizer_order = get_post_meta($template_post_id, 'evge_organizer_order', true);
            if (!empty($organizer_order)) {
                update_post_meta($target_post_id, 'evge_organizer_order', $organizer_order);
            }
        }
        
        // Copy featured image if exists
        $thumbnail_id = get_post_thumbnail_id($template_post_id);
        if ($thumbnail_id) {
            set_post_thumbnail($target_post_id, $thumbnail_id);
        }
        
        return true;
    }
} 