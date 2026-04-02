<?php
namespace WPEventGenius\Common\Registration\Field;
use WPEventGenius\Common\Registration\Submission\Validator;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class SingleCheckboxType extends BaseFieldType {
	public function get_type() {
		return 'single-checkbox';
	}

	public function hidden_label() {
		return true;
	}

	public function get_value( $registration_data = array() ) {
		if ( ! empty( $registration_data[ $this->get_slug() ] ) ){
			return 'true';
		}
		return '';
	}

	public function is_valid( Validator $validator, $value ) {
		return $validator->single_checkbox( $value );
	}
}