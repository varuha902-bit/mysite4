<?php
/**
 * Server-side render for Event Content Heading block
 *
 * @var array    $attributes Block attributes
 * @var string   $content   Block content
 * @var WP_Block $block     Block instance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\Common\Event\EventPost;

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

// Check if about section should be shown and if there's content
$summary = $event_post->get_the_summary();
if ( ! $event_post->should_show_section( 'about_details' ) || empty( $summary ) ) {
	return;
}

// Get block wrapper attributes (includes spacing styles)
$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'evge-event-meta-heading' ) );

// Render the heading matching the content-before.php template
?>
<h3 <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $event_post->about_event_heading() ); ?></h3>
