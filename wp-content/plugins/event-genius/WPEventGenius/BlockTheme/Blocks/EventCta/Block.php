<?php
namespace WPEventGenius\BlockTheme\Blocks\EventCta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\BlockTheme\Blocks\BaseBlock;

/**
 * Event CTA Block
 * 
 * Displays event cost, remaining spots, and registration button.
 * This is a server-side rendered block for block themes.
 */
class Block extends BaseBlock {
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'event-cta', 'wp-event-genius/event-cta' );
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

		// Render the CTA HTML matching the content-after.php template
		ob_start();
		?>
		<div class="evge-single-event-cta evge-sticky evge-dynamic-content"<?php echo \WPEventGenius\Common\Utils\DynamicContentHelper::get_data_attributes( 'event-cta', $event_post->get_the_id(), 'event-cta' ); ?>>
			<?php
			// Only show cost section if there's an actual amount
			$cost_amount = $event_post->get_the_cost_amount();
			if ( ! empty( $cost_amount ) ) : ?>
				<div class="evge-single-event-cost">
					<?php echo esc_html( $event_post->get_the_cost_display() ); ?>
				</div>
			<?php endif; ?>

			<?php if ( $event_post->get_allow_registration() === 'enabled' && $event_post->should_show_section( 'capacity' ) && ! $event_post->registration_has_filled() && ! $event_post->registration_has_closed() ) : ?>
				<div class="evge-single-event-capacity">
					<span><?php echo wp_kses_post( $event_post->get_registration_capacity_text() ); ?></span>
				</div>
			<?php endif; ?>

			<?php 
			do_action( 'evge_event_single_cta_before', $event_post );
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $event_post->get_the_cta( 'single' );
			
			do_action( 'evge_event_single_cta_after', $event_post );
			?>
		</div>
		<?php
		return ob_get_clean();
	}
}
