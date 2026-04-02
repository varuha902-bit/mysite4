<?php
/**
 * Manage plugin settings and options
 *
 * @since 2.21
 */
namespace WPEventGenius\Common\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class Settings {

	/**
	 * Returns the raw setting value or the default value if none
	 * is set.
	 *
	 * @param string $key
	 *
	 * @return mixed|bool|array|string|null
	 *
	 * @since 2.21
	 */
	public static function get( $key ) {
		// Check Pro settings first if Pro version is active
		if ( ! evge_is_free_version() && class_exists( 'WPEventGenius\Pro\Utils\ProSettings' ) ) {
			$pro_value = \WPEventGenius\Pro\Utils\ProSettings::get( $key );
			if ( $pro_value !== null ) {
				return $pro_value;
			}
		}

		$evge_default_form_settings = get_option( 'evge_default_form_settings', '[]' );
		$evge_default_form_settings = json_decode( $evge_default_form_settings, true );
		if ( ! is_array( $evge_default_form_settings ) ) {
			$evge_default_form_settings = array();
		}

		if ( isset( $evge_default_form_settings[ $key ] ) ) {
			if ( in_array( $key, Defaults::json_settings(), true ) ) {
				return json_decode( $evge_default_form_settings[ $key ], true );
			}
			return $evge_default_form_settings[ $key ];
		}
		
		$evge_settings = get_option( 'evge_settings', array() );

		if ( ! is_array( $evge_settings ) ) {
			$evge_settings = array();
		}

		if ( isset( $evge_settings[ $key ] ) ) {
			if ( in_array( $key, Defaults::json_settings(), true ) ) {
				return json_decode( $evge_settings[ $key ], true );
			}
			return $evge_settings[ $key ];
		}

		$evge_registration_settings = get_option( 'evge_registration_settings', array() );

		if ( ! is_array( $evge_registration_settings ) ) {
			$evge_registration_settings = array();
		}

		if ( isset( $evge_registration_settings[ $key ] ) ) {
			if ( in_array( $key, Defaults::json_settings(), true ) ) {
				return json_decode( $evge_registration_settings[ $key ], true );
			}
			return $evge_registration_settings[ $key ];
		}

		

		return Defaults::get( $key );
	}

	public static function update( $key, $value ) {
		// Check if this is a Pro setting
		if ( ! evge_is_free_version() && class_exists( 'WPEventGenius\Pro\Utils\ProSettings' ) ) {
			if ( \WPEventGenius\Pro\Utils\ProSettings::exists( $key ) ) {
				\WPEventGenius\Pro\Utils\ProSettings::update( $key, $value );
				return;
			}
		}

		$evge_settings = get_option( 'evge_settings', array() );
		$evge_settings[ $key ] = $value;
		update_option( 'evge_settings', $evge_settings );
	}
}
