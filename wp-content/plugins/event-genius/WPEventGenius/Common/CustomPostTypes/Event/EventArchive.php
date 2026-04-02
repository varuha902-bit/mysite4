<?php
namespace WPEventGenius\Common\CustomPostTypes\Event;

use WPEventGenius\Common\Utils\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class EventArchive {

	public function __construct(){

	}

	public function init_custom_hooks() {
		add_filter( 'archive_template', array( $this, 'maybe_alter_template' ), 10, 1 );
		add_filter( 'search_template',  array( $this, 'maybe_alter_template' ), 10, 1 );
		add_filter( 'the_content', array( $this, 'maybe_alter_content' ), 10, 1 );
		add_action( 'pre_get_posts', array( $this, 'maybe_alter_query' ), 10, 1 );
		
		// Hide title and featured image on archive pages
		add_filter( 'the_title', array( $this, 'maybe_hide_title' ), 10, 2 );
		add_filter( 'post_thumbnail_html', array( $this, 'maybe_hide_thumbnail' ), 10, 5 );
		add_filter( 'get_the_title', array( $this, 'maybe_hide_title' ), 10, 2 );
		
		// Hide archive page title
		add_filter( 'get_the_archive_title', array( $this, 'maybe_hide_archive_title' ), 10, 1 );
		add_filter( 'single_term_title', array( $this, 'maybe_hide_archive_title' ), 10, 1 );
		add_filter( 'single_cat_title', array( $this, 'maybe_hide_archive_title' ), 10, 1 );
		add_filter( 'single_tag_title', array( $this, 'maybe_hide_archive_title' ), 10, 1 );
	}

	public function is_cpt_page() {
		return is_post_type_archive( EVGE_EVENT_POST_TYPE );
	}

	public function maybe_alter_template( $template ) {
		if ( ! $this->is_target_archive() ) {
			return $template;
		}

		// For block themes, always return the default template
		if ( wp_is_block_theme() ) {
			return $template;
		}

		// For traditional themes, use the plugin template
		return trailingslashit( EVGE_PLUGIN_PATH ) . 'templates/event-genius/events/archive/events-archive.php';
	}

	public function maybe_alter_content( $content ) {
		if ( ! $this->is_target_archive() ) {
			return $content;
		}

		// For block themes, always return the default content
		if ( wp_is_block_theme() ) {
			return $content;
		}

		// Only show the calendar once, not for every post in the loop
		static $calendar_shown = false;
		if ( $calendar_shown ) {
			return ''; // Return empty content for subsequent posts
		}
		$calendar_shown = true;
		
		$template_path = EVGE()->template_manager()->locate_template('events/archive/archive-content.php');
		if ($template_path) {
			ob_start();
			include $template_path;
			return ob_get_clean();
		}
		
		return '';

		return $content;
	}

	public function maybe_alter_query( $query ) {
		if ( ! $this->is_target_archive() ) {
			return $query;
		}
		if ( is_admin() ) {
			return $query;
		}

		// For block themes, always return the default query
		if ( wp_is_block_theme() ) {
			return $query;
		}

		// Only modify the main query, not custom queries
		if ( ! $query->is_main_query() ) {
			return $query;
		}

		return $query;
	}

	public function maybe_hide_title( $title, $post_id = null ) {
		if ( ! $this->is_target_archive() ) {
			return $title;
		}
		
		// Only hide title for the main post in the loop on archive pages
		if ( in_the_loop() && is_main_query() ) {
			return '';
		}
		
		return $title;
	}

	public function maybe_hide_thumbnail( $html, $post_id, $thumbnail_id, $size, $attr ) {
		if ( ! $this->is_target_archive() ) {
			return $html;
		}
		
		// Only hide thumbnail for the main post in the loop on archive pages
		if ( in_the_loop() && is_main_query() ) {
			return '';
		}
		
		return $html;
	}

	public function maybe_hide_archive_title( $title ) {
		if ( ! $this->is_target_archive() ) {
			return $title;
		}
		
		return '';
	}

	public function is_target_archive() {
		if ( is_tax( EVGE_EVENT_TAG_TYPE ) ) {
			return true;
		}
		if ( is_tax( EVGE_EVENT_CATEGORY_TYPE ) ) {
			return true;
		}
		if ( is_post_type_archive( EVGE_EVENT_POST_TYPE ) ) {
			return true;
		}

		return false;
	}

	public function enqueue( $screen ) {
		if ( ! $this->is_target_archive() ) {
			return;
		}
		EVGE()->style_service()->enqueue_style('evge_calendar');
		EVGE()->style_service()->enqueue_style('evge_single_post');
		EVGE()->script_service()->enqueue_script('evge_calendar');
	}

}