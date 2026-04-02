<?php
/**
 * Event Genius Common Initialization
 * 
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin file and path constants
// Determine the correct plugin file based on the version
if ( ! defined( 'EVGE_PLUGIN_FILE' ) ) {
	$plugin_filename = 'event-genius-pro.php'; // Default to pro
	
	if ( defined( 'EVGE_FREE_VERSION' ) && EVGE_FREE_VERSION ) {
		$plugin_filename = 'event-genius.php';
	} elseif ( defined( 'EVGE_TIER' ) && EVGE_TIER === 'basic' ) {
		$plugin_filename = 'event-genius-pro.php';
	} elseif ( defined( 'EVGE_TIER' ) && EVGE_TIER === 'standard' ) {
		$plugin_filename = 'event-genius-standard.php';
	} elseif ( defined( 'EVGE_TIER' ) && EVGE_TIER === 'premium' ) {
		$plugin_filename = 'event-genius-premium.php';
	} elseif ( defined( 'EVGE_TIER' ) && EVGE_TIER === 'advanced' ) {
		$plugin_filename = 'event-genius-advanced.php';
	}
	
	define( 'EVGE_PLUGIN_FILE', dirname( dirname( __FILE__ ) ) . '/' . $plugin_filename );
}
if ( ! defined( 'EVGE_PLUGIN_BASENAME' ) ) {
	define( 'EVGE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}
if ( ! defined( 'EVGE_PLUGIN_PATH' ) ) {
	define( 'EVGE_PLUGIN_PATH', plugin_dir_path( EVGE_PLUGIN_FILE ) );
}
if ( ! defined( 'EVGE_PLUGIN_URL' ) ) {
	define( 'EVGE_PLUGIN_URL', plugin_dir_url( EVGE_PLUGIN_FILE ) );
}

// Template paths
if ( ! defined( 'EVGE_ADMIN_TEMPLATE_PATH' ) ) {
	define( 'EVGE_ADMIN_TEMPLATE_PATH', trailingslashit( EVGE_PLUGIN_PATH ) . 'admin/templates/' );
}
if ( ! defined( 'EVGE_TEMPLATE_PATH' ) ) {
	define( 'EVGE_TEMPLATE_PATH', trailingslashit( EVGE_PLUGIN_PATH ) . 'templates/' );
}

// Admin settings
if ( ! defined( 'EVGE_ADMIN_EVENTS_PER_PAGE' ) ) {
	define( 'EVGE_ADMIN_EVENTS_PER_PAGE', 10 );
}

// Store URL for licensing and updates
if ( ! defined( 'EVGE_STORE_URL' ) ) {
	define( 'EVGE_STORE_URL', 'https://wpeventgenius.com' );
}

// Item ID for licensing and updates
if ( ! defined( 'EVGE_ITEM_ID' ) ) {
	$item_id = '0'; // Default to Free
	
	// Determine item ID based on tier
	if ( defined( 'EVGE_FREE_VERSION' ) && EVGE_FREE_VERSION ) {
		// Free version
		$item_id = '0';
	} elseif ( defined( 'EVGE_TIER' ) ) {
		switch ( EVGE_TIER ) {
			case 'standard':
				$item_id = '370';
				break;
			case 'pro':
				// Basic tier
				$item_id = '439';
				break;
			case 'premium':
				$item_id = '1164';
				break;
			case 'advanced':
				$item_id = '1230';
				break;
			default:
				// All other tiers
				$item_id = '555';
				break;
		}
	}
	
	define( 'EVGE_ITEM_ID', $item_id );
}

// Post types
if ( ! defined( 'EVGE_EVENT_POST_TYPE' ) ) {
	define( 'EVGE_EVENT_POST_TYPE', 'evge_event' );
}
if ( ! defined( 'EVGE_VENUE_POST_TYPE' ) ) {
	define( 'EVGE_VENUE_POST_TYPE', 'evge_venue' );
}
if ( ! defined( 'EVGE_ORGANIZER_POST_TYPE' ) ) {
	define( 'EVGE_ORGANIZER_POST_TYPE', 'evge_organizer' );
}

if ( ! defined( 'EVGE_SERIES_POST_TYPE' ) ) {
	define( 'EVGE_SERIES_POST_TYPE', 'evge_series' );
	define('EVGE_EVENT_SERIES_META_PREFIX', 'evge_series_');
}

// Taxonomies
if ( ! defined( 'EVGE_EVENT_CATEGORY_TYPE' ) ) {
	define( 'EVGE_EVENT_CATEGORY_TYPE', 'evge_event_cat' );
}
if ( ! defined( 'EVGE_EVENT_TAG_TYPE' ) ) {
	define( 'EVGE_EVENT_TAG_TYPE', 'evge_event_tag' );
}

// Load autoloaders
// First load the prefixed vendor autoloader if it exists (for third-party dependencies)
if (file_exists(EVGE_PLUGIN_PATH . 'build/prefixed/vendor/autoload.php')) {
    require_once EVGE_PLUGIN_PATH . 'build/prefixed/vendor/autoload.php';
} 

// Then load the plugin's autoloader for WPEventGenius namespace
require_once EVGE_PLUGIN_PATH . 'vendor/autoload.php';


/**
 * Utility functions for version checking and initialization
 */

