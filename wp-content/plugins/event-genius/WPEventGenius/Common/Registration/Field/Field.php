<?php
namespace WPEventGenius\Common\Registration\Field;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

interface Field {
	public function __construct( $args );

	public function get_id();

	public function get_slug();

	public function get_label();

	public function get_default();

	public function get_value();

	public function get_options();

	public function main_should_show();

	public function guest_should_show();

	public function main_is_required();

	public function guest_is_required();

	public function get_validation();

	public function get_error_message( $type = 'default' );

	public function get_special_validation();

}