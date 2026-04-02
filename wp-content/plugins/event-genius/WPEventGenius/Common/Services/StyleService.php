<?php

namespace WPEventGenius\Common\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StyleService {
    /**
     * Track which styles have been enqueued
     * @var array
     */
    private $enqueued_styles = [];

    /**
     * Constructor
     */
    public function __construct() {}

    /**
     * Initialize the service
     */
    public function init_hooks() {
        // Front-end styles
        add_action('wp_enqueue_scripts', [$this, 'register_styles'], 5);
        
        // Admin styles
        add_action('admin_enqueue_scripts', [$this, 'register_admin_styles'], 5);
    }

    /**
     * Register all front-end styles
     */
    public function register_styles() {
        $dev_mode = defined('EVGE_DEV_MODE') && EVGE_DEV_MODE;

        // Always register core styles first (needed for dependencies)
        $this->register_core_styles();

        if ( $dev_mode ) {
            // Register feature styles after common styles
            $this->register_feature_styles();
        } else {
            // In production mode, enqueue minified CSS files
            wp_enqueue_style(
                'evge-styles',
                trailingslashit(EVGE_PLUGIN_URL) . 'assets/css/dist/evge.min.css',
                array(),
                EVGE_VERSION
            );
            
            // Register premium CSS for conditional enqueuing (includes bulk registration CSS)
            if ( function_exists( 'evge_is_premium_tier' ) && evge_is_premium_tier() ) {
                wp_register_style(
                    'evge_bulk_registration',
                    trailingslashit(EVGE_PLUGIN_URL) . 'assets/css/dist/evge-premium.min.css',
                    array( 'evge-styles' ), // Depend on common styles
                    EVGE_VERSION
                );
                
                // Also register as premium-styles for direct enqueuing
                wp_register_style(
                    'evge-premium-styles',
                    trailingslashit(EVGE_PLUGIN_URL) . 'assets/css/dist/evge-premium.min.css',
                    array( 'evge-styles' ), // Depend on common styles
                    EVGE_VERSION
                );
            }
        }

    }

    /**
     * Register all admin styles
     */
    public function register_admin_styles() {
        // Register common styles first (needed by admin common)
        $this->register_core_styles();
        
        // Register admin global styles
        $this->register_admin_global_styles();
        
        // Register other admin styles
        $this->register_admin_feature_styles();

        // Register pro admin styles if pro features are available
        $this->register_admin_pro_styles();

        // Register front-end feature styles (for admin pages that use front-end components)
        $this->register_feature_styles();
    }

    /**
     * Register core styles that are always needed
     */
    private function register_core_styles() {
        // Core styles that all features depend on
        wp_register_style(
            'evge_common',
            trailingslashit(EVGE_PLUGIN_URL) . 'assets/css/front-end/evge-common.css',
            [], // No dependencies - this is the base stylesheet
            EVGE_VERSION
        );
    }

    /**
     * Register feature-specific styles
     */
    private function register_feature_styles() {
        // Registration Form Feature
        wp_register_style(
            'evge_registration_form',
            trailingslashit(EVGE_PLUGIN_URL) . 'assets/css/front-end/evge-registration-form.css',
            ['evge_common'], // Explicitly depend on common styles
            EVGE_VERSION
        );

        // Attendee List Feature
        wp_register_style(
            'evge_attendee_list',
            trailingslashit(EVGE_PLUGIN_URL) . 'assets/css/front-end/evge-attendee-list.css',
            ['evge_common'], // Explicitly depend on common styles
            EVGE_VERSION
        );

        // Admin Check In Feature (Standard tier only)
        if ( function_exists( 'evge_is_standard_tier' ) && evge_is_standard_tier() ) {
            wp_register_style(
                'evge_admin_check_in',
                trailingslashit(EVGE_PLUGIN_URL) . 'assets/css/front-end/pro/evge-admin-check-in.css',
                ['evge_common'], // Explicitly depend on common styles
                EVGE_VERSION
            );
        }

        // Calendar Feature
        wp_register_style(
            'evge_calendar',
            trailingslashit(EVGE_PLUGIN_URL) . 'assets/css/front-end/evge-calendar.css',
            ['evge_common'], // Explicitly depend on common styles
            EVGE_VERSION
        );

        // Event Feature
        wp_register_style(
            'evge_single_post',
            trailingslashit(EVGE_PLUGIN_URL) . 'assets/css/front-end/evge-single-post.css',
            ['evge_common'], // Explicitly depend on common styles
            EVGE_VERSION
        );

        // Pro Payments Feature (only register if Pro features are available)
        if (function_exists('evge_is_pro_tier') && evge_is_pro_tier()) {
            wp_register_style(
                'evge_payments',
                trailingslashit(EVGE_PLUGIN_URL) . 'assets/css/front-end/pro/evge-payments.css',
                ['evge_common'], // Explicitly depend on common styles
                EVGE_VERSION
            );
        }

        // Additional Guests Feature (register for Standard tier and above)
        // This style is used by AdditionalGuestsService when pro features are available
        if (function_exists('evge_is_pro_tier') && evge_is_pro_tier()) {
            wp_register_style(
                'evge_additional_guests',
                trailingslashit(EVGE_PLUGIN_URL) . 'assets/css/front-end/pro/evge-pro.css',
                ['evge_common'], // Explicitly depend on common styles
                EVGE_VERSION
            );
        }

        // Bulk Registration Feature (Premium tier only)
        if ( function_exists( 'evge_is_premium_tier' ) && evge_is_premium_tier() ) {
            $dev_mode = defined('EVGE_DEV_MODE') && EVGE_DEV_MODE;
            $css_path = $dev_mode 
                ? 'assets/css/front-end/premium/evge-bulk-registration.css'
                : 'assets/css/dist/evge-premium.min.css';
            
            wp_register_style(
                'evge_bulk_registration',
                trailingslashit(EVGE_PLUGIN_URL) . $css_path,
                ['evge_common'], // Explicitly depend on common styles
                EVGE_VERSION
            );
        }

        // Front-end Admin Notices Feature
        wp_register_style(
            'evge-frontend-admin-notices',
            trailingslashit(EVGE_PLUGIN_URL) . 'assets/css/front-end/evge-frontend-admin-notices.css',
            [],
            EVGE_VERSION
        );
    }

