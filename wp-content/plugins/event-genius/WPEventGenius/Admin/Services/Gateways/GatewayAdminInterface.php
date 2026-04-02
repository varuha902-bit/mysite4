<?php

namespace WPEventGenius\Admin\Services\Gateways;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface for payment gateway admin classes
 * 
 * Defines the contract that all gateway admin classes must implement
 * to ensure consistent structure and functionality across different payment gateways.
 */
interface GatewayAdminInterface {
	
	/**
	 * Initialize WordPress hooks for the gateway admin
	 * 
	 * @return void
	 */
	public function init_hooks();
	
	/**
	 * Get gateway details for display in admin
	 * 
	 * @return array Array containing gateway details like name, description, enabled status, etc.
	 */
	public function details();
	
	/**
	 * Register settings fields for the gateway
	 * 
	 * @return void
	 */
	public function settings_fields();
	
	/**
	 * Render the settings tab HTML for the gateway
	 * 
	 * @return void
	 */
	public function the_tab_html();
} 