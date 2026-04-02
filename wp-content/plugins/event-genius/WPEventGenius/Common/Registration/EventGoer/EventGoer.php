<?php

namespace WPEventGenius\Common\Registration\EventGoer;

use WPEventGenius\Common\Database;
use WPEventGenius\Common\Registration\Registration\RegistrationGroup;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

interface EventGoer {
	public function __construct( $user_id, Database $database );

	public function init( $event );

	public function query_existing_registration_data();

	public function get_event_status();

	public function set_event( $event );

	public function set_registration_group( RegistrationGroup $registration_group );

	public function can_register_for_event();

	public function has_made_submission_for_event();

	public function can_edit_registration_type();

	public function can_edit_registration_entry_data();

	public function can_edit_registration_status();

	public function can_edit_something();

	public function can_edit_form_field( $field_slug );

	public function can_view_attendee_list();

	public function can_skip_spam_detection();

	public function get_user_id();

	public function filter_field_value( $value, $field_attributes );

	/**
	 * Get prefill data for form fields with priority: registration data > WordPress user data
	 * 
	 * @return array Array of field values to prefill forms
	 */
	public function get_prefill_data();

}
