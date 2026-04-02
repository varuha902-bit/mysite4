<?php
namespace WPEventGenius\Common\Registration\Field;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class RadioType extends BaseFieldType {
	public function get_type() {
		return 'radio';
	}

	public function get_value( $registration_data = array() ) {
		if ( ! empty( $registration_data[ $this->get_slug() ] ) ){
			if ( is_array( $registration_data[ $this->get_slug() ] ) ) {
				// return first value
				return $registration_data[ $this->get_slug() ][0];
			}
			return $registration_data[ $this->get_slug() ];
		}
		return $this->value;
	}
}