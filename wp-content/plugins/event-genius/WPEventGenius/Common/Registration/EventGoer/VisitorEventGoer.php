<?php

namespace WPEventGenius\Common\Registration\EventGoer;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class VisitorEventGoer extends BaseEventGoer {
	public function can_view_attendee_list() {
		return true;
	}

	public function get_session_action_key() {
		return '';
	}

	public function query_existing_registration_data() {
		return array();
	}

	public function can_register_for_event() {
		// Check if logged out visitors are allowed to register for this specific event
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
		
		return in_array( 'logged_out_visitors', $allowed_users );
	}

	public function can_edit_registration_status() {
		return true;
	}

	public function can_edit_registration_entry_data() {
		return false;
	}

	public function filter_field_value( $value, $field_attributes, $is_guest = false ) {
		$event_data = $this->get_event_data();
		if ( empty( $event_data ) ) {
			return $value;
		}
		$field_slug = str_replace( 'evge_', '', $field_attributes['name'] );

		if ( ! empty( $event_data[ $field_slug ] ) ) {
			return $event_data[ $field_slug ];
		}

		return $this->filter_field_value_with_user_data( $value, $field_slug );
	}
}
