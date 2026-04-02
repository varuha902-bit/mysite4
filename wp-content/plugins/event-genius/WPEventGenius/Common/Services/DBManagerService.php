<?php
namespace WPEventGenius\Common\Services;

use WPEventGenius\Common\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class DBManagerService {

	public function __construct() {
	}

	public function init_hooks() {
		// Add hook for new site creation in multisite
		if (is_multisite()) {
			add_action('wp_initialize_site', array($this, 'create_tables_for_new_site'), 900);
		}
	}

	/**
	 * Create database tables for a new site in multisite
	 * 
	 * @param \WP_Site $new_site The new site object
	 */
	public function create_tables_for_new_site($new_site) {
		switch_to_blog($new_site->blog_id);
		$this->create_db_tables();
		restore_current_blog();
	}

	public function create_db_tables() {
		$db = new Database();
		$db->create_or_update_registrations_table();
		$db->create_or_update_registrations_meta_table();
		$db->create_or_update_payments_table();
		$db->create_or_update_payments_meta_table();
		$db->create_or_update_events_table();
		$db->create_or_update_event_series_relationships_table();
		$db->create_or_update_event_venue_relationships_table();
		$db->create_or_update_event_organizer_relationships_table();
	}

}