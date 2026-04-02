<?php

namespace WPEventGenius\Common\Services;

use WPEventGenius\Common\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class CronService {

    /**
     * Hook name for the cleanup task
     */
    const CLEANUP_HOOK = 'evge_cleanup_orphaned_records';

    /**
     * @var Database
     */
    protected $db;

    /**
     * Constructor
     * 
     * @param Database $db Database instance
     */
    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Initialize the service hooks
     */
    public function init_hooks() {
        // Only hook into the cleanup action
        add_action(self::CLEANUP_HOOK, array($this, 'cleanup_orphaned_records'));
    }

    /**
     * Ensure the cleanup task is scheduled
     * Should only be called in admin when necessary
     * 
     * @return bool True if scheduled, false if already scheduled
     */
    public function ensure_scheduled() {
        if (!wp_next_scheduled(self::CLEANUP_HOOK)) {
            return (bool)wp_schedule_event(time(), 'twicedaily', self::CLEANUP_HOOK);
        }
        return false;
    }

    /**
     * Cleanup orphaned records in batches
     * This runs twice daily via WP Cron
     * 
     * @return array Array containing counts of deleted records
     */
    public function cleanup_orphaned_records() {
        $batch_size = 500; // Process 500 records at a time
        $max_batches = 10; // Maximum number of batches to process in one cron run
        $total_processed = array(
            'events' => 0,
            'relationships' => 0
        );

        // Process batches until we hit the limit or no more records to process
        for ($i = 0; $i < $max_batches; $i++) {
            $deleted = $this->db->cleanup_orphaned_event_records($batch_size);
            
            // Ensure the keys exist in the returned array
            $deleted = array_merge(array(
                'events' => 0,
                'relationships' => 0
            ), $deleted);
            
            $total_processed['events'] += $deleted['events'];
            $total_processed['relationships'] += $deleted['relationships'];

            // If no records were deleted in this batch, we're done
            if ($deleted['events'] + $deleted['relationships'] === 0) {
                break;
            }
        }

        return $total_processed;
    }

    /**
     * Static method to clean up scheduled tasks on plugin deactivation
     * This needs to be static as it's called during plugin deactivation
     * when we don't have access to the instance
     */
    public static function deactivate_cleanup() {
        $timestamp = wp_next_scheduled(self::CLEANUP_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CLEANUP_HOOK);
        }
    }

    /**
     * Force run the cleanup process
     * Useful for admin-triggered cleanup
     * 
     * @return array Array containing counts of deleted records
     */
    public function force_cleanup() {
        return $this->cleanup_orphaned_records();
    }
} 