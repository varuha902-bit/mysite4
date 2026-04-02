<?php
namespace WPEventGenius\Common\Registration\Field;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class PhoneType extends BaseFieldType {
	public function get_type() {
		return 'phone';
	}

	public function get_type_attribute() {
		return 'tel';
	}

	public function is_valid( $validator, $value ) {
		return $validator->phone( $value );
	}
}