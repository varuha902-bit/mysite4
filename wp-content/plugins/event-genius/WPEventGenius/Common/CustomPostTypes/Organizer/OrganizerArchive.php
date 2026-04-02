<?php
namespace WPEventGenius\Common\CustomPostTypes\Organizer;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class OrganizerArchive {

	public function __construct(){

	}

	public function init_custom_hooks() {
		add_filter( 'archive_template', array( $this, 'maybe_alter_template' ), 10, 1 );
		add_filter( 'search_template',  array( $this, 'maybe_alter_template' ), 10, 1 );

	}

	public function is_cpt_page() {
		return is_post_type_archive( EVGE_ORGANIZER_POST_TYPE );
	}

	public function maybe_alter_template( $template ) {
		if ( $this->is_cpt_page() ) {
			/**
			 * TODO: Support an option to completely override the template
			 */
		}
		if ( $this->is_target_archive() ) {
			/**
			 * TODO: Support an option to completely override the template
			 */
		}

		return $template;
	}

	public function maybe_alter_query( $query ) {
		return $query;
	}

	public function is_target_archive() {
		if ( is_post_type_archive( EVGE_ORGANIZER_POST_TYPE ) ) {
			return true;
		}

		return false;
	}


	public function enqueue( $screen ) {
		if ( ! is_post_type_archive( EVGE_ORGANIZER_POST_TYPE ) ) {
			return;
		}
	}

}