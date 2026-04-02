<?php
/**
 * FaeCursor Loader
 * Loads all plugin classes and initializes the plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Fae_Cursor_Loader {
    
    /**
     * Initialize the plugin
     */
    public static function init() {
        // Load required classes
        self::load_classes();
        
        // Initialize components
        // Register settings on admin_init (WordPress admin functions must be loaded)
        add_action('admin_init', array('Fae_Cursor_Settings', 'register_settings'));
        add_action('admin_init', array('Fae_Keyboard_Settings', 'register_settings'));
        add_action('admin_init', array('Fae_Particle_Settings', 'register_settings'));
        Fae_Cursor_Admin::init();
        
        // Hook into WordPress
        add_action('wp_enqueue_scripts', array('Fae_Cursor_Enqueue', 'enqueue_frontend'));
        add_action('admin_enqueue_scripts', array('Fae_Cursor_Enqueue', 'enqueue_admin'));
    }
    
    /**
     * Load all plugin classes
     */
    private static function load_classes() {
        // Load helper functions first
        $helper_file = FAE_CURSOR_DIR . '/includes/fae-cursor-helpers.php';
        if (file_exists($helper_file)) {
            require_once $helper_file;
        }
        
        $classes = array(
            'class-fae-cursor-pro.php',
            'class-fae-cursor-settings.php',
            'class-fae-keyboard-settings.php',
            'class-fae-particle-settings.php',
            'class-fae-cursor-device.php',
            'class-fae-cursor-scope.php',
            'class-fae-cursor-enqueue.php',
            'class-fae-cursor-admin.php',
        );
        
        foreach ($classes as $class_file) {
            $file_path = FAE_CURSOR_DIR . '/includes/' . $class_file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }
}

