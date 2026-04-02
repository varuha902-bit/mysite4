<?php
namespace WPEventGenius\BlockTheme\Blocks\EventAboutItems;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\BlockTheme\Blocks\BaseBlock;

/**
 * Event About Items Block
 * 
 * Displays event about items including cost, capacity, and other details.
 * This is a server-side rendered block for block themes.
 */
class Block extends BaseBlock {
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'event-about-items', 'wp-event-genius/event-about-items' );
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
		
		// Set up registration counter if needed
		$factory = new \WPEventGenius\Common\Services\RegistrationObjectFactory();
		$event_post->set_registration_counter( $factory->create_registration_counter( $event_id, new \WPEventGenius\Common\Database() ) );

		// Check if about section should be shown
		if ( ! $event_post->should_show_section( 'about_details' ) ) {
			return '';
		}

		// Get about items for single view
		$about_items = $event_post->get_the_about_items( 'single' );

		if ( empty( $about_items ) ) {
			return '';
		}

		// Render the about items HTML matching the content-before.php template
		ob_start();
		?>
		<div class="evge-block-event-about-items">
			<div class="evge-single-about-details">
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
								\WPEventGenius\Common\Utils\Icon::output( $about_item['icon'] );
							}
							?>
							<span><?php echo esc_html( $about_item['text'] ); ?></span>
							<?php if ( ! empty( $about_item['down_caret'] ) ) : ?>
								<?php \WPEventGenius\Common\Utils\Icon::output( 'down-carat' ); ?>
							<?php endif; ?>
						</div>
					<?php if ( ! empty( $about_item['link'] ) ) : ?>
						</a>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
