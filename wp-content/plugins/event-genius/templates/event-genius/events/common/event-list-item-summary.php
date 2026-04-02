<?php
/**
 * Event List Item Summary Template
 * 
 * This template displays a list of events with their summaries. It includes:
 * - Event date display (month and day)
 * - Event title with link
 * - Date and recurrence information
 * - Venue and cost details
 * - Event summary/excerpt
 * 
 * @var array $events Array of event objects to display
 * @package WPEventGenius
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\Common\Utils\Icon;
?>

<div class="evge-event-list-compact">
	<?php foreach ( $events as $event_post ) : ?>
		<div class="evge-event-item">
			<div class="evge-event-date">
				<?php
				$start_date = new DateTime( $event_post->get_the_start_date_raw() );
				?>
				<span class="evge-event-month"><?php echo esc_html( wp_date( 'M', $start_date->getTimestamp() ) ); ?></span>
				<span class="evge-event-day"><?php echo esc_html( $start_date->format( 'd' ) ); ?></span>
			</div>
			<div class="evge-event-details">
				<h4 class="evge-event-title">
					<a href="<?php echo esc_url( $event_post->get_the_permalink() ); ?>"><?php echo esc_html( $event_post->get_the_title() ); ?></a>
				</h4>
				<p class="evge-date-detail evge-icon-detail">
					<span class="evge-icon">
						<?php Icon::output( 'date' ); ?>
					</span>
					<?php 
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $event_post->recurrence_display() . esc_html( $event_post->get_the_full_date() ); ?>
				</p>
				<p class="evge-venue-cost-combined evge-icon-detail">
					<?php 
					$venue_title = $event_post->get_the_venue_title();
					$cost_display = $event_post->get_the_cost_display();
					$separator = ! empty( $venue_title ) && ! empty( $cost_display ) ? ' | ' : '';
					if ( ! empty( $venue_title ) ) {
						Icon::output( 'location-outline' );
					}
					?>
					<span>
						<?php
						echo wp_kses_post( $venue_title );
						echo $separator;
						echo wp_kses_post( $event_post->get_the_cost_display() );
						?>
					</span>
				</p>

				<div class="evge-event-excerpt">
					<p><?php echo wp_kses_post( $event_post->get_the_summary( 280, false ) ); ?></p>
				</div>
			</div>
		</div>
	<?php endforeach; ?>
</div>