/**
 * Check if this is the free version
 */
function evge_is_free_version() {
	return defined( 'EVGE_FREE_VERSION' ) && EVGE_FREE_VERSION;
}

/**
 * Check if this is the pro version
 */
function evge_is_pro_version() {
	return defined( 'EVGE_FREE_VERSION' ) && ! EVGE_FREE_VERSION;
}

/**
 * Get the current tier
 */
function evge_get_tier() {
	return defined( 'EVGE_TIER' ) ? EVGE_TIER : 'free';
}

/**
 * Check if the current tier is free
 */
function evge_is_free_tier() {
	return evge_get_tier() === 'free';
}

/**
 * Check if the current tier is pro or higher
 */
function evge_is_pro_tier() {
	$tier = evge_get_tier();
	return in_array( $tier, [ 'pro', 'standard', 'premium', 'advanced' ] );
}

/**
 * Check if the current tier is standard or higher
 */
function evge_is_standard_tier() {
	$tier = evge_get_tier();
	return in_array( $tier, [ 'standard', 'premium', 'advanced' ] );
}

/**
 * Check if the current tier is premium or higher
 */
function evge_is_premium_tier() {
	$tier = evge_get_tier();
	return in_array( $tier, [ 'premium', 'advanced' ] );
}

/**
 * Check if the current tier is advanced
 */
function evge_is_advanced_tier() {
	return evge_get_tier() === 'advanced';
}

/**
 * Check if a specific tier is active
 */
function evge_is_tier( $tier ) {
	return evge_get_tier() === $tier;
}

/**
 * Get the appropriate main class name based on version
 */
function evge_get_main_class() {
	return evge_is_free_version() ? 'WPEventGenius\Main' : 'WPEventGenius\MainPro';
}

/**
 * Get the main plugin instance with the appropriate class
 */
function evge_get_main_instance() {
	$main_class = evge_get_main_class();
	
	// Ensure the class exists before trying to instantiate
	if (!class_exists($main_class)) {
		// Fallback to Main class if MainPro doesn't exist
		$main_class = 'WPEventGenius\Main';
	}
	
	return $main_class::instance();
}

/**
 * Check if a pro feature is available
 */
function evge_has_pro_feature($feature) {
	if (evge_is_free_tier()) {
		return false;
	}
	
	try {
		$main_instance = evge_get_main_instance();
		if (method_exists($main_instance, 'has_pro_feature')) {
			return $main_instance->has_pro_feature($feature);
		}
	} catch (Exception $e) {
		// If there's an error getting the instance, assume no pro features
		return false;
	}
	
	return false;
}

/**
 * Check if a tier-specific feature is available
 */
