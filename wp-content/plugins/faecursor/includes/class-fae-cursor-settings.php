<?php
/**
 * FaeCursor Settings Handler
 * Manages plugin settings, options, and sanitization
 */

if (!defined('ABSPATH')) {
    exit;
}

class Fae_Cursor_Settings {
    
    /**
     * Get default options
     */
    public static function get_default_options() {
        return array(
            'effect' => 'none',
            'color' => '#fcba03',
            'size' => '1.5rem',
            'speed' => 'fast',
            'icon' => 'star.svg',
            'hide_default_cursor' => '0',
            'hide_on_mobile' => '0',
            'hide_on_tablet' => '0',
            'hide_on_desktop' => '0',
            'scope_type' => 'entire_website',
            'scope_pages' => array(),
            'scope_css_selector' => '',
            'user_roles' => array(), // Empty array means all users/all roles
            'include_logged_out' => '0',
            'multi_color' => '0', // Multi-color option for bubbles effect
            'flag' => '', // Flag option for flag-effect (empty means use color only)
            'flag_position' => 'center' // Flag position: center, top, bottom, left, right
        );
    }
    
    /**
     * Get current options
     */
    public static function get_options() {
        return get_option('fae_cursor_options', self::get_default_options());
    }
    
    /**
     * Sanitize options
     */
    public static function sanitize_options($input) {
        $sanitized = array();

        // Sanitize effect - get allowed effects from config
        // All effects are now free - no Pro blocking on effects
        if (isset($input['effect'])) {
            $effects_config = fae_cursor_get_effects_config();
            $allowed_effects = array_keys($effects_config);
            $sanitized['effect'] = in_array($input['effect'], $allowed_effects) ? $input['effect'] : 'none';
        }
        
        // Get selected effect for customization limit checks
        $selected_effect = isset($sanitized['effect']) ? $sanitized['effect'] : 'none';

        // Sanitize color - with limited customization enforcement
        if (isset($input['color'])) {
            $input_color = sanitize_hex_color($input['color']) ?: fae_get_free_default_color();
            
            // Check if this effect has limited customization
            if (fae_cursor_effect_has_limited_customization($selected_effect) && !fae_can_customize_cursor_color($selected_effect)) {
                // Free users on limited effects: force default color
                $sanitized['color'] = fae_get_free_default_color();
            } else {
                // Effects without limits: allow any color
                $sanitized['color'] = $input_color;
            }
        }

        // Sanitize size
        if (isset($input['size'])) {
            $allowed_sizes = array('1rem', '1.5rem', '2rem', '2.5rem');
            $sanitized['size'] = in_array($input['size'], $allowed_sizes) ? $input['size'] : '1.5rem';
        }

        // Sanitize speed - with limited customization enforcement
        if (isset($input['speed'])) {
            $allowed_speeds = array('slow', 'normal', 'fast');
            $input_speed = in_array($input['speed'], $allowed_speeds) ? $input['speed'] : 'normal';
            
            // Check if this effect has limited customization
            if (fae_cursor_effect_has_limited_customization($selected_effect) && !fae_can_customize_cursor_speed($selected_effect)) {
                // Free users on limited effects: force "normal" speed only
                $sanitized['speed'] = fae_get_free_default_speed();
            } else {
                // Effects without limits: allow any speed
                $sanitized['speed'] = $input_speed;
            }
        }

        // Sanitize icon (must be an existing SVG in assets/ionicons)
        if (isset($input['icon'])) {
            $icon = basename(sanitize_text_field($input['icon']));
            $icon_path = FAE_CURSOR_DIR . '/assets/ionicons/' . $icon;
            if (substr($icon, -4) === '.svg' && file_exists($icon_path)) {
                $sanitized['icon'] = $icon;
            } else {
                $sanitized['icon'] = 'star.svg';
            }
        }

        // Sanitize hide_default_cursor (checkbox - if not set, default to '0')
        $sanitized['hide_default_cursor'] = isset($input['hide_default_cursor']) && $input['hide_default_cursor'] === '1' ? '1' : '0';

        // Sanitize multi_color - always force to '0' (Pro feature, disabled in free version)
        // Security: Even if someone manipulates the form, the backend enforces this
        $sanitized['multi_color'] = '0';

        // Sanitize flag (must be an existing SVG in assets/flags)
        if (isset($input['flag'])) {
            $flag = basename(sanitize_text_field($input['flag']));
            if (empty($flag)) {
                $sanitized['flag'] = '';
            } else {
                $flag_path = FAE_CURSOR_DIR . '/assets/flags/' . $flag;
                if (substr($flag, -4) === '.svg' && file_exists($flag_path)) {
                    $sanitized['flag'] = $flag;
                } else {
                    $sanitized['flag'] = '';
                }
            }
        }

        // Sanitize flag_position
        if (isset($input['flag_position'])) {
            $allowed_positions = array('center', 'top', 'bottom', 'left', 'right', 'top-left', 'top-right', 'bottom-left', 'bottom-right');
            $sanitized['flag_position'] = in_array($input['flag_position'], $allowed_positions) ? $input['flag_position'] : 'center';
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
        // Security: User role restrictions are a Pro feature
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
            'fae_cursor',
            'fae_cursor_options',
            array(
                'type'              => 'array',
                'sanitize_callback' => array(__CLASS__, 'sanitize_options'),
            )
        );

        add_settings_section(
            'fae_cursor_section',
            __('Mouse Effect Settings', 'faecursor'),
            null,
            'fae_cursor'
        );

        add_settings_field(
            'fae_cursor_effect',
            __('Mouse Effect', 'faecursor'),
            array(__CLASS__, 'render_effect_field'),
            'fae_cursor',
            'fae_cursor_section'
        );
    }
    
    /**
     * Render effect field
     */
    public static function render_effect_field() {
        $options = self::get_options();
        $selected_effect = isset($options['effect']) ? $options['effect'] : 'none';
        $effects_config = fae_cursor_get_effects_config();
        ?>
        <select name="fae_cursor_options[effect]">
            <?php foreach ($effects_config as $effect_id => $config) : ?>
                <option value="<?php echo esc_attr($effect_id); ?>" <?php selected($selected_effect, $effect_id); ?>>
                    <?php echo esc_html($config['display_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
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

