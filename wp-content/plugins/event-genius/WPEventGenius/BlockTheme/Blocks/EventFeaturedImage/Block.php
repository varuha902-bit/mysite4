<?php
namespace WPEventGenius\BlockTheme\Blocks\EventFeaturedImage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\BlockTheme\Blocks\BaseBlock;

/**
 * Event Featured Image Block
 * 
 * Displays the event featured image with blur effect using the exact HTML structure.
 * This is a server-side rendered block for block themes.
 */
class Block extends BaseBlock {
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'event-featured-image', 'wp-event-genius/event-featured-image' );
	}

	/**
	 * Render the block
	 *
	 * @param array    $attributes Block attributes
	 * @param string   $content   Block content
	 * @param WP_Block $block     Block instance
	 * @return string Rendered HTML
	 */
	public function render_block( $attributes, $content, $block ) {
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
				return '<div class="evge-block-notice">' . esc_html__( 'This block only works with event posts.', 'event-genius' ) . '</div>';
			}
			return '';
		}

		// Get event post object
		$event_post = new \WPEventGenius\Common\Event\EventPost( $event_id );

		// Check if featured image exists
		// Note: We don't check should_show_section() here because this is a block theme block
		// that is explicitly placed in the template, so we always want to show it if it exists
		if ( ! $event_post->has_featured_image() ) {
			return '';
		}

		// Get featured image data
		$featured_image = $event_post->get_the_featured_image( 'full' );
		$image_url = $event_post->get_featured_image_url();

		// Get block wrapper attributes (includes spacing styles)
		$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'evge-single-event-featured-image' ) );

		// Render the exact HTML structure from the PHP template
		ob_start();
		?>
		<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div class="evge-featured-image-wrapper">
				<div class="evge-featured-image-blur" style="background-image: url('<?php echo esc_url( $image_url ); ?>')"></div>
				<div class="evge-featured-image-main">
					<?php 
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $featured_image; ?>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
