<?php
namespace WPEventGenius\Common\Utils;

use WPEventGenius\Common\Database;
use WPEventGenius\Common\Registration\Payment\PaymentHandler;
use WPEventGenius\Common\Registration\Payment\Cart\Cart;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class Utils {


	public static function generate_key( $length = 'auto' ) {
		$key = sha1( uniqid( '', true ) );
		if ( $length === 'auto' ) {
			return $key;
		}

		return substr( $key, 0, $length );
	}

	/**
	 * Generate invoice number
	 * 
	 * @param int $id The ID to use in the invoice number
	 * @param \WPEventGenius\Common\Registration\Registration\RegistrationGroup|null $registration_group Optional registration group for filter context
	 * @param string $prefix Optional prefix (e.g., 'B-' for bulk orders)
	 * @return string The generated invoice number
	 */
	public static function generate_invoice_number( $id, $registration_group = null, $prefix = '' ) {
		$invoice_number = $prefix . wp_date( 'Y' ) . '-' . str_pad( $id, 5, '0', STR_PAD_LEFT );
		
		// Apply filter if registration group is provided
		if ( $registration_group !== null ) {
			$invoice_number = apply_filters( 'evge_invoice_id', $invoice_number, $registration_group );
		}
		
		return $invoice_number;
	}

	public static function get_action_url( $event ) {
		if ( Settings::get( 'action_url_type' ) === 'event' ) {
			return $event->get( 'event_link' );
		}
		return home_url();
	}

	public static function hydrate_meta_cache( $registration_data ) {

		if ( empty( $registration_data['meta_cache'] ) ) {
			return $registration_data;
		}
		if ( is_array( $registration_data['meta_cache'] ) ) {
			foreach ( $registration_data['meta_cache'] as $key => $value ) {
				$registration_data[ $key ] = $value;
			}
			unset( $registration_data['meta_cache'] );
		}

		if ( is_string( $registration_data['meta_cache'] ) ) {
			$meta_cache = json_decode( $registration_data['meta_cache'], true );

			foreach ( $meta_cache as $key => $value ) {
				$registration_data[ $key ] = $value;
			}
			unset( $registration_data['meta_cache'] );
		}

		return $registration_data;
	}

	public static function get_payment_columns() {
		return array(
			'payment_date' => __( 'Date', 'event-genius' ),
			'payment_status' => __( 'Status', 'event-genius' ),
			'payment_gross' => __( 'Payment Amount', 'event-genius' ),
			'invoice_id' => __( 'Invoice #', 'event-genius' ),
			'payment_id' => __( 'Payment ID', 'event-genius' ),
			'gateway' => __( 'Gateway', 'event-genius' ),
			'business' => __( 'Business', 'event-genius' ),
			'currency_code' => __( 'Currency Code', 'event-genius' ),
		);
	}

	public static function get_attendance_columns() {
		return array(
			'checked_in_at' => __( 'Check In Date', 'event-genius' ),
			'attendance_status' => __( 'Status', 'event-genius' ),
			'attendance_quantity' => __( 'Quantity', 'event-genius' ),
			'checked_in_by' => __( 'Checked In By', 'event-genius' ),
		);
	}

	public static function hours_between_dates( $start, $end, $timezone ) {
		$start_date_time = new EvgeDateTime( new \DateTime( $start, DateFormatter::timezone_object( $timezone ) ) );
		$end_date_time = new EvgeDateTime( new \DateTime( $end, DateFormatter::timezone_object( $timezone ) ) );

		$seconds_between = $end_date_time->timestamp() - $start_date_time->timestamp();

		return $seconds_between / 3600;

	}

	public static function parse_iframe_src( $input ) {
		$url = '';
		if ( strpos( $input, '<iframe' ) !== false ) {
			preg_match('/src\s*=\s*["\']([^"\']+)["\']/', $input, $match);
			
			if ( ! empty( $match[1] ) ) {
				$url = $match[1];

			} else {
				// Try alternative pattern if first one fails
				preg_match('/src\s*=\s*([^>\s]+)/', $input, $match);
				if ( ! empty( $match[1] ) ) {
					$url = $match[1];
				}
			}
		} else {
			$url = $input;
		}

		return $url;
	}

	/**
	 * Format event information for display in dropdowns and lists
	 *
	 * @param int $event_id The event ID
	 * @param bool $bold_title Whether to wrap the title in <strong> tags
	 * @return string Formatted event string (Title - Date - Venue)
	 */
	public static function format_event_display( $event_id, $bold_title = false ) {
		$event_post = new \WPEventGenius\Common\Event\EventPost( $event_id );
		
		$title = get_the_title( $event_id );
		if ( $bold_title ) {
			$title = '<strong>' . esc_html( $title ) . '</strong>';
		} else {
			$title = esc_html( $title );
		}
		
		$venue_name = $event_post->get_the_venue_title() ?: __( 'No venue', 'event-genius' );
		$event_date = $event_post->get_the_date_summary( 'brief' );
		
		return sprintf(
			'%s - %s - %s',
			$title,
			esc_html( $event_date ),
			esc_html( $venue_name )
		);
	}

	/**
	 * Check if an event is visible to the current user
	 * 
	 * @param int $event_id The event ID to check
	 * @return bool True if the event is visible to the current user, false otherwise
	 */
	public static function is_event_visible_to_user( $event_id ) {

		// Check if event exists and is the correct post type
		if ( empty( $event_id ) || get_post_type( $event_id ) !== EVGE_EVENT_POST_TYPE ) {
			return false;
		}
		
		// Check if event is published or otherwise visible to the user
		$event_post_status = get_post_status( $event_id );
		
		// Event is visible if it's published
		if ( 'publish' === $event_post_status ) {
			return true;
		}
		
		// Event is visible if user can edit events and event is in a draft/pending state
		if ( current_user_can( 'edit_evge_events' ) && in_array( $event_post_status, array( 'draft', 'pending', 'private' ), true ) ) {
			return true;
		}
		
		// Event is visible if user can read private events and event is private
		if ( current_user_can( 'read_private_evge_events' ) && 'private' === $event_post_status ) {
			return true;
		}
		
		return false;
	}

	/**
	 * Get error message for non-visible events (admin only)
	 * 
	 * @param string $context The context of the error (e.g., 'form_display', 'form_submission', 'calendar_display', 'attendee_list')
	 * @return string Error message or empty string for non-admins
	 */
	public static function get_event_visibility_error_message( $context = 'form_display' ) {
		if ( ! current_user_can( 'edit_evge_events' ) ) {
			return '';
		}
		
		$messages = array(
			'form_display' => __( 'Error: Only visible to admins. The event is not published or you do not have permission to view it.', 'event-genius' ),
			'form_submission' => __( 'Error: Only visible to admins. Form submission failed because the event is not published or you do not have permission to access it.', 'event-genius' ),
			'calendar_display' => __( 'Error: Only visible to admins. The calendar contains events that are not published or you do not have permission to view them.', 'event-genius' ),
			'event_details' => __( 'Error: Only visible to admins. Event details are not available because the event is not published or you do not have permission to access it.', 'event-genius' ),
			'attendee_list' => __( 'Error: Only visible to admins. The attendee list is not available because the event is not published or you do not have permission to access it.', 'event-genius' ),
			'load_more' => __( 'Error: Only visible to admins. Cannot load more attendees because the event is not published or you do not have permission to access it.', 'event-genius' ),
		);
		
		return '<div class="evge-warning-message">' . esc_html( $messages[ $context ] ?? $messages['form_display'] ) . '</div>';
	}

	public static function get_attendee_list_not_available_message( $event_id ) {
		// include link to edit. target blank.
		return '<div class="evge-warning-message">' . esc_html__( 'The attendee list is disabled for this event. Edit the event post to change this.' , 'event-genius' ) . '</div>';
	}
	/**
	 * Build a complete social media URL from various input formats
	 * 
	 * Detects whether input is a full URL, partial URL, or just a username/screenname
	 * and generates the appropriate social media URL.
	 * 
	 * @param string $platform The social media platform (facebook, twitter, instagram, linkedin, youtube)
	 * @param string $input The input value (URL, partial URL, or username)
	 * @return string The complete social media URL
	 */
	public static function build_social_media_url( $platform, $input ) {
		if ( empty( $input ) ) {
			return '';
		}

		// Clean the input
		$input = trim( $input );

		// Check if it's already a complete URL
		if ( filter_var( $input, FILTER_VALIDATE_URL ) ) {
			return $input;
		}

		// Define social media URL patterns
		$platform_urls = array(
			'facebook' => array(
				'base_url' => 'https://www.facebook.com/',
				'patterns' => array(
					'/^facebook\.com\/(.+)$/i',
					'/^www\.facebook\.com\/(.+)$/i',
					'/^fb\.com\/(.+)$/i',
					'/^www\.fb\.com\/(.+)$/i'
				)
			),
			'twitter' => array(
				'base_url' => 'https://twitter.com/',
				'patterns' => array(
					'/^twitter\.com\/(.+)$/i',
					'/^www\.twitter\.com\/(.+)$/i',
					'/^x\.com\/(.+)$/i',
					'/^www\.x\.com\/(.+)$/i'
				)
			),
			'instagram' => array(
				'base_url' => 'https://www.instagram.com/',
				'patterns' => array(
					'/^instagram\.com\/(.+)$/i',
					'/^www\.instagram\.com\/(.+)$/i',
					'/^ig\.com\/(.+)$/i',
					'/^www\.ig\.com\/(.+)$/i'
				)
			),
			'linkedin' => array(
				'base_url' => 'https://www.linkedin.com/',
				'patterns' => array(
					'/^linkedin\.com\/(.+)$/i',
					'/^www\.linkedin\.com\/(.+)$/i'
				)
			),
			'youtube' => array(
				'base_url' => 'https://www.youtube.com/',
				'patterns' => array(
					'/^youtube\.com\/(.+)$/i',
					'/^www\.youtube\.com\/(.+)$/i',
					'/^youtu\.be\/(.+)$/i'
				)
			)
		);

		// Check if platform is supported
		if ( ! isset( $platform_urls[ $platform ] ) ) {
			return $input;
		}

		$platform_config = $platform_urls[ $platform ];

		// Check if input matches any partial URL patterns
		foreach ( $platform_config['patterns'] as $pattern ) {
			if ( preg_match( $pattern, $input, $matches ) ) {
				// Extract the username/path from the partial URL
				$username = $matches[1];
				// Remove any query parameters or fragments
				$username = preg_replace( '/[?#].*$/', '', $username );
				return $platform_config['base_url'] . $username;
			}
		}

		// If no patterns match, treat as username/screenname
		// Remove any @ symbols and clean the username
		$username = ltrim( $input, '@' );
		$username = preg_replace( '/[^a-zA-Z0-9._-]/', '', $username );

		// Special handling for LinkedIn (can be company pages or profiles)
		if ( $platform === 'linkedin' ) {
			// If it looks like a company page (starts with company/), keep as is
			if ( strpos( $username, 'company/' ) === 0 ) {
				return $platform_config['base_url'] . $username;
			}
			// Otherwise, assume it's a profile
			return $platform_config['base_url'] . 'in/' . $username;
		}

		// Special handling for YouTube (can be channels or users)
		if ( $platform === 'youtube' ) {
			// If it looks like a channel ID (starts with UC_), use channel format
			if ( preg_match( '/^UC[a-zA-Z0-9_-]+$/', $username ) ) {
				return $platform_config['base_url'] . 'channel/' . $username;
			}

			// Otherwise, assume it's a channel name
			return $platform_config['base_url'] . $username;
		}

		// For other platforms, just append the username
		return $platform_config['base_url'] . $username;
	}

	/**
	 * Get a complete registration group from any registration ID
	 * 
	 * This method will:
	 * - If the ID is a main registration (parent = 0), return the group with main + all guests
	 * - If the ID is a guest registration (parent > 0), find the main registration and return the complete group
	 * 
	 * @param int $registration_id The registration ID (main or guest)
	 * @return \WPEventGenius\Common\Registration\Registration\RegistrationGroup|null The complete registration group or null if not found
	 * 
	 * @since 2.21
	 */
	public static function get_registration_group_from_id( $registration_id ) {
		$database = new \WPEventGenius\Common\Database();
		
		// First, get the registration to determine if it's main or guest
		$where = array(
			array(
				'column' => 'id',
				'value'  => $registration_id,
				'compare' => '=',
				'type' => 'int'
			)
		);
		$registrations = $database->registration_query( $where );
		
		if ( empty( $registrations ) ) {
			return null;
		}
		
		$registration_data = $registrations[0];
		$parent_id = isset( $registration_data['parent'] ) ? (int) $registration_data['parent'] : 0;
		
		// Determine the main registration ID
		$main_registration_id = $parent_id > 0 ? $parent_id : $registration_id;
		
		// Create the registration group
		$registration_group = new \WPEventGenius\Common\Registration\Registration\RegistrationGroup( $database );
		$registration_group->set_from_existing( $main_registration_id );
		
		return $registration_group;
	}

	public static function maybe_add_group_data( $registration, $event_post ) {
		// Return early if not in standard tier or higher
		if ( ! function_exists( 'evge_is_standard_tier' ) || ! evge_is_standard_tier() ) {
			return $registration;
		}

		$parent_id = isset( $registration['parent'] ) ? intval( $registration['parent'] ) : 0;
		if ( $parent_id > 0 ) {
			return $registration;
		}

		$additional_guest_type = $event_post->get_form()->get_guest_registration_type();
		if ( $additional_guest_type === 'none' || $additional_guest_type === 'simple_count' ) {
			if ( isset( $registration['payment_gross'] ) ) {
				return $registration;
			}
		} else {
			$registration['is_main_registration'] = true;
		}

		$registration_id = isset( $registration['id'] ) ? intval( $registration['id'] ) : 0;
		if ( $registration_id > 0 ) {
			try {
				// Create admin registration group from the main registration ID to allow canceled registrations
				// Use Pro AdminRegistrationGroup if Pro is active and available, otherwise fall back to regular RegistrationGroup
				if ( function_exists( 'evge_is_free_version' ) && ! evge_is_free_version() && class_exists( 'WPEventGenius\Pro\Registration\Registration\AdminRegistrationGroup' ) ) {
					$registration_group = new \WPEventGenius\Pro\Registration\Registration\AdminRegistrationGroup( new Database() );
				} else {
					$registration_group = new \WPEventGenius\Common\Registration\Registration\RegistrationGroup( new Database() );
				}
				$registration_group->set_from_existing( $registration_id );
				if ( ! $registration_group ) {
					if ( empty( $registration['cost'] ) ) {
						return '-';
					}
				}
				if ( empty( $registration_group->payment_handler() ) ) {
					$registration_group->build_payment_handler( new \WPEventGenius\Common\Registration\Payment\PaymentHandler( new \WPEventGenius\Common\Registration\Payment\Cart\Cart() ) );
				}
				
				// Get the additional guest count
				$registration['quantity'] = $registration_group->get_total_registration_count();

				if ( empty( $registration['payment_gross'] )) {
				    $registration['payment_gross'] = $registration_group->payment_handler()->calculate_total();
				} else {
					$registration['payment_gross'] = $registration['payment_gross'];
				}
				
			} catch ( Exception $e ) {
				return $registration;
			}
		}
		
		return $registration;
	}
}