<?php
/**
 * FaeCursor Admin Interface
 * Handles admin menu, pages, and UI rendering
 */

if (!defined('ABSPATH')) {
    exit;
}

class Fae_Cursor_Admin {
    
    /**
     * Initialize admin functionality
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        add_action('admin_notices', array(__CLASS__, 'save_settings_notice'));
        add_action('admin_head', array(__CLASS__, 'hide_other_admin_notices_on_fae_page'));
        add_filter('admin_footer_text', array(__CLASS__, 'custom_admin_footer'));
        add_filter('update_footer', array(__CLASS__, 'hide_wp_version_in_footer'), 9999);
        add_action('admin_init', array(__CLASS__, 'handle_preview_page'));
        add_action('wp_ajax_fae_save_preview_settings', array(__CLASS__, 'ajax_save_preview_settings'));
        add_action('wp_ajax_fae_save_cursor_settings', array(__CLASS__, 'ajax_save_cursor_settings'));
        add_action('wp_ajax_fae_save_keyboard_settings', array(__CLASS__, 'ajax_save_keyboard_settings'));
        add_action('wp_ajax_fae_save_particle_settings', array(__CLASS__, 'ajax_save_particle_settings'));
    }
    
    /**
     * On FaeCursor settings page only: hide WordPress update nag and all third-party admin notices.
     * Re-add only our own notices (save confirmation, conflict notice).
     */
    public static function hide_other_admin_notices_on_fae_page() {
        if ( ! function_exists( 'get_current_screen' ) ) {
            return;
        }
        $screen = get_current_screen();
        if ( ! $screen || strpos( $screen->id, 'fae_cursor' ) === false ) {
            return;
        }
        remove_all_actions( 'admin_notices' );
        remove_all_actions( 'all_admin_notices' );
        add_action( 'admin_notices', array( __CLASS__, 'save_settings_notice' ) );
        if ( class_exists( 'Fae_Cursor_Conflict_Free' ) ) {
            add_action( 'admin_notices', array( 'Fae_Cursor_Conflict_Free', 'show_admin_notice' ) );
        }
    }

    /**
     * Add admin menu
     */
    public static function add_admin_menu() {
        // Get SVG icon path
        $icon_path = FAE_CURSOR_DIR . '/assets/icons/icon.svg';
        $icon_url = FAE_CURSOR_URL . 'assets/icons/icon.ico'; // Fallback to .ico
        
        // Use SVG icon with base64 encoding if available (like Ultimate Cursor)
        if (file_exists($icon_path)) {
            // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
            $icon_url = 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($icon_path));
        }
        