function evge_has_tier_feature($feature, $required_tier = 'pro') {
	$current_tier = evge_get_tier();
	
	// Define tier hierarchy
	$tier_hierarchy = [
		'free' => 0,
		'pro' => 1,
		'standard' => 2,
		'premium' => 3,
		'advanced' => 4
	];
	
	$current_level = isset($tier_hierarchy[$current_tier]) ? $tier_hierarchy[$current_tier] : 0;
	$required_level = isset($tier_hierarchy[$required_tier]) ? $tier_hierarchy[$required_tier] : 1;
	
	return $current_level >= $required_level;
}

/**
 * Get pro features list
 */
function evge_get_pro_features() {
	if (evge_is_free_tier()) {
		return array();
	}
	
	try {
		$main_instance = evge_get_main_instance();
		if (method_exists($main_instance, 'get_pro_features')) {
			return $main_instance->get_pro_features();
		}
	} catch (Exception $e) {
		// If there's an error getting the instance, return empty array
		return array();
	}
	
	return array();
}

/**
 * Get pro template manager
 */
function evge_get_pro_template_manager() {
	if (evge_is_free_tier()) {
		return null;
	}
	
	try {
		$main_instance = evge_get_main_instance();
		if (method_exists($main_instance, 'pro_template_manager')) {
			return $main_instance->pro_template_manager();
		}
	} catch (Exception $e) {
		return null;
	}
	
	return null;
}

/**
 * Get pro admin template manager
 */
function evge_get_pro_admin_template_manager() {
	if (evge_is_free_tier()) {
		return null;
	}
	
	try {
		$main_instance = evge_get_main_instance();
		if (method_exists($main_instance, 'pro_admin_template_manager')) {
			return $main_instance->pro_admin_template_manager();
		}
	} catch (Exception $e) {
		return null;
	}
	
	return null;
}

/**
 * Load a pro template
 */
function evge_get_pro_template($template_name, $args = array(), $echo = true) {
	$template_manager = evge_get_pro_template_manager();
	if (!$template_manager) {
		return '';
	}
	
	return $template_manager->get_pro_template($template_name, $args, $echo);
}

/**
 * Load a pro admin template
 */
function evge_get_pro_admin_template($template_name, $args = array(), $echo = true) {
	$template_manager = evge_get_pro_admin_template_manager();
	if (!$template_manager) {
		return '';
	}
	
	return $template_manager->get_pro_admin_template($template_name, $args, $echo);
}

/**
 * Check if a pro template exists
 */
function evge_pro_template_exists($template_name) {
	$template_manager = evge_get_pro_template_manager();
	if (!$template_manager) {
		return false;
	}
	
	return $template_manager->pro_template_exists($template_name);
}

/**
 * Check if a pro admin template exists
 */
function evge_pro_admin_template_exists($template_name) {
	$template_manager = evge_get_pro_admin_template_manager();
	if (!$template_manager) {
		return false;
	}
	
	return $template_manager->pro_admin_template_exists($template_name);
}

/**
 * Get attendance database instance for Standard tier users
 * 
 * @return \WPEventGenius\Standard\Database\StandardDatabase|false Returns StandardDatabase instance if Standard tier, false otherwise
 */
function evge_get_attendance_db() {
	if ( ! evge_is_standard_tier() ) {
		return false;
	}
	
	return new \WPEventGenius\Standard\Database\StandardDatabase();
}

/**
 * Initialize database updates
 * Run early to ensure database is up to date before other services
 * This runs for all tiers (free, pro, standard)
 */
function evge_init_updates() {
	$update_service = new \WPEventGenius\Common\Services\UpdateService();
	// Check for updates immediately
	$update_service->maybe_run_updates();
	// Also check on admin_init as a fallback
	if ( is_admin() ) {
		add_action( 'admin_init', array( $update_service, 'maybe_run_updates' ), 1 );
	}
}
add_action( 'plugins_loaded', 'evge_init_updates', 1 );