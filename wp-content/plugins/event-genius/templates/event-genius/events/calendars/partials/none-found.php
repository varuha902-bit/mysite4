<?php
/**
 * No Events Found Template
 * 
 * This template displays a message when no events are found in the calendar view.
 * It provides different messages and actions based on whether the user is searching
 * or filtering events. The template includes:
 * - A contextual icon (search or calendar)
 * - A heading indicating no events were found
 * - A descriptive message explaining why no events were found
 * - Action buttons to clear search/filters or view all events
 * 
 * @param bool $has_search Whether the user is currently searching for events
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\Common\Utils\Icon;
?>

<div class="evge-no-events-found">
	<div class="evge-no-events-message">
		<?php if ( $has_search ) : ?>
			<div class="evge-no-events-icon">
				<?php Icon::output( 'search-empty' ); ?>
			</div>

			<h3><?php esc_html_e( 'No Events Found', 'event-genius' ); ?></h3>

			<p class="evge-no-events-description">
				<?php esc_html_e( "We couldn't find any events matching your search.", 'event-genius' ); ?>
			</p>

			<div class="evge-no-events-actions">
				<button class="evge-button evge-button-secondary evge-clear-search">
					<?php esc_html_e( 'Clear Search', 'event-genius' ); ?>
				</button>
				<button class="evge-button evge-button-primary evge-green-button evge-view-all">
					<?php esc_html_e( 'View All Events', 'event-genius' ); ?>
				</button>
			</div>
		<?php else : ?>
			<div class="evge-no-events-icon">
				<?php Icon::output( 'calendar-empty' ); ?>
			</div>

			<h3><?php esc_html_e( 'No Events Found', 'event-genius' ); ?></h3>

			<p class="evge-no-events-description">
				<?php esc_html_e( 'There are no events that fit your filters.', 'event-genius' ); ?>
			</p>

			<div class="evge-no-events-actions">
				<button class="evge-button evge-button-secondary evge-clear-filters">
					<?php esc_html_e( 'Clear Filters', 'event-genius' ); ?>
				</button>
				<button class="evge-button evge-button-primary evge-green-button evge-view-all">
					<?php esc_html_e( 'View All Events', 'event-genius' ); ?>
				</button>
			</div>
		<?php endif; ?>
	</div>
</div>
