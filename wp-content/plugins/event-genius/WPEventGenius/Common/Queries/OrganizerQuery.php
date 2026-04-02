<?php
namespace WPEventGenius\Common\Queries;


use WPEventGenius\Common\Event\OrganizerPost;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class OrganizerQuery {

	protected $args;

	protected $organizers;
	public function __construct( $args = array() ) {
		if ( empty( $args ) ) {
			$args = array();
		}

		$args['post_type'] = EVGE_ORGANIZER_POST_TYPE;

		if ( ! empty( $args['ID'] ) ) {

			if ( ! is_array( $args['ID'] ) ) {
				$args['ID'] = array( $args['ID'] );
			}
			$args['post__in'] = $args['ID'];
			unset( $args['ID'] );
		}

		if ( empty( $args['posts_per_page'] ) ) {
			$args['posts_per_page'] = -1;
		}

		$this->args = $args;
	}

	public function get_organizers() {
		return $this->organizers;
	}

	public function hydrate() {
		$organizers = $this->get_organizers();


		$organizers_hydrated = array();
		foreach ( $organizers as $organizer ) {
			// Check if organizer exists before creating OrganizerPost object
			if ( get_post_status( $organizer->ID ) === 'publish' ) {
				$organizer_post = new OrganizerPost( $organizer->ID );
				$organizer->details = array();
				$organizer->details['email'] = $organizer_post->get_the_email();
				$organizer->details['phone'] = $organizer_post->get_the_phone();
			} else {
				$organizer->details = array();
				$organizer->details['email'] = '';
				$organizer->details['phone'] = '';
			}

			$organizers_hydrated[] = $organizer;
		}

		$this->organizers = $organizers_hydrated;
	}

	public function add_wp_query() {
		$query = new \WP_Query( $this->args );

		$this->organizers = $query->get_posts();

		wp_reset_postdata();
	}

}