<?php
namespace WPEventGenius\BlockTheme\Blocks\EventCategories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\BlockTheme\Blocks\BaseBlock;

/**
 * Event Categories Block
 * 
 * Displays event categories as pill links with a heading.
 * This is a server-side rendered block for block themes.
 */
class Block extends BaseBlock {
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'event-categories', 'wp-event-genius/event-categories' );
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

		// Check if categories should be shown
		$categories = $event_post->get_categories();
		if ( empty( $categories ) || ! $event_post->should_show_section( 'categories' ) ) {
			return '';
		}

		// Render the categories HTML matching the content-after.php template
		ob_start();
		?>
		<div class="evge-event-list-categories evge-single-event-section">
			<h3 class="evge-event-meta-heading"><?php echo esc_html( $event_post->categories_heading() ); ?></h3>
			<div class="evge-categories evge-pill-link-wrap">
				<?php foreach ( $categories as $category ) : ?>
					<a href="<?php echo esc_url( get_term_link( $category['id'] ) ); ?>" class="evge-pill-link evge-beige">
						<?php echo esc_html( $category['label'] ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
