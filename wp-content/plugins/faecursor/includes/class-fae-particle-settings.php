<?php
/**
 * FaeCursor Particle Settings Handler
 * Manages particle effect settings, options, and sanitization
 */

if (!defined('ABSPATH')) {
    exit;
}

class Fae_Particle_Settings {
    
    /**
     * Get default options
     */
    public static function get_default_options() {
        return array(
            'effect' => 'none',
            'color' => '#667eea',
            'speed' => 'normal',
            'interactive_cursor' => '0', // Pro feature - default to disabled
            'hide_default_cursor' => '0',
            'hide_on_mobile' => '0',
            'hide_on_tablet' => '0',
            'hide_on_desktop' => '0',
            'scope_type' => 'entire_website',
            'scope_pages' => array(),
            'user_roles' => array(), // Empty array means all users/all roles
            'include_logged_out' => '0'
        );
    }
    
    /**
     * Get current options
     */
    public static function get_options() {
        return get_option('fae_particle_options', self::get_default_options());
    }
    
    /**
     * Sanitize options
     */
    public static function sanitize_options($input) {
        $sanitized = array();

        // Sanitize effect - get allowed effects from config
        // All effects are now free - no Pro blocking on effects
        if (isset($input['effect'])) {
            $effects_config = fae_particle_get_effects_config();
            $allowed_effects = array_keys($effects_config);
            $sanitized['effect'] = in_array($input['effect'], $allowed_effects) ? $input['effect'] : 'none';
        }
        
        // Get selected effect for customization limit checks
        $selected_effect = isset($sanitized['effect']) ? $sanitized['effect'] : 'none';

        // Sanitize color - with limited customization enforcement
        if (isset($input['color'])) {
            $input_color = sanitize_hex_color($input['color']) ?: fae_get_particle_free_default_color();
            
            // Check if this effect has limited customization
            if (fae_particle_effect_has_limited_customization($selected_effect) && !fae_can_customize_particle_color($selected_effect)) {
                // Free users on limited effects: force default color
                $sanitized['color'] = fae_get_particle_free_default_color();
            } else {
                // Effects without limits: allow any color
                $sanitized['color'] = $input_color;
            }
        }

        // Sanitize speed - with limited customization enforcement
        if (isset($input['speed'])) {
            $allowed_speeds = array('slow', 'normal', 'fast');
            $input_speed = in_array($input['speed'], $allowed_speeds) ? $input['speed'] : 'normal';
            
            // Check if this effect has limited customization
            if (fae_particle_effect_has_limited_customization($selected_effect) && !fae_can_customize_particle_speed($selected_effect)) {
                // Free users on limited effects: force "normal" speed only
                $sanitized['speed'] = fae_get_particle_free_default_speed();
            } else {
                // Effects without limits: allow any speed
                $sanitized['speed'] = $input_speed;
            }
        }

        // Sanitize interactive_cursor - always force to '0' (Pro feature, disabled in free version)
        // Security: Even if someone manipulates the form, the backend enforces this
        $sanitized['interactive_cursor'] = '0';

        // Sanitize hide_default_cursor (checkbox - if not set, default to '0')
        $sanitized['hide_default_cursor'] = isset($input['hide_default_cursor']) && $input['hide_default_cursor'] === '1' ? '1' : '0';

        // Sanitize device hide options
        $sanitized['hide_on_mobile'] = isset($input['hide_on_mobile']) && $input['hide_on_mobile'] === '1' ? '1' : '0';
        $sanitized['hide_on_tablet'] = isset($input['hide_on_tablet']) && $input['hide_on_tablet'] === '1' ? '1' : '0';
        $sanitized['hide_on_desktop'] = isset($input['hide_on_desktop']) && $input['hide_on_desktop'] === '1' ? '1' : '0';

        // Sanitize scope settings - always force to 'entire_website' (Pro features disabled in free version)
        // Security: specific_pages is a Pro feature
        $sanitized['scope_type'] = 'entire_website';

        // Sanitize scope pages - always force empty (Pro feature disabled in free version)
        $sanitized['scope_pages'] = array();

        // Sanitize user roles - always force empty/all users (Pro feature disabled in free version)
        $sanitized['user_roles'] = array();

        // Sanitize include_logged_out - always force to '0' (Pro feature disabled in free version)
        $sanitized['include_logged_out'] = '0';

        return $sanitized;
    }
    
    /**
     * Register settings
     */
    public static function register_settings() {
        register_setting(
            'fae_particle',
            'fae_particle_options',
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

