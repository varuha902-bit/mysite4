<?php

namespace WPEventGenius\Common\Services;

use WPEventGenius\Common\Integrations\BaseIntegration;
use WPEventGenius\Common\Integrations\TranslatePress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Service to manage and initialize plugin integrations.
 *
 * This service is responsible for detecting active third-party plugins
 * and initializing their corresponding integration classes within Event Genius.
 */
class IntegrationService {

	/**
	 * Array of registered integration classes.
	 *
	 * @var BaseIntegration[]
	 */
	private $integrations = [];

	/**
	 * Constructor.
	 * Registers default integrations.
	 */
	public function __construct() {
		$this->register_default_integrations();
	}

	/**
	 * Register default integrations provided by Event Genius.
	 *
	 * @return void
	 */
	private function register_default_integrations() {
		$this->integrations['translatepress'] = new TranslatePress();

		// Allow other plugins to register their integrations
		$this->integrations = apply_filters( 'evge_register_integrations', $this->integrations );
	}

	/**
	 * Initialize all active integrations.
	 * This method should be called during plugin initialization.
	 *
	 * @return void
	 */
	public function init() {
		foreach ( $this->integrations as $integration_id => $integration_instance ) {
			if ( $integration_instance->is_active() ) {
				$integration_instance->init();
			}
		}
	}

	/**
	 * Check if a specific integration is active.
	 *
	 * @param string $integration_id The ID of the integration (e.g., 'translatepress').
	 * @return bool True if the integration is active, false otherwise.
	 */
	public function is_integration_active( $integration_id ) {
		if ( isset( $this->integrations[ $integration_id ] ) ) {
			return $this->integrations[ $integration_id ]->is_active();
		}
		return false;
	}

	/**
	 * Get an instance of an active integration.
	 *
	 * @param string $integration_id The ID of the integration.
	 * @return BaseIntegration|null The integration instance if active, null otherwise.
	 */
	public function get_integration( $integration_id ) {
		if ( isset( $this->integrations[ $integration_id ] ) && $this->integrations[ $integration_id ]->is_active() ) {
			return $this->integrations[ $integration_id ];
		}
		return null;
	}
}

