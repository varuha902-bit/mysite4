<?php
namespace WPEventGenius\Common\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\Common\Utils\Settings;

/**
 * Helper class for wrapping dynamic content with timestamp and metadata
 * 
 * This allows JavaScript to detect stale content and refresh it automatically
 * when caching plugins are used.
 * 
 * @package WPEventGenius
 * @since 1.0.0
 */
class DynamicContentHelper {

	/**
	 * Get data attributes for dynamic content
	 * 
	 * Always returns attributes - JavaScript controls whether they're used.
	 * Note: Does not include 'class' attribute - add that to the element directly.
	 * 
	 * @param string $content_type Type identifier (e.g., 'registration-status')
	 * @param int $event_id Event ID
	 * @param string $update_endpoint REST endpoint name
	 * @param array $additional_attrs Additional data attributes (excluding 'class')
	 * @return string HTML attributes string (with leading space)
	 */
	public static function get_data_attributes( $content_type, $event_id, $update_endpoint, $additional_attrs = array() ) {
		$attrs = array_merge(
			array(
				'data-content-type' => $content_type,
				'data-event-id' => $event_id,
				'data-rendered-at' => time(),
				'data-update-endpoint' => $update_endpoint,
			),
			$additional_attrs
		);

		$attrs_string = '';
		foreach ( $attrs as $key => $value ) {
			$attrs_string .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
		}

		return $attrs_string;
	}

	/**
	 * Wrap dynamic content with timestamp and metadata
	 * 
	 * @param string $content HTML content to wrap
	 * @param string $content_type Type identifier (e.g., 'registration-status')
	 * @param int $event_id Event ID
	 * @param string $update_endpoint REST endpoint name
	 * @param array $additional_attrs Additional data attributes
	 * @return string Wrapped HTML
	 */
	public static function wrap_dynamic_content( $content, $content_type, $event_id, $update_endpoint, $additional_attrs = array() ) {
		$attrs_string = self::get_data_attributes( $content_type, $event_id, $update_endpoint, $additional_attrs );

		return sprintf(
			'<div%s><div class="evge-dynamic-content-inner">%s</div></div>',
			$attrs_string,
			$content
		);
	}

	/**
	 * Check if dynamic content refresh is enabled
	 * 
	 * @return bool True if enabled, false if disabled
	 */
	public static function is_enabled() {
		// Check if feature is enabled via setting
		$enabled = Settings::get( 'enable_dynamic_content_refresh' );
		return $enabled === 'enabled';
	}
}
