<?php
/**
 * Used for validating registration form submissions
 *
 * @since 2.21
 */
namespace WPEventGenius\Common\Registration\Submission;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class Validator {

	/**
	 * Whether or not the subject has a valid length
	 *
	 * @param string $subject
	 * @param string|int $min
	 * @param string|int $max
	 *
	 * @return bool
	 *
	 * @since 2.21
	 */
	public function length( $subject, $min, $max ) {
		$working_max = $max;
		$working_min = $min;

		if ( $working_max === 'none' || $working_max > 50000 ) {
			$working_max = 50000;
		}

		if ( $working_min < 0 ) {
			$working_min = 0;
		}

		if ( strlen( $subject ) >= (int) $working_min && strlen( $subject ) <= (int) $working_max ) {
			return true;
		}

		return false;
	}

	/**
	 * Whether or not the subject is a valid number
	 *
	 * @param string $subject
	 * @param string|int $minval
	 * @param string|int $maxval
	 *
	 * @return bool
	 *
	 * @since 2.21
	 */
	public function numval( $subject, $minval, $maxval ) {
		$working_max = $maxval;
		$working_min = $minval;

		if ( $working_max === 'no-max' || $working_max > 9999999 ) {
			$working_max = 9999999;
		}

		if ( $working_min === 'no-min' || $working_min < -9999999 ) {
			$working_min = -9999999;
		}

		if ( $subject >= (int) $working_min && $subject <= (int) $working_max ) {
			return true;
		}

		return false;
	}

	/**
	 * Subject is a valid email
	 *
	 * @param string $subject
	 *
	 * @return false|string
	 *
	 * @since 2.21
	 */
	public function email( $subject ) {
		return is_email( $subject );
	}

	public function count( $subject, $acceptable_counts, $count_what = 'numbers' ) {
		$working_counts = $acceptable_counts;
		if ( $count_what === 'numbers' ) {
			$stripped_subject = preg_replace( '/\D/', '', $subject );

		} elseif ( $count_what === 'letters' ) {
			$stripped_subject = str_replace( '/^\p{L}+$/ui', '', $subject );
		} else {
			$stripped_subject = $subject;
		}

		if ( ! is_array( $working_counts ) ) {
			$working_counts = explode( ',', $working_counts );
		}

		foreach ( $working_counts as $acceptable_count ) {

			if ( strlen( $stripped_subject ) === (int) $acceptable_count ) {
				return true;
			}
		}

		if ( 'letters' === $count_what ) {
			return true;
		}

		return false;
	}

	public function phone( $subject ) {
		$stripped_subject = preg_replace( '/\D/', '', $subject );

		if ( strlen( $stripped_subject ) >= 5 ) {
			return true;
		}

		return false;
	}

	/**
	 * Two integers are equal
	 *
	 * @param int $first_val
	 * @param int $second_val
	 * @param string $strictness
	 *
	 * @return bool
	 *
	 * @since 2.21
	 */
	public function num_equality( $first_val, $second_val, $strictness = 'strict' ) {
		if ( 'strict' === $strictness ) {
			return ( (int) $first_val === (int) $second_val );
		} else {
			return (int) $second_val > 0;
		}
	}

	/**
	 * Whether or not the email provides is unique for the event's registration records
	 *
	 * @param $email
	 * @param $event_id
	 *
	 * @return bool
	 *
	 * @since 2.21
	 */
	public function unique_email_for_event( $email, $event_id ) {
		if ( ! is_email( $email ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Validates checkbox submissions
	 * 
	 * @param mixed $subject Can be array for multiple checkboxes or single value
	 * 
	 * @return bool
	 * 
	 * @since 2.21
	 */
	public function checkbox($subject) {
		// Handle array of checkboxes
		if (is_array($subject)) {
			// Check if any non-empty values exist in the array
			foreach ($subject as $value) {
				if (!empty($value)) {
					return true;
				}
			}
			return false;
		}

		// Handle single checkbox
		return !empty($subject) && $subject !== false;
	}

	/**
	 * Validates a single checkbox submission
	 * 
	 * @param mixed $subject The checkbox value
	 * 
	 * @return bool
	 * 
	 * @since 2.21
	 */
	public function single_checkbox($subject) {
		return !empty($subject) && $subject !== false;
	}

}
