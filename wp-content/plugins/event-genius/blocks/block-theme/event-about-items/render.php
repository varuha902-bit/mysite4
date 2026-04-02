<?php
/**
 * Server-side render for Event About Items block
 *
 * @var array    $attributes Block attributes
 * @var string   $content   Block content
 * @var WP_Block $block     Block instance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\Common\Event\EventPost;
use WPEventGenius\Common\Utils\DynamicContentHelper;
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

// Check if about section should be shown
if ( ! $event_post->should_show_section( 'about_details' ) ) {
	return;
}

// Get about items for single view
$about_items = $event_post->get_the_about_items( 'single' );

if ( empty( $about_items ) ) {
	return;
}

// Get block wrapper attributes (includes spacing styles)
$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'evge-block-event-about-items' ) );

// Render the about items HTML matching the content-before.php template
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="evge-single-about-details evge-event-meta-row">
	<?php foreach ( $about_items as $about_item ) : 
		$atts = '';
		if ( ! empty( $about_item['atts'] ) ) {
			foreach ( $about_item['atts'] as $att => $value ) {
				$atts .= ' ' . esc_attr( $att ) . '="' . esc_attr( $value ) . '"';
			}
		}
		if ( ! empty( $about_item['link'] ) ) : 
			if ( ! empty( $about_item['link'] ) ) {
				$atts .= ' href="' . esc_url( $about_item['link'] ) . '"';
			}
		?>
			<a class="evge-modal-trigger" 
			   href="<?php echo esc_url( $about_item['link'] ); ?>"
			   role="button"
			   aria-haspopup="dialog"
			   aria-expanded="false"
			   aria-controls="evge-modal"
			   <?php 
			   // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			   echo $atts; ?>>
		<?php endif; ?>
			<div id="evge-about-detail-<?php echo esc_attr( $about_item['slug'] ); ?>" 
				 class="evge-about-detail-<?php echo esc_attr( $about_item['slug'] ); ?> evge-about-detail">
				<?php if ( ! empty( $about_item['icon'] ) ) {
					Icon::output( $about_item['icon'] );
				}
				?>
				<?php if ( ! empty( $about_item['is_dynamic'] ) ) : ?>
					<span class="evge-dynamic-content"<?php echo DynamicContentHelper::get_data_attributes( $about_item['content_type'], $about_item['event_id'], $about_item['update_endpoint'] ); ?>><?php echo esc_html( $about_item['text'] ); ?></span>
				<?php else : ?>
					<span><?php echo esc_html( $about_item['text'] ); ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $about_item['down_caret'] ) ) : ?>
					<?php Icon::output( 'down-carat' ); ?>
				<?php endif; ?>
			</div>
		<?php if ( ! empty( $about_item['link'] ) ) : ?>
			</a>
		<?php endif; ?>
	<?php endforeach; ?>
	</div>
</div>
