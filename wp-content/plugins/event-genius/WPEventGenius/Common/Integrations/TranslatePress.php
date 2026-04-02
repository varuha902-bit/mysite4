<?php

namespace WPEventGenius\Common\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TranslatePress Integration
 * 
 * Provides locale-aware date and time formatting when TranslatePress is active
 */
class TranslatePress extends BaseIntegration {

	/**
	 * Check if TranslatePress is active
	 * 
	 * @return bool True if TranslatePress is active, false otherwise
	 */
	public function is_active() {
		// Check if TranslatePress plugin is active
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		
		return is_plugin_active( 'translatepress-multilingual/index.php' ) 
			|| class_exists( 'TRP_Translate_Press' )
			|| function_exists( 'trp_get_languages' );
	}

	/**
	 * Initialize the integration
	 * 
	 * Sets up filters to adjust date and time formats based on current language
	 */
	public function init() {
		// Hook into date format filter
		add_filter( 'evge_date_format', array( $this, 'filter_date_format' ), 10, 2 );
		
		// Hook into time format filter
		add_filter( 'evge_time_format', array( $this, 'filter_time_format' ), 10, 2 );

		// Hook into date_i18n filter
		add_filter( 'evge_date_i18n', array( $this, 'maybe_filter_date_i18n' ), 10, 3 );
	}

	public function maybe_filter_date_i18n( $formatteddate, $timestamp, $format ) {
		// if we aren't doing AJAX, return the formatted date
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			return $formatteddate;
		}

		$language = determine_locale();
		
		if ( ! empty( $language ) && function_exists( 'trp_switch_language' ) ) {
			trp_switch_language( $language );
			$original_language_switched = true;
			
			// CRITICAL: Reload the default textdomain to ensure locale files are loaded
			if ( function_exists( 'load_default_textdomain' ) ) {
				load_default_textdomain( $language );
			}
			
			// CRITICAL: Recreate $wp_locale so it contains the new locale's data
			// $wp_locale is initialized early and may have cached data
			unset( $GLOBALS['wp_locale'] );
			$GLOBALS['wp_locale'] = new \WP_Locale();
		}

