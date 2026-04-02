<?php
/**
 * FaeCursor Conflict Handler (Free Version)
 * Prevents activation when Pro version is active
 */

if (!defined('ABSPATH')) {
    exit;
}

class Fae_Cursor_Conflict_Free {
    
    /**
     * Pro plugin slug
     */
    const PRO_PLUGIN_SLUG = 'faecursor-pro/faecursor-pro.php';
    
    /**
     * Pro constant name
     */
    const PRO_CONSTANT = 'FAECURSOR_PRO_ACTIVE';
    
    /**
     * Option name for admin notice
     */
    const NOTICE_OPTION = 'faecursor_free_blocked_by_pro';
    
    /**
     * Initialize conflict prevention
     * Call this early in the main plugin file
     * Returns true if should exit early, false otherwise
     */
    public static function init() {
        // Check if Pro is active - exit early if so
        if (self::is_pro_active()) {
            return true; // Signal to exit early
        }
        
        // Register activation hook
        self::register_activation_hook();
        
        // Register admin notices
        self::register_admin_notices();
        
        return false; // Continue loading
    }
    
    /**
     * Check if Pro version is active
     */
    private static function is_pro_active() {
        // Method 1: Check constant (fastest)
        if (defined(self::PRO_CONSTANT)) {
            return true;
        }
        
        // Method 2: Check active plugins list
        if (function_exists('get_option')) {
            $active = get_option('active_plugins', array());
            if (!empty($active) && in_array(self::PRO_PLUGIN_SLUG, $active, true)) {
                return true;
            }
            
            // Multisite check
            if (function_exists('is_multisite') && is_multisite() && function_exists('get_site_option')) {
                $network_active = get_site_option('active_sitewide_plugins', array());
                if (!empty($network_active) && isset($network_active[self::PRO_PLUGIN_SLUG])) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Register activation hook
     */
    private static function register_activation_hook() {
        $plugin_file = __DIR__ . '/../faecursor.php';
        register_activation_hook($plugin_file, array('Fae_Cursor_Conflict_Free', 'on_activation'));
    }
    
    /**
     * Activation hook callback
     */
    public static function on_activation() {
        if (!function_exists('is_plugin_active')) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        // Check if Pro is active
        if (defined(self::PRO_CONSTANT) || is_plugin_active(self::PRO_PLUGIN_SLUG)) {
            // Deactivate this plugin immediately
            $plugin_file = __DIR__ . '/../faecursor.php';
            deactivate_plugins(plugin_basename($plugin_file));
            update_option(self::NOTICE_OPTION, true);
            
            // Prevent activation
            wp_die(
                '<h1>' . esc_html__('FaeCursor Pro is Active', 'faecursor') . '</h1>' .
                '<p>' . esc_html__('The free version of FaeCursor cannot be activated while FaeCursor Pro is active. Please deactivate FaeCursor Pro first if you want to use the free version.', 'faecursor') . '</p>' .
                '<p><a href="' . esc_url(admin_url('plugins.php')) . '">' . esc_html__('Return to Plugins', 'faecursor') . '</a></p>',
                esc_html__('Plugin Activation Error', 'faecursor'),
                array('back_link' => true)
            );
        }
    }
    
    /**
     * Register admin notices
     */
    private static function register_admin_notices() {
        add_action('admin_notices', array('Fae_Cursor_Conflict_Free', 'show_admin_notice'));
    }
    
    /**
     * Show admin notice when activation was blocked
     */
    public static function show_admin_notice() {
        if (get_option(self::NOTICE_OPTION)) {
            ?>
            <div class="notice notice-info is-dismissible">
                <p>
                    <strong><?php esc_html_e('FaeCursor Free', 'faecursor'); ?></strong> - 
                    <?php esc_html_e('Cannot be activated because FaeCursor Pro is currently active. Please deactivate FaeCursor Pro first if you want to use the free version.', 'faecursor'); ?>
                </p>
            </div>
            <?php
            delete_option(self::NOTICE_OPTION);
        }
    }
}
