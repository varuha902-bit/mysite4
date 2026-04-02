<?php
/**
 * FaeCursor Asset Enqueuing
 * Handles loading of CSS and JS assets for effects
 */

if (!defined('ABSPATH')) {
    exit;
}

class Fae_Cursor_Enqueue {
    
    /**
     * Enqueue frontend assets
     */
    public static function enqueue_frontend() {
        // Enqueue scope handler script (needed for CSS selector scoping)
        wp_enqueue_script(
            'fae-cursor-scope',
            FAE_CURSOR_URL . 'assets/js/fae-cursor-scope.js',
            array(),
            FAE_CURSOR_VERSION,
            true
        );
        
        // Enqueue cursor effects
        self::enqueue_cursor_effects();
        
        // Enqueue keyboard effects
        self::enqueue_keyboard_effects();
        
        // Enqueue particle effects
        self::enqueue_particle_effects();
    }
    
    /**
     * Enqueue cursor effects
     */
    private static function enqueue_cursor_effects() {
        $options = Fae_Cursor_Settings::get_options();
        $effect = isset($options['effect']) ? $options['effect'] : 'none';
        
        if ($effect === 'none') {
            return;
        }
        
        // All effects are now free - no Pro blocking on effects
        // Pro lock only applies to advanced features (scoping, user roles, etc.)
        
        // Check if effect should be shown for current user role
        if (!Fae_Cursor_Settings::should_show_for_user($options)) {
            return;
        }
        
        // Check if effect should be hidden on current device
        if (Fae_Cursor_Device::should_hide_effect($options)) {
            return;
        }
        
        // Check if effect should be displayed based on scope settings
        if (!Fae_Cursor_Scope::should_display_effect($options)) {
            return;
        }
        
        // Cursor effects are in assets/effects/cursor/{effect_name}/
        $effect_dir = FAE_CURSOR_DIR . '/assets/effects/cursor/' . $effect;
        
        if (!is_dir($effect_dir)) {
            return;
        }
        
        // Enqueue CSS files
        $first_css_handle = self::enqueue_css_files($effect, $effect_dir, $options);
        
        // Enqueue JS files
        self::enqueue_js_files($effect, $effect_dir, $options);
    }
    
    /**
     * Enqueue keyboard effects
     */
    private static function enqueue_keyboard_effects() {
        if (!function_exists('fae_keyboard_get_effects_config')) {
            return;
        }
        
        $options = Fae_Keyboard_Settings::get_options();
        $effect = isset($options['effect']) ? $options['effect'] : 'none';
        
        if ($effect === 'none') {
            return;
        }
        
        // All effects are now free - no Pro blocking on effects
        // Pro lock only applies to advanced features (scoping, user roles, etc.)
        
        // Check if effect should be shown for current user role
        if (!Fae_Keyboard_Settings::should_show_for_user($options)) {
            return;
        }
        
        // Check if effect should be hidden on current device
        if (Fae_Cursor_Device::should_hide_effect($options)) {
            return;
        }
        
        // Check if effect should be displayed based on scope settings
        if (!Fae_Cursor_Scope::should_display_effect($options)) {
            return;
        }
        
        // Keyboard effects are in assets/effects/keyboard/{effect_name}/
        $effect_dir = FAE_CURSOR_DIR . '/assets/effects/keyboard/' . $effect;
        
        if (!is_dir($effect_dir)) {
            return;
        }
        
        // Enqueue CSS files
        $first_css_handle = self::enqueue_keyboard_css_files($effect, $effect_dir, $options);
        
        // Enqueue JS files
        self::enqueue_keyboard_js_files($effect, $effect_dir, $options);
    }
    
    /**
     * Enqueue CSS files for keyboard effect
     */
    private static function enqueue_keyboard_css_files($effect, $effect_dir, $options) {
        $first_css_handle = null;
        
        foreach (glob($effect_dir . '/*.css') as $css_file) {
            $handle = 'fae-keyboard-style-' . basename($css_file, '.css');
            $css_url = FAE_CURSOR_URL . 'assets/effects/keyboard/' . $effect . '/' . basename($css_file);
            wp_enqueue_style($handle, $css_url, array(), FAE_CURSOR_VERSION);
            
            if ($first_css_handle === null) {
                $first_css_handle = $handle;
            }
        }
        
        return $first_css_handle;
    }
    
