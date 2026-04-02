<?php


namespace WPEventGenius\Common\Utils\EventExport;

use WPEventGenius_Vendor\Spatie\IcalendarGenerator\Components\Calendar;
use WPEventGenius_Vendor\Spatie\IcalendarGenerator\Components\Event;
use WPEventGenius\Common\Event\EventPost;
use WPEventGenius\Common\Event\OrganizerPost;
use WPEventGenius\Common\Event\VenuePost;
use WPEventGenius\Common\Utils\DateFormatter;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class iCal {

	protected $events;

	protected $context;
	public function __construct( $context ) {
		$this->events = [];

		$this->context = $context;
	}

	private function get_safe_filename() {
		if (empty($this->events)) {
			return 'calendar.ics';
		}

		try {
			$event = reset($this->events);
			if ( ! isset( $event->ID ) || empty( $event->ID ) ) {
				return 'calendar.ics';
			}

			$event_post = new EventPost($event->ID);
			
			// Get the event title and start date
			$title = $event_post->get_the_title();
			$start_date = $event_post->get_the_start_date();
			
			// Create a safe filename by:
			// 1. Converting to lowercase
			// 2. Replacing spaces with hyphens
			// 3. Removing any non-alphanumeric characters except hyphens
			// 4. Limiting length to 50 characters
			$safe_title = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $title)));
			$safe_title = substr($safe_title, 0, 50);
			
			// Format the date as YYYY-MM-DD
			$timestamp = strtotime($start_date);
			if ( $timestamp === false ) {
				$date = date('Y-m-d');
			} else {
				$date = date('Y-m-d', $timestamp);
			}
			
			// Add series suffix if this is a series export
			if ( $this->context === 'series' ) {
				return sprintf('%s-series-%s.ics', $safe_title, $date);
			}
			
			return sprintf('%s-%s.ics', $safe_title, $date);
		} catch ( \Exception $e ) {
			// Fallback to default filename if anything goes wrong
			error_log( 'Event Genius iCal: Error generating filename: ' . $e->getMessage() );
			return 'calendar.ics';
		}
	}

	public function set_events( $events ) {
		$this->events = $events;
	}

	public function get_export_content() {
		$list_description = sprintf( 
            /* translators: %s: website name */
            __( 'Events for %s', 'event-genius' ), 
            get_bloginfo( 'name' ) 
        );
		$list = Calendar::create()->name(get_bloginfo( 'name' ))->description($list_description);

		$events_added = 0;
		$skipped_events = array();

		foreach ( $this->events as $event ) {
			if ( ! isset( $event->ID ) || empty( $event->ID ) ) {
				$skipped_events[] = 'Invalid event object (no ID)';
				continue;
			}

			try {
				$event_post = new EventPost( $event->ID );
				
				// Get event title - required for iCal
				// Decode HTML entities (e.g., &#038; becomes &)
				$event_title = html_entity_decode( $event_post->get_the_title(), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
				if ( empty( $event_title ) ) {
					error_log( sprintf( 'Event Genius iCal: Event ID %d has no title', $event->ID ) );
					$skipped_events[] = sprintf( 'Event ID %d: No title', $event->ID );
					continue;
				}
				
				// Validate event has required date data
				$start_date_raw = $event_post->get_the_start_date_raw();
				$end_date_raw = $event_post->get_the_end_date_raw();
				
				if ( empty( $start_date_raw ) || empty( $end_date_raw ) ) {
					error_log( sprintf( 'Event Genius iCal: Event ID %d missing start or end date', $event->ID ) );
					$skipped_events[] = sprintf( 'Event ID %d: Missing dates', $event->ID );
					continue;
				}

				$timezone = $event_post->get_the_timezone();
				$timezone_obj = DateFormatter::timezone_object( $timezone );
				
				// Validate timezone object
				if ( ! $timezone_obj instanceof \DateTimeZone ) {
					error_log( sprintf( 'Event Genius iCal: Invalid timezone for Event ID %d: %s', $event->ID, $timezone ) );
					$timezone_obj = new \DateTimeZone( 'UTC' );
				}

				// Create DateTime objects with error handling
				try {
					$start_date = new \DateTime( $start_date_raw, $timezone_obj );
				} catch ( \Exception $e ) {
					error_log( sprintf( 'Event Genius iCal: Invalid start date for Event ID %d: %s - %s', $event->ID, $start_date_raw, $e->getMessage() ) );
					$skipped_events[] = sprintf( 'Event ID %d: Invalid start date (%s)', $event->ID, $start_date_raw );
					continue;
				}

				try {
					$end_date = new \DateTime( $end_date_raw, $timezone_obj );
				} catch ( \Exception $e ) {
					error_log( sprintf( 'Event Genius iCal: Invalid end date for Event ID %d: %s - %s', $event->ID, $end_date_raw, $e->getMessage() ) );
					$skipped_events[] = sprintf( 'Event ID %d: Invalid end date (%s)', $event->ID, $end_date_raw );
					continue;
				}

				// Validate end date is after start date
				if ( $end_date <= $start_date ) {
					error_log( sprintf( 'Event Genius iCal: Event ID %d has end date before or equal to start date', $event->ID ) );
					$skipped_events[] = sprintf( 'Event ID %d: End date before start date', $event->ID );
					continue;
				}

				$uid = $event->ID . '-' . $start_date->getTimestamp() . '-' . $end_date->getTimestamp() . '@' . wp_parse_url( home_url( '/' ), PHP_URL_HOST );

				// Check if this is an all-day event
				$is_all_day = $event_post->is_all_day();

				// Decode HTML entities in description as well
				$event_description = html_entity_decode( $event_post->get_the_summary( 1000, false ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

				$ical_event = Event::create()
				                   ->name( $event_title )
				                   ->description( $event_description )
				                   ->uniqueIdentifier( $uid )
				                   ->startsAt( $start_date )
				                   ->endsAt( $end_date );

				// Add event permalink as URL
				$event_permalink = $event_post->get_the_permalink();
				if ( ! empty( $event_permalink ) ) {
					$ical_event->url( $event_permalink );
				}

				// Preserve timezone information (unless it's UTC or all-day)
				if ( ! $is_all_day && $timezone_obj->getName() !== 'UTC' ) {
					$ical_event->withTimezone();
				}

				// Mark as all-day event if applicable
				if ( $is_all_day ) {
					$ical_event->fullDay();
				}

				if ( ! empty( $event_post->get_the_venue_id() ) ) {
					try {
						$venue_post = new VenuePost( $event_post->get_the_venue_id() );
						$venue_name = html_entity_decode( $venue_post->get_the_title(), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
						$venue_address = html_entity_decode( $venue_post->get_the_full_address(), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
						if ( ! empty( $venue_name ) ) {
							$ical_event->addressName( $venue_name );
						}
						if ( ! empty( $venue_address ) ) {
							$ical_event->address( $venue_address );
						}
					} catch ( \Exception $e ) {
						error_log( sprintf( 'Event Genius iCal: Error adding venue for Event ID %d: %s', $event->ID, $e->getMessage() ) );
					}
				}

				if ( ! empty( $event_post->get_the_organizer_id() ) ) {
					try {
						$organizer_post = new OrganizerPost( $event_post->get_the_organizer_id() );
						$organizer_email = $organizer_post->get_the_email();
						$organizer_name = html_entity_decode( $organizer_post->get_the_title(), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
						if ( ! empty( $organizer_email ) ) {
							$ical_event->organizer( $organizer_email, $organizer_name );
						}
					} catch ( \Exception $e ) {
						error_log( sprintf( 'Event Genius iCal: Error adding organizer for Event ID %d: %s', $event->ID, $e->getMessage() ) );
					}
				}

				$list->event($ical_event);
				$events_added++;
			} catch ( \Exception $e ) {
				error_log( sprintf( 'Event Genius iCal: Error processing Event ID %d: %s', $event->ID, $e->getMessage() ) );
				$skipped_events[] = sprintf( 'Event ID %d: %s', $event->ID, $e->getMessage() );
				// Continue with next event instead of failing completely
				continue;
			}
		}

		// Ensure at least one event was added
		if ( $events_added === 0 ) {
			$error_msg = sprintf(
				'No valid events could be added to iCal export. Skipped events: %s',
				implode( ', ', $skipped_events )
			);
			error_log( 'Event Genius iCal: ' . $error_msg );
			throw new \Exception( $error_msg );
		}

		return $list->get();
	}

	public function export() {
		try {
			// Get the iCal content before setting headers
			$content = $this->get_export_content();
			
			// Check if we have any content
			if ( empty( $content ) ) {
				throw new \Exception( 'Generated iCal content is empty' );
			}
			
			// Ensure proper iCal line endings (\r\n)
			// First normalize all line breaks, then ensure they're \r\n
			$content = str_replace(array("\r\n", "\r"), "\n", $content);
			$content = str_replace("\n", "\r\n", $content);
			
			// Set headers BEFORE any output
			header('Content-Type: text/calendar; charset=utf-8');
			header('Content-Disposition: attachment; filename="' . $this->get_safe_filename() . '"');
			// do not cache the file
			header('Cache-Control: no-cache, no-store, must-revalidate');
			header('Pragma: no-cache');
			header('Expires: 0');
			
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $content;

			die();
		} catch ( \Exception $e ) {
			// Log the error
			$error_message = sprintf(
				/* translators: %s: Error message */
				__( 'iCal export failed: %s', 'event-genius' ),
				$e->getMessage()
			);
			
			error_log( 'Event Genius iCal Export Error: ' . $error_message );
			error_log( 'Stack trace: ' . $e->getTraceAsString() );
			
			// Log to DebugLogger if available
			if ( class_exists( '\WPEventGenius\Common\Utils\Logger\DebugLogger' ) ) {
				\WPEventGenius\Common\Utils\Logger\DebugLogger::log(
					$error_message,
					array(
						'context' => $this->context,
						'exception' => $e->getMessage(),
						'trace' => $e->getTraceAsString(),
					)
				);
			}
			
			// Re-throw to be caught by the calling method
			throw $e;
		}
	}
}