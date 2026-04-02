<?php
namespace WPEventGenius\Common\Utils\EventExport;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

use WPEventGenius\Common\Event\VenuePost;
use WPEventGenius\Common\Utils\DateFormatter;
	
class Options {
	protected $options;

	protected $event_post;

	protected $venue_post;
	public function __construct( $event_post ) {
		$this->event_post = $event_post;
		
		// Only create VenuePost if venue exists
		$venue_id = $event_post->get_the_venue_id();
		if ( $venue_id && $event_post->venue_exists( $venue_id ) ) {
			$this->venue_post = new VenuePost( $venue_id );
		} else {
			$this->venue_post = null;
		}
		
		$this->options = array(
			'ical' => array(
				'name' => __( 'iCalendar', 'event-genius' ),
				'url' => add_query_arg( array(
					'ical' => '1',
				), $event_post->get_the_permalink() ),
				'attr' => '',
			),
			'google' => array(
				'name' => __( 'Google Calendar', 'event-genius' ),
				'url' => $this->gcal_url(),
				'attr' => 'target="_blank" rel="noreferrer noopener"',
			),

		);
	}

	public function get_options() {
		return $this->options;
	}
	public function gcal_url() {
		$event_timezone = $this->event_post->get_the_timezone();
		// Use raw date methods to ensure we always have parseable date strings
		$params = array(
			'action' => 'TEMPLATE',
			'text' => $this->event_post->get_the_title(),
			'dates' => DateFormatter::gcal_date( $this->event_post->get_the_start_date_raw(), $event_timezone ) . '/' . DateFormatter::gcal_date( $this->event_post->get_the_end_date_raw(), $event_timezone ),
			'details' => $this->event_post->get_the_summary( 1000, false ),
			'location' => $this->venue_post ? $this->venue_post->get_the_title() . ', ' . $this->venue_post->get_the_full_address() : '',
			'trp' => 'false',
			'ctz' => $event_timezone,
			'sprop' => 'website:' . home_url( '/' ),
		);
		$url = add_query_arg( $params, 'https://www.google.com/calendar/event' );
		return $url;
	}

}