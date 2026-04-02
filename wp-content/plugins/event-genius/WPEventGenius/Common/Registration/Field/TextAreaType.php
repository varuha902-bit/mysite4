<?php
namespace WPEventGenius\Common\Registration\Field;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class TextAreaType extends BaseFieldType {
	public function get_type() {
		return 'textarea';
	}
}