<?php

namespace WPEventGenius\Common\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Service to register block theme templates and blocks for Event Genius post types
 */
class BlockThemeTemplateService {

	/**
	 * Initialize the service
	 */
	public function init() {
		// Only register templates and blocks for block themes
		if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
			return;
		}

		add_filter( 'get_block_templates', array( $this, 'register_templates' ), 10, 3 );
		
		// Register blocks on init hook (register_block_type requires init hook)
		add_action( 'init', array( $this, 'register_block_theme_blocks' ), 10 );
	}

	/**
	 * Register blocks that are only available for block themes
	 */
	public function register_block_theme_blocks() {
		// Only register for block themes
		if ( ! wp_is_block_theme() ) {
			return;
		}

		$event_date_block = new \WPEventGenius\Blocks\EventDate\Block();
		$event_date_block->init();
	}

	/**
	 * Register block theme templates
	 *
	 * @param array  $query_result Array of template objects.
	 * @param array  $query        Optional. Arguments to retrieve templates.
	 * @param string $template_type Optional. The template type (wp_template or wp_template_part).
	 * @return array Modified array of template objects.
	 */
	public function register_templates( $query_result, $query, $template_type = 'wp_template' ) {
		// Only process wp_template, not template parts
		if ( 'wp_template' !== $template_type ) {
			return $query_result;
		}

		// Check if we're querying for a specific slug
		$slug = isset( $query['slug__in'] ) ? $query['slug__in'] : null;
		$post_type = isset( $query['post_type'] ) ? $query['post_type'] : null;

		// If querying for our specific template slug, or for event post type, or no specific query
		$should_add = false;
		if ( null === $slug && null === $post_type ) {
			// General query - add our template
			$should_add = true;
		} elseif ( is_array( $slug ) && in_array( 'single-evge_event', $slug, true ) ) {
			// Querying for our specific template
			$should_add = true;
		} elseif ( EVGE_EVENT_POST_TYPE === $post_type ) {
			// Querying for event post type templates
			$should_add = true;
		}

		if ( $should_add ) {
			$template = $this->get_single_event_template();
			if ( $template ) {
				// Check if template already exists in results
				$exists = false;
				foreach ( $query_result as $existing_template ) {
					if ( isset( $existing_template->id ) && $existing_template->id === $template->id ) {
						$exists = true;
						break;
					}
				}
				if ( ! $exists ) {
					$query_result[] = $template;
				}
			}
		}

		return $query_result;
	}

	/**
	 * Get the single event template object
	 *
	 * @return WP_Block_Template|null Template object or null if not found.
	 */
	private function get_single_event_template() {
		$template_file = EVGE_PLUGIN_PATH . 'templates/block-themes/single-evge_event.html';

		if ( ! file_exists( $template_file ) ) {
			return null;
		}

		$template_content = file_get_contents( $template_file );

		// Create template object matching WordPress core structure
		$template                 = new \WP_Block_Template();
		$template->id             = 'event-genius//single-evge_event';
		$template->theme          = 'event-genius';
		$template->content        = $template_content;
		$template->slug           = 'single-evge_event';
		$template->source         = 'plugin';
		$template->type           = 'wp_template';
		$template->title          = __( 'Single Event', 'event-genius' );
		$template->description    = __( 'Template for displaying a single event', 'event-genius' );
		$template->status         = 'publish';
		$template->has_theme_file = false;
		$template->is_custom      = false; // Plugin-provided templates are not custom
		$template->post_types      = array( EVGE_EVENT_POST_TYPE );
		$template->origin          = 'plugin';
		$template->wp_id          = null; // Not a database template

		return $template;
	}
}
