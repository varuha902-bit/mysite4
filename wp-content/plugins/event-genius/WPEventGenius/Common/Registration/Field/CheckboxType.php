<?php
namespace WPEventGenius\Common\Registration\Field;
use WPEventGenius\Common\Registration\Submission\Validator;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class CheckboxType extends BaseFieldType {
	public function get_type() {
		return 'checkbox';
	}

	public function get_value( $registration_data = array() ) {

		if ( ! empty( $registration_data[ $this->get_slug() ] ) ){
			if ( is_string( $registration_data[ $this->get_slug() ] ) ) {
				return array( $registration_data[ $this->get_slug() ] );
			}
			return $registration_data[ $this->get_slug() ];
		}
		return array();
	}

	public function is_valid( Validator $validator, $value ) {
		return $validator->checkbox( $value );
	}
}