<?php
/**
 * List Item Template
 * 
 * This template displays a single event item in the list view format.
 * It includes the event's date, title, venue, cost, about details, summary, and featured image.
 * The date is displayed in a prominent format with month and day.
 * 
 * @param EventPost $event_post The event post object
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\Common\Event\EventPost;
use WPEventGenius\Common\Utils\Icon;
use WPEventGenius\Common\Event\RegistrationCounter;

$event_id = $event_post->get_the_id();
?>

<div class="evge-event-list-item-wrap">
	<div class="evge-event-list-item evge-event-<?php echo esc_attr( $event_id ); ?>">
		<div class="evge-event-date">
			<?php
			$start_date = new DateTime( $event_post->get_the_start_date_raw() );
			?>
			<span class="evge-event-month"><?php echo esc_html( wp_date( 'M', $start_date->getTimestamp() ) ); ?></span>
			<span class="evge-event-day"><?php echo esc_html( $start_date->format( 'd' ) ); ?></span>
		</div>

		<div class="evge-event-list-details">
			<h3>
				<a href="<?php echo esc_url( $event_post->get_the_permalink() ); ?>">
					<?php echo esc_html( $event_post->get_the_title() ); ?>
				</a>
			</h3>

			<div class="evge-event-list-meta-wrap">
				<div class="evge-event-list-date evge-event-meta-item">
					<?php 
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo Icon::get( 'list-outline' ); ?>
					<?php 
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $event_post->recurrence_display() . $event_post->get_the_date_summary(); ?>
				</div>

				<div class="evge-event-list-cost evge-event-meta-item">
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
				</div>
			</div>

			<?php $about_items = $event_post->get_the_about_items( 'list' ); ?>
			<?php if ( ! empty( $about_items ) && $event_post->should_show_section( 'about_details' ) ) : ?>
				<div class="evge-single-about-details">
					<?php foreach ( $about_items as $about_item ) : 
						$atts = '';
						if ( ! empty( $about_item['atts'] ) ) {
							foreach ( $about_item['atts'] as $att => $value ) {
								$atts .= ' ' . esc_attr( $att ) . '="' . esc_attr( $value ) . '"';
							}
						}
						?>
						<div 
							id="evge-about-detail-<?php echo esc_attr( $about_item['slug'] ); ?>" 
							class="evge-about-detail-<?php echo esc_attr( $about_item['slug'] ); ?> evge-about-detail"
					>
							<?php if ( ! empty( $about_item['icon'] ) ) : ?>
								<?php Icon::output( $about_item['icon'] ); ?>
							<?php endif; ?>
							<span><?php echo esc_html( $about_item['text'] ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $event_post->get_the_summary() ) ) : ?>
			<p class="evge-event-list-summary">
				<?php echo wp_kses_post( $event_post->get_the_summary() ); ?>
			</p>
			<?php endif; ?>

			<?php 
			$bulk_registration_enabled = isset( $bulk_registration_enabled ) ? $bulk_registration_enabled : false;
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $event_post->get_the_listing_cta( array( 'bulk_registration' => $bulk_registration_enabled ) ); 
			?>
		</div>

		<?php if ( $event_post->has_featured_image() ) : ?>
		<div class="evge-event-list-featured-image">
			<a href="<?php echo esc_url( $event_post->get_the_permalink() ); ?>">
				<?php 
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $event_post->get_the_featured_image( 'large' ); ?>
			</a>
		</div>
		<?php endif; ?>
	</div>
</div>

