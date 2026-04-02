<?php
/**
 * Object to store defaults
 *
 * @since 2.21
 */
namespace WPEventGenius\Common\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class Defaults {

	/**
	 * All default values in key value pairs
	 *
	 * @return string[]
	 *
	 * @since 2.21
	 */
	public static function all() {
		$defaults = array();

		$defaults = array_merge(
			$defaults,
			self::general_defaults(),
			self::text_defaults(),
			self::email_defaults(),
		);

		// Add Pro defaults if Pro version is active
		if ( ! evge_is_free_version() && class_exists( 'WPEventGenius\Pro\Utils\ProDefaults' ) ) {
			$pro_defaults = \WPEventGenius\Pro\Utils\ProDefaults::all();
			$defaults = array_merge( $defaults, $pro_defaults );
		}

		return $defaults;
	}

	/**
	 * Return a single default value by key.
	 *
	 * @param string $key
	 *
	 * @return string|null
	 *
	 * @since 2.21
	 */
	public static function get( $key ) {
		$defaults = self::all();

		if ( isset( $defaults[ $key ] ) ) {
			if ( in_array( $key, Defaults::json_settings(), true ) ) {
				return json_decode( $defaults[ $key ], true );
			}
			return $defaults[ $key ];
		}

		return null;
	}

	public static function general_defaults() {
		$defaults = array(
			'action_url_type'  => 'event',
			'allow_registration' => 'enabled',
			'allow_cancellation' => 'enabled',
			'business' => get_bloginfo( 'name' ),
			'cancellation_close_type' => 'same',
			'cancellation_relative_close_offset' => 0,
			'cancellation_relative_close_offset_type' => 'days',
			'capacity' => 30,
			'capacity_per_registration' => 1,
			'capacity_unlimited' => 'disabled',
			'currency' => 'USD',
			'currency_symbol' => '$',
			'cost_display' => '{symbol}{amount}',
			'default_calendar_settings' => json_encode( array(
				'view_type' => 'month',
				'events_per_page' => 10,
				'events_per_day' => 3,
				'show_toolbar' => 'enabled',
				'filter_relationship' => 'AND',
				'filters' => [],	
				'color' => '#3498db'
			) ),
			'fees' => json_encode( array(
				'label' => __( 'Fees', 'event-genius' ),
				'flat_cost' => .30,
				'percent_cost' => 2.9,
			) ),
			'prevent_duplicate_emails' => 'disabled',
			'allowed_registration_users' => array( 'logged_out_visitors', 'logged_in_users' ),
			'show_attendee_list' => 'disabled',
			'start_of_the_week' => 'sunday',
			'who_can_see_attendee_list' => 'everyone',
			'show_export_options' => 'enabled',
			'single_event_elements' => array(
				'title',
				'featured_image',
				'date',
				'locations',
				'map',
				'about_details',
				'organizers',
				'capacity',
				'categories',
				'tags',
			),
			'color_theme' => 'light',
			'full_date_format' => 'D, F j, Y',
			'date_summary_format' => 'D, F j, Y',
			'registration_timeline_format' => 'F j g:i A',
			'full_date_format_custom' => 'D, F j, Y',
			'date_summary_format_custom' => 'D, F j, Y',
			'registration_timeline_format_custom' => 'F j g:i A',
			'time_format' => 'ga',
			'time_format_custom' => 'ga',
			// Registration timing defaults
			'open_type' => 'immediate',
			'relative_open_offset' => 3,
			'relative_open_offset_type' => 'days',
			'close_type' => 'relative',
			'relative_close_offset' => 0,
			'relative_close_offset_type' => 'days',
			// Dynamic content refresh defaults
			'enable_dynamic_content_refresh' => 'enabled',
			'dynamic_content_staleness_threshold' => 3, // minutes
			// Main blog loop: include events with posts on homepage and tag archives
			'show_events_in_main_loop' => 'disabled',
		);

		return $defaults;
	}

