<?php

namespace WPEventGenius\Admin\Services;

use WPEventGenius\Admin\Settings\EventSettings;
use WPEventGenius\Admin\Settings\EventTextSettings;
use WPEventGenius\Admin\Settings\GeneralSettings;
use WPEventGenius\Admin\Settings\RegistrationSettings;
use WPEventGenius\Admin\Settings\RegistrationTextSettings;
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}
class SettingsService {

	protected $settings_groups;
	public function __construct() {
		$this->settings_groups = array(
			new GeneralSettings(),
			new EventSettings(),
			new RegistrationSettings(),
			new RegistrationTextSettings(),
			new EventTextSettings(),
		);

		// Add Series text settings only for Pro tier
		if ( function_exists( 'evge_is_pro_tier' ) && evge_is_pro_tier() ) {
			if ( class_exists( 'WPEventGenius\Admin\Pro\Settings\SeriesTextSettings' ) ) {
				$this->settings_groups[] = new \WPEventGenius\Admin\Pro\Settings\SeriesTextSettings();
			}
		}
	}

	public function init_hooks() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'register_settings_fields' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );

	}

	public function get_settings() {
		return get_option( 'evge_event_settings' );
	}

	public function register_settings() {
		register_setting( 'evge_settings', 'evge_settings',
			array( '\WPEventGenius\Admin\BaseAdminPage', 'sanitize_settings' )
		);

		register_setting( 'evge_registration_settings', 'evge_registration_settings',
			array( '\WPEventGenius\Admin\BaseAdminPage', 'sanitize_settings' )
		);
	}

	public function register_settings_fields() {
		foreach ( $this->settings_groups as $settings_group ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$active_tab = ! empty( $_REQUEST['tab'] ) ? sanitize_key( wp_unslash( $_REQUEST['tab'] ) ) : 'general';
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$active_subtab = ! empty( $_REQUEST['subtab'] ) ? sanitize_key( wp_unslash( $_REQUEST['subtab'] ) ) : '';

			$active_tab = ! empty( $active_subtab ) ? $active_tab . '-' . $active_subtab : $active_tab;

			if ( $settings_group->get_tab() !== $active_tab ) {
				continue;
			}
			$settings_group->settings();
		}
	}

	public function enqueue( $screen ) {
		if ( ! $this->is_settings_page() ) {
			return;
		}
		EVGE()->script_service()->enqueue_script( 'evge_settings' );
	}

	protected function is_settings_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$subtab = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : '';
		if ( strpos( $page, 'evge-settings' ) === 0 ) {
			return true;
		}
		if ( strpos( $page, 'evge-registrations' ) === 0 ) {
			return $tab === 'forms' && $subtab !== 'builder';
		}
		return false;
	}
}