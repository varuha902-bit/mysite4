<?php
namespace WPEventGenius\Common\CustomPostTypes\Venue;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class VenueBase {

	public function __construct(){

	}

	public function init_custom_hooks() {

	}

	public function is_cpt_page() {
		return is_post_type_archive( EVGE_VENUE_POST_TYPE )  || get_post_type() === EVGE_VENUE_POST_TYPE;
	}


	public function maybe_alter_template( $template ) {
		return $template;
	}

	public function maybe_alter_query( $query ) {
		return $query;
	}

	public function enqueue( $screen ) {
		if ( ! $this->is_cpt_page() ) {
			return;
		}
		EVGE()->style_service()->enqueue_style('evge_common');
		EVGE()->style_service()->enqueue_style('evge_single_post');
		EVGE()->script_service()->enqueue_script('evge_common');
		EVGE()->script_service()->enqueue_script('evge_single_post');
	}


}