	public static function email_defaults() {
		$defaults = array(
			'send_notification_email'  => 'enabled',
			'notification_from_name'  => get_bloginfo( 'name' ),
			'email_from_address_custom'  => 'disabled',
			'email_from_address'  => get_option( 'admin_email' ),
			'notification_recipients'  => get_option( 'admin_email' ),
			/* translators: 1: Event title */
			'notification_subject_new_reg'  => sprintf( __( 'New Registration - %1$s', 'event-genius' ), '{event-title}' ),
			'notification_email_content'  => self::notification_content(),
			'cancel_request_subject'  => '{event-title}',
			'cancel_request_email_content'  => self::cancel_request_email_content(),
			'send_confirmation_email'  => 'enabled',
			'confirmation_from_name'  => get_bloginfo( 'name' ),
			'confirmation_subject_new_reg'  => '{event-title}',
			'confirmation_email_content'  => self::confirmation_email_content(),
			/* translators: 1: Event title */
			'waiting_list_promotion_subject'  => sprintf( __( 'Waiting List Promotion - %1$s', 'event-genius' ), '{event-title}' ),
			'waiting_list_promotion_content'  => self::waiting_list_promotion_email_content(),
			'send_cancel_notification_email'  => 'enabled',
			'cancel_notification_subject'  => __( 'Cancellation for {event-title}', 'event-genius' ),
			'cancel_notification_email_content'  => self::cancel_notification_email_content(),
			'send_cancel_confirmation_email'  => 'enabled',
			'cancel_confirmation_subject'  => __( 'Cancellation Confirmed for {event-title}', 'event-genius' ),
			'cancel_confirmation_email_content'  => self::cancel_confirmation_email_content(),
		);
		return $defaults;
	}



	public static function json_settings() {
		$json_settings = array(
			'fees',
		);

		// Add Pro JSON settings if Pro version is active
		if ( ! evge_is_free_version() && class_exists( 'WPEventGenius\Pro\Utils\ProDefaults' ) ) {
			$pro_json_settings = \WPEventGenius\Pro\Utils\ProDefaults::json_settings();
			$json_settings = array_merge( $json_settings, $pro_json_settings );
		}

		return $json_settings;
	}

