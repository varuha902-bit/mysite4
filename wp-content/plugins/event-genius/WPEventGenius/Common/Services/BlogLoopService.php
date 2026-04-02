<?php
/**
 * Service that modifies the main blog loop to include events when the
 * "Include events in main blog loop" setting is enabled.
 *
 * @since 2.21
 */
namespace WPEventGenius\Common\Services;

use WPEventGenius\Common\Utils\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BlogLoopService {

	/**
	 * Register WordPress hooks.
	 */
	public function init_hooks() {
		add_action( 'parse_query', array( $this, 'parse_query' ), 50 );
	}

	/**
	 * Parse the main query and add events to the blog loop when the setting is enabled.
	 *
	 * @param \WP_Query $query The query object.
	 */
	public function parse_query( $query ) {
		if ( ! $query instanceof \WP_Query || is_admin() ) {
			return;
		}

		if ( ! $query->is_main_query() ) {
			return;
		}

		// Do not modify queries that explicitly request all post types.
		$query_post_types = $this->get_query_post_types( $query );
		if ( $query_post_types === array( 'any' ) ) {
			return;
		}

		// Include events on the blog homepage when the option is enabled.
		if ( $query->is_home() ) {
			if ( $this->should_include_events_in_loop() ) {
				$this->add_post_type_to_query( $query, EVGE_EVENT_POST_TYPE );
			}
			return;
		}

		// Include events on tag archives when the option is enabled.
		if ( $query->is_tag() ) {
			if ( $this->should_include_events_in_loop() ) {
				$is_event_only = $query_post_types === array( EVGE_EVENT_POST_TYPE );
				if ( ! $is_event_only ) {
					$this->add_post_type_to_query( $query, EVGE_EVENT_POST_TYPE );
				}
			}
		}
	}

	/**
	 * Whether the "Include events in main blog loop" setting is enabled.
	 *
	 * @return bool
	 */
	protected function should_include_events_in_loop() {
		$value = Settings::get( 'show_events_in_main_loop' );
		return $value === 'enabled' || $value === true;
	}

	/**
	 * Get the post type(s) currently set on the query (normalized to array).
	 *
	 * @param \WP_Query $query The query object.
	 * @return array<string> Post type slug(s).
	 */
	protected function get_query_post_types( \WP_Query $query ) {
		$post_types = (array) $query->get( 'post_type' );
		// Main blog query often has no post_type set; treat as 'post'.
		if ( $post_types === array( '' ) ) {
			$post_types = array( 'post' );
		}
		return $post_types;
	}

	/**
	 * Add one or more post types to the query (merge with existing).
	 *
	 * @param \WP_Query $query       The query object.
	 * @param string    ...$post_types Post type slug(s) to add.
	 */
	protected function add_post_type_to_query( \WP_Query $query, string ...$post_types ) {
		$current = $this->get_query_post_types( $query );
		$merged  = array_unique( array_merge( $post_types, $current ) );
		$query->set( 'post_type', $merged );
		$query->query['post_type'] = $merged;
	}
}
