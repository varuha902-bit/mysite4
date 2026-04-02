<?php
/**
 * Event Card Template
 * 
 * This template displays a single event card in the calendar view. It includes:
 * - Event title and time
 * - Venue information
 * - Category tags with custom colors
 * 
 * 
 * @var array $event_data {
 *     Array of event data and display settings
 * 
 *     @type object $event          The event object
 *     @type string $classes        CSS classes for the card
 *     @type bool   $show_time      Whether to display event time
 *     @type string $time_display   Formatted time string
 * }
 * @package WPEventGenius
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$event = $event_data['event'];
$categories = wp_get_post_terms( $event->get_the_id(), EVGE_EVENT_CATEGORY_TYPE );
$category_colors = [];

foreach ( $categories as $cat ) {
	$color = get_term_meta( $cat->term_id, 'evge_category_color', true );
	if ( $color ) {
		$category_colors[] = $color;
	}
}
?>

<div class="evge-event-card <?php echo esc_attr( $event_data['classes'] ); ?>"
	 data-event-id="<?php echo esc_attr( $event->get_the_id() ); ?>"
	<?php if ( ! empty( $category_colors ) ) : ?>
	 style="--event-color: <?php echo esc_attr( $category_colors[0] ); ?>"
	<?php endif; ?>>
	
	<div class="evge-event-card-container">
		<div class="evge-event-title">
			<?php echo esc_html( $event->get_the_title() ); ?>
		</div>
		
		<?php if ( $event_data['show_time'] ) : ?>
			<div class="evge-event-time">
				<?php echo esc_html( $event_data['time_display'] ); ?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $event->get_the_venue_title() ) ) : ?>
			<div class="evge-event-venue">
				<?php echo esc_html( $event->get_the_venue_title() ); ?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $categories ) ) : ?>
			<div class="evge-event-categories">
				<?php foreach ( $categories as $cat ) : ?>
					<span class="evge-event-category">
						<?php echo esc_html( $cat->name ); ?>
					</span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</div> 