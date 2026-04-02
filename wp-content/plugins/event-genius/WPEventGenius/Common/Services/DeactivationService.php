<?php

namespace WPEventGenius\Common\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class DeactivationService {

    /**
     * Constructor
     */
    public function __construct() {
    }

    /**
     * Initialize hooks
     */
    public function init_hooks() {
    }

    /**
     * Handle plugin deactivation tasks
     */
    public function deactivate() {
        global $wpdb;

        // Handle multisite deactivation
        if (is_multisite()) {
            // Try to use get_sites() first (WordPress 4.6+)
            if (function_exists('get_sites')) {
                $sites = get_sites(array(
                    'number' => 0, // Get all sites
                    'fields' => 'ids'
                ));
                foreach ($sites as $blog_id) {
                    switch_to_blog($blog_id);
                    $this->deactivate_single_site();
                    restore_current_blog();
                }
            } else {
                // Fallback to direct database query for older WordPress versions
                $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
                foreach ($blog_ids as $blog_id) {
                    switch_to_blog($blog_id);
                    $this->deactivate_single_site();
                    restore_current_blog();
                }
            }
        } else {
            $this->deactivate_single_site();
        }

        // Add any additional deactivation cleanup tasks here
        do_action('evge_plugin_deactivated');
    }

    /**
     * Handle deactivation for a single site
     */
    private function deactivate_single_site() {
        // Clean up scheduled cron tasks
        wp_clear_scheduled_hook('evge_cleanup_expired_registrations');
        wp_clear_scheduled_hook('evge_cleanup_old_events');
        
        // Clean up Pro cron jobs if Pro is active
        if ( class_exists( '\WPEventGenius\Pro\Services\ProCronService' ) ) {
            $pro_cron_service = new \WPEventGenius\Pro\Services\ProCronService();
            $pro_cron_service->stop_cron_jobs();
        } else {
            // Fallback: clean up abandoned payments cron if ProCronService not available
            wp_clear_scheduled_hook('evge_check_abandoned_payments');
        }
    }
} 