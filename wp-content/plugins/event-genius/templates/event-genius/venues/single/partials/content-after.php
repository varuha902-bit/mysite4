<?php
/**
 * Venue Content After Template
 * 
 * This template displays additional content sections after the main venue content including:
 * - Events at this venue
 * 
 * @var int    $venue_id The ID of the current venue
 * @package WPEventGenius
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\Common\Event\VenuePost;

$venue_post = new VenuePost( $venue_id );

			// Query upcoming events for this venue
			$upcoming_query = new \WPEventGenius\Common\Event\EventQuery( [
				'venue_id' => $venue_post->get_the_id(),
				'num' => 3,
				'time_filter' => 'upcoming'
			] );
			$upcoming_query->apply_params();
			$upcoming_events = $upcoming_query->get_events();
			
			// If we need more events, get past events
			$remaining_slots = 3 - count( $upcoming_events );
			$past_events = [];
			
			if ( $remaining_slots > 0 ) {
				$past_query = new \WPEventGenius\Common\Event\EventQuery( [
					'venue_id' => $venue_post->get_the_id(),
					'num' => $remaining_slots,
					'time_filter' => 'past'
				] );
				$past_query->apply_params();
				$past_events = $past_query->get_events();
			}
			
			// Combine the events
			$events = array_merge( $upcoming_events, $past_events );
			
			if ( ! empty( $events ) ) : 
				EVGE()->template_manager()->get_template( 'events/common/event-list-item-summary.php', [
					'events' => $events
				] );
			endif; 
			?>
		</div>
	</div>
</div>