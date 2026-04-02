<?php

namespace WPEventGenius\Common\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class DateFormatter {
	public static function date_format( $date, $context ) {
		$timestamp = self::timestamp( $date );

		if ($context === 'brief') {
			// Get WordPress locale
			$wp_locale = get_locale();
			
			// Default to m/d/Y for US, d/m/Y for most others
			$format = strpos($wp_locale, 'en_US') === 0 ? 'm/d/Y' : 'd/m/Y';
			
			// Override for specific locales
			switch ($wp_locale) {
				case 'en_GB':
				case 'en_AU':
				case 'en_NZ':
					$format = 'd/m/Y';
					break;
				case 'ja':
				case 'zh_CN':
				case 'zh_TW':
					$format = 'Y/m/d';
					break;
			}
			
			// Apply filter to allow dynamic format changes
			$format = apply_filters( 
				'evge_date_format', 
				$format, 
				array(
					'context' => $context,
					'date' => $date,
					'timestamp' => $timestamp,
					'locale' => function_exists('determine_locale') ? determine_locale() : get_locale(),
					'original_format' => $format,
				)
			);
			
			return self::date_i18n( $timestamp, $format );
		}

		$format = Settings::get( $context . '_format' );

		if ( 'custom' === $format ) {
			$format = Settings::get( $context . '_format_custom' );
			if ( empty( $format ) ) {
				$format = Defaults::get( $context );
			}
		}

		if ( empty( $format ) ) {
			$format = get_option( 'date_format' );
		}

		if ( $context === 'time_format' ) {
			$format = self::time_format( $date );
		}

		// Apply filter to allow dynamic format changes
		$format = apply_filters( 
			'evge_date_format', 
			$format, 
			array(
				'context' => $context,
				'date' => $date,
				'timestamp' => $timestamp,
				'locale' => function_exists('determine_locale') ? determine_locale() : get_locale(),
				'original_format' => $format,
			)
		);

		return self::date_i18n( $timestamp, $format );
	}

	public static function time_format( $date, $context = '' ) {
		$timestamp = self::timestamp( $date );

		$format = Settings::get( 'time_format' );

		if ( 'custom' === $format ) {
			$format = Settings::get( 'time_format_custom' );
			if ( empty( $format ) ) {
				$format = Defaults::get( 'time_format' );
			}
		}

		if ( empty( $format ) ) {
			$format = get_option( 'time_format' );
		}

		if ( in_array( $format, array( 'ga', 'gA', 'g a' ), true ) ) {
			if ( gmdate('i', $timestamp) !== '00' ) {
				$format = str_replace( 'g', 'g:i', $format );
			}
		}

		// Apply filter to allow dynamic format changes
		$format = apply_filters( 
			'evge_time_format', 
			$format, 
			array(
				'context' => $context,
				'date' => $date,
				'timestamp' => $timestamp,
				'locale' => function_exists('determine_locale') ? determine_locale() : get_locale(),
				'original_format' => $format,
			)
		);

		return self::date_i18n( $timestamp, $format );
	}

	public static function timestamp( $date_or_timestamp ) {
		if ( is_int( $date_or_timestamp ) ) {
			return $date_or_timestamp;
		}

		if ( empty( $date_or_timestamp ) ) {
			return 0;
		}

		return strtotime( $date_or_timestamp );
	}

	public static function gcal_date( $date_or_timestamp, $timezone = 'UTC' ) {
		// If we have a date string, parse it in the specified timezone
		if ( ! is_int( $date_or_timestamp ) && ! empty( $date_or_timestamp ) ) {
			$timezone_obj = self::timezone_object( $timezone );
			$t = new \DateTime( $date_or_timestamp, $timezone_obj );
		} else {
			// If we have a timestamp, convert it to the specified timezone
			$timestamp = self::timestamp( $date_or_timestamp );
			if ( ! $timestamp ) {
				$timestamp = time();
			}
			$timezone_obj = self::timezone_object( $timezone );
			$t = new \DateTime( '@' . $timestamp );
			$t->setTimezone( $timezone_obj );
		}
		
		// Format without 'Z' suffix when using timezone parameter (ctz)
		// Google Calendar expects local time format when ctz parameter is provided
		$date = $t->format( 'Ymd\THis' );
		return $date;
	}

