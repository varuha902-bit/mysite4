<?php
/**
 * FaeCursor Pro Feature Detection
 * Handles Pro version feature checks (WordPress.org compliant - uses Freemius SDK)
 * 
 * Pro features are only available when FaeCursor Pro plugin is installed and activated.
 * This follows the same approach as Ultimate Cursor and other WordPress.org plugins.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Fae_Cursor_Pro {
    
    /**
     * Check if Pro version is active
     * 
     * WordPress.org compliant: Uses Freemius SDK for Pro detection
     * Falls back to class_exists check if Freemius not available
     * 
     * @return bool True if Pro plugin is active
     */
    public static function is_pro() {
        // Method 1: Check via Freemius SDK (primary method)
        if (function_exists('faecursor_fs')) {
            $fs = faecursor_fs();
            if (is_object($fs) && method_exists($fs, 'is_paying')) {
                return $fs->is_paying();
            }
        }
        
        // Method 2: Fallback - check if Pro plugin class exists
        return class_exists('FaeCursor_Pro');
    }
    
    /**
     * Check if an effect is Pro-only
     * 
     * All effects are free in the free version.
     * 
     * @param string $effect_id Effect ID
     * @param string $type Effect type (cursor, keyboard, particle)
     * @return bool Always false - all effects are free
     */
    public static function is_pro_effect($effect_id, $type = 'cursor') {
        // All effects are free - no Pro-only effects
        return false;
    }
    
    /**
     * Get list of Pro-only effects
     * All effects are now free - Pro only adds advanced features
     * 
     * @return array Empty arrays - all effects are free
     */
    public static function get_pro_effects() {
        return array(
            'cursor' => array(),
            'keyboard' => array(),
            'particle' => array(),
        );
    }
    
    /**
     * Check if a feature requires Pro
     * Used for displaying Pro badges (marketing/upsell - allowed by WordPress.org)
     * 
     * @param string $feature Feature name
     * @return bool True if feature requires Pro plugin
     */
    public static function is_pro_feature($feature) {
        $pro_features = array(
            'css_selector_scoping',
            'user_role_restrictions',
            'specific_pages_scoping',
            'keyboard_multi_color',
            'cursor_multi_color',
            'interactive_cursor',
            'cursor_color_customization',
            'cursor_speed_customization',
        );
        
        return in_array($feature, $pro_features);
    }
    
    /**
     * Get upgrade URL
     * 
     * @return string Upgrade URL (FaeCursor website)
     */
    public static function get_upgrade_url() {
        return 'https://faecursor.com/';
    }
    
    /**
     * Get Pro badge HTML
     * For marketing/upsell purposes (allowed by WordPress.org)
     * 
     * @return string Pro badge HTML
     */
    public static function get_pro_badge() {
        return '<span class="fae-pro-badge" title="Available in Pro">PRO</span>';
    }
    
    /**
     * Get upgrade notice HTML
     * For marketing/upsell purposes (allowed by WordPress.org)
     * 
     * @param string $feature_name Name of the Pro feature
     * @return string Upgrade notice HTML
     */
    public static function get_upgrade_notice($feature_name = '') {
        $message = !empty($feature_name) 
            /* translators: %s: Feature name (e.g., "Custom Cursors", "Particle Effects") */
            ? sprintf(__('Get %s with FaeCursor Pro.', 'faecursor'), $feature_name)
            : __('Get advanced features with FaeCursor Pro.', 'faecursor');
        
        return sprintf(
            '<div class="fae-upgrade-notice">
                <div class="fae-upgrade-notice-content">
                    <svg class="fae-upgrade-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                    </svg>
                    <div class="fae-upgrade-text">
                        <strong>%s</strong>
                        <p>%s</p>
                    </div>
                    <a href="%s" target="_blank" class="fae-upgrade-button">Upgrade to Pro</a> 
                </div>
            </div>',
            esc_html__('Pro Feature', 'faecursor'),
            esc_html($message),
            esc_url(self::get_upgrade_url())
        );
    }
}

