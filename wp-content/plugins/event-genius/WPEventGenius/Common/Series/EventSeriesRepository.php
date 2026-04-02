<?php
namespace WPEventGenius\Common\Series;

use WPEventGenius\Common\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class EventSeriesRepository {
    protected $db;
    
    public function __construct( Database $db) {
        $this->db = $db;
    }
    
    /**
     * Create a new series
     */
    public function create_series($series_data) {
        $post_data = array(
            'post_type' => EVGE_SERIES_POST_TYPE,
            'post_title' => $series_data['title'],
            'post_status' => 'publish'
        );
        
        $series_id = wp_insert_post($post_data);
        
        if (!is_wp_error($series_id)) {
            return $series_id;
        }

        return false;
        
    }

    /**
     * Recreate series events using an existing series post
     * 
     * @param int $series_id The existing series ID to use
     * @param int $template_id The template event ID
     * @param array $pattern_data The recurrence pattern data
     * @return bool True if recreation was successful
     */
    public function recreate_series_events($series_id, $template_id, $pattern_data) {
        try {
            // Start transaction
            $this->db->start_transaction();
            
            // Update the template event to reference this series
            update_post_meta($template_id, 'evge_series_id', $series_id);
            
            // Update the series meta to reference the template event
            update_post_meta($series_id, 'template_event_id', $template_id);
            
            // Create pattern object
            $pattern = new \WPEventGenius\Common\Series\RecurrencePattern($pattern_data);
            
            // Queue series recreation
            $queue = new \WPEventGenius\Common\Series\Queue\SeriesQueue($this->db);
            $queue->enqueue(array(
                'type' => \WPEventGenius\Common\Series\Queue\SeriesQueue::TASK_TYPE_CREATE,
                'series_id' => $series_id, // Use existing series ID
                'pattern' => $pattern,
                'template_id' => $template_id,
                'preserve_series' => true // Flag to indicate we're preserving the series
            ));
            
            // Commit transaction
            $this->db->commit_transaction();
            
            return true;
            
        } catch (\Exception $e) {
            // Rollback on error
            $this->db->rollback_transaction();
            
            return false;
        }
    }
    
    /**
     * Add relationship between series and event
     * 
     * @param int $series_id The series ID
     * @param int $event_id The event ID
     * @return bool Success status
     */
    public function add_relationship($series_id, $event_id, $non_recurring = false) {
        // Store the series ID as post meta on the event
        $meta_result = update_post_meta($event_id, 'evge_series_id', $series_id);
        
        // Also store in the custom table for backward compatibility
        $db_result = true;
        
        // Check if relationship already exists to avoid duplicates
        if (!$this->db->series_relationship_exists($series_id, $event_id)) {
            $db_result = $this->db->insert_series_relationship($series_id, $event_id);
        }
        if ($non_recurring) {
            update_post_meta($event_id, 'evge_series_added_via_setting', $series_id);
        }
        return $meta_result && $db_result;
    }
    
    /**
     * Get all events in a series
     * 
     * @param int $series_id The series ID
     * @return array Array of event post IDs
     */
    public function get_series_events($series_id) {
        // Use WP_Query to get events in this series based on post meta
        $args = array(
            'post_type' => EVGE_EVENT_POST_TYPE,
            'post_status' => array('publish', 'draft', 'pending', 'future'),
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => 'evge_series_id',
                    'value' => $series_id,
                    'compare' => '='
                )
            )
        );
        
        $query = new \WP_Query($args);
        $post_ids = $query->posts;
        
        // Also check the custom table for any additional events (for backward compatibility)
        $table_post_ids = $this->db->get_series_event_ids_from_table($series_id);
        
        // Merge and remove duplicates
        $post_ids = array_unique(array_merge($post_ids, $table_post_ids));
        
        return $post_ids;
    }
    
    /**
     * Remove relationship between series and event
     * 
     * @param int $series_id The series ID
     * @param int $event_id The event ID
     * @return bool Success status
     */
    public function remove_relationship($series_id, $event_id) {
        // Remove the series ID from the event's post meta
        $meta_result = delete_post_meta($event_id, 'evge_series_id', $series_id);
        
        // Also remove from the custom table for backward compatibility
        $db_result = $this->db->delete_series_relationship($series_id, $event_id);
        
        // Clean up the "added via setting" meta if it exists for this series
        delete_post_meta($event_id, 'evge_series_added_via_setting');
        
        return $meta_result && ($db_result !== false);
    }
    
    /**
     * Delete all relationships for a series and return affected event IDs
     * 
     * @param int $series_id
     * @return array Array of event IDs that were in the series
     */
    public function delete_series_relationships($series_id) {
        // Get all event IDs first
        $event_ids = $this->get_series_events($series_id);
        
        // Remove the series ID from each event's post meta
        foreach ($event_ids as $event_id) {
            delete_post_meta($event_id, 'evge_series_id', $series_id);
        }
        
        // Also delete from the custom table for backward compatibility
        $this->db->delete_all_series_relationships($series_id);
        
        return $event_ids;
    }
    
    /**
     * Reset a series by clearing only recurrence-created events but preserving the series post and events added via settings
     * 
     * @param int $series_id The ID of the series to reset
     * @param int $template_id The template event ID
     * @return bool True if reset was successful
     */
    public function reset_series($series_id, $template_id = null) {
        try {
            // Start transaction
            $this->db->start_transaction();
            
            // Get all events in the series
            $event_ids = $this->get_series_events($series_id);

            // Remove series ID from template event
            if ($template_id) {
                delete_post_meta($template_id, 'evge_series_id', $series_id);
            }
            
            // Process events in the series
            foreach ($event_ids as $event_id) {
                if ($event_id == $template_id) {
                    continue;
                }
                
                // Check if this event was added to the series via settings
                $added_via_setting = get_post_meta($event_id, 'evge_series_added_via_setting', true);
                
                if ($added_via_setting && (int)$added_via_setting === (int)$series_id) {
                    // Event was added via series settings - KEEP IT in the series, do nothing
                    continue;
                } else {
                    // Event was created via recurrence - delete it entirely
                    wp_delete_post($event_id, true);
                    
                    // Delete from events table and other event-related tables
                    $this->db->delete_event_custom_data($event_id);
                }
            }
            
            // Delete series relationships (this will remove the relationships for deleted events)
            $this->db->delete_all_series_relationships($series_id);
            
            // Re-add relationships for events that were added via settings
            foreach ($event_ids as $event_id) {
                if ($event_id == $template_id) {
                    continue;
                }
                
                $added_via_setting = get_post_meta($event_id, 'evge_series_added_via_setting', true);
                if ($added_via_setting && (int)$added_via_setting === (int)$series_id) {
                    // Re-add the relationship for events added via settings
                    $this->add_relationship($series_id, $event_id, true);
                }
            }
            
            // Note: We do NOT delete the series post - we preserve it with all its data
            
            // Commit transaction
            $this->db->commit_transaction();
            
            return true;
            
        } catch (\Exception $e) {
            // Rollback on error
            $this->db->rollback_transaction();
            
            return false;
        }
    }

    /**
     * Delete a series and all its related data
     * 
     * @param int $series_id The ID of the series to delete
     * @return bool True if deletion was successful
     */
    public function delete_series($series_id, $template_id = null) {
        try {
            // Start transaction
            $this->db->start_transaction();
            
            // Get all events in the series
            $event_ids = $this->get_series_events($series_id);

            delete_post_meta($template_id, 'evge_series_id', $series_id);
            
            // Process events in the series
            foreach ($event_ids as $event_id) {
                if ($event_id == $template_id) {
                    continue;
                }
                
                // Check if this event was added to the series via settings
                $added_via_setting = get_post_meta($event_id, 'evge_series_added_via_setting', true);
                
                if ($added_via_setting && (int)$added_via_setting === (int)$series_id) {
                    // Event was added via series settings - only remove the relationship, don't delete the event
                    $this->remove_relationship($series_id, $event_id);
                } else {
                    // Event was created via recurrence - delete it entirely
                    wp_delete_post($event_id, true);
                    
                    // Delete from events table and other event-related tables
                    $this->db->delete_event_custom_data($event_id);
                }
            }
            
            // Delete series relationships
            $this->db->delete_all_series_relationships($series_id);
            
            // Delete series and its meta (true = force delete, bypass trash)
            wp_delete_post($series_id, true);
            
            // Commit transaction
            $this->db->commit_transaction();
            
            return true;
            
        } catch (\Exception $e) {
            // Rollback on error
            $this->db->rollback_transaction();
            
            return false;
        }
    }
    
    /**
     * Clean up any additional event data in custom tables
     */
    protected function cleanup_event_data($event_id) {
        $this->db->delete_event_custom_data($event_id);
    }
    
    /**
     * Get the series ID for a template event
     * 
     * @param int $template_id The template event ID
     * @return int|false Series ID if found, false otherwise
     */
    public function get_series_id_for_template($template_id) {
        $series_id = get_post_meta($template_id, 'evge_series_id', true);
        
        return $series_id ? (int)$series_id : false;
    }
    
    /**
     * Get the series ID for any event
     * 
     * @param int $event_id The event ID
     * @return int|false Series ID if found, false otherwise
     */
    public function get_series_id_for_event($event_id) {
        // First check post meta
        $series_id = get_post_meta($event_id, 'evge_series_id', true);
        if ($series_id) {
            return (int)$series_id;
        }
        
        // If not found in post meta, check the custom table for backward compatibility
        $series_id = $this->db->get_series_id_for_event($event_id);
        if ($series_id) {
            // Update post meta for future use
            update_post_meta($event_id, 'evge_series_id', $series_id);
            return (int)$series_id;
        }
        
        return false;
    }
    
    /**
     * Get the count of events in a series
     * 
     * @param int $series_id The series ID
     * @return int Number of events in the series
     */
    public function get_series_event_count($series_id) {
        
        // Add any additional events from the custom table
        $table_count = $this->db->get_series_event_count($series_id);
        
        return (int)$table_count;
    }
    
    /**
     * Get the template event ID for a series
     * 
     * @param int $series_id The series ID
     * @return int|false The template event ID if found, false otherwise
     */
    public function get_template_event_id($series_id) {
        // First check post meta for any event with this series ID and evge_is_recurrence meta
        $args = array(
            'post_type' => EVGE_EVENT_POST_TYPE,
            'post_status' => array('publish', 'draft', 'pending', 'future'),
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'evge_series_id',
                    'value' => $series_id,
                    'compare' => '='
                ),
                array(
                    'key' => 'evge_is_recurrence',
                    'compare' => 'EXISTS'
                )
            )
        );
        
        $query = new \WP_Query($args);
        if (!empty($query->posts)) {
            $event_id = $query->posts[0];
            $is_recurrence = get_post_meta($event_id, 'evge_is_recurrence', true);
            if (! empty($is_recurrence)) {
                return absint($is_recurrence);
            }
        }
        
        return false;
    }
} 