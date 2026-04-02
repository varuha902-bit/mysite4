<?php

namespace WPEventGenius\Common\Calendars;

use DateTime;
use DateTimeZone;
use WPEventGenius\Common\Utils\DateFormatter;
use WPEventGenius\Common\Utils\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class CalendarBuilder {
    protected $events;
    protected $current_month;
    protected $timezone;
    protected $first_day_of_week;
    protected $event_positions = [];
    protected $first_occurrence_positions = [];
    protected $continuing_events = []; // Store events that continue into future weeks
    const SINGLE_DAY_EVENT_POSITION = 9999; // High enough to sort after multi-day events

    public function __construct($events, $current_month, $timezone) {
        $this->events = $events;
        $this->current_month = $current_month;
        $this->timezone = DateFormatter::timezone_object( $timezone );
        $start_of_week = Settings::get( 'start_of_the_week' );
        $this->first_day_of_week = DateFormatter::get_weekday_number($start_of_week);
    }
	/**
	 * Get current month
	 *
	 * @return string Current month in Y-m format
	 */
	public function get_current_month() {
		return $this->current_month;
	}

    public function get_events() {
        return $this->events;
    }

    /**
     * Get formatted calendar data
     *
     * @return array Calendar data organized by weeks
     */
    public function get_calendar_data() {
        $start_date = new \DateTimeImmutable($this->current_month . '-01', $this->timezone);
        $end_date = new \DateTimeImmutable($this->current_month . '-' . $start_date->format('t'), $this->timezone);

        // Get the first day to display (last Sunday/Monday of previous month if needed)
        $first_display = $start_date;
        $current_weekday = (int)$start_date->format('w');
        $days_to_subtract = ($current_weekday - $this->first_day_of_week + 7) % 7;
        if ($days_to_subtract > 0) {
            $first_display = $start_date->modify('-' . $days_to_subtract . ' days');
        }

        // Get the last day to display (first Saturday/Sunday of next month if needed)
        $last_display = $end_date;
        $current_weekday = (int)$end_date->format('w');
        $days_to_add = (($this->first_day_of_week + 6) % 7) - $current_weekday;
        if ($days_to_add < 0) {
            $days_to_add += 7;
        }
        if ($days_to_add > 0) {
            $last_display = $end_date->modify('+' . $days_to_add . ' days');
        }

        // Validate date range before looping
        if ($last_display < $first_display) {
            error_log('CalendarBuilder: Invalid date range - end date before start date');
            $last_display = clone $first_display;
        }

        // Organize events by date FIRST
        $events_by_date = $this->organize_events();
        // Build weeks array
        $weeks = [];
        $current_week = [];
        $current_date = $first_display;
        $week_start = null;

        // Safeguards for calendar month view (max 60 iterations for 6-7 weeks)
        $max_iterations = 60;
        $iteration_count = 0;
        $previous_date_str = null;

        while ($current_date <= $last_display && $iteration_count < $max_iterations) {
            $date_key = $current_date->format('Y-m-d');
            
            // Check if date advanced
            if ($previous_date_str === $date_key) {
                error_log('CalendarBuilder: Date not advancing in get_calendar_data() - breaking loop');
                break;
            }
            $previous_date_str = $date_key;
            
            // Get events for this date from our organized array
            $day_events = isset($events_by_date[$date_key]) ? $events_by_date[$date_key] : [];

            $day_data = [
                'date' => $date_key,
                'day_number' => $current_date->format('j'),
                'is_other_month' => $current_date->format('m') !== $start_date->format('m'),
                'is_today' => $date_key === (new DateTime('now', $this->timezone))->format('Y-m-d'),
                'events' => $day_events, // Add the events for this day
            ];

            $current_week[] = $day_data;

            // Start new week if we've reached the last day of the week
            $current_weekday = (int)$current_date->format('w');
            if ($current_weekday === (($this->first_day_of_week + 6) % 7)) {
                $weeks[] = $current_week;
                $current_week = [];
            }

            $current_date = $current_date->modify('+1 day');
            $iteration_count++;
        }

        // Log warning if limit hit
        if ($iteration_count >= $max_iterations) {
            error_log('CalendarBuilder: Hit iteration limit in get_calendar_data()');
        }

        // Add any remaining days as the last week
        if (!empty($current_week)) {
            $weeks[] = $current_week;
        }

        return [
            'weeks' => $weeks,
            'month_info' => [
                'year' => $start_date->format('Y'),
                'month' => $start_date->format('m'),
                'month_name' => wp_date( 'F', $start_date->getTimestamp() ),
            ]
        ];
    }

    /**
     * Get formatted date for display (localized via WordPress language)
     *
     * @return string
     */
    public function get_formatted_date() {
        $date = new DateTime($this->current_month . '-01', $this->timezone);
        return wp_date( 'F Y', $date->getTimestamp() );
    }

    /**
     * Organize events by date
     *
     * @return array Events organized by date
     */
    protected function organize_events() {
        $events_by_date = [];
        $this->event_positions = [];
        $this->first_occurrence_positions = [];

        // First, sort all events by start date
        $sorted_events = $this->events;
        usort($sorted_events, function($a, $b) {
            $a_time = strtotime($a->get_the_start_date());
            $b_time = strtotime($b->get_the_start_date());
            if ($a_time === $b_time) {
                return strcmp($a->get_the_title(), $b->get_the_title());
            }
            return $a_time - $b_time;
        });

        // First pass: Process all events and assign to days
        foreach ($sorted_events as $event) {
            $start_date = new DateTime($event->get_the_start_date_raw(), $this->timezone);
            $end_date = new DateTime($event->get_the_end_date_raw(), $this->timezone);
            
            // Validate date range
            if ($end_date < $start_date) {
                error_log('CalendarBuilder: Invalid event date range - end date before start date for event ID: ' . $event->get_the_id());
                $end_date = clone $start_date;
            }
            
            $current_date = clone $start_date;
            $end_date_str = $end_date->format('Y-m-d');
            
            // Safeguards for event date ranges (max 1000 iterations for ~2.7 years of daily events)
            $max_iterations = 1000;
            $iteration_count = 0;
            $previous_date_str = null;
            
            while ($current_date->format('Y-m-d') <= $end_date_str && $iteration_count < $max_iterations) {
                $date_key = $current_date->format('Y-m-d');
                
                // Check if date advanced
                if ($previous_date_str === $date_key) {
                    error_log('CalendarBuilder: Date not advancing in organize_events() date iteration - breaking loop for event ID: ' . $event->get_the_id());
                    break;
                }
                $previous_date_str = $date_key;
                
                if (!isset($events_by_date[$date_key])) {
                    $events_by_date[$date_key] = [];
                }
                $events_by_date[$date_key][] = $event;
                $current_date->modify('+1 day');
                $iteration_count++;
            }
            
            // Log warning if limit hit
            if ($iteration_count >= $max_iterations) {
                error_log('CalendarBuilder: Hit iteration limit in organize_events() date iteration for event ID: ' . $event->get_the_id());
            }
        }

        // Second pass: Assign positions based on first occurrence
        foreach ($events_by_date as $date_key => $day_events) {
            // Get the week number for this date
            $date = new DateTime($date_key, $this->timezone);
            $week_number = $date->format('W');

            // Sort events for this day by start date and title
            usort($day_events, function($a, $b) {
                $a_time = strtotime($a->get_the_start_date_raw());
                $b_time = strtotime($b->get_the_start_date_raw());
                if ($a_time === $b_time) {
                    return strcmp($a->get_the_title(), $b->get_the_title());
                }
                return $a_time - $b_time;
            });

            // Assign positions for this day
            $used_positions = [];
            foreach ($day_events as $event) {
                $event_id = $event->get_the_id();

                // If this is a multi-day event and we haven't assigned a position yet
                if (!isset($this->event_positions[$week_number][$event_id])) {
                    // Find the first available position
                    $position = 1;
                    $max_attempts = 1000; // Reasonable for event positioning
                    $attempts = 0;
                    while (isset($used_positions[$position]) && $attempts < $max_attempts) {
                        $position++;
                        $attempts++;
                    }
                    
                    // Log warning if limit hit
                    if ($attempts >= $max_attempts) {
                        error_log('CalendarBuilder: Hit iteration limit in organize_events() position finding for event ID: ' . $event_id);
                    }
                    
                    // Assign this position to the event
                    $this->event_positions[$week_number][$event_id] = $position;
                    $used_positions[$position] = true;
                    
                    // Store the first occurrence position
                    $this->first_occurrence_positions[$event_id] = [
                        'date' => $date_key,
                        'position' => $position
                    ];
                } else {
                    // For multi-day events, use their assigned position
                    $used_positions[$this->event_positions[$week_number][$event_id]] = true;
                }
            }
        }

        // Final pass: Sort events for each day based on assigned positions
        foreach ($events_by_date as &$day_events) {
            usort($day_events, function($a, $b) {
                $date = new DateTime($a->get_the_start_date_raw(), $this->timezone);
                $week_number = $date->format('W');
                
                $a_position = isset($this->event_positions[$week_number][$a->get_the_id()]) ? 
                    $this->event_positions[$week_number][$a->get_the_id()] : 
                    self::SINGLE_DAY_EVENT_POSITION;
                $b_position = isset($this->event_positions[$week_number][$b->get_the_id()]) ? 
                    $this->event_positions[$week_number][$b->get_the_id()] : 
                    self::SINGLE_DAY_EVENT_POSITION;

                // If both events have positions, sort by position
                if ($a_position !== self::SINGLE_DAY_EVENT_POSITION && 
                    $b_position !== self::SINGLE_DAY_EVENT_POSITION) {
                    return $a_position - $b_position;
                }

                // If only one has a position, it comes first
                if ($a_position !== self::SINGLE_DAY_EVENT_POSITION) {
                    return -1;
                }
                if ($b_position !== self::SINGLE_DAY_EVENT_POSITION) {
                    return 1;
                }

                // For single-day events, sort by start time and title
                $a_time = strtotime($a->get_the_start_date());
                $b_time = strtotime($b->get_the_start_date());
                if ($a_time === $b_time) {
                    return strcmp($a->get_the_title(), $b->get_the_title());
                }
                return $a_time - $b_time;
            });
        }

        return $events_by_date;
    }

    /**
     * Get previous month's date
     *
     * @return string Date in Y-m format
     */
    public function get_prev_month() {
        $date = new DateTime($this->current_month . '-01', $this->timezone);
        $date->modify('-1 month');
        return $date->format('Y-m');
    }

    /**
     * Get next month's date
     *
     * @return string Date in Y-m format
     */
    public function get_next_month() {
        $date = new DateTime($this->current_month . '-01', $this->timezone);
        $date->modify('+1 month');
        return $date->format('Y-m');
    }
} 