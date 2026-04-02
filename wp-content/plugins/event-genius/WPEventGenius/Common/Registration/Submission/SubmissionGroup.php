<?php
/**
 * Base class for registration form submissions
 *
 * @since 2.21
 */
namespace WPEventGenius\Common\Registration\Submission;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class SubmissionGroup {

	/**
	 * @var Submission
	 *
	 * @since 2.21
	 */
	protected $main_submission;

	public function __construct( Submission $main_submission ) {
		$this->main_submission = $main_submission;
	}


	/**
	 * @return Submission
	 *
	 * @since 2.21
	 */
	public function get_main_submission() {
		return $this->main_submission;
	}

	/**
	 * Removes prefixes from raw form submission array
	 *
	 * @since 2.21
	 */
	public function normalize_all() {
		$this->main_submission->normalize();
	}

	/**
	 * Checks for validity of a form submission relative to each form field
	 *
	 * @since 2.21
	 */
	public function validate_all() {
		$this->main_submission->validate();
	}

	/**
	 * Whether or not all submissions in the group are valid
	 *
	 * @return bool
	 *
	 * @since 2.21
	 */
	public function all_valid() {
		if ( ! $this->main_submission->is_valid() ) {
			return false;
		}

		return true;
	}

	/**
	 * Sanitizes responses for safe handling
	 *
	 * @since 2.21
	 */
	public function sanitize_all() {
		$this->main_submission->sanitize();
	}

	/**
	 * Returns a key value pair of all submission data
	 *
	 * @return array[]
	 *
	 * @since 2.21
	 */
	public function get_all_data() {
		$return         = array(
			'main'     => array(),
		);
		$return['main'] = $this->main_submission->get_data();

		return $return;
	}

	/**
	 * Returns a key value pair of all errors from validation
	 *
	 * @return array[]
	 *
	 * @since 2.21
	 */
	public function get_all_errors() {
		$return         = array(
			'main'     => array(),
		);
		$return['main'] = $this->main_submission->get_errors();

		return $return;
	}

	/**
	 * Child submissions will inherit the form responses from the "parent" or main registration
	 * if the field is not included or required for connected guests
	 *
	 * @since 2.21
	 */
	public function add_inherited_responses() {
		$this->main_submission->add_user_data();
	}

	/**
	 * Check if there are any errors in the submission group
	 * 
	 * @return bool True if there are any errors, false otherwise
	 * 
	 * @since 2.21
	 */
	public function has_errors() {
		$errors = $this->get_all_errors();
		return ! empty( $errors ) && ! empty( $errors['main'] );
	}

	/**
	 * Calculate the total submitted quantity
	 * In the base class, this just returns the main submission quantity
	 * Pro version overrides this to include additional guests
	 * 
	 * @return int Total quantity (main registration)
	 * 
	 * @since 2.21
	 */
	public function get_total_submitted_quantity() {
		// Get the main submission quantity
		$main_data = $this->main_submission->get_data();
		$main_quantity = isset( $main_data['quantity'] ) ? (int) $main_data['quantity'] : 1;
		
		// Base class only has main submission, so just return that
		return $main_quantity;
	}
}