    /**
     * Enqueue JS files for keyboard effect
     */
    private static function enqueue_keyboard_js_files($effect, $effect_dir, $options) {
        $localized = false;
        
        foreach (glob($effect_dir . '/*.js') as $js_file) {
            $handle = 'fae-keyboard-script-' . basename($js_file, '.js');
            $js_url = FAE_CURSOR_URL . 'assets/effects/keyboard/' . $effect . '/' . basename($js_file);
            wp_enqueue_script($handle, $js_url, array('fae-cursor-scope'), FAE_CURSOR_VERSION, true);

            if (!$localized) {
                self::localize_keyboard_script($handle, $effect, $options);
                $localized = true;
            }
        }
    }
    
    /**
     * Localize keyboard script with settings
     */
    private static function localize_keyboard_script($handle, $effect, $options) {
        $scope_settings = Fae_Cursor_Scope::get_scope_for_js($options);
        
        // Get color value with limited customization enforcement
        $color = isset($options['color']) ? $options['color'] : fae_get_keyboard_free_default_color();
        
        // Enforce color restriction for effects with limited color customization
        if (fae_keyboard_effect_has_limited_color($effect) && !fae_can_customize_keyboard_color($effect)) {
            $color = fae_get_keyboard_free_default_color();
        }
        
        wp_localize_script($handle, 'faeCursorSettings', array(
            'effect' => $effect,
            'color'  => $color,
            'assetsUrl' => FAE_CURSOR_URL . 'assets/',
            'multiColor' => false, // Multi-color is disabled (Pro feature)
            'scope' => $scope_settings,
        ));
    }
    
    /**
     * Enqueue particle effects
     */
    private static function enqueue_particle_effects() {
        if (!function_exists('fae_particle_get_effects_config')) {
            return;
        }
        
        $options = Fae_Particle_Settings::get_options();
        $effect = isset($options['effect']) ? $options['effect'] : 'none';
        
        if ($effect === 'none') {
            return;
        }
        
        // All effects are now free - no Pro blocking on effects
        // Pro lock only applies to advanced features (scoping, user roles, etc.)
        
        // Check if effect should be shown for current user role
        if (!Fae_Particle_Settings::should_show_for_user($options)) {
            return;
        }
        
        // Check if effect should be hidden on current device
        if (Fae_Cursor_Device::should_hide_effect($options)) {
            return;
        }
        
        // Check if effect should be displayed based on scope settings
        if (!Fae_Cursor_Scope::should_display_effect($options)) {
            return;
        }
        
        // Get directory name for particle effect
        $effect_directory = function_exists('fae_particle_get_effect_directory') 
            ? fae_particle_get_effect_directory($effect) 
            : $effect;
        
        // Particle effects are in assets/effects/particles/{directory_name}/
        $effect_dir = FAE_CURSOR_DIR . '/assets/effects/particles/' . $effect_directory;
        
        if (!is_dir($effect_dir)) {
            return;
        }
        
        // Enqueue CSS files
        $first_css_handle = self::enqueue_particle_css_files($effect, $effect_dir, $effect_directory, $options);
        
        // Enqueue JS files
        self::enqueue_particle_js_files($effect, $effect_dir, $effect_directory, $options);
    }
    
    /**
     * Enqueue CSS files for particle effect
     */
    private static function enqueue_particle_css_files($effect, $effect_dir, $effect_directory, $options) {
        $first_css_handle = null;
        
        foreach (glob($effect_dir . '/*.css') as $css_file) {
            $handle = 'fae-particle-style-' . basename($css_file, '.css');
            $css_url = FAE_CURSOR_URL . 'assets/effects/particles/' . $effect_directory . '/' . basename($css_file);
            wp_enqueue_style($handle, $css_url, array(), FAE_CURSOR_VERSION);
            
            if ($first_css_handle === null) {
                $first_css_handle = $handle;
            }
        }
        
        return $first_css_handle;
    }
    
