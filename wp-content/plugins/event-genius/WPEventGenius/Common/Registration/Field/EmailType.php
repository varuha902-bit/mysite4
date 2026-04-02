<?php
namespace WPEventGenius\Common\Registration\Field;

use WPEventGenius\Common\Registration\Submission\Validator;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class EmailType extends BaseFieldType {
	public function get_type() {
		return 'email';
	}
	public function get_type_attribute() {
		// Return 'text' instead of 'email' to prevent browser validation
		// We handle email validation through our custom validation system
		return 'text';
	}

	public function is_valid( Validator $validator, $value ) {
		return $validator->email( $value );
	}

	public function additional_input_attributes() {
		// Add inputmode and autocomplete to improve mobile UX and accessibility
		echo 'inputmode="email" autocomplete="email"';
	}

	public function additional_wrapper_data_attributes() {
		// Add data attribute to help JavaScript identify this as an email field
		echo 'data-field-type="email"';
	}
}