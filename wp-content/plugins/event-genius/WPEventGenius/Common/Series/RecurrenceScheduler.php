<?php
namespace WPEventGenius\Common\Series;

use WPEventGenius\Admin\CustomPostTypes\QuickCreate\EventCreate;
use WPEventGenius\Common\Series\Queue\SeriesQueue;
use WPEventGenius\Common\Utils\DateFormatter;
use WPEventGenius\Common\Utils\EvgeDateTime;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class RecurrenceScheduler {
    protected $repository;
    
    public function __construct(EventSeriesRepository $repository) {
        $this->repository = $repository;
    }
    
    /**
     * Generate dates based on recurrence pattern
     */
    protected function generate_dates(RecurrencePattern $pattern) {
        $dates = array();
        
        // Create DateTime objects with local timezone
        $timezone = DateFormatter::timezone_object($pattern->get_timezone());
        $start_date = new \DateTime($pattern->get_start_date(), $timezone);
        $end_date = new \DateTime($pattern->get_end_date(), $timezone);
    
        // Validate date range
        if ($end_date < $start_date) {
            error_log('RecurrenceScheduler: Invalid date range - end date before start date');
            $end_date = clone $start_date;
        }
    
        $interval = $pattern->get_interval();
        
        $current_date = clone $start_date;

        // Log pattern details
        if ($this->is_debug_mode_enabled()) {
            \WPEventGenius\Common\Utils\Logger\RecurrenceLogger::log('Generating recurrence dates', [
                'type' => $pattern->get_type(),
                'interval' => $interval,
                'start_date' => $start_date->format('Y-m-d H:i:s'),
                'end_date' => $end_date->format('Y-m-d H:i:s'),
                'timezone' => $pattern->get_timezone()
            ]);
        }

        // Safeguards for recurrence patterns (max 10000 iterations for long-term recurring events)
        $max_iterations = 10000;
        $iteration_count = 0;
        $previous_date_str = null;

        while ($current_date <= $end_date && $iteration_count < $max_iterations) {
            $current_date_str = $current_date->format('Y-m-d');
            
            // Check if date advanced
            if ($previous_date_str === $current_date_str) {
                error_log('RecurrenceScheduler: Date not advancing in generate_dates() - breaking loop');
                break;
            }
            $previous_date_str = $current_date_str;
            // For weekday pattern, skip weekends
            if ($pattern->get_type() === 'weekday') {
                $day_of_week = (int)$current_date->format('N'); // 1 (Monday) to 7 (Sunday)
                if ($day_of_week <= 5) { // Only add weekdays (Monday-Friday)
                    $dates[] = clone $current_date;
                }
                // Always advance by 1 day for weekday pattern
                $current_date->modify('+1 day');
                continue;
            }

            // For other patterns
            $dates[] = clone $current_date;
            
            // Add interval based on pattern type
            switch ($pattern->get_type()) {
                case 'daily':
                    $current_date->modify("+{$interval} days");
                    break;
                case 'weekly':
                    $current_date->modify("+{$interval} weeks");
                    break;
                case 'monthly':
                    // Get the original day of month and day of week
                    $original_day = (int)$start_date->format('j');
                    $original_week = ceil($original_day / 7);
                    $original_day_of_week = (int)$start_date->format('N');
                    
                    // Move to next month
                    $current_date->modify("+{$interval} months");
                    
                    // Find the same occurrence (e.g., "third Monday")
                    $first_day = clone $current_date;
                    $first_day->modify('first day of this month');
                    
                    // Find first matching day of week (max 7 attempts for one week)
                    $max_attempts = 7;
                    $attempts = 0;
                    while ((int)$first_day->format('N') !== $original_day_of_week && $attempts < $max_attempts) {
                        $first_day->modify('+1 day');
                        $attempts++;
                    }
                    
                    // Log warning if limit hit
                    if ($attempts >= $max_attempts) {
                        error_log('RecurrenceScheduler: Hit iteration limit in generate_dates() day-of-week matching');
                    }
                    
                    // Move to correct week
                    $first_day->modify('+' . ($original_week - 1) . ' weeks');
                    
                    // If we've gone past the end of the month, use last occurrence instead
                    if ($first_day->format('m') !== $current_date->format('m')) {
                        $current_date->modify('last ' . $start_date->format('l') . ' of this month');
                    } else {
                        $current_date = clone $first_day;
                    }
                    break;
            }
            
            $iteration_count++;
        }

        // Log warning if limit hit
        if ($iteration_count >= $max_iterations) {
            error_log('RecurrenceScheduler: Hit iteration limit in generate_dates()');
        }

        // Log generated dates
        if ($this->is_debug_mode_enabled()) {
            \WPEventGenius\Common\Utils\Logger\RecurrenceLogger::log('Recurrence dates generated', [
                'total_dates' => count($dates),
                'first_date' => $dates[0]->format('Y-m-d H:i:s'),
                'last_date' => $dates[count($dates)-1]->format('Y-m-d H:i:s')
            ]);
        }
        
        return $dates;
    }
    
    /**
     * Generate a unique post name for a recurring event
     * 
     * @param string $template_title The template event's title
     * @param \DateTime $start_date The event's start date
     * @return string The generated post name
     */
    protected function generate_post_name($template_title, \DateTime $start_date) {
        // Format date based on locale
        $wp_date_format = get_option('date_format', 'Y-m-d');
        $date_suffix = '';
        
        // For URL purposes, we want to ensure the format is URL-friendly regardless of locale
        // First try to use WordPress date_i18n for localized format
        if (function_exists('date_i18n')) {
            // IMPORTANT: date_i18n() uses WordPress's timezone, not the event's timezone.
            // To preserve the event's date while still getting translations, we need to create
            // a timestamp that, when formatted in WordPress's timezone, produces the same date
            // as the event's date in its timezone.
            
            // Get the date components (Y-m-d) from the event's DateTime in its timezone
            $event_date_string = $start_date->format('Y-m-d');
            
            // Create a DateTime at midnight in WordPress's timezone with the same date
            $wp_timezone = wp_timezone();
            $wp_date_time = new \DateTime($event_date_string . ' 00:00:00', $wp_timezone);
            
            // Get the timestamp that will produce the correct date when formatted by date_i18n
            $timestamp_for_i18n = $wp_date_time->getTimestamp();
            
            // Get localized date in the site's date format (now will show correct date)
            $localized_date = date_i18n($wp_date_format, $timestamp_for_i18n);
            // Clean it up for URL use
            $date_suffix = sanitize_title($localized_date);
        }
        
        // Fallback to a URL-friendly format if the localized date doesn't produce a good slug
        if (empty($date_suffix) || strlen($date_suffix) < 3) {
            // Use a standard format that works well in URLs
            $date_suffix = $start_date->format('Y-m-d');
        }
        
        $post_name = sanitize_title($template_title . '-' . $date_suffix);
        
        // Ensure uniqueness by appending a number if needed
        $counter = 1;
        $original_post_name = $post_name;
        while ($this->post_name_exists($post_name)) {
            $post_name = $original_post_name . '-' . $counter;
            $counter++;
        }
        
        return $post_name;
    }

    /**
     * Check if a post name already exists
     * 
     * @param string $post_name The post name to check
     * @return bool Whether the post name exists
     */
    protected function post_name_exists($post_name) {
        global $wpdb;
        //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type = %s",
            $post_name,
            EVGE_EVENT_POST_TYPE
        ));
        return !empty($exists);
    }

    /**
     * Create a single recurring event
     */
    protected function create_recurring_event($template_id, \DateTime $start_date) {
        try {
            // Get template event's timezone
            $timezone = get_post_meta($template_id, 'evge_timezone', true);
            $timezone_obj = DateFormatter::timezone_object($timezone);
            
            // IMPORTANT: Ensure the start_date DateTime object has the correct timezone set.
            // This ensures the permalink date matches the event's start date in the correct timezone.
            $start_date->setTimezone($timezone_obj);
            
            // Get template event's duration
            $template_start = new \DateTime(get_post_meta($template_id, 'evge_start_date', true), $timezone_obj);
            $template_end = new \DateTime(get_post_meta($template_id, 'evge_end_date', true), $timezone_obj);
            $duration = $template_start->diff($template_end);
            
            // Get template title and create a meaningful slug with date
            $template_title = get_the_title($template_id);
            $post_name = $this->generate_post_name($template_title, $start_date);
            
            // Create the new event post
            $post_data = array(
                'post_type' => EVGE_EVENT_POST_TYPE,
                'post_status' => 'publish',
                'post_title' => $template_title,
                'post_name' => $post_name
            );
            
            $event_id = wp_insert_post($post_data);
            
            if (!$event_id || is_wp_error($event_id)) {
                throw new \Exception('Failed to create event post: ' . ($event_id instanceof \WP_Error ? $event_id->get_error_message() : 'Unknown error'));
            }
            
            // Copy all event data from template to new event
            EventCreate::copy_event($template_id, $event_id);
            
            // Calculate end date by adding the same duration
            $end_date = clone $start_date;
            $end_date->add($duration);
            
            // Set the new dates
            $start_date_obj = new EvgeDateTime($start_date);
            $end_date_obj = new EvgeDateTime($end_date);
            
            // Update date meta
            update_post_meta($event_id, 'evge_start_date', $start_date_obj->format('Y-m-d H:i:s'));
            update_post_meta($event_id, 'evge_end_date', $end_date_obj->format('Y-m-d H:i:s'));
            
            // Update UTC dates
            update_post_meta($event_id, 'evge_start_date_utc', gmdate('Y-m-d H:i:s', $start_date_obj->utc_timestamp()));
            update_post_meta($event_id, 'evge_end_date_utc', gmdate('Y-m-d H:i:s', $end_date_obj->utc_timestamp()));
            
            // Mark as recurrence
            update_post_meta($event_id, 'evge_is_recurrence', $template_id);
            
            // Sync with events table
            $event_create = new EventCreate(array());
            $event_create->sync_event_timing(
                $event_id,
                $start_date_obj,
                $end_date_obj,
                $timezone
            );

            // Fire action for recurrence created - allows syncing scheduled emails, etc.
            do_action('evge_recurrence_created', $event_id, $template_id);

            // Log successful event creation
            if ($this->is_debug_mode_enabled()) {
                \WPEventGenius\Common\Utils\Logger\RecurrenceLogger::log('Recurring event created', [
                    'event_id' => $event_id,
                    'template_id' => $template_id,
                    'start_date' => $start_date_obj->format('Y-m-d H:i:s'),
                    'end_date' => $end_date_obj->format('Y-m-d H:i:s'),
                    'timezone' => $timezone
                ]);
            }
            
            return $event_id;
        } catch (\Exception $e) {
            // Log event creation failure
            if ($this->is_debug_mode_enabled()) {
                \WPEventGenius\Common\Utils\Logger\RecurrenceLogger::log('Failed to create recurring event', [
                    'template_id' => $template_id,
                    'start_date' => $start_date->format('Y-m-d H:i:s'),
                    'error' => $e->getMessage()
                ]);
            }
            return false;
        }
    }

    public function get_recurrence_dates(RecurrencePattern $pattern) {
        return $this->generate_dates($pattern);
    }

	/**
	 * Create a batch of recurring events
	 * @param array $task
	 * @param int $offset
	 * @param int $batch_size
	 * @return int
	 */
    public function create_batch_of_events($task, $offset, $batch_size) {
        $dates = $this->generate_dates($task['data']['pattern']);
        $series_id = $task['data']['series_id'];
        $batch_dates = array_slice($dates, $offset, $batch_size);
        $created = 0;

        // Get template event's UTC start date for comparison
        $template_start = get_post_meta($task['data']['template_id'], 'evge_start_date', true);
  
        foreach ($batch_dates as $date) {
            // Skip if this date matches the template event's date
            if ($date->format('Y-m-d H:i:s') === $template_start) {
                // Count it as created since we're skipping it intentionally
                $created++;
                continue;
            }
     
            $event_id = $this->create_recurring_event($task['data']['template_id'], $date);
            if ($event_id) {
                $this->repository->add_relationship($series_id, $event_id);
                $created++;
            }

        }

        return $created;
    }

    public function update_batch_of_events($task, $offset, $batch_size) {
        $series_id = $task['data']['series_id'];
        $changes = $task['data']['changes'];
        
        // Get events in this batch
        $event_ids = $this->repository->get_series_events($series_id);
        $batch_ids = array_slice($event_ids, $offset, $batch_size);
        $updated = 0;

        foreach ($batch_ids as $event_id) {
            if ($event_id === $task['data']['template_id']) {
                $updated++;
                continue;
            }

            // Skip events that were added to the series via settings
            $added_via_setting = get_post_meta($event_id, 'evge_series_added_via_setting', true);
            if ($added_via_setting && (int)$added_via_setting === (int)$series_id) {
                $updated++;
                continue;
            }

            if ($this->update_recurring_event($event_id, $task['data']['template_id'], $changes)) {
                $updated++;
            }
        }

        return $updated;
    }

    protected function update_recurring_event($event_id, $template_id, $changes) {
        try {
            $timezone = get_post_meta($event_id, 'evge_timezone', true);
            $timezone_obj = DateFormatter::timezone_object($timezone);
            // Create DateTime objects with the event's timezone to ensure correct date calculations
            $start_date = new \DateTime(get_post_meta($event_id, 'evge_start_date', true), $timezone_obj);
            $end_date = new \DateTime(get_post_meta($event_id, 'evge_end_date', true), $timezone_obj);
            
            $original_start_date = clone $start_date;

            // Log update attempt
            if ($this->is_debug_mode_enabled()) {
                \WPEventGenius\Common\Utils\Logger\RecurrenceLogger::log('Updating recurring event', [
                    'event_id' => $event_id,
                    'template_id' => $template_id,
                    'changes' => $changes
                ]);
            }

            // Apply changes
            foreach ($changes as $key => $change) {
                switch ($key) {
                    case 'timezone':
                        $timezone = $change['to'];
                        $timezone_obj = DateFormatter::timezone_object($timezone);
                        update_post_meta($event_id, 'evge_timezone', $change['to']);
                        break;
                        
                    case 'all_day':
                        if ($change['to'] === 'enabled') {
                            $start_date->setTime(0, 0);
                            $end_date->setTime(23, 59);
                        }
                        update_post_meta($event_id, 'evge_all_day', $change['to']);
                        break;
                        
                    case 'start_date':
                        // Apply the same time difference to this event's start date
                        $start_date->modify(($change['difference'] >= 0 ? '+' : '') . $change['difference'] . ' seconds');
                        update_post_meta($event_id, 'evge_start_date', $start_date->format('Y-m-d H:i:s'));
                        break;

                    case 'end_date':
                        // Apply the same time difference to this event's end date
                        $end_date->modify(($change['difference'] >= 0 ? '+' : '') . $change['difference'] . ' seconds');
                        update_post_meta($event_id, 'evge_end_date', $end_date->format('Y-m-d H:i:s'));
                        break;
                }
            }

            // Check if the start date has changed to a different calendar day
            if ($start_date->format('Y-m-d') !== $original_start_date->format('Y-m-d')) {
                // Ensure the start_date has the correct timezone before generating post name
                $start_date->setTimezone($timezone_obj);
                // Update the post name to reflect the new date
                $template_title = get_the_title($template_id);
                $new_post_name = $this->generate_post_name($template_title, $start_date);
                
                // Update the post name
                wp_update_post(array(
                    'ID' => $event_id,
                    'post_name' => $new_post_name
                ));
            }
  
            // Update UTC dates
            $start_date_with_tz = new \DateTime($start_date->format('Y-m-d H:i:s'), $timezone_obj);
            $end_date_with_tz = new \DateTime($end_date->format('Y-m-d H:i:s'), $timezone_obj);
            
            $utc_timezone = new \DateTimeZone('UTC');
            $start_date_with_tz->setTimezone($utc_timezone);
            $end_date_with_tz->setTimezone($utc_timezone);

            update_post_meta($event_id, 'evge_start_date_utc', $start_date_with_tz->format('Y-m-d H:i:s'));
            update_post_meta($event_id, 'evge_end_date_utc', $end_date_with_tz->format('Y-m-d H:i:s'));

            // insert copying of event data from template to event
            EventCreate::copy_event($template_id, $event_id);

            // Update the event timing in the events table
            $event_create = new EventCreate(array());
            $event_create->sync_event_timing(
                $event_id,
                new EvgeDateTime($start_date_with_tz),
                new EvgeDateTime($end_date_with_tz),
                $timezone_obj->getName()
            );

            // Fire action for recurrence updated - allows syncing scheduled emails, etc.
            do_action('evge_recurrence_updated', $event_id, $template_id);

            // Log successful update
            if ($this->is_debug_mode_enabled()) {
                \WPEventGenius\Common\Utils\Logger\RecurrenceLogger::log('Recurring event updated', [
                    'event_id' => $event_id,
                    'template_id' => $template_id,
                    'new_start_date' => $start_date_with_tz->format('Y-m-d H:i:s'),
                    'new_end_date' => $end_date_with_tz->format('Y-m-d H:i:s'),
                    'timezone' => $timezone_obj->getName()
                ]);
            }

            return true;
        } catch (\Exception $e) {
            // Log update failure
            if ($this->is_debug_mode_enabled()) {
                \WPEventGenius\Common\Utils\Logger\RecurrenceLogger::log('Failed to update recurring event', [
                    'event_id' => $event_id,
                    'template_id' => $template_id,
                    'error' => $e->getMessage()
                ]);
            }
            return false;
        }
    }

    /**
     * Check if recurrence debug mode is enabled
     * 
     * @return bool True if debug mode is enabled
     */
    protected function is_debug_mode_enabled() {
        return (bool) get_transient('evge_recurrence_debug_mode');
    }
} 