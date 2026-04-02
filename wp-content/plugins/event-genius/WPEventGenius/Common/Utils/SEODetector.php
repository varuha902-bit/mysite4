<?php

namespace WPEventGenius\Common\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

/**
 * SEO Plugin Detection Utility
 *
 * Detects the active SEO plugin (only one expected) to enable compatibility handling.
 *
 * @package WPEventGenius
 * @since 1.6.0
 */
class SEODetector {

	/**
	 * Cache for active SEO plugin to avoid repeated checks
	 * 
	 * @var string|null
	 */
	private static $active_plugin_cache = null;

	/**
	 * Check if Yoast SEO is active
	 * 
	 * @return bool True if Yoast SEO is active
	 */
	public static function is_yoast_active() {
		return defined( 'WPSEO_VERSION' ) || function_exists( 'YoastSEO' );
	}

	/**
	 * Check if Rank Math is active
	 * 
	 * @return bool True if Rank Math is active
	 */
	public static function is_rank_math_active() {
		return defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' );
	}

	/**
	 * Check if All In One SEO is active
	 * 
	 * @return bool True if AIOSEO is active
	 */
	public static function is_aioseo_active() {
		return defined( 'AIOSEO_VERSION' ) || class_exists( 'AIOSEO' );
	}

	/**
	 * Check if SEOPress is active
	 * 
	 * @return bool True if SEOPress is active
	 */
	public static function is_seopress_active() {
		return defined( 'SEOPRESS_VERSION' );
	}

	/**
	 * Get the active SEO plugin identifier
	 * 
	 * Returns the first active SEO plugin found, or null if none are active.
	 * Priority order: Yoast, Rank Math, AIOSEO, SEOPress
	 * 
	 * @return string|null Active SEO plugin identifier ('yoast', 'rank_math', 'aioseo', 'seopress') or null
	 */
	public static function get_active_seo_plugin() {
		// Use cache if available
		if ( self::$active_plugin_cache !== null ) {
			return self::$active_plugin_cache;
		}

		$active = null;

		// Check in priority order
		if ( self::is_yoast_active() ) {
			$active = 'yoast';
		} elseif ( self::is_rank_math_active() ) {
			$active = 'rank_math';
		} elseif ( self::is_aioseo_active() ) {
			$active = 'aioseo';
		} elseif ( self::is_seopress_active() ) {
			$active = 'seopress';
		}

		// Cache the result
		self::$active_plugin_cache = $active;

		/**
		 * Filter the active SEO plugin
		 * 
		 * @param string|null $active Active SEO plugin identifier or null
		 * @return string|null Modified active SEO plugin identifier or null
		 */
		return apply_filters( 'evge_active_seo_plugin', $active );
	}

	/**
	 * Check if any SEO plugin is active
	 * 
	 * @return bool True if any SEO plugin is active
	 */
	public static function has_seo_plugin() {
		return self::get_active_seo_plugin() !== null;
	}

	/**
	 * Check if a specific SEO plugin is active
	 * 
	 * @param string $plugin Plugin identifier ('yoast', 'rank_math', 'aioseo', 'seopress')
	 * @return bool True if the specified plugin is active
	 */
	public static function is_plugin_active( $plugin ) {
		return self::get_active_seo_plugin() === $plugin;
	}

	/**
	 * Clear the active plugin cache
	 * 
	 * Useful for testing or when plugins are activated/deactivated
	 */
	public static function clear_cache() {
		self::$active_plugin_cache = null;
	}
}
