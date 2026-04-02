<?php

namespace WPEventGenius\Common\Services;

use WPEventGenius\Common\Utils\SEODetector;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

/**
 * SEO Service
 * 
 * Handles SEO-related functionality for Event Genius, including
 * sitemap filtering for recurring events.
 * 
 * @package WPEventGenius
 * @since 1.6.0
 */
class SEOService {

	/**
	 * Initialize hooks
	 * 
	 * @return void
	 */
	public function init_hooks() {
		// Only filter WordPress core sitemaps if they're enabled
		// SEO plugins often disable core sitemaps and use their own
		if ( $this->should_filter_core_sitemaps() ) {
			add_filter( 'wp_sitemaps_posts_query_args', array( $this, 'filter_event_sitemap_query' ), 10, 2 );
		}
	}

	/**
	 * Determine if we should filter WordPress core sitemaps
	 * 
	 * Only filter if:
	 * 1. WordPress core sitemaps are enabled (WP 5.5+)
	 * 2. No SEO plugin is active (SEO plugins typically disable core sitemaps)
	 * 3. Filter allows it
	 * 
	 * @return bool True if we should filter core sitemaps
	 */
	protected function should_filter_core_sitemaps() {
		// WordPress core sitemaps were introduced in 5.5
		if ( ! function_exists( 'wp_sitemaps_get_server' ) ) {
			return false;
		}

		// If an SEO plugin is active, they likely handle sitemaps themselves
		// Don't interfere with their sitemap generation
		if ( SEODetector::has_seo_plugin() ) {
			return false;
		}

		/**
		 * Filter whether to filter WordPress core sitemaps
		 * 
		 * @param bool $should_filter Whether to filter core sitemaps
		 * @return bool Modified value
		 */
		return apply_filters( 'evge_filter_core_sitemaps', true );
	}

	/**
	 * Filter WordPress core sitemap query args for events
	 * 
	 * Excludes recurrence instances from sitemaps, only including
	 * template events (parent events) to avoid sitemap bloat.
	 * 
	 * @param array  $args      WP_Query arguments
	 * @param string $post_type Post type name
	 * @return array Modified query arguments
	 */
	public function filter_event_sitemap_query( $args, $post_type ) {
		// Only filter our event post type
		if ( $post_type !== EVGE_EVENT_POST_TYPE ) {
			return $args;
		}

		/**
		 * Filter whether to exclude recurrence instances from sitemaps
		 * 
		 * @param bool $exclude_recurrences Whether to exclude recurrence instances
		 * @return bool Modified value
		 */
		$exclude_recurrences = apply_filters( 'evge_sitemap_exclude_recurrences', true );

		if ( ! $exclude_recurrences ) {
			return $args;
		}

		// Exclude recurrence instances - only include template events
		// Recurrence instances have evge_is_recurrence meta set to template ID
		// Template events either don't have this meta, or have it set to empty/false
		if ( ! isset( $args['meta_query'] ) ) {
			$args['meta_query'] = array();
		}

		// Add meta query to exclude recurrence instances
		// We want events where evge_is_recurrence is NOT set or is empty
		$args['meta_query'][] = array(
			'relation' => 'OR',
			array(
				'key'     => 'evge_is_recurrence',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => 'evge_is_recurrence',
				'value'   => '',
				'compare' => '=',
			),
		);

		/**
		 * Filter the modified sitemap query arguments
		 * 
		 * @param array  $args      Modified query arguments
		 * @param string $post_type Post type name
		 * @return array Modified query arguments
		 */
		return apply_filters( 'evge_sitemap_query_args', $args, $post_type );
	}
}
