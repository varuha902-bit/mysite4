<?php
namespace WPEventGenius\Admin\CustomPostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

interface CustomPostType {
	public function __construct();

	public function get_post_type();

	public function register_taxonomies();
	public function register();


	public function add_meta_boxes();

}