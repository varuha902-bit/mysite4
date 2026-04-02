<?php
namespace WPEventGenius\BlockTheme\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base Block Class
 * 
 * Provides common functionality for all Event Genius block theme blocks.
 * Styles are handled globally via BlockThemeTemplateService.
 */
abstract class BaseBlock {
	
	/**
	 * Block directory name (e.g., 'event-date')
	 *
	 * @var string
	 */
	protected $block_dir;

	/**
	 * Block namespace (e.g., 'wp-event-genius/event-date')
	 *
	 * @var string
	 */
	protected $block_namespace;

	/**
	 * Constructor
	 *
	 * @param string $block_dir Block directory name
	 * @param string $block_namespace Block namespace
	 */
	public function __construct( $block_dir, $block_namespace ) {
		$this->block_dir = $block_dir;
		$this->block_namespace = $block_namespace;
	}

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
		// Early exit if not a block theme
		if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
			return;
		}
		if ( is_admin() ) {
			if ( ! wp_style_is( 'evge_common', 'registered' ) ) {
				wp_register_style(
					'evge_common',
					trailingslashit( EVGE_PLUGIN_URL ) . 'assets/css/front-end/evge-common.css',
					array(),
					EVGE_VERSION
				);
			}

			if ( ! wp_style_is( 'evge_single_post', 'registered' ) ) {
				wp_register_style(
					'evge_single_post',
					trailingslashit( EVGE_PLUGIN_URL ) . 'assets/css/front-end/evge-single-post.css',
					array( 'evge_common' ),
					EVGE_VERSION
				);
			}
		}
		// Register the block using block.json file
		$block_json_path = EVGE_PLUGIN_PATH . 'blocks/block-theme/' . $this->block_dir . '/block.json';
		
		if ( file_exists( $block_json_path ) ) {
			// Register from block.json with render callback
			register_block_type(
				$block_json_path,
				array(
					'render_callback' => array( $this, 'render_block' ),
				)
			);
		} else {
			// Fallback to PHP registration
			register_block_type(
				$this->block_namespace,
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
	}

	/**
	 * Render the block
	 * 
	 * This method must be implemented by child classes.
	 *
	 * @param array    $attributes Block attributes
	 * @param string   $content   Block content
	 * @param WP_Block $block     Block instance
	 * @return string Rendered HTML
	 */
	abstract public function render_block( $attributes, $content, $block );
}
