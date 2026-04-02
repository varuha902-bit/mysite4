<?php
/**
 * FaeCursor Scope Handler
 * 
 * Free version: Effects display on entire website
 * Advanced scoping (specific pages, CSS selectors) available in FaeCursor Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class Fae_Cursor_Scope {
    
    /**
     * Check if effect should be displayed on current page
     * 
     * Free version: Always returns true (display on entire website)
     * Page/CSS selector scoping is available in FaeCursor Pro
     * 
     * @param array $options Effect options (unused in free version)
     * @return bool Always true in free version
     */
    public static function should_display_effect($options) {
        // Free version: Display effects on entire website
        // Advanced scoping is available in FaeCursor Pro
        return true;
    }
    
    /**
     * Get scope settings for JavaScript localization
     * 
     * Free version: Always returns entire_website scope
     * 
     * @param array $options Effect options (unused in free version)
     * @return array Scope settings for JS
     */
    public static function get_scope_for_js($options) {
        // Free version: Always entire website, no CSS selector scoping
        return array(
            'scope_type' => 'entire_website',
            'scope_css_selector' => ''
        );
    }
}

