<?php
namespace WPEventGenius\Blocks\EventDate;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\Common\Event\EventPost;
use WPEventGenius\Common\Utils\Icon;

/**
 * Event Date Block
 * 
 * Displays event date information including recurrence and full date.
 * This is a server-side rendered block for block themes.
 */
class Block {
	
	/**
	 * Initialize the block
	 */
	public function init() {
		$this->register_block();
	}

	/**
	 * Register the block
	 */
	public function register_block() {
		// Register the block using block.json file
		$block_json_path = EVGE_PLUGIN_PATH . 'blocks/event-date/block.json';
		
		if ( file_exists( $block_json_path ) ) {
			// Register from block.json (preferred method)
			register_block_type( $block_json_path );
		} else {
			// Fallback to PHP registration
			register_block_type(
				'wp-event-genius/event-date',
				array(
					'render_callback' => array( $this, 'render_block' ),
					'attributes'      => array(
						'eventId' => array(
							'type'    => 'number',
							'default' => 0,
						),
					),
					'uses_context'    => array( 'postId', 'postType' ),
				)
			);
		}
		
		// Enqueue styles needed for the date display
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	/**
	 * Enqueue styles for the block
	 */
	public function enqueue_styles() {
		EVGE()->style_service()->enqueue_style( 'evge_common' );
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
		$event_post = new EventPost( $event_id );
		
		// Set up registration counter if needed
		$factory = new \WPEventGenius\Common\Services\RegistrationObjectFactory();
		$event_post->set_registration_counter( $factory->create_registration_counter( $event_id, new \WPEventGenius\Common\Database() ) );

		// Check if date section should be shown
		if ( ! $event_post->should_show_section( 'date' ) ) {
			return '';
		}

		// Render the date HTML matching the event-meta.php template
		ob_start();
		?>
		<p class="evge-meta-date-summary evge-event-meta-row">
			<?php Icon::output( 'calendar' ); ?>
			<span>
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $event_post->recurrence_display() . esc_html( $event_post->get_the_full_date() );
				?>
			</span>
		</p>
		<?php
		return ob_get_clean();
	}
}
