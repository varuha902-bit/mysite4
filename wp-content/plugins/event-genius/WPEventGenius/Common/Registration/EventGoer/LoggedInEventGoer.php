<?php

namespace WPEventGenius\Common\Registration\EventGoer;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class LoggedInEventGoer extends BaseEventGoer {
	public function can_view_attendee_list() {
		return true;
	}

	public function query_existing_registration_data() {
		if ( empty( $this->event ) || empty( $this->user_id ) ) {
			return array();
		}

		$event_id = $this->event->get_the_id();
		
		// Query for existing registrations by user_id and event_id
		// Only get main registrations (parent = 0), not additional guests
		$where = array(
			array(
				'column' => 'user_id',
				'value' => $this->user_id,
				'compare' => '=',
				'type' => 'int'
			),
			array(
				'column' => 'event_id',
				'value' => $event_id,
				'compare' => '=',
				'type' => 'int'
			),
			array(
				'column' => 'parent',
				'value' => 0,
				'compare' => '=',
				'type' => 'int'
			),
			array(
				'column' => 'status',
				'value' => 'canceled',
				'compare' => '!=',
				'type' => 'string'
			)
		);

		$registrations = $this->database->registration_query( $where, 'registration_date DESC', 1 );
		
		if ( empty( $registrations ) ) {
			return array();
		}

		// Create RegistrationGroup for consistency and use it to get the data
		$registration_group = new \WPEventGenius\Common\Registration\Registration\RegistrationGroup( $this->database );
		$registration_group->set_from_existing( $registrations[0]['id'] );
		
		// Return the registration data from the RegistrationGroup
		return $registration_group->get_main()->get_registration_data();
	}

	public function can_register_for_event() {
		// Check if logged in users are allowed to register for this specific event
		$allowed_users = array( 'logged_out_visitors', 'logged_in_users' ); // Default fallback
		
		if ( $this->event && method_exists( $this->event, 'get_the_id' ) ) {
			$event_id = $this->event->get_the_id();
			$event_allowed_users = get_post_meta( $event_id, 'allowed_registration_users', true );
			
			// If event has specific settings, use those; otherwise fall back to global
			if ( is_array( $event_allowed_users ) ) {
				$allowed_users = $event_allowed_users;
			} else {
				// Fall back to global setting
				$global_allowed_users = \WPEventGenius\Common\Utils\Settings::get( 'allowed_registration_users' );
				if ( is_array( $global_allowed_users ) ) {
					$allowed_users = $global_allowed_users;
				}
			}
		}
		
		return in_array( 'logged_in_users', $allowed_users ) && $this->user_id > 0;
	}

	public function can_edit_registration_type() {
		return true;
	}

	public function can_edit_registration_entry_data() {
		return true;
	}

	public function can_edit_registration_status() {
		return true;
	}

	public function can_edit_form_field( $field_slug ) {
		return true;
	}

	public function can_skip_spam_detection() {
		return true;
	}



}