	public static function text_defaults() {
		$defaults = array(
			'cancel_success_message'  => __( 'Your registration was was successfully canceled for the event {event-title} on {start-date}', 'event-genius' ),
			'cancel_request_success_message'  => __( 'Please check your email inbox for further instructions.', 'event-genius' ),
			'cancel_button_text'  => __( 'Cancel My Registration', 'event-genius' ),
			'cancel_registration_button_text'  => __( 'Cancel Registration', 'event-genius' ),
			'cancel_confirm_button_text'  => __( 'Confirm', 'event-genius' ),
			'cancel_none_found_message'  => __( 'No record found. This registration may have already been canceled.', 'event-genius' ),
			'cancel_confirm_needed_message'  => __( 'Confirm your cancellation for the event {event-title} on {start-date} by clicking the button below.', 'event-genius' ),
			'cancel_no_results'  => __( 'No record found. Please make sure this is the email address you registered with.', 'event-genius' ),
			'cancel_request_field_label' => __( 'Enter Email', 'event-genius' ),
			'confirmed_modal_message'  => self::confirmed_modal_message(),
			'form_register_button_text'  => __( 'Register', 'event-genius' ),
			'form_submit_button_text'  => __( 'Submit', 'event-genius' ),
			'how_many_registrations'  => __( 'How many registrations?', 'event-genius' ),
			'already_registered'  => __( 'Already Registered?', 'event-genius' ),
			'registration_capacity_text'  => __( '{remaining} spots available', 'event-genius' ),
			'registration_capacity_text_singular'  => __( '{remaining} spot available', 'event-genius' ),
			'registration_capacity_text_short'  => __( '{remaining} left', 'event-genius' ),
			'registration_not_open_text'  => __( 'Registration will open on {open-date}.', 'event-genius' ),
			'registration_not_open_text_short'  => __( 'Registration Opens {open-date}', 'event-genius' ),
			'registration_closed_text'  => __( 'Registration has closed for this event.', 'event-genius' ),
			'registration_closed_text_short'  => __( 'Registration Closed', 'event-genius' ),
			'registration_filled_text'  => __( 'Registration has filled for this event.', 'event-genius' ),
			'registration_filled_text_short'  => __( 'Registration Filled', 'event-genius' ),
			'cancel_request_instructions'  => __( 'If you need to cancel your registration, enter the email address you registered with below. You will receive an email with further instructions', 'event-genius' ),
			'cancel_request_submit_button_text'  => __( 'Submit', 'event-genius' ),
			'date_and_time_text'  => __( 'Date and Time', 'event-genius' ),
			'locations_text'  => __( 'Locations', 'event-genius' ),
			'location_text'  => __( 'Location', 'event-genius' ),
			'about_event_text'  => __( 'About the Event', 'event-genius' ),
			'about_organizer_text'  => __( 'About the Organizer', 'event-genius' ),
			'about_organizers_text'  => __( 'About the Organizers', 'event-genius' ),
			'tags_text'  => __( 'Tags', 'event-genius' ),
			'categories_text'  => __( 'Categories', 'event-genius' ),
			'add_to_calendar_text'  => __( 'Add to Calendar', 'event-genius' ),
			'see_map_text'  => __( 'See Map', 'event-genius' ),
			'learn_more_text'  => __( 'Learn More', 'event-genius' ),
			// Registration text settings
			'registration_available_text'  => __( 'Registration Available', 'event-genius' ),
			'already_registered_message'  => __( 'You have already registered for this event', 'event-genius' ),
			'manage_registration_button_text'  => __( 'Manage Registration', 'event-genius' ),
			'confirmation_email_resent_message'  => __( 'Confirmation email resent! Check your email inbox.', 'event-genius' ),
			'field_not_editable_message'  => __( 'This field is not editable.', 'event-genius' ),
			'edit_success_message'  => __( 'Your registration has been updated successfully.', 'event-genius' ),
			'confirmation_condition'  => 'payment_complete',
			'pending_approval_modal_message'  => self::pending_approval_modal_message(),
		);

		return $defaults;
	}

	public static function notification_content() {
		$lines = array();
		/* translators: 1: Event title, 2: Venue title, 3: Event start date. */
		$lines[] = sprintf( __( 'The following submission was made for: %1$s at %2$s on %3$s', 'event-genius' ), '{event-title}', '{venue-title}', '{start-date}' );
		$lines[] = '';
		$lines[] = '{all-fields}';
		$lines[] = '';
		$lines[] = '{admin-manage-registration}';

		return implode( "\n", $lines );
	}

	public static function confirmation_email_content() {
		$lines = array();
		/* translators: 1: First name */
		$lines[] = sprintf( __( 'Hey %1$s,', 'event-genius' ), '{first}' );
		$lines[] = '';
		/* translators: 1: Event title, 2: Venue title */
		$lines[] = sprintf( __( 'You are registered for %1$s at %2$s', 'event-genius' ), '{event-title}', '{venue-title}' );
		$lines[] = '';
		$lines[] = __( 'The event will be held on this date:', 'event-genius' );
		$lines[] = '';
		$lines[] = '{date-summary}';
		$lines[] = '';
		$lines[] = __( 'The event will be held at the following location:', 'event-genius' );
		$lines[] = '';
		$lines[] = '{venue-title}';
		$lines[] = '{venue-address}';
		$lines[] = __( '{venue-city}, {venue-state} {venue-zip}', 'event-genius' );
		$lines[] = '';
		$lines[] = __( 'See you there!', 'event-genius' );

		return implode( "\n", $lines );
	}

