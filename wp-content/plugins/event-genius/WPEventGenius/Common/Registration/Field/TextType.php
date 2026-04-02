<?php
namespace WPEventGenius\Common\Registration\Field;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class TextType extends BaseFieldType {
	public function get_type() {
		return 'text';
	}
}