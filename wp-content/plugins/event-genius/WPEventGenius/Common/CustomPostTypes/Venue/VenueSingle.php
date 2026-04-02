<?php
namespace WPEventGenius\Common\CustomPostTypes\Venue;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

use WPEventGenius\Common\Utils\Templater;

class VenueSingle {

	public function __construct(){

	}

	public function init_custom_hooks() {
		add_filter( 'single_template', array( $this, 'maybe_alter_template' ), 10, 1 );
		add_filter( 'the_content', array( $this, 'maybe_alter_content' ), 10, 1 );

		add_action('wp_enqueue_scripts', array($this, 'enqueue'));
	}

	public function is_cpt_page() {
		return is_singular( EVGE_VENUE_POST_TYPE );
	}

	public function get_template_type() {
		return get_option('evge_settings')['venue_template'] ?? 'default';
	}

	public function maybe_alter_template( $template ) {
		if ( ! $this->is_cpt_page() ) {
			return $template;
		}

		// For block themes, always return the default template
		if ( wp_is_block_theme() ) {
			return $template;
		}

		$template_type = $this->get_template_type();

		// For custom templates, check theme directory first
		if ( $template_type === 'custom' ) {
			$theme_template = locate_template( array(
				'event-genius/single-venue.php',
				'event-genius/venues/single/single-venue.php'
			));
			if ( $theme_template ) {
				return $theme_template;
			}
		}

		// For default template or if custom template not found, use plugin template
		if ( $template_type === 'default' || ($template_type === 'custom' && !$theme_template) ) {
			$template_path = EVGE()->template_manager()->locate_template('venues/single/single-venue.php');
			if ($template_path) {
				return $template_path;
			}
			// Fallback to plugin template
			return trailingslashit( EVGE_PLUGIN_PATH ) . 'templates/event-genius/venues/single/single-venue.php';
		}

		// For theme template or fallback, use default template
		return $template;
	}

	public function maybe_alter_content( $content ) {
		if ( ! $this->is_cpt_page() ) {
			return $content;
		}

		// For block themes, always return the default content
		if ( wp_is_block_theme() ) {
			return $content;
		}

		// Request modal since venue single pages use modal triggers
		EVGE()->modal_service()->request_modal();

		$template_type = $this->get_template_type();
		
		// For traditional themes, only modify content for theme template or when custom template isn't found
		if ( $template_type === 'theme' || 
			($template_type === 'custom' && !locate_template(array('event-genius/single-venue.php', 'event-genius/venues/single/single-venue.php'))) ) {
			
			ob_start();
			
			$post_id = get_the_ID();
			$this->before_venue_content($post_id);
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $content;
			$this->after_venue_content($post_id);
			
			return ob_get_clean();
		}

		return $content;
	}

	public function before_venue_content( $venue_id = 0 ) {
		$template_path = EVGE()->template_manager()->locate_template('venues/single/partials/content-before.php');
		if ($template_path) {
			include $template_path;
			return;
		}
	}

	public function after_venue_content( $venue_id = 0 ) {
		$template_path = EVGE()->template_manager()->locate_template('venues/single/partials/content-after.php');
		if ($template_path) {
			include $template_path;
			return;
		}
	}

	public function maybe_alter_query( $query ) {
		if ( ! $this->is_cpt_page() ) {
			return $query;
		}
		return $query;
	}

	public function enqueue( $screen ) {
	}

	public function maybe_add_modal() {
		if ( $this->is_cpt_page() ) {
			$template_path = EVGE()->template_manager()->locate_template('common/modal.php');
			if ($template_path) {
				include $template_path;
			}
		}
	}

}