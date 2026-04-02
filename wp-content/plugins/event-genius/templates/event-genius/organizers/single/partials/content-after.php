<?php
/**
 * Organizer Content After Template
 * 
 * This template displays additional content sections after the main organizer content including:
 * - Events by this organizer
 * 
 * @var int    $organizer_id The ID of the current organizer
 * @package WPEventGenius
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\Common\Event\OrganizerPost;

$organizer_post = new OrganizerPost( $organizer_id );
?>
		</div>
		<div class="evge-organizer-events">
			<?php
			$upcoming_events = $organizer_post->events();
			$past_events = $organizer_post->events( array( 'past' => true ) );
			if (!empty($upcoming_events)) :
				?>
				<h3><?php esc_html_e('Upcoming Events', 'event-genius'); ?></h3>
			<?php
				EVGE()->template_manager()->get_template('events/common/event-list-item-summary.php', [
					'events' => $upcoming_events
				]);
			endif;

			if (!empty($past_events)) : ?>
				<h3><?php esc_html_e('Past Events', 'event-genius'); ?></h3>
			<?php
				EVGE()->template_manager()->get_template('events/common/event-list-item-summary.php', [
					'events' => $past_events
				]);
			endif;

			if (empty($upcoming_events) && empty( $past_events )) : ?>
				<p><?php esc_html_e('No events for this organizer. Check back soon!', 'event-genius'); ?></p>
			<?php endif; ?>

		</div>
	</div>
</div>
