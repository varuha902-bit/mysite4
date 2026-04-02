<?php
/**
 * Server-side render for Event Organizers block
 *
 * @var array    $attributes Block attributes
 * @var string   $content   Block content
 * @var WP_Block $block     Block instance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\Common\Event\EventPost;
use WPEventGenius\Common\Event\OrganizerPost;

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

// Check if organizer section should be shown
if ( ! $event_post->should_show_section( 'organizers' ) ) {
	return;
}

// Get organizer IDs
$organizer_ids = $event_post->get_organizer_ids();

if ( empty( $organizer_ids ) ) {
	return;
}

// Get block wrapper attributes (includes spacing styles)
$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'evge-block-event-organizers' ) );

// Render the organizer HTML matching the content-after.php template
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="evge-event-list-organizer evge-single-event-section">
		<h3 class="evge-event-meta-heading"><?php echo esc_html( $event_post->organizer_heading() ); ?></h3>
		<div class="evge-organizer-wrap">
			<?php foreach ( $organizer_ids as $organizer_id ) :
				$organizer_post = new OrganizerPost( $organizer_id );
			?>
				<div class="evge-single-organizer-wrap">
					<div class="evge-organizer-avatar">
						<?php 
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo $organizer_post->get_the_featured_image();
						?>
					</div>

					<div class="evge-organizer evge-multi-line-align">
						<div class="evge-organizer-title">
							<a href="<?php echo esc_url( $organizer_post->get_the_permalink() ); ?>" target="_blank" rel="noopener noreferrer">
								<?php echo esc_html( $organizer_post->get_the_title() ); ?>
							</a>
						</div>
						<div class="evge-organizer-summary">
							<?php echo wp_kses_post( $organizer_post->get_the_summary() ); ?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