	public static function date_string( $context, $start_date_or_timestamp, $end_date_or_timestamp, $timezone = 'UTC' ) {
		$start_timestamp = self::timestamp( $start_date_or_timestamp );
		$end_timestamp = self::timestamp( $end_date_or_timestamp );

		$date_time_separator = ' | ';
		$multi_day_separator = ' - ';
		$multi_day_datetime_separator = ' ';

		$string = '';
		if ( $context === 'full_date' || $context === 'date_summary' ) {
			if ( self::is_same_list_day( $start_timestamp, $end_timestamp, $timezone ) ) {
				$string = self::date_format( $start_timestamp, $context ) . $date_time_separator . self::time_format( $start_timestamp, $context ) . ' - ' . self::time_format( $end_timestamp, $context );
			} else {
				$string = self::date_format( $start_timestamp, $context ) . $multi_day_datetime_separator . self::time_format( $start_timestamp, $context ) . $multi_day_separator . self::date_format( $end_timestamp, $context ) . $multi_day_datetime_separator . self::time_format( $end_timestamp, $context );
			}
		} elseif ( $context === 'registration_timeline' ) {
			$string = self::date_format( $start_timestamp, $context );
		} elseif ( $context === 'registration_admin' ) {
			$dt = new \DateTime('@' . $start_timestamp, self::timezone_object('UTC'));
			$dt->setTimezone(self::timezone_object($timezone));
			$date_format = get_option( 'date_format' );
			$time_format = get_option( 'time_format' );
			$string = $dt->format( $date_format . ' ' . $time_format );
		} elseif ( $context === 'registration_admin_card' ) {
			$dt = new \DateTime('@' . $start_timestamp, self::timezone_object('UTC'));
			$dt->setTimezone(self::timezone_object($timezone));
			
			// Get current year
			$current_year = (int)date('Y');
			$event_year = (int)$dt->format('Y');
			
			// Get date format and remove year if it's current year
			$date_format = get_option('date_format');
			if ($event_year === $current_year) {
				// Remove year from format
				$date_format = str_replace(array('Y', 'y'), '', $date_format);
				// Clean up any remaining separators
				$date_format = trim($date_format, ' -/.,');
			}
			
			$string = $dt->format($date_format);
		} elseif ( $context === 'brief' ) {
			if (self::date_format($start_timestamp, 'brief' ) === self::date_format($end_timestamp, 'brief')) {
				$string = self::date_format($start_timestamp, 'brief') . ' ' . 
					self::time_format($start_timestamp, 'brief') . '-' . 
					self::time_format($end_timestamp, 'brief');
			} else {
				$string = self::date_format($start_timestamp, 'brief') .  ' ' . self::time_format($start_timestamp, 'brief') .' - ' . 
					self::date_format($end_timestamp, 'brief') . ' ' . self::time_format($end_timestamp, 'brief');
			}
		}

		return $string;
	}

	public static function is_same_list_day($timestamp1, $timestamp2, $timezone = 'UTC') {
		try {
			// Create DateTime objects with the given timezone
			$date1 = new \DateTime();
			$date1->setTimestamp($timestamp1);
			$date1->setTimezone(self::timezone_object($timezone));

			$date2 = new \DateTime();
			$date2->setTimestamp($timestamp2);
			$date2->setTimezone(self::timezone_object($timezone));

			// Compare year, month, and day
			return $date1->format('Y-m-d') === $date2->format('Y-m-d');
		} catch (\Exception $e) {
			// Handle invalid timezone
			return false;
		}
	}

	public static function time( $date_or_timestamp ) {
		$timestamp = self::timestamp( $date_or_timestamp );
		$format = Settings::get( 'time_format' );

		if ( 'custom' === $format ) {
			$format = Settings::get( 'time_format_custom' );
			if ( empty( $format ) ) {
				$format = Defaults::get( 'time_format' );
			}
		}

		if ( empty( $format ) ) {
			$format = get_option( 'time_format' );
		}

		// Apply filter to allow dynamic format changes
		$format = apply_filters( 
			'evge_time_format', 
			$format, 
			array(
				'context' => '', // No specific context for this method
				'date' => $date_or_timestamp,
				'timestamp' => $timestamp,
				'locale' => function_exists('determine_locale') ? determine_locale() : get_locale(),
				'original_format' => $format,
			)
		);

		return self::date_i18n( $timestamp, $format );
	}

