<?php
/**
 * Uninstall WP Event Genius
 * 
 * This file runs when the plugin is uninstalled to clean up all plugin data.
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if we should preserve data
$settings = get_option('evge_settings', array());
if (!empty($settings['preserve_data']) && $settings['preserve_data'] === 'enabled') {
    return;
}

/**
 * Uninstall a single site
 */
function evge_uninstall_single_site() {
    global $wpdb;

    // Delete custom database tables
    $tables = array(
        'evge_registrations',
        'evge_registrations_meta',
        'evge_payments',
        'evge_payments_meta',
        'evge_events',
        'evge_event_series_relationships',
        'evge_event_venue_relationships',
        'evge_event_organizer_relationships',
        // Standard tier tables
        'evge_forms',
        'evge_fields',
        'evge_forms_and_fields',
        'evge_attendance_status',
        'evge_scheduled_email_queue'
    );

    foreach ($tables as $table) {
        $table_name = $wpdb->prefix . $table;
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
        wp_cache_delete($table, 'evge_db_tables');
    }

    // Delete WordPress options
    $options = array(
        'evge_event_settings',
        'evge_gateways',
        'evge_registration_settings',
        'evge_settings',
        'evge_states',
        'evge_all_fields',
        'evge_form',
        'evge_series_queue',
        'evge_default_form_settings'
    );

    foreach ($options as $option) {
        delete_option($option);
        wp_cache_delete($option, 'options');
    }

    // Remove capabilities from default WordPress roles
    $affected_roles = array('administrator', 'editor', 'author', 'contributor', 'evge_event_manager', 'evge_check_in_staff');

    $all_capabilities = array(
        // Event capabilities
        'edit_evge_events',
        'read_evge_events',
        'delete_evge_events',
        'edit_others_evge_events',
        'publish_evge_events',
        'read_private_evge_events',
        'delete_others_evge_events',
        'delete_private_evge_events',
        'delete_published_evge_events',
        'delete_pending_evge_events',
        'edit_private_evge_events',
        'edit_published_evge_events',
        'edit_pending_evge_events',
        
        // Venue capabilities
        'edit_evge_venues',
        'read_evge_venues',
        'delete_evge_venues',
        'edit_others_evge_venues',
        'publish_evge_venues',
        'read_private_evge_venues',
        'delete_others_evge_venues',
        'delete_private_evge_venues',
        'delete_published_evge_venues',
        'delete_pending_evge_venues',
        'edit_private_evge_venues',
        'edit_published_evge_venues',
        'edit_pending_evge_venues',
        
        // Organizer capabilities
        'edit_evge_organizers',
        'read_evge_organizers',
        'delete_evge_organizers',
        'edit_others_evge_organizers',
        'publish_evge_organizers',
        'read_private_evge_organizers',
        'delete_others_evge_organizers',
        'delete_private_evge_organizers',
        'delete_published_evge_organizers',
        'delete_pending_evge_organizers',
        'edit_private_evge_organizers',
        'edit_published_evge_organizers',
        'edit_pending_evge_organizers',
        
        // Series capabilities
        'edit_evge_series',
        'read_evge_series',
        'delete_evge_series',
        'edit_others_evge_series',
        'publish_evge_series',
        'read_private_evge_series',
        'delete_others_evge_series',
        'delete_private_evge_series',
        'delete_published_evge_series',
        'delete_pending_evge_series',
        'edit_private_evge_series',
        'edit_published_evge_series',
        'edit_pending_evge_series',
        
        // Email Template capabilities
        'edit_evge_email_template',
        'read_evge_email_template',
        'delete_evge_email_template',
        'edit_others_evge_email_templates',
        'publish_evge_email_templates',
        'read_private_evge_email_templates',
        'delete_others_evge_email_templates',
        'delete_private_evge_email_templates',
        'delete_published_evge_email_templates',
        'delete_pending_evge_email_templates',
        'edit_private_evge_email_templates',
        'edit_published_evge_email_templates',
        'edit_pending_evge_email_templates',
        
        // Registration capabilities
        'view_evge_registrations',
        'export_evge_registrations',
        'manage_evge_registrations',
        
        // Check-in capabilities
        'evge_check_in'
    );

    foreach ($affected_roles as $role_name) {
        $role = get_role($role_name);
        if ($role) {
            foreach ($all_capabilities as $cap) {
                $role->remove_cap($cap);
            }
        }
    }

    // Remove custom check-in staff role
    if (get_role('evge_check_in_staff')) {
        remove_role('evge_check_in_staff');
    }

    // Delete custom post types and their meta
    $post_types = array(
        'evge_event',
        'evge_venue',
        'evge_organizer',
        'evge_series',
        'evge_email_template'
    );

    // First delete all terms from our custom taxonomies
    $taxonomies = array(
        'evge_event_cat',
        'evge_event_tag',
        'evge_calendar',
        'evge_email_category',
    );

    foreach ($taxonomies as $taxonomy) {
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'number' => 0
        ));
        
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                // Delete term meta
                $meta_keys = get_term_meta($term->term_id);
                if (is_array($meta_keys)) {
                    foreach ($meta_keys as $meta_key => $meta_value) {
                        delete_term_meta($term->term_id, $meta_key);
                    }
                }
                // Delete the term
                wp_delete_term($term->term_id, $taxonomy);
            }
        }
    }

    foreach ($post_types as $post_type) {
        $cache_key = 'evge_uninstall_' . $post_type;
        $items = wp_cache_get($cache_key);
        
        if (false === $items) {
            $items = get_posts(array(
                'post_type' => $post_type,
                'post_status' => 'any',
                'numberposts' => -1,
                'fields' => 'ids'
            ));
            wp_cache_set($cache_key, $items, '', 60);
        }

        if (!empty($items)) {
            foreach ($items as $item) {
                // Delete all post meta
                $meta_keys = get_post_custom_keys($item);
                if (is_array($meta_keys)) {
                    foreach ($meta_keys as $meta_key) {
                        delete_post_meta($item, $meta_key);
                    }
                }
                // Delete the post
                wp_delete_post($item, true);
            }
        }
        
        wp_cache_delete($cache_key);
    }

    // Delete block theme templates (wp_template posts)
    // These are created by BlockThemeTemplateService for block themes
    if (function_exists('wp_is_block_theme')) {
        $template_slugs = array(
            'single-evge_event',
            'archive-evge_event',
            'single-evge_organizer',
            'single-evge_venue',
            'single-evge_series',
        );

        // Query for all Event Genius block templates
        $templates = get_posts(array(
            'post_type'      => 'wp_template',
            'post_name__in'  => $template_slugs,
            'post_status'    => 'any',
            'numberposts'    => -1,
            'fields'         => 'ids',
            'tax_query'      => array(
                array(
                    'taxonomy' => 'wp_theme',
                    'field'    => 'name',
                    'terms'    => 'event-genius',
                ),
            ),
        ));

        if (!empty($templates)) {
            foreach ($templates as $template_id) {
                // Delete all post meta
                $meta_keys = get_post_custom_keys($template_id);
                if (is_array($meta_keys)) {
                    foreach ($meta_keys as $meta_key) {
                        delete_post_meta($template_id, $meta_key);
                    }
                }
                // Delete the template post
                wp_delete_post($template_id, true);
            }
        }

        // Delete the wp_theme taxonomy term if it exists and is empty
        $term = get_term_by('name', 'event-genius', 'wp_theme');
        if ($term && !is_wp_error($term)) {
            // Check if term has any remaining posts
            $remaining = get_posts(array(
                'post_type'   => 'wp_template',
                'post_status' => 'any',
                'numberposts' => 1,
                'fields'      => 'ids',
                'tax_query'   => array(
                    array(
                        'taxonomy' => 'wp_theme',
                        'field'    => 'term_id',
                        'terms'    => $term->term_id,
                    ),
                ),
            ));

            // Only delete the term if no templates remain
            if (empty($remaining)) {
                wp_delete_term($term->term_id, 'wp_theme');
            }
        }
    }

    wp_cache_flush();
}

// Handle multisite uninstallation
if (is_multisite()) {
    // Try to use get_sites() first (WordPress 4.6+)
    if (function_exists('get_sites')) {
        $sites = get_sites(array(
            'number' => 0, // Get all sites
            'fields' => 'ids'
        ));
        foreach ($sites as $blog_id) {
            switch_to_blog($blog_id);
            evge_uninstall_single_site();
            restore_current_blog();
        }
    } else {
        global $wpdb;
        $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
        foreach ($blog_ids as $blog_id) {
            switch_to_blog($blog_id);
            evge_uninstall_single_site();
            restore_current_blog();
        }
    }
} else {
    evge_uninstall_single_site();
}

