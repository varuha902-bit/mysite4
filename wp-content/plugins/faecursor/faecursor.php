<?php
/**
 * @package FaeCursor
 */
/*
Plugin Name: FaeCursor – Interaction Effects Toolkit
Description: Bring your WordPress site to life with interactive cursor, keyboard, and screen effects — built for smooth performance and full control.
Version: 1.2.2
Author: FaeCursor Plugin Team
Author URI: https://faecursor.com
License: GPLv2 or later
Text Domain: faecursor
*/

/**
 * Prevent direct access to this file.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'You are not allowed to access this file directly.' );
}

/**
 * Load conflict handler class
 */
require_once __DIR__ . '/includes/class-fae-cursor-conflict.php';

/**
 * Initialize conflict prevention
 * This prevents free plugin from loading if Pro is active
 */
if ( Fae_Cursor_Conflict_Free::init() ) {
	return; // Exit early if Pro is active
}

/**
 * Initialize Freemius SDK (WordPress.org compliant)
 * Following the same approach as Ultimate Cursor
 */
if ( ! function_exists( 'faecursor_fs' ) ) {
	// Create a helper function for easy SDK access.
	function faecursor_fs() {
		global $faecursor_fs;

		if ( ! isset( $faecursor_fs ) ) {
			// Activate multisite network integration.
			if ( ! defined( 'WP_FS__PRODUCT_22561_MULTISITE' ) ) {
				define( 'WP_FS__PRODUCT_22561_MULTISITE', true );
			}

			// Include Freemius SDK.
			require_once dirname( __FILE__ ) . '/vendor/freemius/start.php';

			$faecursor_fs = fs_dynamic_init( array(
				'id'                  => '22561',
				'slug'                => 'faecursor',
				'premium_slug'        => 'faecursor-pro',
				'type'                => 'plugin',
				'public_key'          => 'pk_22357c6e5eb4d6802700ca1aa120d',
				'is_premium'          => false,
				'is_premium_only'     => false,
				'has_addons'          => false,
				'has_paid_plans'      => true,
				'is_live'             => true,
				'is_org_compliant'    => true,
				// No parallel_activation - Pro REPLACES Free (standalone model)
				'menu'                => array(
					'slug'        => 'fae_cursor',
					'first-path'  => 'admin.php?page=fae_cursor',
					'support'     => false,
					'contact'     => false,
					'pricing'     => true,
				),
			) );
		}

		return $faecursor_fs;
	}

	// Init Freemius.
	faecursor_fs();
	// Signal that SDK was initiated.
	do_action( 'faecursor_fs_loaded' );
}

// Plugin Directory.
if ( ! defined( 'FAE_CURSOR_DIR' ) ) {
	define( 'FAE_CURSOR_DIR', __DIR__ );
}

// Plugin URL.
if ( ! defined( 'FAE_CURSOR_URL' ) ) {
	define( 'FAE_CURSOR_URL', plugins_url( '/', __FILE__ ) );
}

// Include effects configuration
require_once FAE_CURSOR_DIR . '/config/fae-cursor-effects-config.php';
require_once FAE_CURSOR_DIR . '/config/fae-keyboard-effects-config.php';
require_once FAE_CURSOR_DIR . '/config/fae-particle-effects-config.php';

// Custom Debug Constant, intended for developer use.
if ( ! defined( 'FAE_CURSOR_DEBUG' ) ) {
	define( 'FAE_CURSOR_DEBUG', false ); // Change to true for debugging
}

// Constants.
define( 'FAE_CURSOR_PLUGIN_NAME', 'faecursor' ); // Updated to match text domain
define( 'FAE_CURSOR_VERSION', '1.2.2' ); // Update this version as necessary

// Load plugin classes
require_once FAE_CURSOR_DIR . '/includes/class-fae-cursor-loader.php';
require_once FAE_CURSOR_DIR . '/includes/class-fae-cursor-review.php';

// Initialize the plugin
Fae_Cursor_Loader::init();

// Initialize review system
Fae_Cursor_Review::init();

// Backward compatibility functions
if ( ! function_exists( 'fae_cursor_get_default_options' ) ) {
	function fae_cursor_get_default_options() {
		return Fae_Cursor_Settings::get_default_options();
	}
}

if ( ! function_exists( 'fae_detectDeviceType' ) ) {
	function fae_detectDeviceType() {
		return Fae_Cursor_Device::detect();
	}
}

if ( ! function_exists( 'fae_cursor_get_effect_icon_svg' ) ) {
	function fae_cursor_get_effect_icon_svg($effect_id) {
		return Fae_Cursor_Admin::get_effect_icon_svg($effect_id);
	}
}

// Legacy function for backward compatibility
if ( ! function_exists( 'fae_cursor_options_page' ) ) {
	function fae_cursor_options_page() {
		Fae_Cursor_Admin::render_options_page();
	}
}
