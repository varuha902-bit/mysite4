<?php

namespace WPEventGenius\Common\Registration\EventGoer;

use WPEventGenius\Common\Database;
use WPEventGenius\Common\Event\Event;
use WPEventGenius\Common\Registration\Registration\RegistrationGroup;
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}
class BaseEventGoer implements EventGoer {
	protected $user_id;

	protected $database;

	/**
	 * @var Event
	 */
	protected $event;

	/**
	 * @var RegistrationGroup
	 */
	protected $registration_group;

	protected $user_data;

	protected $submission_status;

	protected $event_status;

	public function __construct( $user_id, Database $database ) {
		$this->user_id  = $user_id;
		$this->database = $database;
	}

	public function init( $event ) {
		$this->event = $event;

		$user_data = array();
		$user_meta = array();
		if ( $this->user_id > 0 ) {
			$user_meta = get_user_meta( $this->user_id, '', true );

			$user_obj              = get_userdata( $this->user_id );
			$user_data['first']    = isset( $user_meta['first_name'] ) ? $user_meta['first_name'][0] : '';
			$user_data['last']     = isset( $user_meta['last_name'] ) ? $user_meta['last_name'][0] : '';
			$user_data['email']    = isset( $user_obj->data->user_email ) ? $user_obj->data->user_email : '';
			$user_data['nickname'] = isset( $user_meta['nickname'] ) ? $user_meta['nickname'][0] : '';

		}

		$this->user_data = apply_filters( 'evge_user_data', $user_data, $user_meta );

		$existing_event_data = $this->query_existing_registration_data();

		$user_submission_status = 'new';

		if ( ! empty( $existing_event_data ) ) {
			$user_submission_status = 'submission_made';
		}
		$this->submission_status = $user_submission_status;

		if ( $user_submission_status !== 'new' ) {
			$this->registration_group = new RegistrationGroup( $this->database );
			$this->registration_group->set_from_existing( $existing_event_data['id'] );
			$this->event_status = $this->registration_group;
		}

		return $existing_event_data;
	}

	/**
	 * @return Event
	 */
	public function event() {
		return $this->event;
	}

	public function query_existing_registration_data() {
		return array();
	}

	public function get_event_status() {
		return $this->event_status;
	}

	public function set_event( $event ) {
		$this->event = $event;
	}

	public function set_registration_group( RegistrationGroup $registration_group ) {
		$this->registration_group = $registration_group;
	}

	public function can_register_for_event() {
		return false;
	}

	public function has_made_submission_for_event() {
		return $this->submission_status === 'submission_made';
	}

	public function can_edit_registration_type() {
		return false;
	}

	public function can_edit_registration_entry_data() {
		return false;
	}

	public function can_edit_registration_status() {
		return false;
	}


	public function can_edit_something() {
		return $this->can_edit_registration_entry_data() || $this->can_edit_registration_status() || $this->can_edit_registration_type();
	}

	public function can_edit_form_field( $field_slug ) {
		return false;
	}

	public function has_completed_payment() {
		if ( empty( $this->registration_group ) ) {
			return false;
		}
		if ( ! $this->registration_group->has_cost() ) {
			return true;
		}

		return true;
	}

	public function can_view_attendee_list() {
		return true;
	}

	public function can_skip_spam_detection() {
		return false;
	}

	public function get_user_id() {
		return $this->user_id;
	}

	public function get_entry_id() {
		if ( empty( $this->registration_group ) ) {
			return false;
		}
		if ( ! $this->registration_group->get_main()->get_entry_id() ) {
			return false;
		}
		return $this->registration_group->get_main()->get_entry_id();
	}

	public function get_reserved_spots_for_event() {
		if ( ! $this->has_made_submission_for_event() ) {
			return 0;
		}
		$guest_count = 0;

		return $guest_count;
	}

	public function filter_field_value( $value, $field_attributes ) {
		$event_data = $this->get_event_data();
		$field_slug = str_replace( 'evge_', '', $field_attributes['name'] );

		if ( ! empty( $event_data ) && ! empty( $event_data[ $field_slug ] ) ) {
			return $event_data[ $field_slug ];
		}

		$value = $this->filter_field_value_with_user_data( $value, $field_slug );

		return $value;
	}

	protected function filter_field_value_with_user_data( $value, $field_slug ) {

		if ( ! empty( $this->user_data[ $field_slug ] ) ) {
			return $this->user_data[ $field_slug ];
		}

		return $value;
	}

	/**
	 * Get prefill data for form fields with priority: registration data > WordPress user data
	 * 
	 * @return array Array of field values to prefill forms
	 */
	public function get_prefill_data() {
		$prefill_data = array();
		
		// Get existing registration data (highest priority)
		$existing_data = $this->query_existing_registration_data();
		
		// Get WordPress user data as fallback
		$wp_user_data = array();
		if ( $this->user_id > 0 ) {
			$user_meta = get_user_meta( $this->user_id, '', true );
			$user_obj = get_userdata( $this->user_id );
			
			$wp_user_data = array(
				'first' => isset( $user_meta['first_name'] ) ? $user_meta['first_name'][0] : '',
				'last' => isset( $user_meta['last_name'] ) ? $user_meta['last_name'][0] : '',
				'email' => isset( $user_obj->data->user_email ) ? $user_obj->data->user_email : '',
				'nickname' => isset( $user_meta['nickname'] ) ? $user_meta['nickname'][0] : '',
			);
		}
		
		// Merge data with registration data taking precedence
		if ( ! empty( $existing_data ) ) {
			// Extract form field values from existing registration data
			// Remove the 'evge_' prefix from field names if present
			foreach ( $existing_data as $key => $value ) {
				if ( strpos( $key, 'evge_' ) === 0 ) {
					$clean_key = str_replace( 'evge_', '', $key );
					$prefill_data[ $clean_key ] = $value;
				} else {
					$prefill_data[ $key ] = $value;
				}
			}
		}

		// Fill in missing values with WordPress user data
		foreach ( $wp_user_data as $key => $value ) {
			if ( empty( $prefill_data[ $key ] ) && ! empty( $value ) ) {
				$prefill_data[ $key ] = $value;
			}
		}

		return $prefill_data;
	}
}
