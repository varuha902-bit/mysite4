<?php

namespace WPEventGenius\Common\Services;

use WPEventGenius\Common\Database;
use WPEventGenius\Admin\Roles\EventManagerRole;
use WPEventGenius\Admin\CustomPostTypes\Event;
use WPEventGenius\Admin\CustomPostTypes\Venue;
use WPEventGenius\Admin\CustomPostTypes\Organizer;
use WPEventGenius\Admin\CustomPostTypes\Series;
use WPEventGenius\Common\Taxonomies\CalendarTaxonomy;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class ActivationService {

	public function __construct() {

	}
	public function init_hooks() {
	}

	/**
	 * Handle plugin activation
	 * 
	 * @param bool $network_wide Whether to activate network-wide in multisite
	 */
	public function activate($network_wide = false) {
		global $wpdb;

		// If this is a multisite network activation, create tables for each site
		if ($network_wide && is_multisite()) {
			// Get all blog IDs
			$blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
			
			foreach ($blog_ids as $blog_id) {
				switch_to_blog($blog_id);
				$this->activate_single_site();
				restore_current_blog();
			}
		} else {
			$this->activate_single_site();
		}
	}

	/**
	 * Handle activation for a single site
	 */
	private function activate_single_site() {
		$db = new Database();
		
		// Initialize cron service and schedule cleanup
		$cron_service = new CronService($db);
		$cron_service->ensure_scheduled();
		
		$db_service = new DBManagerService();
		$db_service->create_db_tables();

        // Set default settings
        $settings = get_option('evge_settings', array());
        if (!isset($settings['preserve_data'])) {
            $settings['preserve_data'] = 'enabled';
            update_option('evge_settings', $settings);
        }

        // Create event manager role
        $event_manager_role = new EventManagerRole();
        $event_manager_role->create_role();

		// Flag to run slug conflict check on next request (after other plugins have registered CPTs).
		update_option( \WPEventGenius\Common\Services\CptSlugService::OPTION_KEY_PENDING_CHECK, true );

		// Register custom post types and taxonomies before flushing rewrite rules
		$this->register_custom_post_types();

		// Flush rewrite rules after all post types are registered
		flush_rewrite_rules();
	}

	/**
	 * Register all custom post types and taxonomies
	 * This ensures they're registered before flushing rewrite rules during activation
	 */
	private function register_custom_post_types() {
		// Use SeriesPro for pro versions, Series for free version
		$series_class = Series::class;
		if ( defined( 'EVGE_FREE_VERSION' ) && ! EVGE_FREE_VERSION ) {
			// Pro version - use SeriesPro
			$series_class = \WPEventGenius\Admin\Pro\CustomPostTypes\SeriesPro::class;
		}

		// Register custom post types
		$custom_post_types = array(
			new Event(),
			new Venue(),
			new Organizer(),
			new $series_class(),
		);

		foreach ( $custom_post_types as $custom_post_type ) {
			$custom_post_type->register_taxonomies();
			$custom_post_type->register();
		}

		// Register Calendar taxonomy
		$calendar_taxonomy = new CalendarTaxonomy();
		$calendar_taxonomy->register();
	}
}