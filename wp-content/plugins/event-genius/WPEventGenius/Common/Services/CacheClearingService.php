<?php

namespace WPEventGenius\Common\Services;

use WPEventGenius\Common\Utils\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Service for clearing page caches when registrations or cancellations occur
 * 
 * Supports the most common WordPress caching plugins to ensure dynamic content
 * (attendee counts, registration status, etc.) is updated immediately.
 * 
 * Supported caching plugins:
 * - WP Super Cache
 * - W3 Total Cache
 * - WP Rocket
 * - LiteSpeed Cache
 * 
 * Other caching plugins can integrate via the evge_clear_url_cache and evge_clear_post_cache hooks.
 * 
 * @package WPEventGenius
 * @since 1.0.0
 */
class CacheClearingService {

	/**
	 * Initialize the service and register hooks
	 */
	public function init_hooks() {
		// Hook into registration events
		add_action( 'evge_after_registration_submitted', array( $this, 'clear_event_cache_from_registration_group' ), 10, 1 );
		add_action( 'evge_after_bulk_registration_submitted', array( $this, 'clear_event_cache_from_bulk_registration_groups' ), 10, 1 );
		add_action( 'evge_registration_updated', array( $this, 'clear_event_cache_from_registration_id' ), 10, 3 );
		add_action( 'evge_after_successful_cancel', array( $this, 'clear_event_cache_from_registration_data' ), 10, 1 );
	}

	/**
	 * Check if dynamic content refresh is enabled
	 * 
	 * @return bool True if enabled, false if disabled
	 */
	protected function is_enabled() {
		return Settings::get( 'enable_dynamic_content_refresh' ) === 'enabled';
	}

	/**
	 * Clear cache for an event page
	 * 
	 * @param int $event_id Event ID
	 */
	public function clear_event_cache( $event_id ) {
		// Only clear cache if dynamic content refresh is enabled
		if ( ! $this->is_enabled() ) {
			return;
		}

		if ( empty( $event_id ) ) {
			return;
		}

		$event_id = absint( $event_id );
		$event_url = get_permalink( $event_id );

		if ( ! $event_url ) {
			return;
		}

		$this->clear_url_cache( $event_url );
		$this->clear_post_cache( $event_id );
	}

	/**
	 * Clear cache from bulk registration submission hook
	 * 
	 * @param array $registration_groups Array of successfully created registration group objects
	 */
	public function clear_event_cache_from_bulk_registration_groups( $registration_groups ) {
		if ( empty( $registration_groups ) || ! is_array( $registration_groups ) ) {
			return;
		}

		// Collect unique event IDs from registration groups
		$event_ids = array();
		foreach ( $registration_groups as $registration_group ) {
			if ( ! empty( $registration_group ) && method_exists( $registration_group, 'get_main' ) ) {
				$main_registration = $registration_group->get_main();
				if ( $main_registration && method_exists( $main_registration, 'get_registration_data' ) ) {
					$event_id = $main_registration->get_registration_data( 'event_id' );
					if ( $event_id ) {
						$event_ids[] = absint( $event_id );
					}
				}
			}
		}

		// Remove duplicates and clear cache for each unique event
		$event_ids = array_unique( $event_ids );
		foreach ( $event_ids as $event_id ) {
			$this->clear_event_cache( $event_id );
		}
	}

	/**
	 * Clear cache from registration data array
	 * 
	 * @param array $registration_data Registration data array
	 */
	public function clear_event_cache_from_registration_data( $registration_data ) {
		if ( empty( $registration_data ) || ! is_array( $registration_data ) ) {
			return;
		}

		$event_id = isset( $registration_data['event_id'] ) ? absint( $registration_data['event_id'] ) : 0;
		if ( $event_id ) {
			$this->clear_event_cache( $event_id );
		}
	}

	/**
	 * Clear cache from registration group object
	 * 
	 * @param object $registration_group Registration group object
	 */
	public function clear_event_cache_from_registration_group( $registration_group ) {
		if ( empty( $registration_group ) || ! method_exists( $registration_group, 'get_main' ) ) {
			return;
		}

		$main_registration = $registration_group->get_main();
		if ( ! $main_registration || ! method_exists( $main_registration, 'get_registration_data' ) ) {
			return;
		}

		$event_id = $main_registration->get_registration_data( 'event_id' );
		if ( $event_id ) {
			$this->clear_event_cache( $event_id );
		}
	}

	/**
	 * Clear cache from registration ID
	 * 
	 * @param int $registration_id Registration ID
	 * @param array $old_data Old registration data (optional, for evge_registration_updated hook)
	 * @param array $new_data New registration data (optional, for evge_registration_updated hook)
	 */
	public function clear_event_cache_from_registration_id( $registration_id, $old_data = null, $new_data = null ) {
		if ( empty( $registration_id ) ) {
			return;
		}

		$registration_id = absint( $registration_id );
		
		// Try to get event_id from new_data first (if provided from evge_registration_updated hook)
		$event_id = null;
		if ( ! empty( $new_data ) && is_array( $new_data ) && isset( $new_data['event_id'] ) ) {
			$event_id = absint( $new_data['event_id'] );
		} elseif ( ! empty( $old_data ) && is_array( $old_data ) && isset( $old_data['event_id'] ) ) {
			$event_id = absint( $old_data['event_id'] );
		} else {
			// Fallback to querying the database
			$database = new \WPEventGenius\Common\Database();
			$registration = $database->query_registration_record( $registration_id );

			if ( ! empty( $registration ) && isset( $registration[0]['event_id'] ) ) {
				$event_id = absint( $registration[0]['event_id'] );
			}
		}

		if ( $event_id ) {
			$this->clear_event_cache( $event_id );
		}
	}

	/**
	 * Clear cache for a specific URL
	 * 
	 * @param string $url URL to clear cache for
	 */
	protected function clear_url_cache( $url ) {
		// WP Super Cache
		if ( function_exists( 'wp_cache_post_change' ) ) {
			$post_id = url_to_postid( $url );
			if ( $post_id ) {
				wp_cache_post_change( $post_id );
			}
		}

		// W3 Total Cache
		if ( function_exists( 'w3tc_flush_url' ) ) {
			w3tc_flush_url( $url );
		}

		// WP Rocket
		if ( function_exists( 'rocket_clean_post' ) ) {
			$post_id = url_to_postid( $url );
			if ( $post_id ) {
				rocket_clean_post( $post_id );
			}
		}

		// LiteSpeed Cache
		if ( class_exists( '\LiteSpeed\Core' ) ) {
			do_action( 'litespeed_purge_url', $url );
		}

		// Generic hook for other caching plugins
		do_action( 'evge_clear_url_cache', $url );
	}

	/**
	 * Clear cache for a specific post ID
	 * 
	 * @param int $post_id Post ID
	 */
	protected function clear_post_cache( $post_id ) {
		if ( empty( $post_id ) ) {
			return;
		}

		$post_id = absint( $post_id );

		// WP Super Cache
		if ( function_exists( 'wp_cache_post_change' ) ) {
			wp_cache_post_change( $post_id );
		}

		// W3 Total Cache
		if ( function_exists( 'w3tc_flush_post' ) ) {
			w3tc_flush_post( $post_id );
		}

		// WP Rocket
		if ( function_exists( 'rocket_clean_post' ) ) {
			rocket_clean_post( $post_id );
		}

		// LiteSpeed Cache
		if ( class_exists( '\LiteSpeed\Core' ) ) {
			do_action( 'litespeed_purge_post', $post_id );
		}

		// Generic hook for other caching plugins
		do_action( 'evge_clear_post_cache', $post_id );
	}
}
