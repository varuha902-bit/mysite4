<?php
namespace WPEventGenius\BlockTheme\Blocks\OrganizerContent;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\BlockTheme\Blocks\BaseBlock;

/**
 * Organizer Content Block
 * 
 * Renders the organizer content using the existing template partials.
 * This block is hidden from the inserter and only used in block templates.
 */
class Block extends BaseBlock {
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'organizer-content', 'wp-event-genius/organizer-content' );
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
		// Get organizer ID from context or current post
		$organizer_id = ! empty( $block->context['postId'] ) ? $block->context['postId'] : get_the_ID();

		// Verify this is an organizer post type
		if ( get_post_type( $organizer_id ) !== EVGE_ORGANIZER_POST_TYPE ) {
			if ( current_user_can( 'edit_posts' ) ) {
				return '<div class="evge-block-notice">' . esc_html__( 'This block only works with organizer posts.', 'event-genius' ) . '</div>';
			}
			return '';
		}

		// Request modal since organizer pages use modal triggers
		EVGE()->modal_service()->request_modal();

		// Set up global post for templates that use get_the_ID()
		global $post;
		$original_post = $post;
		$post = get_post( $organizer_id );
		setup_postdata( $post );

		ob_start();

		// Content before (featured image, contact info, social links, title)
		$before_path = EVGE()->template_manager()->locate_template( 'organizers/single/partials/content-before.php' );
		if ( ! $before_path ) {
			$before_path = trailingslashit( EVGE_PLUGIN_PATH ) . 'templates/event-genius/organizers/single/partials/content-before.php';
		}
		if ( file_exists( $before_path ) ) {
			include $before_path;
		}

		// Main content
		$content_path = EVGE()->template_manager()->locate_template( 'organizers/single/partials/content.php' );
		if ( ! $content_path ) {
			$content_path = trailingslashit( EVGE_PLUGIN_PATH ) . 'templates/event-genius/organizers/single/partials/content.php';
		}
		if ( file_exists( $content_path ) ) {
			include $content_path;
		}

		// Content after (events)
		$after_path = EVGE()->template_manager()->locate_template( 'organizers/single/partials/content-after.php' );
		if ( ! $after_path ) {
			$after_path = trailingslashit( EVGE_PLUGIN_PATH ) . 'templates/event-genius/organizers/single/partials/content-after.php';
		}
		if ( file_exists( $after_path ) ) {
			include $after_path;
		}

		wp_reset_postdata();
		$post = $original_post;

		return ob_get_clean();
	}
}