    /**
     * Enqueue JS files for particle effect
     */
    private static function enqueue_particle_js_files($effect, $effect_dir, $effect_directory, $options) {
        $localized = false;
        
        foreach (glob($effect_dir . '/*.js') as $js_file) {
            $handle = 'fae-particle-script-' . basename($js_file, '.js');
            $js_url = FAE_CURSOR_URL . 'assets/effects/particles/' . $effect_directory . '/' . basename($js_file);
            wp_enqueue_script($handle, $js_url, array('fae-cursor-scope'), FAE_CURSOR_VERSION, true);

            if (!$localized) {
                self::localize_particle_script($handle, $effect, $options);
                $localized = true;
            }
        }
    }
    
    /**
     * Localize particle script with settings
     */
    private static function localize_particle_script($handle, $effect, $options) {
        $scope_settings = Fae_Cursor_Scope::get_scope_for_js($options);
        
        // Get color and speed values
        $color = isset($options['color']) ? $options['color'] : fae_get_particle_free_default_color();
        $speed = isset($options['speed']) ? $options['speed'] : fae_get_particle_free_default_speed();
        
        // Enforce customization restrictions for effects with limited customization
        if (fae_particle_effect_has_limited_customization($effect)) {
            // Check if user can customize color
            if (!fae_can_customize_particle_color($effect)) {
                $color = fae_get_particle_free_default_color();
            }
            // Check if user can customize speed
            if (!fae_can_customize_particle_speed($effect)) {
                $speed = fae_get_particle_free_default_speed();
            }
        }
        
        wp_localize_script($handle, 'faeCursorSettings', array(
            'effect' => $effect,
            'color'  => $color,
            'speed'  => $speed,
            'assetsUrl' => FAE_CURSOR_URL . 'assets/',
            'interactiveCursor' => false, // Always false in free version
            'scope' => $scope_settings,
        ));
    }
    
    /**
     * Enqueue CSS files for an effect
     */
    private static function enqueue_css_files($effect, $effect_dir, $options) {
        $first_css_handle = null;
        
        foreach (glob($effect_dir . '/*.css') as $css_file) {
            $handle = 'fae-cursor-style-' . basename($css_file, '.css');
            // Cursor effects are in assets/effects/cursor/{effect_name}/
            $css_url = FAE_CURSOR_URL . 'assets/effects/cursor/' . $effect . '/' . basename($css_file);
            wp_enqueue_style($handle, $css_url, array(), FAE_CURSOR_VERSION);
            
            if ($first_css_handle === null) {
                $first_css_handle = $handle;
            }
        }
        
        // Add inline CSS to hide default cursor if needed
        if ($first_css_handle !== null) {
            self::add_hide_cursor_css($first_css_handle, $effect, $options);
        }
        
        return $first_css_handle;
    }
    
    /**
     * Add inline CSS to hide default cursor
     */
    private static function add_hide_cursor_css($handle, $effect, $options) {
        $effect_config = fae_cursor_get_effect_config($effect);
        $supports_hide_cursor = isset($effect_config['supports_hide_cursor']) ? $effect_config['supports_hide_cursor'] : true;
        $hide_default_cursor = isset($options['hide_default_cursor']) ? $options['hide_default_cursor'] : '0';
        
        // Always hide cursor for effects that don't support the option
        // Or hide if option is enabled for effects that support it
        if (!$supports_hide_cursor || ($supports_hide_cursor && $hide_default_cursor === '1')) {
            $hide_cursor_css = 'body, body * { cursor: none !important; }';
            wp_add_inline_style($handle, $hide_cursor_css);
        }
    }
    
    /**
     * Enqueue JS files for an effect
     */
    private static function enqueue_js_files($effect, $effect_dir, $options) {
        $localized = false;
        
        foreach (glob($effect_dir . '/*.js') as $js_file) {
            $handle = 'fae-cursor-script-' . basename($js_file, '.js');
            // Cursor effects are in assets/effects/cursor/{effect_name}/
            $js_url = FAE_CURSOR_URL . 'assets/effects/cursor/' . $effect . '/' . basename($js_file);
            wp_enqueue_script($handle, $js_url, array('fae-cursor-scope'), FAE_CURSOR_VERSION, true);

            if (!$localized) {
                self::localize_script($handle, $effect, $options);
                $localized = true;
            }
        }
    }
    
