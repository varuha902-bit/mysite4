<?php
/**
 * Update Service
 * 
 * Handles database updates and migrations when the plugin version changes.
 * Uses WordPress best practices for efficient update checking and execution.
 * 
 * @package WPEventGenius
 * @since 1.3.2
 */

namespace WPEventGenius\Common\Services;

use WPEventGenius\Common\States;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UpdateService {

	/**
	 * Database version option key
	 */
	const DB_VERSION_OPTION = 'evge_db_version';

	/**
	 * Current database version
	 * Update this when database schema changes
	 */
	const CURRENT_DB_VERSION = '1.1';

	/**
	 * Transient key for update lock
	 */
	const UPDATE_LOCK_KEY = 'evge_updating_db';

	/**
	 * Update lock timeout (5 minutes)
	 */
	const UPDATE_LOCK_TIMEOUT = 300;

	/**
	 * States instance
	 * 
	 * @var States
	 */
	protected $states;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->states = new States();
	}


	/**
	 * Check if updates are needed and run them
	 */
	public function maybe_run_updates() {
		// Prevent concurrent updates
		if ( $this->is_updating() ) {
			return;
		}

		$current_version = $this->get_db_version();
		$target_version = self::CURRENT_DB_VERSION;

		// Compare versions - if current is less than target, run updates
		if ( version_compare( $current_version, $target_version, '<' ) ) {
			$this->run_updates( $current_version, $target_version );
		}
	}

	/**
	 * Get current database version
	 * 
	 * @return string Database version
	 */
	public function get_db_version() {
		$version = get_option( self::DB_VERSION_OPTION, '0.0.0' );
		return $version;
	}

	/**
	 * Set database version
	 * 
	 * @param string $version Version to set
	 */
	protected function set_db_version( $version ) {
		update_option( self::DB_VERSION_OPTION, $version );
	}

	/**
	 * Check if an update is currently running
	 * 
	 * @return bool True if update is in progress
	 */
	protected function is_updating() {
		return (bool) get_transient( self::UPDATE_LOCK_KEY );
	}

	/**
	 * Set update lock
	 */
	protected function set_update_lock() {
		set_transient( self::UPDATE_LOCK_KEY, time(), self::UPDATE_LOCK_TIMEOUT );
	}

	/**
	 * Remove update lock
	 */
	protected function remove_update_lock() {
		delete_transient( self::UPDATE_LOCK_KEY );
	}

	/**
	 * Run updates from current version to target version
	 * 
	 * @param string $current_version Current database version
	 * @param string $target_version Target database version
	 */
	protected function run_updates( $current_version, $target_version ) {
		// Set lock to prevent concurrent updates
		$this->set_update_lock();

		// Log update start
		$this->log_update( "Starting database update from {$current_version} to {$target_version}" );

		try {
			// Get list of update methods to run
			$updates_to_run = $this->get_updates_to_run( $current_version, $target_version );

			if ( empty( $updates_to_run ) ) {
				// No updates needed, just update version
				$this->set_db_version( $target_version );
				$this->remove_update_lock();
				return;
			}

			// Run each update in order
			foreach ( $updates_to_run as $version => $method ) {
				if ( ! method_exists( $this, $method ) ) {
					$this->log_update( "Update method {$method} not found for version {$version}", 'error' );
					continue;
				}

				$this->log_update( "Running update {$method} for version {$version}" );

				// Run the update method
				$result = call_user_func( array( $this, $method ) );

				if ( $result === false ) {
					$this->log_update( "Update {$method} failed for version {$version}", 'error' );
					// Continue with other updates even if one fails
				} else {
					// Update version after each successful migration
					$this->set_db_version( $version );
					$this->log_update( "Successfully updated to version {$version}" );
				}
			}

			// Set final version
			$this->set_db_version( $target_version );

			// Store update completion state
			$this->states->set_state( 'last_db_update', time() );
			$this->states->set_state( 'last_db_version', $target_version );

			$this->log_update( "Database update completed successfully" );

		} catch ( \Exception $e ) {
			$this->log_update( "Database update failed: " . $e->getMessage(), 'error' );
		} finally {
			// Always remove lock
			$this->remove_update_lock();
		}
	}

	/**
	 * Get list of updates to run based on version comparison
	 * 
	 * @param string $current_version Current version
	 * @param string $target_version Target version
	 * @return array Array of version => method_name
	 */
	protected function get_updates_to_run( $current_version, $target_version ) {
		// Define all available updates in order
		// Format: 'version' => 'method_name'
		$all_updates = array(
			'1.1' => 'update_to_1_1',
			// Add future updates here:
			// '1.3.3' => 'update_to_1_3_3',
			// '1.4.0' => 'update_to_1_4_0',
		);

		$updates_to_run = array();

		foreach ( $all_updates as $version => $method ) {
			// Only include updates that are newer than current version
			if ( version_compare( $current_version, $version, '<' ) ) {
				$updates_to_run[ $version ] = $method;
			}
		}

		return $updates_to_run;
	}

	/**
	 * Update to version 1.1
	 * 
	 * This update handles:
	 * - Adding attendee list feature support
	 * - Database schema updates for attendee lists
	 * - Migration from free to pro version
	 * - Standard version updates
	 * 
	 * @return bool True on success, false on failure
	 */
	protected function update_to_1_1() {
		global $wpdb;

		// Check if this update has already been run
		$update_key = 'db_update_1_1_complete';
		if ( $this->states->get_state( $update_key ) ) {
			$this->log_update( "Update 1.1 already completed, skipping" );
			return true;
		}

		// Start transaction
		$wpdb->query( 'START TRANSACTION' );

		try {
			// 1. Update database tables if needed
			$this->update_database_tables_1_1();

			// 2. Handle free to pro upgrade
			$this->handle_free_to_pro_upgrade();

			// 3. Handle standard version updates (database structure only)
			$this->handle_standard_version_updates();

			// 4. Update any attendee list related data (only for existing installations)
			$this->update_attendee_list_data();

			// 5. Schedule email template migration for init hook (needs WordPress fully initialized)
			// This is non-critical and can run after WordPress is ready
			if ( ! did_action( 'init' ) ) {
				add_action( 'init', array( $this, 'run_email_template_migration' ), 1 );
			} else {
				// If init already fired, run it now
				$this->run_email_template_migration();
			}

			// Mark update as complete
			$this->states->set_state( $update_key, true );
			$this->states->set_state( $update_key . '_date', time() );

			// Commit transaction
			$wpdb->query( 'COMMIT' );

			$this->log_update( "Update 1.1 completed successfully" );
			return true;

		} catch ( \Exception $e ) {
			// Rollback transaction
			$wpdb->query( 'ROLLBACK' );
			$this->log_update( "Update 1.1 failed: " . $e->getMessage(), 'error' );
			return false;
		}
	}

	/**
	 * Update database tables for version 1.1
	 */
	protected function update_database_tables_1_1() {
		// Check if Standard tier or higher is active
		if ( function_exists( 'evge_is_standard_tier' ) && evge_is_standard_tier() ) {
			$standard_db = new \WPEventGenius\Standard\Database\StandardDatabase();
			
			// Ensure all tables are up to date
			$standard_db->create_all_tables();
			
			// Check if form fields table needs the show_in_attendee_list column
			$this->ensure_attendee_list_columns();
		}
	}

	/**
	 * Ensure attendee list columns exist in form fields table
	 */
	protected function ensure_attendee_list_columns() {
		global $wpdb;

		// Only run if Standard tier or higher is active
		if ( ! function_exists( 'evge_is_standard_tier' ) || ! evge_is_standard_tier() ) {
			return;
		}

		$standard_db = new \WPEventGenius\Standard\Database\StandardDatabase();
		$form_fields_table = $standard_db->get_table_name( 'form_fields' );

		// Check if show_in_attendee_list column exists
		$column_exists = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT COLUMN_NAME 
				FROM INFORMATION_SCHEMA.COLUMNS 
				WHERE TABLE_SCHEMA = %s 
				AND TABLE_NAME = %s 
				AND COLUMN_NAME = 'show_in_attendee_list'",
				DB_NAME,
				$form_fields_table
			)
		);

		if ( empty( $column_exists ) ) {
			// Add the column
			$wpdb->query(
				"ALTER TABLE {$form_fields_table} 
				ADD COLUMN show_in_attendee_list TINYINT(1) DEFAULT 0 AFTER editable"
			);
			$this->log_update( "Added show_in_attendee_list column to form_fields table" );
			
			// Mark that column was just added so we can set defaults for existing records
			$this->states->set_state( 'attendee_list_column_just_added', true );
		}
	}

	/**
	 * Handle upgrade from free to pro version
	 */
	protected function handle_free_to_pro_upgrade() {
		$current_tier = defined( 'EVGE_TIER' ) ? EVGE_TIER : ( defined( 'EVGE_FREE_VERSION' ) && EVGE_FREE_VERSION ? 'free' : 'pro' );
		$previous_tier = $this->states->get_state( 'current_tier' );
		
		// If no previous tier recorded, check if we have legacy free version data
		if ( ! $previous_tier ) {
			// Check for legacy free version options
			$has_legacy_data = get_option( 'evge_form', false ) || get_option( 'evge_all_fields', false );
			if ( $has_legacy_data && in_array( $current_tier, array( 'pro', 'standard', 'premium', 'advanced' ), true ) ) {
				$previous_tier = 'free';
			}
		}

		// If upgrading from free to pro/standard
		if ( $previous_tier === 'free' && in_array( $current_tier, array( 'pro', 'standard', 'premium', 'advanced' ), true ) ) {
			$this->log_update( "Detected upgrade from free to {$current_tier}" );

			// Migrate any free version data to pro structure
			$this->migrate_free_to_pro_data();

			// When upgrading from free to any pro tier, the series post type changes from hidden to public
			// so we need to flush rewrite rules to ensure permalinks work correctly
			// Register the series post type first to ensure it's registered with public rewrite rules
			if ( ! did_action( 'init' ) ) {
				add_action( 'init', array( $this, 'flush_series_rewrite_rules' ), 999 );
			} else {
				$this->flush_series_rewrite_rules();
			}

			// Update tier tracking
			$this->states->set_state( 'upgraded_from_free_date', time() );
		}
		
		// Always update current tier
		$this->states->set_state( 'current_tier', $current_tier );
		if ( $previous_tier && $previous_tier !== $current_tier ) {
			$this->states->set_state( 'previous_tier', $previous_tier );
		}
	}

	/**
	 * Migrate data from free version to pro structure
	 */
	protected function migrate_free_to_pro_data() {
		// If Standard tier or higher, run the form migration
		if ( function_exists( 'evge_is_standard_tier' ) && evge_is_standard_tier() ) {
			$standard_db = new \WPEventGenius\Standard\Database\StandardDatabase();
			
			// This will check internally if migration is needed
			$standard_db->migrate_legacy_form_data();
		}
	}

	/**
	 * Handle standard version updates
	 * Note: Email template migration is handled separately on init hook
	 * since it requires WordPress to be fully initialized (rewrite system)
	 */
	protected function handle_standard_version_updates() {
		// Only run if Standard tier or higher is active
		if ( ! function_exists( 'evge_is_standard_tier' ) || ! evge_is_standard_tier() ) {
			return;
		}

		$standard_db = new \WPEventGenius\Standard\Database\StandardDatabase();

		// Ensure all tables exist
		$standard_db->create_all_tables();

		// Run form migration if needed (this will create default form for fresh installs)
		$standard_db->migrate_legacy_form_data();
	}

	/**
	 * Flush rewrite rules for series post type
	 * This is called when upgrading to pro/standard tier to ensure permalinks work
	 */
	public function flush_series_rewrite_rules() {
		// Register SeriesPro to ensure it's registered with public rewrite rules
		// Only if Pro is actually active (not just if class exists)
		if ( function_exists( 'evge_is_free_version' ) && ! evge_is_free_version() && class_exists( '\WPEventGenius\Admin\Pro\CustomPostTypes\SeriesPro' ) ) {
			$series_pro = new \WPEventGenius\Admin\Pro\CustomPostTypes\SeriesPro();
			$series_pro->register();
		}

		// Flush rewrite rules to ensure series permalinks work
		flush_rewrite_rules( false );
		$this->log_update( "Flushed rewrite rules for series post type" );
	}

	/**
	 * Run email template migration
	 * This runs on init hook since it requires WordPress to be fully initialized
	 * (wp_insert_post needs rewrite system for permalinks)
	 * The EmailTemplateMigrationService has its own completion check, so this is safe to call multiple times
	 */
	public function run_email_template_migration() {
		// Only run if Standard tier or higher is active
		if ( ! function_exists( 'evge_is_standard_tier' ) || ! evge_is_standard_tier() ) {
			return;
		}

		// Run email template migration
		// The service itself checks if migration is already complete
		$email_migration = new \WPEventGenius\Standard\Services\EmailTemplateMigrationService();
		$email_migration->migrate_email_templates();
	}

	/**
	 * Update attendee list related data
	 * Only updates defaults for existing Standard installations where the column was just added.
	 * Does NOT override user preferences set during free→pro migration.
	 * 
	 * Logic:
	 * - If column was just added AND we're not in a migration scenario: set defaults
	 * - If column already existed: respect existing values (user preferences)
	 * - If migrating from free: migration already handles defaults correctly
	 */
	protected function update_attendee_list_data() {
		// Only run if Standard tier is active
		if ( ! defined( 'EVGE_TIER' ) || EVGE_TIER !== 'standard' ) {
			return;
		}

		global $wpdb;

		$standard_db = new \WPEventGenius\Standard\Database\StandardDatabase();
		$form_fields_table = $standard_db->get_table_name( 'form_fields' );

		// Check if this is a fresh column addition (column didn't exist before this update)
		$column_just_added = $this->states->get_state( 'attendee_list_column_just_added' );
		
		// Check if we're in a migration scenario (free→pro upgrade)
		// If so, the migration already handled defaults correctly, so skip this
		$is_migration = $this->states->get_state( 'upgraded_from_free_date' );
		$migration_just_completed = $is_migration && ( time() - $is_migration < 60 ); // Within last minute
		
		// Only update if:
		// 1. Column was just added in this update run
		// 2. We're NOT in a migration scenario (migration handles its own defaults)
		if ( $column_just_added && ! $migration_just_completed ) {
			// Set default show_in_attendee_list for first and last name fields
			// This only affects existing Standard installations where the column was just added
			// For fresh installs, defaults are already correct via FormCreationService
			// For migrations, defaults are handled by migrate_form_fields()
			// Update fields where show_in_attendee_list is NULL or 0
			$fields_table = $standard_db->get_table_name( 'evge_fields' );
			$updated = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$form_fields_table} f
					INNER JOIN {$fields_table} fl ON f.field_id = fl.field_id
					SET f.show_in_attendee_list = 1 
					WHERE fl.name IN (%s, %s) 
					AND (f.show_in_attendee_list IS NULL OR f.show_in_attendee_list = 0)",
					'first',
					'last'
				)
			);

			if ( $updated > 0 ) {
				$this->log_update( "Updated default attendee list visibility for {$updated} first/last name field(s) in existing Standard installation" );
			}
			
			// Clear the flag so we don't run this again
			$this->states->set_state( 'attendee_list_column_just_added', false );
		} elseif ( $column_just_added && $migration_just_completed ) {
			// Column was added during migration - migration already handled defaults
			$this->log_update( "Skipping attendee list defaults update - handled by migration" );
			$this->states->set_state( 'attendee_list_column_just_added', false );
		}
	}

	/**
	 * Log update messages
	 * 
	 * @param string $message Log message
	 * @param string $level Log level (info, warning, error)
	 */
	protected function log_update( $message, $level = 'info' ) {
		return; // debug logging is disabled
		// Store in states for debugging
		$logs = $this->states->get_state( 'update_logs' );
		if ( ! is_array( $logs ) ) {
			$logs = array();
		}

		$logs[] = array(
			'time' => current_time( 'mysql' ),
			'level' => $level,
			'message' => $message,
		);

		// Keep only last 50 log entries
		$logs = array_slice( $logs, -50 );

		$this->states->set_state( 'update_logs', $logs );

		// Also log to error log for debugging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "EVGE Update [{$level}]: {$message}" );
		}
	}
}

