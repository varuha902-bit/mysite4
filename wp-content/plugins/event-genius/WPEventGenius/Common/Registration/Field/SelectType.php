<?php
namespace WPEventGenius\Common\Registration\Field;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class SelectType extends BaseFieldType {
	public function get_type() {
		return 'select';
	}

	public function get_value( $registration_data = array() ) {

		if ( ! empty( $registration_data[ $this->get_slug() ] ) ){
			if ( is_array( $registration_data[ $this->get_slug() ] ) ) {
				return $registration_data[ $this->get_slug() ][0];
			}
			return $registration_data[ $this->get_slug() ];
		}
		return $this->value;
	}
}