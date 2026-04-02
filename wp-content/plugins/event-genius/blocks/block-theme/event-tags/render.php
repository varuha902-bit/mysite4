<?php
/**
 * Server-side render for Event Tags block
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

// Check if tags should be shown
$tags = $event_post->get_tags();
if ( empty( $tags ) || ! $event_post->should_show_section( 'tags' ) ) {
	return;
}

// Get block wrapper attributes (includes spacing styles)
$wrapper_attributes = get_block_wrapper_attributes();

// Render the tags HTML matching the content-after.php template
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="evge-event-list-tags evge-single-event-section">
	<h3 class="evge-event-meta-heading"><?php echo esc_html( $event_post->tags_heading() ); ?></h3>
	<div class="evge-tags evge-pill-link-wrap">
		<?php foreach ( $tags as $tag ) : ?>
			<a href="<?php echo esc_url( get_tag_link( $tag['id'] ) ); ?>" class="evge-pill-link evge-beige">
				<?php echo esc_html( $tag['label'] ); ?>
			</a>
		<?php endforeach; ?>
	</div>
	</div>
</div>