    /**
     * Localize script with settings
     */
    private static function localize_script($handle, $effect, $options) {
        $scope_settings = Fae_Cursor_Scope::get_scope_for_js($options);
        
        // Get color and speed values
        $color = isset($options['color']) ? $options['color'] : fae_get_free_default_color();
        $speed = isset($options['speed']) ? $options['speed'] : fae_get_free_default_speed();
        
        // Enforce customization restrictions for effects with limited customization
        if (fae_cursor_effect_has_limited_customization($effect)) {
            // Check if user can customize color
            if (!fae_can_customize_cursor_color($effect)) {
                $color = fae_get_free_default_color();
            }
            // Check if user can customize speed
            if (!fae_can_customize_cursor_speed($effect)) {
                $speed = fae_get_free_default_speed();
            }
        }
        
        wp_localize_script($handle, 'faeCursorSettings', array(
            'effect' => $effect,
            'color'  => $color,
            'size'   => isset($options['size']) ? $options['size'] : '1.5rem',
            'speed'  => $speed,
            'icon'   => isset($options['icon']) ? $options['icon'] : 'star.svg',
            'flag'   => isset($options['flag']) ? $options['flag'] : '',
            'flagPosition' => isset($options['flag_position']) ? $options['flag_position'] : 'center',
            'assetsUrl' => FAE_CURSOR_URL . 'assets/',
            'hideDefaultCursor' => isset($options['hide_default_cursor']) && $options['hide_default_cursor'] === '1',
            'multiColor' => false, // Always false in free version
            'scope' => $scope_settings,
        ));
    }
    
    /**
     * Enqueue admin assets
     */
    public static function enqueue_admin($hook) {
        // Only load on FaeCursor admin pages
        if ($hook !== 'toplevel_page_fae_cursor') {
            return;
        }
        
        // Enqueue admin styles
        wp_enqueue_style(
            'fae-cursor-admin-styles',
            FAE_CURSOR_URL . 'assets/css/fae-cursor-admin.css',
            array(),
            FAE_CURSOR_VERSION
        );
        
        // Enqueue admin scripts
        wp_enqueue_script(
            'fae-cursor-effects-config',
            FAE_CURSOR_URL . 'config/fae-cursor-effects-config.js',
            array(),
            FAE_CURSOR_VERSION,
            false
        );
        
        wp_enqueue_script(
            'fae-particle-effects-config',
            FAE_CURSOR_URL . 'config/fae-particle-effects-config.js',
            array(),
            FAE_CURSOR_VERSION,
            false
        );
        
        wp_enqueue_script(
            'fae-cursor-admin-script',
            FAE_CURSOR_URL . 'assets/js/fae-cursor-admin.js',
            array('jquery', 'fae-cursor-effects-config', 'fae-particle-effects-config'),
            FAE_CURSOR_VERSION,
            true
        );
        
        // Enqueue review notification script
        wp_enqueue_script(
            'fae-cursor-review-script',
            FAE_CURSOR_URL . 'assets/js/fae-cursor-review.js',
            array('jquery'),
            FAE_CURSOR_VERSION,
            true
        );
        
        // Localize scripts
        self::localize_admin_scripts();
    }
    
    /**
     * Localize admin scripts
     */
    private static function localize_admin_scripts() {
        // Check if Pro plugin is active (WordPress.org compliant - uses class_exists)
        $is_pro = Fae_Cursor_Pro::is_pro();
        
        wp_localize_script(
            'fae-cursor-admin-script',
            'faeAdminData',
            array(
                'baseUrl' => FAE_CURSOR_URL,
                'assetsUrl' => FAE_CURSOR_URL . 'assets/',
                'adminUrl' => admin_url('admin.php'),
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'isPremium' => $is_pro, // True only when FaeCursor Pro plugin is active
                'upgradeUrl' => Fae_Cursor_Pro::get_upgrade_url(),
                'nonces' => array(
                    'cursor' => wp_create_nonce('fae_cursor'),
                    'keyboard' => wp_create_nonce('fae_keyboard'),
                    'particle' => wp_create_nonce('fae_particle'),
                ),
            )
        );
    }
}
