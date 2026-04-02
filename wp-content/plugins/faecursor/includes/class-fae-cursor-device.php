<?php
/**
 * FaeCursor Device Detection
 * Handles device type detection for conditional loading
 */

if (!defined('ABSPATH')) {
    exit;
}

class Fae_Cursor_Device {
    
    /**
     * Detect device type
     * Returns 'mobile', 'tablet', or 'desktop'
     */
    public static function detect() {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- User agent is used for device detection only, not output
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';

        // Tablet regex
        $is_tablet = preg_match('/ipad|tablet|playbook|silk|kindle|android(?!.*mobile)/i', $ua);

        // Mobile regex
        $is_mobile = preg_match('/iphone|ipod|android.*mobile|windows phone|blackberry|mobile/i', $ua) && !$is_tablet;

        if ($is_mobile) {
            return 'mobile';
        }

        if ($is_tablet) {
            return 'tablet';
        }

        return 'desktop';
    }
    
    /**
     * Check if effect should be hidden on current device
     */
    public static function should_hide_effect($options) {
        $device_type = self::detect();
        $hide_on_mobile = isset($options['hide_on_mobile']) ? $options['hide_on_mobile'] : '0';
        $hide_on_tablet = isset($options['hide_on_tablet']) ? $options['hide_on_tablet'] : '0';
        $hide_on_desktop = isset($options['hide_on_desktop']) ? $options['hide_on_desktop'] : '0';
        
        return (
            ($device_type === 'mobile' && $hide_on_mobile === '1') ||
            ($device_type === 'tablet' && $hide_on_tablet === '1') ||
            ($device_type === 'desktop' && $hide_on_desktop === '1')
        );
    }
}

