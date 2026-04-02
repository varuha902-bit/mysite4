<?php
namespace WPEventGenius\Common\Registration\Field;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class HoneyPotType extends BaseFieldType {
	public function get_type() {
		return 'honeypot';
	}

    public function get_slug() {
        return 'user_comments';
    }

    public function get_id() {
        return 'evge_user_comments';
    }
}