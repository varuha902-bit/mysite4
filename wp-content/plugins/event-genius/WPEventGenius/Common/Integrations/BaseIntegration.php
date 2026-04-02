<?php

namespace WPEventGenius\Common\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base class for plugin integrations
 * 
 * All integration classes should extend this base class
 */
abstract class BaseIntegration {

	/**
	 * Check if the integrated plugin is active
	 * 
	 * @return bool True if the plugin is active, false otherwise
	 */
	abstract public function is_active();

	/**
	 * Initialize the integration
	 * 
	 * This method should be overridden by child classes to set up hooks and filters
	 */
	abstract public function init();
}

