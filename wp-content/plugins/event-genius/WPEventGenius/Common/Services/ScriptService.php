<?php

namespace WPEventGenius\Common\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\Common\Utils\Settings;
use WPEventGenius\Common\Utils\Icon;

class ScriptService {

    /**
     * Track which scripts have been enqueued
     * @var array
     */
    private $enqueued_scripts = [];

    /**
     * Constructor
     */
    public function __construct() {}

    /**
     * Initialize the service
     */
    public function init_hooks() {
        add_action('wp_enqueue_scripts', [$this, 'register_scripts'], 5);
        add_action('admin_enqueue_scripts', [$this, 'register_admin_scripts'], 5);
    }

    /**
     * Register all scripts
     */
    public function register_scripts() {
        $this->register_core_scripts();
        $this->register_feature_scripts();
    }

    /**
     * Register core scripts that are always needed
     */
    private function register_core_scripts() {
        $dev_mode = defined('EVGE_DEV_MODE') && EVGE_DEV_MODE;
        $js_path = $dev_mode ? 'front-end' : 'dist';
        $js_suffix = $dev_mode ? '.js' : '.min.js';

        // Core script that all features depend on
        wp_register_script(
            'evge_common',
            trailingslashit(EVGE_PLUGIN_URL) . "assets/js/{$js_path}/evge-common{$js_suffix}",
            ['jquery'],
            EVGE_VERSION,
            true
        );

        // Localize core script
        wp_localize_script('evge_common', 'evge', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('evge_nonce'),
            'i18n' => [
                'ajaxError' => __('An error occurred. Please try again.', 'evge'),
            ],
            'dynamicContentRefresh' => [
                'enabled' => Settings::get('enable_dynamic_content_refresh') === 'enabled',
                'stalenessThreshold' => (int) Settings::get('dynamic_content_staleness_threshold') * 60, // Convert minutes to seconds
            ],
        ]);
    }

    /**
     * Register feature-specific scripts
     */
    private function register_feature_scripts() {
        $dev_mode = defined('EVGE_DEV_MODE') && EVGE_DEV_MODE;
        $js_path = $dev_mode ? 'front-end' : 'dist';
        $js_suffix = $dev_mode ? '.js' : '.min.js';

        // Registration Form Feature
        wp_register_script(
            'evge_registration_form',
            trailingslashit(EVGE_PLUGIN_URL) . "assets/js/{$js_path}/evge-registration-form{$js_suffix}",
            ['jquery', 'evge_common'],
            EVGE_VERSION,
            true
        );
        wp_localize_script(
            'evge_registration_form',
            'evgeRegistrationForm',
            array(
                'settings' => array(
                    'validateDuplicateEmail' => Settings::get('prevent_duplicate_emails') === 'enabled'
                )
            )
        );

        // Attendee List Feature
        wp_register_script(
            'evge_attendee_list',
            trailingslashit(EVGE_PLUGIN_URL) . "assets/js/{$js_path}/evge-attendee-list{$js_suffix}",
            ['jquery', 'evge_common'],
            EVGE_VERSION,
            true
        );

        // Admin Check In Feature (Standard tier only)
        if ( function_exists( 'evge_is_standard_tier' ) && evge_is_standard_tier() ) {
            // Admin check-in is in pro directory, use direct path
            $admin_check_in_path = $dev_mode ? 'front-end/pro/evge-admin-check-in.js' : 'dist/evge-admin-check-in.min.js';
            wp_register_script(
                'evge_admin_check_in',
                trailingslashit(EVGE_PLUGIN_URL) . "assets/js/{$admin_check_in_path}",
                ['jquery', 'evge_common'],
                EVGE_VERSION,
                true
            );
            wp_localize_script(
                'evge_admin_check_in',
                'evgeAdminCheckIn',
                array(
                    'restUrl' => rest_url( 'wp-event-genius/v1/admin-check-in/' ),
                    'nonce' => wp_create_nonce( 'wp_rest' ),
                    'canManageRegistrations' => current_user_can( 'manage_evge_registrations' ) || current_user_can( 'view_evge_registrations' ),
                    'adminUrl' => admin_url( 'admin.php' ),
                    'i18n' => array(
                        'checkInSuccess' => __( 'Checked in successfully!', 'event-genius' ),
                        'checkInError' => __( 'Error checking in. Please try again.', 'event-genius' ),
                        'loading' => __( 'Loading...', 'event-genius' ),
                        'noResults' => __( 'No registrations found.', 'event-genius' ),
                        'attendanceOptions' => __( 'Attendance Options', 'event-genius' ),
                        'markAsNoShow' => __( 'Mark as No Show', 'event-genius' ),
                        'markAsExcused' => __( 'Mark as Excused', 'event-genius' ),
                        'resetCheckIn' => __( 'Reset Check In', 'event-genius' ),
                        'manageRegistration' => __( 'Manage Registration', 'event-genius' ),
                        'statusUpdateSuccess' => __( 'Status updated successfully.', 'event-genius' ),
                        'statusUpdateError' => __( 'Error updating status. Please try again.', 'event-genius' )
                    )
                )
            );
        }

        // Enqueue Tooltipster
        wp_register_script(
            'evge_tooltipster',
            trailingslashit(EVGE_PLUGIN_URL) . "assets/js/{$js_path}/evge-tooltipster.bundle{$js_suffix}",
            ['jquery'],
            EVGE_VERSION,
            true
        );

        // Bulk Registration Feature (Premium tier only)
        if ( function_exists( 'evge_is_premium_tier' ) && evge_is_premium_tier() ) {
            wp_register_script(
                'evge_bulk_registration',
                trailingslashit(EVGE_PLUGIN_URL) . "assets/js/{$js_path}/evge-bulk-registration{$js_suffix}",
                ['jquery', 'evge_common'],
                EVGE_VERSION,
                true
            );
            wp_localize_script(
                'evge_bulk_registration',
                'evgeBulkRegistration',
                array(
                    'i18n' => array(
                        /* translators: %s: number of events (placeholder: {num}) */
                        'registerForEvents' => __( 'Register for {num} Events', 'event-genius' ),
                    ),
                )
            );
        }

        // Calendar Feature
        wp_register_script(
            'evge_calendar',
            trailingslashit(EVGE_PLUGIN_URL) . "assets/js/{$js_path}/evge-calendar{$js_suffix}",
            ['jquery', 'evge_common', 'evge_tooltipster'],
            EVGE_VERSION,
            true
        );

        // Display Element Feature
        wp_register_script(
            'evge_single_post',
            trailingslashit(EVGE_PLUGIN_URL) . "assets/js/{$js_path}/evge-single-post{$js_suffix}",
            ['jquery', 'evge_common', 'evge_registration_form'],
            EVGE_VERSION,
            true
        );

        // Additional Guests Feature (Standard tier and above)
        // Only register if pro features are available (Standard tier has pro features)
        if (function_exists('evge_is_pro_tier') && evge_is_pro_tier()) {
            // Always use front-end/pro path (file is not minified, so same in dev and production)
            wp_register_script(
                'evge_additional_guests',
                trailingslashit(EVGE_PLUGIN_URL) . 'assets/js/front-end/pro/evge-additional-guests.js',
                ['jquery', 'evge_common'],
                EVGE_VERSION,
                true
            );
        }

        // Pro Payments Feature (only register if Pro features are available)
        if (function_exists('evge_is_pro_tier') && evge_is_pro_tier()) {
            wp_register_script(
                'evge_payments',
                trailingslashit(EVGE_PLUGIN_URL) . 'assets/js/front-end/pro/evge-payments.js',
                ['jquery', 'evge_common'],
                EVGE_VERSION,
                true
            );
            wp_localize_script('evge_payments', 'evgePayments', array(
                'ajax_url' => admin_url('admin-ajax.php'),
            ));
        }

        // Front-end Admin Notices Feature
        wp_register_script(
            'evge-frontend-admin-notices',
            trailingslashit(EVGE_PLUGIN_URL) . 'assets/js/front-end/evge-frontend-admin-notices.js',
            ['jquery'],
            EVGE_VERSION,
            true
        );
        wp_localize_script(
            'evge-frontend-admin-notices',
            'evgeFrontendNotices',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('evge_frontend_notice_dismiss'),
                'i18n'    => array(
                    'dismissing' => __('Dismissing...', 'event-genius'),
                ),
            )
        );
    }

    /**
     * Register admin scripts
     */
    public function register_admin_scripts() {
        $this->register_scripts();
        $this->register_admin_common_scripts();
        $this->register_admin_feature_scripts();
        $this->register_admin_pro_scripts();
    }

    private function register_admin_common_scripts() {
        // Register Select2
        wp_register_script(
            'select2',
            trailingslashit(EVGE_PLUGIN_URL) . 'assets/js/lib/select2.min.js',
            ['jquery'],
            '4.1.0',
            true
        );
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
        wp_register_script(
            'evge_admin_common',
            trailingslashit(EVGE_PLUGIN_URL) . 'assets/js/admin/evge-admin-common.js',
            ['jquery'], 
            EVGE_VERSION
        );
        wp_localize_script(
			'evge_admin_common',
			'evgeAdminCommon',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'ajaxNonce' => wp_create_nonce( 'evge-admin' ),
				'nonce' => wp_create_nonce( 'evge-admin' ),
				'i18n' => array(
					'addMedia' => __( 'Add Media', 'event-genius' ),
					'networkError' => __( 'A network error occurred. Please try again.', 'event-genius' ),
					'refreshing' => __( 'Refreshing...', 'event-genius' ),
					'errorOccurred' => __( 'An error occurred', 'event-genius' ),
					'successfullySentOneEmail' => sprintf( __( 'Successfully sent the confirmation email.', 'event-genius' ), 1 ),
				),
			)
		);
    }

    /**
     * Register admin feature-specific scripts
     */
    private function register_admin_feature_scripts() {

        wp_register_script(
            'evge_admin_form_builder',
            trailingslashit(EVGE_PLUGIN_URL) . 'assets/js/admin/evge-form-builder.js',
            ['jquery', 'jquery-ui-sortable', 'evge_admin_common'],
            EVGE_VERSION,
            true
        );

        wp_register_script(
            'evge_email_template_preview',
            trailingslashit(EVGE_PLUGIN_URL) . 'assets/js/admin/evge-email-template-preview.js',
            ['jquery', 'evge_admin_common'],
            EVGE_VERSION,
            true
        );
        wp_localize_script(
			'evge_admin_form_builder',
			'evgeFB',
			array(
				'textSettings' => array(
					'confirmDelete' => esc_html__( 'This cannot be undone.', 'event-genius' ),
					'confirmLeave' => esc_html__( 'Looks like you have some unsaved changes. Are you sure you want to leave?', 'event-genius' ),
					'unsavedChanges' => __('You have unsaved changes that will be lost if you leave this page.', 'event-genius'),
				),
			)
		);

        wp_register_script(
            'evge_settings',
            trailingslashit(EVGE_PLUGIN_URL) . 'assets/js/admin/evge-settings.js',
            ['jquery', 'evge_admin_common'],
            EVGE_VERSION,
            true
        );
        wp_localize_script(
			'evge_settings',
			'evgeSettings',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'ajaxNonce'   => wp_create_nonce( 'evge-admin' ),
				'toggleGatewayEnabledNonce'  => wp_create_nonce( 'evge_toggle_gateway_enabled' ),
				'saveGatewaySettingsNonce' => wp_create_nonce( 'evge_save_gateway_settings' ),
				'themeTemplateMessage' => __( 'Title and Featured Image settings are disabled because you chose the content override option. These elements are typically already added to the single event page.', 'event-genius' ),
			)
		);

        wp_register_script(
            'evge_admin_taxonomy',
            trailingslashit(EVGE_PLUGIN_URL) . 'assets/js/admin/evge-taxonomy.js',
            ['jquery', 'evge_admin_common'],
            EVGE_VERSION,
            true
        );

        wp_register_script(
            'evge_admin_components_filter_bar',
            trailingslashit(EVGE_PLUGIN_URL) . 'assets/js/admin/components/evge-filter-bar.js',
            ['jquery', 'evge_admin_common'],
            EVGE_VERSION,
            true
        );

        wp_register_script(
            'evge_post_editor_common',
            trailingslashit(EVGE_PLUGIN_URL) . 'assets/js/admin/post-editor/evge-post-editor-common.js',
            ['jquery'],
            EVGE_VERSION,
            true
        );
		wp_localize_script('evge_post_editor_common', 'evgeIcon', array(
			'close' => Icon::get('close'),
		));
		wp_localize_script('evge_post_editor_common', 'evgeCPTAdmin', array(
			'nonce' => wp_create_nonce('evge_admin_nonce'),
            'i18n' => array(
				'trashRecurrenceTitle' => __('Trash Recurrence', 'event-genius'),
				/* translators: %d: number of recurring events in the series */
                'trashRecurrenceMessage' => __('This event has %d recurrences. What would you like to do?', 'event-genius'),
				'trashSingleRecurrence' => __('Trash Single Recurrence', 'event-genius'),
				'trashAllRecurrences' => __('Trash All Recurrences', 'event-genius'),
				'cancel' => __('Cancel', 'event-genius'),
				'errorTrashingRecurrences' => __('Error trashing recurrences. Please try again.', 'event-genius'),
				'loading' => __('Loading...', 'event-genius'),
				'errorLoadingSeriesInfo' => __('Error loading series information. Please try again.', 'event-genius')
			)
		));

        wp_register_script(
            'evge_post_editor_block',
            trailingslashit(EVGE_PLUGIN_URL) . 'assets/js/admin/post-editor/evge-post-editor-block.js',
            ['jquery', 'wp-data', 'wp-hooks', 'wp-notices', 'evge_post_editor_common'],
            EVGE_VERSION,
            true
        );
        wp_localize_script('evge_post_editor_block', 'evgePostSave', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('evge_post_save_nonce'),
            'postType' => EVGE_EVENT_POST_TYPE
        ));

        wp_register_script(
            'evge_post_editor_classic',
            trailingslashit(EVGE_PLUGIN_URL) . 'assets/js/admin/post-editor/evge-post-editor-classic.js',
            ['jquery', 'evge_post_editor_common'],
            EVGE_VERSION,
            true
        );

        wp_register_script(
            'evge_admin_custom_post_type_settings',
            trailingslashit(EVGE_PLUGIN_URL) . 'assets/js/admin/evge-custom-post-type-settings.js',
            ['jquery', 'evge_admin_common'],
            EVGE_VERSION,
            true
        );

        wp_register_script(
            'evge_admin_calendar_builder',
            trailingslashit(EVGE_PLUGIN_URL) . 'assets/js/admin/evge-calendar-builder.js',
            ['jquery', 'evge_admin_common'],
            EVGE_VERSION,
            true
        );  
		wp_localize_script('evge_admin_calendar_builder', 'evgeAdmin', [
			'ajaxurl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('evge_calendar_builder'),
			'i18n' => [
				'previewUpdated' => __('Preview updated', 'event-genius'),
				'previewError' => __('Error updating preview', 'event-genius'),
				'saveSuccess' => __('Calendar saved', 'event-genius'),
				'saveError' => __('Error saving calendar', 'event-genius'),
				'filterError' => __('Please complete all filter fields', 'event-genius'),
				'remove' => __('Remove', 'event-genius'),
			]
		]);

        wp_register_script(
            'evge_admin_blocks_shared',
            trailingslashit(EVGE_PLUGIN_URL) . 'assets/js/admin/evge-blocks-shared.js',
            ['jquery', 'evge_admin_common'],
            EVGE_VERSION,
            true
        );

        // Series Management Script (Pro tier only)
        if ( function_exists( 'evge_is_pro_tier' ) && evge_is_pro_tier() ) {
            wp_register_script(
                'evge_series_management',
                trailingslashit(EVGE_PLUGIN_URL) . 'assets/js/admin/pro/evge-series-management.js',
                ['jquery', 'select2', 'evge_admin_common'],
                EVGE_VERSION,
                true
            );
        }

        // Scheduled Emails Admin Feature (Standard tier only)
        if (function_exists('evge_is_standard_tier') && evge_is_standard_tier()) {
            wp_register_script(
                'evge-scheduled-emails-admin',
                trailingslashit(EVGE_PLUGIN_URL) . 'assets/js/admin/pro/evge-scheduled-emails-admin.js',
                ['jquery'],
                EVGE_VERSION,
                true
            );
            // Check if Classic Editor plugin is active
            $is_classic_editor = defined('CLASSIC_EDITOR_VERSION') || class_exists('Classic_Editor');
            wp_localize_script(
                'evge-scheduled-emails-admin',
                'evgeScheduledEmailsL10n',
                array(
                    'confirmDelete' => __('Are you sure you want to remove this scheduled email?', 'event-genius'),
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('evge_dismiss_skipped_emails'),
                    'cancelNonce' => wp_create_nonce('evge_cancel_scheduled_message'),
                    'isClassicEditor' => $is_classic_editor,
                )
            );
        }

    }

    /**
     * Register admin pro scripts if pro features are available
     */
    private function register_admin_pro_scripts() {
        if (function_exists('evge_is_pro_tier') && evge_is_pro_tier()) {
            // Register pro admin functionality script (available for all pro tiers)
            wp_register_script(
                'evge_admin_pro_functionality',
                trailingslashit(EVGE_PLUGIN_URL) . 'assets/js/admin/pro/evge-admin-pro.js',
                ['jquery', 'evge_admin_common'],
                EVGE_VERSION,
                true
            );
            
            // Register pro form builder script (available for all pro tiers)
            wp_register_script(
                'evge_admin_pro_form_builder',
                trailingslashit(EVGE_PLUGIN_URL) . 'assets/js/admin/pro/evge-form-builder-pro.js',
                ['jquery', 'evge_admin_form_builder'],
                EVGE_VERSION,
                true
            );
            
            // Register email template management script (available for all pro tiers)
            wp_register_script(
                'evge_email_template_management',
                trailingslashit(EVGE_PLUGIN_URL) . 'assets/js/admin/pro/evge-email-template-management.js',
                ['jquery', 'evge_admin_common'],
                EVGE_VERSION,
                true
            );
            
            // Register registration edit AJAX handler script (available for all pro tiers)
            wp_register_script(
                'evge_admin_registration_edit',
                trailingslashit(EVGE_PLUGIN_URL) . 'assets/js/admin/pro/evge-admin-registration-edit.js',
                ['jquery', 'evge_admin_common'],
                EVGE_VERSION,
                true
            );
            
            // Only register advanced payment gateway management for tiers that have advanced payment gateways
            if (function_exists('evge_has_tier_feature') && evge_has_tier_feature('advanced_payment_gateways', 'pro')) {
                wp_register_script(
                    'evge_admin_pro',
                    trailingslashit(EVGE_PLUGIN_URL) . 'assets/js/admin/pro/evge-payment-gateway-management.js',
                    ['jquery', 'jquery-ui-sortable', 'evge_admin_common'],
                    EVGE_VERSION,
                    true
                );
            }
            
            // Localize pro admin functionality script
            wp_localize_script(
                'evge_admin_pro_functionality',
                'evgeAdminPro',
                array(
                    'i18n' => array(
                        'errorUnknown' => __('Unknown error occurred', 'event-genius'),
                        'networkError' => __('Network error occurred. Please try again.', 'event-genius'),
                        'pleaseFillFields' => __('Please fill in all required fields and select registrations.', 'event-genius'),
                        'transferRegistrations' => __('Transfer Registrations', 'event-genius'),
                        'copyRegistrations' => __('Copy Registrations', 'event-genius'),
                    ),
                )
            );
            
            // Enqueue pro admin scripts immediately
            wp_enqueue_script('evge_admin_pro_functionality');
            wp_enqueue_script('evge_admin_pro_form_builder');
            wp_enqueue_script('evge_email_template_management');
            wp_enqueue_script('evge_admin_registration_edit');
            $this->enqueued_scripts['evge_admin_pro_functionality'] = true;
            $this->enqueued_scripts['evge_admin_pro_form_builder'] = true;
            $this->enqueued_scripts['evge_email_template_management'] = true;
            $this->enqueued_scripts['evge_admin_registration_edit'] = true;
            
            // Only enqueue advanced payment gateway management for tiers that have it
            if (function_exists('evge_has_tier_feature') && evge_has_tier_feature('advanced_payment_gateways', 'pro')) {
                wp_enqueue_script('evge_admin_pro');
                $this->enqueued_scripts['evge_admin_pro'] = true;
            }
        }
    }



    /**
     * Enqueue a script if it hasn't been enqueued yet
     * 
     * @param string $script_handle The script handle to enqueue
     */
    public function enqueue_script($script_handle) {
        if (!isset($this->enqueued_scripts[$script_handle])) {
            wp_enqueue_script($script_handle);
            $this->enqueued_scripts[$script_handle] = true;
        }
    }
}