	public static function waiting_list_promotion_email_content() {
		$lines = array();
		/* translators: 1: First name */
		$lines[] = sprintf( __( 'Hey %1$s,', 'event-genius' ), '{first}' );
		$lines[] = '';
		/* translators: 1: Event title, 2: Venue title */
		$lines[] = sprintf( __( 'Great news! A spot has opened up for %1$s at %2$s, and you have been promoted from the waiting list!', 'event-genius' ), '{event-title}', '{venue-title}' );
		$lines[] = '';
		/* translators: 1: Event title, 2: Venue title */
		$lines[] = sprintf( __( 'You are now registered for %1$s at %2$s', 'event-genius' ), '{event-title}', '{venue-title}' );
		$lines[] = '';
		$lines[] = __( 'The event will be held on this date:', 'event-genius' );
		$lines[] = '';
		$lines[] = '{date-summary}';
		$lines[] = '';
		$lines[] = __( 'The event will be held at the following location:', 'event-genius' );
		$lines[] = '';
		$lines[] = '{venue-title}';
		$lines[] = '{venue-address}';
		$lines[] = __( '{venue-city}, {venue-state} {venue-zip}', 'event-genius' );
		$lines[] = '';
		$lines[] = __( 'See you there!', 'event-genius' );

		return implode( "\n", $lines );
	}

	public static function cancel_request_email_content() {
		$lines = array();
		/* translators: 1: First name */
		$lines[] = sprintf( __( 'Hey %1$s,', 'event-genius' ), '{first}' );
		$lines[] = '';
		/* translators: 1: Event title, 2: Venue title, 3: Event start date. */
		$lines[] = sprintf( __( 'Click the button below to confirm the cancellation of your registration for %1$s at %2$s on %3$s.', 'event-genius' ), '{event-title}', '{venue-title}', '{start-date}' );
		$lines[] = '';
		$lines[] = '{registration-cancel-button}';

		return implode( "\n", $lines );
	}

	public static function cancel_notification_email_content() {
		$lines = array();
		/* translators: 1: Event title, 2: Venue title, 3: Event start date. */
		$lines[] = sprintf( __( 'There has been a cancellation for: %1$s at %2$s on %3$s', 'event-genius' ), '{event-title}', '{venue-title}', '{start-date}' );
		$lines[] = '';
		$lines[] = '{all-fields}';

		return implode( "\n", $lines );
	}
	public static function cancel_confirmation_email_content() {
		$lines = array();
		/* translators: 1: First name */
		$lines[] = sprintf( __( 'Hey %1$s,', 'event-genius' ), '{first}' );
		$lines[] = '';
		/* translators: 1: Event title, 2: Venue title, 3: Event start date. */
		$lines[] = sprintf( __( 'You are no longer registered for %1$s at %2$s on %3$s.', 'event-genius' ), '{event-title}', '{venue-title}', '{start-date}' );

		return implode( "\n", $lines );
	}



	public static function confirmed_modal_message() {
		$lines = array();
		/* translators: 1: First name */
		$lines[] = '<strong>' . __( 'Success!', 'event-genius' ) . '</strong>';
		$lines[] = '';
		/* translators: 1: Event title, 2: Venue title, 3: Event start date. */
		$lines[] = __( 'Please check your email inbox for a confirmation message.', 'event-genius' );

		return implode( "\n", $lines );
	}

	/**
	 * Default message shown after registration when form uses manual review only.
	 *
	 * @return string
	 */
	public static function pending_approval_modal_message() {
		$lines = array();
		$lines[] = '<strong>' . __( 'Registration received.', 'event-genius' ) . '</strong>';
		$lines[] = '';
		$lines[] = __( "We'll review it and email you once it's approved.", 'event-genius' );
		return implode( "\n", $lines );
	}

}