		$return = date_i18n( $format, $timestamp );
		return $return;
	}

	/**
	 * Filter date format based on current locale
	 * 
	 * @param string $format The date format string
	 * @param array  $args   Additional arguments (context, date, timestamp, locale, original_format)
	 * @return string Modified format string
	 */
	public function filter_date_format( $format, $args ) {
		$locale = isset( $args['locale'] ) ? $args['locale'] : get_locale();
		$lang_code = substr( $locale, 0, 2 );

		// Languages that typically use day-month format (day before month)
		$day_first_languages = array(
			'fr', 'de', 'es', 'it', 'pt', 'nl', 'pl', 'ru', 'sv', 'da', 'fi', 'no', 
			'cs', 'sk', 'hu', 'ro', 'bg', 'hr', 'sl', 'et', 'lv', 'lt', 'el', 'tr', 
			'uk', 'be', 'sr', 'mk', 'sq', 'is', 'ga', 'mt', 'cy'
		);

		// Determine format based on locale
		if ( in_array( $lang_code, $day_first_languages, true ) ) {
			// Day before month format (e.g., "7 juin" in French)
			// For brief context, use text format
			if ( isset( $args['context'] ) && $args['context'] === 'brief' ) {
				return 'j F';
			}
			
			// For other contexts, swap month and day order if needed
			$format = $this->swap_month_day_order( $format, true );
		} elseif ( strpos( $locale, 'en_' ) === 0 ) {
			// English locales: month before day (e.g., "June 7")
			if ( isset( $args['context'] ) && $args['context'] === 'brief' ) {
				return 'F j';
			}
			// For English, ensure month comes before day
			$format = $this->swap_month_day_order( $format, false );
		} elseif ( $lang_code === 'ja' || $lang_code === 'zh' || $lang_code === 'ko' ) {
			// Asian languages: year/month/day numeric format
			if ( isset( $args['context'] ) && $args['context'] === 'brief' ) {
				return 'Y/m/d';
			}
		}

		return $format;
	}

	/**
	 * Swap month and day order in date format string
	 * 
	 * @param string $format The date format string
	 * @param bool   $day_first True to put day first, false to put month first
	 * @return string Modified format string
	 */
	private function swap_month_day_order( $format, $day_first = true ) {
		// Common format patterns - check for exact matches first
		$common_formats = array(
			'F j, Y' => array( 'j F Y', 'F j, Y' ),           // June 7, 2024 -> 7 juin 2024
			'F j Y' => array( 'j F Y', 'F j Y' ),              // June 7 2024 -> 7 juin 2024
			'F j' => array( 'j F', 'F j' ),                    // June 7 -> 7 juin
			'M j, Y' => array( 'j M Y', 'M j, Y' ),            // Jun 7, 2024 -> 7 Jun 2024
			'M j Y' => array( 'j M Y', 'M j Y' ),              // Jun 7 2024 -> 7 Jun 2024
			'M j' => array( 'j M', 'M j' ),                    // Jun 7 -> 7 Jun
			'm/d/Y' => array( 'd/m/Y', 'm/d/Y' ),              // 06/07/2024 -> 07/06/2024
			'm-d-Y' => array( 'd-m-Y', 'm-d-Y' ),              // 06-07-2024 -> 07-06-2024
			'm.d.Y' => array( 'd.m.Y', 'm.d.Y' ),              // 06.07.2024 -> 07.06.2024
			'd/m/Y' => array( 'd/m/Y', 'm/d/Y' ),              // 07/06/2024 -> 06/07/2024
			'd-m-Y' => array( 'd-m-Y', 'm-d-Y' ),              // 07-06-2024 -> 06-07-2024
			'd.m.Y' => array( 'd.m/Y', 'm.d.Y' ),              // 07.06.2024 -> 06.07.2024
		);

		// Formats with day of week - French and day-first languages typically don't use commas
		$day_of_week_formats = array(
			'D, F j, Y' => array( 'D j F Y', 'D, F j, Y' ),    // Mon, June 7, 2024 -> lundi 7 juin 2024 (no comma)
			'l, F j, Y' => array( 'l j F Y', 'l, F j, Y' ),    // Monday, June 7, 2024 -> lundi 7 juin 2024 (no comma)
			'D, j F Y' => array( 'D j F Y', 'D, F j, Y' ),    // Mon, 7 June 2024 -> lundi 7 juin 2024 (no comma)
			'l, j F Y' => array( 'l j F Y', 'l, F j, Y' ),     // Monday, 7 June 2024 -> lundi 7 juin 2024 (no comma)
			'D F j, Y' => array( 'D j F Y', 'D, F j, Y' ),     // Mon June 7, 2024 -> lundi 7 juin 2024
			'l F j, Y' => array( 'l j F Y', 'l, F j, Y' ),     // Monday June 7, 2024 -> lundi 7 juin 2024
		);

		// Check for exact match in day of week formats first
		if ( isset( $day_of_week_formats[ $format ] ) ) {
			return $day_of_week_formats[ $format ][ $day_first ? 0 : 1 ];
		}

		// Check for exact match in common formats
		if ( isset( $common_formats[ $format ] ) ) {
			return $common_formats[ $format ][ $day_first ? 0 : 1 ];
		}

		// For formats with day of week prefix, handle separately
		// French and day-first languages typically don't use commas after day of week
		if ( preg_match( '/^(D|l),?\s*(.+)$/', $format, $matches ) ) {
			$day_of_week = $matches[1];
			$format_without_prefix = $matches[2];

			// Check if the format without prefix is in our common formats
			if ( isset( $common_formats[ $format_without_prefix ] ) ) {
				$swapped_format = $common_formats[ $format_without_prefix ][ $day_first ? 0 : 1 ];
				// For day-first languages, remove comma after day of week (e.g., "lundi 7 juin" not "lundi, 7 juin")
				// For month-first languages (English), keep comma (e.g., "Monday, June 7")
				$separator = $day_first ? ' ' : ', ';
				return $day_of_week . $separator . $swapped_format;
			}
		}

		// For text-based formats (F, M for month names), swap F/j or M/j patterns
		if ( preg_match( '/([FM])\s*([\s\.,\-/]*)\s*(j|d)/', $format, $matches ) ) {
			// Format has month (F or M) before day (j or d)
			if ( $day_first ) {
				// Swap to day before month
				$new_format = preg_replace( '/([FM])\s*([\s\.,\-/]*)\s*(j|d)/', '$3$2$1', $format );
				return $new_format;
			}
			// Already month first, keep as is
			return $format;
		} elseif ( preg_match( '/(j|d)\s*([\s\.,\-/]*)\s*([FM])/', $format, $matches ) ) {
			// Format has day (j or d) before month (F or M)
			if ( ! $day_first ) {
				// Swap to month before day
				$new_format = preg_replace( '/(j|d)\s*([\s\.,\-/]*)\s*([FM])/', '$3$2$1', $format );
				return $new_format;
			}
			// Already day first, keep as is
			return $format;
		}

		// For numeric formats (m/d/Y style), swap m and d
		if ( preg_match( '/m\s*([\s\.,\-/]*)\s*d/', $format ) ) {
			if ( $day_first ) {
				return preg_replace( '/m\s*([\s\.,\-/]*)\s*d/', 'd$1m', $format );
			}
			return $format;
		} elseif ( preg_match( '/d\s*([\s\.,\-/]*)\s*m/', $format ) ) {
			if ( ! $day_first ) {
				return preg_replace( '/d\s*([\s\.,\-/]*)\s*m/', 'm$1d', $format );
			}
			return $format;
		}

		// If we can't determine, return format as-is
		return $format;
	}

	/**
	 * Filter time format based on current locale
	 * 
	 * @param string $format The time format string
	 * @param array  $args   Additional arguments (context, date, timestamp, locale, original_format)
	 * @return string Modified format string
	 */
	public function filter_time_format( $format, $args ) {
		$locale = isset( $args['locale'] ) ? $args['locale'] : get_locale();
		$lang_code = substr( $locale, 0, 2 );

		// Languages that typically use 24-hour format
		$twenty_four_hour_languages = array(
			'fr', 'de', 'es', 'it', 'pt', 'nl', 'pl', 'ru', 'sv', 'da', 'fi', 'no',
			'cs', 'sk', 'hu', 'ro', 'bg', 'hr', 'sl', 'et', 'lv', 'lt', 'el', 'tr',
			'uk', 'be', 'sr', 'mk', 'sq', 'is', 'ga', 'mt', 'cy', 'ja', 'zh', 'ko'
		);

		// For 24-hour languages, use 24-hour format
		if ( in_array( $lang_code, $twenty_four_hour_languages, true ) ) {
			// Check if WordPress is configured with a special 24-hour format
			$wp_time_format = get_option( 'time_format' );
			if ( $wp_time_format === 'H\hi' ) {
				return 'H\hi';
			}
			return 'H:i';
		}

		// English and other locales that typically use 12-hour format
		// Check WordPress time format setting first
		$wp_time_format = get_option( 'time_format' );
		if ( ! empty( $wp_time_format ) && ! in_array( $format, array( 'ga', 'gA', 'g a' ), true ) ) {
			// If WordPress has a time format set and it's not one of our special formats, use it
			return $wp_time_format;
		}

		// Default to 12-hour format with AM/PM for English locales
		return 'g:i A';
	}

	/**
	 * Get locale for a given language code
	 * 
	 * @param string $language_code Language code (e.g., 'fr', 'en')
	 * @return string|false Full locale string or false if not found
	 */
	private static function get_locale_for_language_code( $language_code ) {
		if ( function_exists( 'trp_get_languages' ) ) {
			$languages = trp_get_languages();
			if ( isset( $languages[ $language_code ] ) && isset( $languages[ $language_code ]['locale'] ) ) {
				return $languages[ $language_code ]['locale'];
			}
		}
		
		// Common locale mappings
		$locale_map = array(
			'fr' => 'fr_FR',
			'en' => 'en_US',
			'de' => 'de_DE',
			'es' => 'es_ES',
			'it' => 'it_IT',
			'pt' => 'pt_PT',
			'nl' => 'nl_NL',
		);
		
		return isset( $locale_map[ $language_code ] ) ? $locale_map[ $language_code ] : false;
	}
}

