<?php
/**
 * Server-side render for Event Location block
 *
 * @var array    $attributes Block attributes
 * @var string   $content   Block content
 * @var WP_Block $block     Block instance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\Common\Event\EventPost;
use WPEventGenius\Common\Event\VenuePost;
use WPEventGenius\Common\Utils\Icon;

// Get event ID from attributes, block context, or current post
$event_id = ! empty( $attributes['eventId'] ) ? intval( $attributes['eventId'] ) : 0;

if ( empty( $event_id ) && ! empty( $block->context['postId'] ) ) {
	$event_id = intval( $block->context['postId'] );
}

if ( empty( $event_id ) ) {
	$event_id = get_the_ID();
}

// Verify this is an event post type
if ( get_post_type( $event_id ) !== EVGE_EVENT_POST_TYPE ) {
	if ( current_user_can( 'edit_posts' ) ) {
		echo '<div class="evge-block-notice">' . esc_html__( 'This block only works with event posts.', 'event-genius' ) . '</div>';
	}
	return;
}

// Get event post object
$event_post = new EventPost( $event_id );

// Set up registration counter if needed
$factory = new \WPEventGenius\Common\Services\RegistrationObjectFactory();
$event_post->set_registration_counter( $factory->create_registration_counter( $event_id, new \WPEventGenius\Common\Database() ) );

// Check if location section should be shown
if ( ! $event_post->should_show_section( 'locations' ) ) {
	return;
}

// Get venue IDs
$venue_ids = $event_post->get_venue_ids();

if ( empty( $venue_ids ) ) {
	return;
}

// Get block wrapper attributes (includes spacing styles)
$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'evge-block-event-location' ) );

// Render the location HTML matching the event-meta.php template
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php foreach ( $venue_ids as $venue_id ) :
		$venue_post = new VenuePost( $venue_id );
		$full_address = $venue_post->get_the_full_address();
		?>
		<div class="evge-meta-venue-summary evge-event-meta-row">
		<div class="evge-event-meta-item">
			<?php Icon::output( 'location' ); ?>
			<span class="evge-meta-venue-title">
				<?php
				$venue_unlink = false; // Can be made configurable later if needed
				if ( empty( $venue_unlink ) ) {
					echo '<a href="' . esc_url( $venue_post->get_the_permalink() ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $venue_post->get_the_title() ) . '</a>';
				} else {
					echo esc_html( $venue_post->get_the_title() );
				}
				if ( ! empty( $full_address ) ) {
					echo ',';
				}
				?>
			</span>
			<?php if ( ! empty( $full_address ) ) : ?>
				<span class="evge-meta-venue-address">
					<?php echo esc_html( $full_address ); ?>
				</span>
			<?php endif; ?>
		</div>
	</div>
	<?php endforeach; ?>
</div>
