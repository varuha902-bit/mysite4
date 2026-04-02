<?php
namespace WPEventGenius\Common\Registration\Field;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class SubmitType extends BaseFieldType {
	public function get_type() {
		return 'submit';
	}
}