        add_menu_page(
            'FaeCursor Settings',
            'FaeCursor',
            'manage_options',
            'fae_cursor',
            array(__CLASS__, 'render_options_page'),
            $icon_url,
            100
        );
    }
    
    /**
     * Render options page
     */
    public static function render_options_page() {
        $options = Fae_Cursor_Settings::get_options();
        $selected_effect = isset($options['effect']) ? $options['effect'] : 'none';
        $selected_color = isset($options['color']) ? $options['color'] : '#fcba03';
        $selected_size = isset($options['size']) ? $options['size'] : '1.5rem';
        $selected_speed = isset($options['speed']) ? $options['speed'] : 'fast';
        $selected_icon = isset($options['icon']) ? $options['icon'] : 'star.svg';
        $selected_flag = isset($options['flag']) ? $options['flag'] : '';
        $flag_position = isset($options['flag_position']) ? $options['flag_position'] : 'center';
        
        // Get preview background from cookie (set by JavaScript), default to 'dark'
        $preview_bg = isset($_COOKIE['fae_preview_bg']) ? sanitize_text_field(wp_unslash($_COOKIE['fae_preview_bg'])) : 'dark';
        if (!in_array($preview_bg, array('dark', 'light'))) {
            $preview_bg = 'dark';
        }
        $hide_default_cursor = isset($options['hide_default_cursor']) ? $options['hide_default_cursor'] : '0';
        $multi_color = isset($options['multi_color']) ? $options['multi_color'] : '0';
        $hide_on_mobile = isset($options['hide_on_mobile']) ? $options['hide_on_mobile'] : '0';
        $hide_on_tablet = isset($options['hide_on_tablet']) ? $options['hide_on_tablet'] : '0';
        $hide_on_desktop = isset($options['hide_on_desktop']) ? $options['hide_on_desktop'] : '0';
        
        // Scope settings
        $scope_type = isset($options['scope_type']) ? $options['scope_type'] : 'entire_website';
        $scope_pages = isset($options['scope_pages']) ? $options['scope_pages'] : array();
        $scope_css_selector = isset($options['scope_css_selector']) ? $options['scope_css_selector'] : '';
        $user_roles = isset($options['user_roles']) ? $options['user_roles'] : array();
        
        // Keyboard effect options
        $keyboard_options = Fae_Keyboard_Settings::get_options();
        $selected_keyboard_effect = isset($keyboard_options['effect']) ? $keyboard_options['effect'] : 'none';
        $selected_keyboard_color = isset($keyboard_options['color']) ? $keyboard_options['color'] : '#667eea';
        $keyboard_multi_color = isset($keyboard_options['multi_color']) ? $keyboard_options['multi_color'] : '0';
        $keyboard_hide_on_mobile = isset($keyboard_options['hide_on_mobile']) ? $keyboard_options['hide_on_mobile'] : '0';
        $keyboard_hide_on_tablet = isset($keyboard_options['hide_on_tablet']) ? $keyboard_options['hide_on_tablet'] : '0';
        $keyboard_hide_on_desktop = isset($keyboard_options['hide_on_desktop']) ? $keyboard_options['hide_on_desktop'] : '0';
        
        // Keyboard scope settings
        $keyboard_scope_type = isset($keyboard_options['scope_type']) ? $keyboard_options['scope_type'] : 'entire_website';
        $keyboard_scope_pages = isset($keyboard_options['scope_pages']) ? $keyboard_options['scope_pages'] : array();
        $keyboard_scope_css_selector = isset($keyboard_options['scope_css_selector']) ? $keyboard_options['scope_css_selector'] : '';
        $keyboard_user_roles = isset($keyboard_options['user_roles']) ? $keyboard_options['user_roles'] : array();
        
        // Particle effect options
        $particle_options = Fae_Particle_Settings::get_options();
        $selected_particle_effect = isset($particle_options['effect']) ? $particle_options['effect'] : 'none';
        $selected_particle_color = isset($particle_options['color']) ? $particle_options['color'] : '#667eea';
        $selected_particle_speed = isset($particle_options['speed']) ? $particle_options['speed'] : 'normal';
        $particle_interactive_cursor = isset($particle_options['interactive_cursor']) ? $particle_options['interactive_cursor'] : '0';
        $particle_hide_default_cursor = isset($particle_options['hide_default_cursor']) ? $particle_options['hide_default_cursor'] : '0';
        $particle_hide_on_mobile = isset($particle_options['hide_on_mobile']) ? $particle_options['hide_on_mobile'] : '0';
        $particle_hide_on_tablet = isset($particle_options['hide_on_tablet']) ? $particle_options['hide_on_tablet'] : '0';
        $particle_hide_on_desktop = isset($particle_options['hide_on_desktop']) ? $particle_options['hide_on_desktop'] : '0';
        
        // Particle scope settings
        $particle_scope_type = isset($particle_options['scope_type']) ? $particle_options['scope_type'] : 'entire_website';
        $particle_scope_pages = isset($particle_options['scope_pages']) ? $particle_options['scope_pages'] : array();
        $particle_user_roles = isset($particle_options['user_roles']) ? $particle_options['user_roles'] : array();
        
        include FAE_CURSOR_DIR . '/includes/views/admin-page.php';
    }
    
    /**
     * Get effect abbreviation for display
     * 
     * @param string $effect_id Effect ID
     * @param string $type Effect type (cursor, keyboard, particle)
     * @return string Abbreviation
     */
    public static function get_effect_abbrev($effect_id, $type = 'cursor') {
        $abbrevs = array(
            // Cursor effects
            'cursor' => array(
                'none' => '—',
                'drop-effect' => 'DR',
                'rise-effect' => 'RI',
                'line-effect' => 'LN',
                'duo-circle' => 'DC',
                'duo-circle-2' => 'D2',
                'bubbles-effect' => 'BB',
                'fireworks-effect' => 'FW',
                'spark-effect' => 'SP',
                'flag-effect' => 'FL',
                'genuine-effect' => 'GN',
                'gradient-trail' => 'GT',
                'leaf-effect' => 'LE',
            ),
            // Keyboard effects
            'keyboard' => array(
                'none' => '—',
                'sparkle-keys' => 'SK',
                'bubble-keys' => 'BK',
                'ink-keys' => 'IK',
                'matrix-keys' => 'MK',
            ),
            // Particle effects
            'particle' => array(
                'none' => '—',
                'particle-1' => 'MG', // Morph Grid
                'particle-4' => 'SC', // Swirl Cursor
                'particle-5' => 'RC', // Repel Cursor
                'particle-8' => 'CB', // Color Borrower
                'particle-10' => 'SF', // Snowfall
            ),
        );
        
        if (isset($abbrevs[$type][$effect_id])) {
            return $abbrevs[$type][$effect_id];
        }
        
        // Fallback: first 2 letters uppercase
        return strtoupper(substr($effect_id, 0, 2));
    }
    
    /**
     * Save settings notice
     */
    public static function save_settings_notice() {
        if (
            isset($_GET['settings-updated']) &&
            '1' === sanitize_text_field(wp_unslash($_GET['settings-updated'])) &&
            isset($_GET['_wpnonce']) &&
            wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'update-options')
        ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <?php esc_html_e('Settings saved! ', 'faecursor'); ?>
                    <a href="<?php echo esc_url(home_url()); ?>" target="_blank">
                        <?php esc_html_e('Visit your site\'s frontend', 'faecursor'); ?>
                    </a>
                    <?php esc_html_e(' to see the mouse effects in action.', 'faecursor'); ?>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Custom admin footer: on FaeCursor page, show only plugin version (no "Thank you for creating with WordPress").
     */
    public static function custom_admin_footer($footer_text) {
        if ( ! function_exists( 'get_current_screen' ) ) {
            return $footer_text;
        }
        $screen = get_current_screen();
        if ( $screen && $screen->id === 'toplevel_page_fae_cursor' ) {
            return '';
        }
        return $footer_text;
    }

    /**
     * Hide WordPress version in admin footer on FaeCursor page.
     */
    public static function hide_wp_version_in_footer($content) {
        if ( ! function_exists( 'get_current_screen' ) ) {
            return $content;
        }
        $screen = get_current_screen();
        if ( $screen && $screen->id === 'toplevel_page_fae_cursor' ) {
            return '';
        }
        return $content;
    }
    
    /**
     * Handle preview page request
     */
    public static function handle_preview_page() {
        // Embed preview (for iframe in dashboard)
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Preview page for admin users only
        if (isset($_GET['fae_embed_preview']) && $_GET['fae_embed_preview'] === '1' && current_user_can('manage_options')) {
            include FAE_CURSOR_DIR . '/includes/views/preview-embed.php';
            exit;
        }
        // Full preview page (legacy, can still be used)
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Preview page for admin users only
        if (isset($_GET['fae_preview']) && $_GET['fae_preview'] === '1' && current_user_can('manage_options')) {
            include FAE_CURSOR_DIR . '/includes/views/preview-page.php';
            exit;
        }
    }
    
    /**
     * AJAX handler to save settings from preview page
     */
    public static function ajax_save_preview_settings() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'fae_save_preview_settings')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $type = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : 'cursor';
        $color = isset($_POST['color']) ? sanitize_hex_color(wp_unslash($_POST['color'])) : '#667eea';
        $speed = isset($_POST['speed']) ? sanitize_text_field(wp_unslash($_POST['speed'])) : 'normal';
        $size = isset($_POST['size']) ? sanitize_text_field(wp_unslash($_POST['size'])) : '1.5rem';
        $icon = isset($_POST['icon']) ? sanitize_file_name(wp_unslash($_POST['icon'])) : 'star.svg';
        
        // Get current options and update only appearance settings
        if ($type === 'cursor') {
            $options = get_option('fae_cursor_options', array());
            $options['color'] = $color;
            $options['speed'] = $speed;
            $options['size'] = $size;
            $options['icon'] = $icon;
            update_option('fae_cursor_options', $options);
        } elseif ($type === 'keyboard') {
            $options = get_option('fae_keyboard_options', array());
            $options['color'] = $color;
            update_option('fae_keyboard_options', $options);
        } elseif ($type === 'particle') {
            $options = get_option('fae_particle_options', array());
            $options['color'] = $color;
            $options['speed'] = $speed;
            update_option('fae_particle_options', $options);
        }
        
        wp_send_json_success('Settings saved');
    }
    
    /**
     * AJAX handler to save cursor settings
     */
    public static function ajax_save_cursor_settings() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'fae_cursor')) {
            wp_send_json_error(array('message' => 'Invalid security token. Please refresh the page and try again.'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'You do not have permission to save settings.'));
        }
        
        // Get and sanitize all form data
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Array data sanitized via sanitize_options method
        $input = isset($_POST['fae_cursor_options']) ? wp_unslash($_POST['fae_cursor_options']) : array();
        $sanitized = Fae_Cursor_Settings::sanitize_options($input);
        
        // Get existing options to compare
        $existing = Fae_Cursor_Settings::get_options();
        
        // Check if effect was enabled/disabled
        $old_effect = isset($existing['effect']) ? $existing['effect'] : 'none';
        $new_effect = isset($sanitized['effect']) ? $sanitized['effect'] : 'none';
        $effect_changed = ($old_effect !== $new_effect);
        
        // Free version: Check if another effect type is already active
        // Only allow one effect type (cursor, keyboard, or particle) at a time
        if ($new_effect !== 'none') {
            $keyboard_options = Fae_Keyboard_Settings::get_options();
            $particle_options = Fae_Particle_Settings::get_options();
            $keyboard_effect = isset($keyboard_options['effect']) ? $keyboard_options['effect'] : 'none';
            $particle_effect = isset($particle_options['effect']) ? $particle_options['effect'] : 'none';
            
            if ($keyboard_effect !== 'none' || $particle_effect !== 'none') {
                $active_types = array();
                if ($keyboard_effect !== 'none') {
                    $active_types[] = 'Keyboard';
                }
                if ($particle_effect !== 'none') {
                    $active_types[] = 'Screen';
                }
                $active_type = implode(' or ', $active_types);
                
                wp_send_json_error(array(
                    'message' => sprintf(
                        'Only one effect type can be active at a time. Please disable the %s effect first, or upgrade to Pro to use multiple effects simultaneously.',
                        $active_type
                    ),
                    'upgrade_notice' => true,
                    'active_type' => $active_type
                ));
            }
        }
        
        // Check if values actually changed (compare serialized arrays for accurate comparison)
        $values_changed = (serialize($sanitized) !== serialize($existing));
        
        // Save the options
        $result = update_option('fae_cursor_options', $sanitized);
        
        // If values are the same, update_option returns false but that's OK
        // If values changed and update_option returns false, that's an error
        if ($result !== false || !$values_changed) {
            // Determine appropriate message
            if ($effect_changed) {
                if ($new_effect === 'none') {
                    $message = 'Cursor effect disabled successfully!';
                } else {
                    $effect_config = fae_cursor_get_effect_config($new_effect);
                    $effect_name = isset($effect_config['display_name']) ? $effect_config['display_name'] : $new_effect;
                    $message = 'Cursor effect "' . $effect_name . '" enabled successfully!';
                }
            } else {
                $message = 'Cursor settings saved successfully!';
            }
            
            wp_send_json_success(array(
                'message' => $message,
                'options' => $sanitized,
                'effect_changed' => $effect_changed,
                'old_effect' => $old_effect,
                'new_effect' => $new_effect
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to save settings. Please try again.'));
        }
    }
    
    /**
     * AJAX handler to save keyboard settings
     */
    public static function ajax_save_keyboard_settings() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'fae_keyboard')) {
            wp_send_json_error(array('message' => 'Invalid security token. Please refresh the page and try again.'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'You do not have permission to save settings.'));
        }
        
        // Get and sanitize all form data
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Array data sanitized via sanitize_options method
        $input = isset($_POST['fae_keyboard_options']) ? wp_unslash($_POST['fae_keyboard_options']) : array();
        $sanitized = Fae_Keyboard_Settings::sanitize_options($input);
        
        // Get existing options to compare
        $existing = Fae_Keyboard_Settings::get_options();
        
        // Check if effect was enabled/disabled
        $old_effect = isset($existing['effect']) ? $existing['effect'] : 'none';
        $new_effect = isset($sanitized['effect']) ? $sanitized['effect'] : 'none';
        $effect_changed = ($old_effect !== $new_effect);
        
        // Free version: Check if another effect type is already active
        // Only allow one effect type (cursor, keyboard, or particle) at a time
        if ($new_effect !== 'none') {
            $cursor_options = Fae_Cursor_Settings::get_options();
            $particle_options = Fae_Particle_Settings::get_options();
            $cursor_effect = isset($cursor_options['effect']) ? $cursor_options['effect'] : 'none';
            $particle_effect = isset($particle_options['effect']) ? $particle_options['effect'] : 'none';
            
            if ($cursor_effect !== 'none' || $particle_effect !== 'none') {
                $active_types = array();
                if ($cursor_effect !== 'none') {
                    $active_types[] = 'Cursor';
                }
                if ($particle_effect !== 'none') {
                    $active_types[] = 'Screen';
                }
                $active_type = implode(' or ', $active_types);
                
                wp_send_json_error(array(
                    'message' => sprintf(
                        'Only one effect type can be active at a time. Please disable the %s effect first, or upgrade to Pro to use multiple effects simultaneously.',
                        $active_type
                    ),
                    'upgrade_notice' => true,
                    'active_type' => $active_type
                ));
            }
        }
        
        // Check if values actually changed (compare serialized arrays for accurate comparison)
        $values_changed = (serialize($sanitized) !== serialize($existing));
        
        // Save the options
        $result = update_option('fae_keyboard_options', $sanitized);
        
        // If values are the same, update_option returns false but that's OK
        // If values changed and update_option returns false, that's an error
        if ($result !== false || !$values_changed) {
            // Determine appropriate message
            if ($effect_changed) {
                if ($new_effect === 'none') {
                    $message = 'Keyboard effect disabled successfully!';
                } else {
                    $effect_config = fae_keyboard_get_effect_config($new_effect);
                    $effect_name = isset($effect_config['display_name']) ? $effect_config['display_name'] : $new_effect;
                    $message = 'Keyboard effect "' . $effect_name . '" enabled successfully!';
                }
            } else {
                $message = 'Keyboard settings saved successfully!';
            }
            
            wp_send_json_success(array(
                'message' => $message,
                'options' => $sanitized,
                'effect_changed' => $effect_changed,
                'old_effect' => $old_effect,
                'new_effect' => $new_effect
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to save settings. Please try again.'));
        }
    }
    
    /**
     * AJAX handler to save particle settings
     */
    public static function ajax_save_particle_settings() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'fae_particle')) {
            wp_send_json_error(array('message' => 'Invalid security token. Please refresh the page and try again.'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'You do not have permission to save settings.'));
        }
        
        // Get and sanitize all form data
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Array data sanitized via sanitize_options method
        $input = isset($_POST['fae_particle_options']) ? wp_unslash($_POST['fae_particle_options']) : array();
        $sanitized = Fae_Particle_Settings::sanitize_options($input);
        
        // Get existing options to compare
        $existing = Fae_Particle_Settings::get_options();
        
        // Check if effect was enabled/disabled
        $old_effect = isset($existing['effect']) ? $existing['effect'] : 'none';
        $new_effect = isset($sanitized['effect']) ? $sanitized['effect'] : 'none';
        $effect_changed = ($old_effect !== $new_effect);
        
        // Free version: Check if another effect type is already active
        // Only allow one effect type (cursor, keyboard, or particle) at a time
        if ($new_effect !== 'none') {
            $cursor_options = Fae_Cursor_Settings::get_options();
            $keyboard_options = Fae_Keyboard_Settings::get_options();
            $cursor_effect = isset($cursor_options['effect']) ? $cursor_options['effect'] : 'none';
            $keyboard_effect = isset($keyboard_options['effect']) ? $keyboard_options['effect'] : 'none';
            
            if ($cursor_effect !== 'none' || $keyboard_effect !== 'none') {
                $active_types = array();
                if ($cursor_effect !== 'none') {
                    $active_types[] = 'Cursor';
                }
                if ($keyboard_effect !== 'none') {
                    $active_types[] = 'Keyboard';
                }
                $active_type = implode(' or ', $active_types);
                
                wp_send_json_error(array(
                    'message' => sprintf(
                        'Only one effect type can be active at a time. Please disable the %s effect first, or upgrade to Pro to use multiple effects simultaneously.',
                        $active_type
                    ),
                    'upgrade_notice' => true,
                    'active_type' => $active_type
                ));
            }
        }
        
        // Check if values actually changed (compare serialized arrays for accurate comparison)
        $values_changed = (serialize($sanitized) !== serialize($existing));
        
        // Save the options
        $result = update_option('fae_particle_options', $sanitized);
        
        // If values are the same, update_option returns false but that's OK
        // If values changed and update_option returns false, that's an error
        if ($result !== false || !$values_changed) {
            // Determine appropriate message
            if ($effect_changed) {
                if ($new_effect === 'none') {
                    $message = 'Particle effect disabled successfully!';
                } else {
                    $effect_config = fae_particle_get_effect_config($new_effect);
                    $effect_name = isset($effect_config['display_name']) ? $effect_config['display_name'] : $new_effect;
                    $message = 'Particle effect "' . $effect_name . '" enabled successfully!';
                }
            } else {
                $message = 'Particle settings saved successfully!';
            }
            
            wp_send_json_success(array(
                'message' => $message,
                'options' => $sanitized,
                'effect_changed' => $effect_changed,
                'old_effect' => $old_effect,
                'new_effect' => $new_effect
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to save settings. Please try again.'));
        }
    }
}