    /**
     * Register admin global styles that are always needed
     */
    private function register_admin_global_styles() {
        // Register Select2 CSS
        wp_register_style(
            'select2',
            trailingslashit(EVGE_PLUGIN_URL) . 'assets/css/lib/select2.min.css',
            [],
            '4.1.0'
        );
        
        // Admin global styles that all admin features depend on
        wp_register_style(
            'evge_admin_global',
            trailingslashit(EVGE_PLUGIN_URL) . 'assets/css/admin/evge-admin-global.css',
            [], // No dependencies - this is the base admin stylesheet
            EVGE_VERSION
        );

        // Enqueue admin global styles immediately to ensure they load first
        wp_enqueue_style('evge_admin_global');
        $this->enqueued_styles['evge_admin_global'] = true;
    }

    /**
     * Register admin feature-specific styles
     */
    private function register_admin_feature_styles() {
        // Admin Common Styles
        wp_register_style(
            'evge_admin_common',
            trailingslashit(EVGE_PLUGIN_URL) . 'assets/css/admin/evge-admin-common.css',
            ['evge_common'], // Depend on common styles (global utilities)
            EVGE_VERSION
        );

        // Calendar Builder Styles
        wp_register_style(
            'evge_calendar_builder',
            trailingslashit(EVGE_PLUGIN_URL) . 'assets/css/admin/evge-calendar-builder.css',
            ['evge_admin_common'], // Depend on admin global styles
            EVGE_VERSION
        );

        // Form Builder Styles
        wp_register_style(
            'evge_form_builder',
            trailingslashit(EVGE_PLUGIN_URL) . 'assets/css/admin/evge-form-builder.css',
            ['evge_admin_common'], // Depend on admin global styles
            EVGE_VERSION
        );

        // Custom Post Type Settings Styles
        wp_register_style(
            'evge_custom_post_type_settings',
            trailingslashit(EVGE_PLUGIN_URL) . 'assets/css/admin/evge-custom-post-type-settings.css',
            ['evge_admin_common'], // Depend on admin global styles
            EVGE_VERSION
        );

        // Blocks Shared Styles
        wp_register_style(
            'evge_blocks_shared',
            trailingslashit(EVGE_PLUGIN_URL) . 'assets/css/admin/evge-blocks-shared.css',
            ['evge_admin_common'], // Depend on admin global styles
            EVGE_VERSION
        );

        // Series Management Styles (Pro tier only)
        if ( function_exists( 'evge_is_pro_tier' ) && evge_is_pro_tier() ) {
            wp_register_style(
                'evge_series_management',
                trailingslashit(EVGE_PLUGIN_URL) . 'assets/css/admin/pro/evge-series-management.css',
                ['evge_admin_common', 'select2'], // Depend on admin global styles and Select2
                EVGE_VERSION
            );
        }

        // Scheduled Emails Admin Styles (Standard tier only)
        if (function_exists('evge_is_standard_tier') && evge_is_standard_tier()) {
            wp_register_style(
                'evge-scheduled-emails-admin',
                trailingslashit(EVGE_PLUGIN_URL) . 'assets/css/admin/pro/evge-scheduled-emails-admin.css',
                [],
                EVGE_VERSION
            );
        }
    }

    /**
     * Enqueue a style if it hasn't been enqueued yet
     * 
     * @param string $style_handle The style handle to enqueue
     */
    public function enqueue_style($style_handle) {
        if (!isset($this->enqueued_styles[$style_handle])) {
            wp_enqueue_style($style_handle);
            $this->enqueued_styles[$style_handle] = true;
        }
    }

    /**
     * Register admin pro styles if pro features are available
     */
    private function register_admin_pro_styles() {
        if (function_exists('evge_is_pro_tier') && evge_is_pro_tier()) {
            // Register pro admin CSS for all pro tiers
            wp_register_style(
                'evge_admin_pro',
                trailingslashit(EVGE_PLUGIN_URL) . 'assets/css/admin/pro/evge-admin-pro.css',
                ['evge_admin_common'], // Depend on admin common styles
                EVGE_VERSION
            );
            
            // Enqueue pro admin styles immediately
            wp_enqueue_style('evge_admin_pro');
            $this->enqueued_styles['evge_admin_pro'] = true;
        }
    }

    /**
     * Enqueue Pro payments styles if Pro features are available
     */
    public function enqueue_pro_payments_styles() {
        if (function_exists('evge_is_pro_tier') && evge_is_pro_tier()) {
            $this->enqueue_style('evge_payments');
        }
    }
} 