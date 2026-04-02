<?php

namespace WPEventGenius\Common\Services;

use WPEventGenius\Common\Taxonomies\CalendarTaxonomy;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class CustomPostTypeService {

	/**
	 * @var array
	 */
	private $custom_post_types;

	public function __construct( $custom_post_types ) {
		$this->custom_post_types = $custom_post_types;
	}

	public function init_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 10, 1 );

		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 10, 1 );

		foreach ( $this->custom_post_types as $custom_post_type ) {
			foreach ( $custom_post_type as $cpt ) {
				$cpt->init_custom_hooks();
			}
		}

		add_action('init', [$this, 'register_taxonomy']);

	}

	public function pre_get_posts( $query ) {
		foreach ( $this->custom_post_types as $custom_post_type ) {
			foreach ( $custom_post_type as $cpt ) {
				$query = $cpt->maybe_alter_query( $query );
			}
		}
		return $query;
	}

	public function template_include( $template ) {
		foreach ( $this->custom_post_types as $custom_post_type ) {
			foreach ( $custom_post_type as $cpt ) {
				$cpt->maybe_alter_template( $template );
			}
		}
	}

	public function register_taxonomy() {
		$calendar_taxonomy = new CalendarTaxonomy();
		$calendar_taxonomy->register();
	}



	public function enqueue( $screen ) {
		foreach ( $this->custom_post_types as $custom_post_type ) {
			foreach ( $custom_post_type as $cpt ) {
				$cpt->enqueue( $screen );
			}
		}
	}
	

}