	public static function timezone_object( $timezone_string ) {
		if (empty($timezone_string) || $timezone_string === 'default') {
			$timezone_string = wp_timezone_string();
		}

		// Handle empty string case
		if (empty($timezone_string)) {
			return new \DateTimeZone('UTC');
		}

		try {
			// First try to create timezone object directly
			$timezone_object = new \DateTimeZone($timezone_string);
		} catch (\Exception $e) {
			// If that fails, check if it's an offset-based string
			if (preg_match('/^UTC[+-]\d{1,2}$/', $timezone_string)) {
				// Convert UTC±X to ±X:00
				$offset = substr($timezone_string, 3);
				$offset_string = sprintf('%s%02d:00', 
					$offset[0], // Keep the + or -
					abs((int)substr($offset, 1)) // Get the number
				);
				try {
					$timezone_object = new \DateTimeZone($offset_string);
				} catch (\Exception $e) {
					$timezone_object = new \DateTimeZone('UTC');
				}
			} else {
				$timezone_object = new \DateTimeZone('UTC');
			}
		}
		return $timezone_object;
	}

	/**
	 * Get the numeric value (0-6) for a given day of the week
	 * 
	 * @param string $day_name Lowercase day name (e.g. 'sunday', 'monday')
	 * @return int Numeric value (0-6) representing the day of the week
	 */
	public static function get_weekday_number($day_name) {
		static $weekday_map = [
			'sunday' => 0,
			'monday' => 1,
			'tuesday' => 2,
			'wednesday' => 3,
			'thursday' => 4,
			'friday' => 5,
			'saturday' => 6
		];
		
		return $weekday_map[$day_name] ?? 0;
	}

	/**
	 * Get the day name for a given numeric value (0-6)
	 * 
	 * @param int $day_number Numeric value (0-6) representing the day of the week
	 * @return string Lowercase day name (e.g. 'sunday', 'monday')
	 */
	public static function get_weekday_name($day_number) {
		static $weekday_names = [
			'sunday',
			'monday',
			'tuesday',
			'wednesday',
			'thursday',
			'friday',
			'saturday'
		];
		
		return $weekday_names[$day_number] ?? 'sunday';
	}


	/**
	 * Get localized abbreviated weekday names starting from a given first day of the week
	 * 
	 * @param int $first_weekday Numeric value (0-6) representing the first day of the week (0 = Sunday, 1 = Monday, etc.)
	 * @param string|DateTimeZone|null $timezone Optional timezone string or DateTimeZone object. Defaults to WordPress timezone.
	 * @return array Array of abbreviated weekday names (e.g., ['Sun', 'Mon', 'Tue', ...])
	 */
	public static function get_weekday_names($first_weekday, $timezone = null) {
		if ($timezone === null) {
			$timezone = self::timezone_object(wp_timezone_string());
		} elseif (is_string($timezone)) {
			$timezone = self::timezone_object($timezone);
		}

		// Use a known date (2024-01-01 is a Monday) and calculate weekdays from there
		$base_date = new \DateTimeImmutable('2024-01-01', $timezone);
		$base_weekday = (int) $base_date->format('w'); // 0 = Sunday, 1 = Monday, etc.
		
		// Calculate how many days to add to get to the first weekday
		$days_to_first_weekday = ($first_weekday - $base_weekday + 7) % 7;
		$first_weekday_date = $base_date->modify('+' . $days_to_first_weekday . ' days');
		
		// Generate weekday names starting from the first weekday
		$weekdays = [];
		$current_date = $first_weekday_date;
		for ($i = 0; $i < 7; $i++) {
			$weekdays[] = wp_date('D', $current_date->getTimestamp());
			$current_date = $current_date->modify('+1 day');
		}

		return $weekdays;
	}

	public static function date_i18n( $timestamp, $format ) {
		return apply_filters( 
			'evge_date_i18n', 
			date_i18n( $format, $timestamp ), 
			$timestamp, 
			$format 
		);
	}
}