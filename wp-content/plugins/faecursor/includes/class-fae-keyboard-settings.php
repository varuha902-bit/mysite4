<?php
/**
 * FaeCursor Keyboard Settings Handler
 * Manages keyboard effect settings, options, and sanitization
 */

if (!defined('ABSPATH')) {
    exit;
}

class Fae_Keyboard_Settings {
    
    /**
     * Get default options
     */
    public static function get_default_options() {
        return array(
            'effect' => 'none',
            'color' => '#667eea',
            'hide_on_mobile' => '0',
            'hide_on_tablet' => '0',
            'hide_on_desktop' => '0',
            'scope_type' => 'entire_website',
            'scope_pages' => array(),
            'scope_css_selector' => '',
            'user_roles' => array(), // Empty array means all users/all roles
            'include_logged_out' => '0',
            'multi_color' => '0' // Multi-color option for sparkle-keys effect
        );
    }
    
    /**
     * Get current options
     */
    public static function get_options() {
        return get_option('fae_keyboard_options', self::get_default_options());
    }
    
    /**
     * Sanitize options
     */
    public static function sanitize_options($input) {
        $sanitized = array();

        // Sanitize effect - get allowed effects from config
        // All effects are now free - no Pro blocking on effects
        if (isset($input['effect'])) {
            $effects_config = fae_keyboard_get_effects_config();
            $allowed_effects = array_keys($effects_config);
            $sanitized['effect'] = in_array($input['effect'], $allowed_effects) ? $input['effect'] : 'none';
        }
        
        // Get selected effect for customization limit checks
        $selected_effect = isset($sanitized['effect']) ? $sanitized['effect'] : 'none';

        // Sanitize color - with limited customization enforcement
        if (isset($input['color'])) {
            $input_color = sanitize_hex_color($input['color']) ?: fae_get_keyboard_free_default_color();
            
            // Check if this effect has limited color customization
            if (fae_keyboard_effect_has_limited_color($selected_effect) && !fae_can_customize_keyboard_color($selected_effect)) {
                // Free users on limited effects: force default color
                $sanitized['color'] = fae_get_keyboard_free_default_color();
            } else {
                // Effects without limits: allow any color
                $sanitized['color'] = $input_color;
            }
        }

        // Sanitize device hide options
        $sanitized['hide_on_mobile'] = isset($input['hide_on_mobile']) && $input['hide_on_mobile'] === '1' ? '1' : '0';
        $sanitized['hide_on_tablet'] = isset($input['hide_on_tablet']) && $input['hide_on_tablet'] === '1' ? '1' : '0';
        $sanitized['hide_on_desktop'] = isset($input['hide_on_desktop']) && $input['hide_on_desktop'] === '1' ? '1' : '0';

        // Sanitize scope settings - always force to 'entire_website' (Pro features disabled in free version)
        // Security: specific_pages and css_selector are Pro features
        $sanitized['scope_type'] = 'entire_website';

        // Sanitize scope pages - always force empty (Pro feature disabled in free version)
        $sanitized['scope_pages'] = array();

        // Sanitize CSS selector - always force empty (Pro feature disabled in free version)
        $sanitized['scope_css_selector'] = '';

        // Sanitize user roles - always force empty/all users (Pro feature disabled in free version)
        $sanitized['user_roles'] = array();

        // Sanitize include_logged_out - always force to '0' (Pro feature disabled in free version)
        $sanitized['include_logged_out'] = '0';

        // Security: Always force multi_color to 0 (Pro feature, disabled in free version)
        $sanitized['multi_color'] = '0';

        return $sanitized;
    }
    
    /**
     * Register settings
     */
    public static function register_settings() {
        register_setting(
            'fae_keyboard',
            'fae_keyboard_options',
            array(
                'type'              => 'array',
                'sanitize_callback' => array(__CLASS__, 'sanitize_options'),
            )
        );
    }
    
    /**
     * Check if current user should see the effect
     * 
     * Free version: Always returns true (show to all users)
     * User role filtering is a Pro feature available in FaeCursor Pro plugin
     * 
     * @param array $options Settings options array (unused in free version)
     * @return bool Always true in free version
     */
    public static function should_show_for_user($options) {
        // Free version: Show effects to all users
        // User role restrictions are available in FaeCursor Pro
        return true;
    }
}

