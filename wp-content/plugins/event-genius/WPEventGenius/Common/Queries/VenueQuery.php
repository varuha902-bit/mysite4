<?php
namespace WPEventGenius\Common\Queries;

use WPEventGenius\Common\Event\VenuePost;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class VenueQuery {

	protected $args;

	protected $venues;
	public function __construct( $args = array() ) {
		if ( empty( $args ) ) {
			$args = array();
		}

		$args['post_type'] = EVGE_VENUE_POST_TYPE;

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

	public function get_venues() {
		return $this->venues;
	}

	public function hydrate() {
		$venues = $this->get_venues();

		$venues_hydrated = array();
		foreach ( $venues as $venue ) {
			// Check if venue exists before creating VenuePost object
			if ( get_post_status( $venue->ID ) === 'publish' ) {
				$venue_post = new VenuePost( $venue->ID );
				$venue->details = array();
				$venue->details['full_address'] = $venue_post->get_the_full_address();
			} else {
				$venue->details = array();
				$venue->details['full_address'] = '';
			}

			$venues_hydrated[] = $venue;
		}

		$this->venues = $venues_hydrated;
	}

	public function add_wp_query() {
		$query = new \WP_Query( $this->args );

		$this->venues = $query->get_posts();

		wp_reset_postdata();
	}

}