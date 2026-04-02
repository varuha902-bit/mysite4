<?php
/**
 * Base object for form submissions
 *
 * @since 1.0
 */
namespace WPEventGenius\Common\Registration\Submission;

use WPEventGenius\Common\Registration\Form\Form;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class BaseSubmission implements Submission {

	/**
	 * @var Validator
	 *
	 * @since 1.0
	 */
	protected $validator;

	/**
	 * @var array
	 */
	protected $raw_data;

	/**
	 * @var array
	 *
	 * @since 1.0
	 */
	protected $data;

	/**
	 * @var Form
	 *
	 * @since 1.0
	 */
	protected $form;

	/**
	 * @var array
	 *
	 * @since 1.0
	 */
	protected $errors = array();

	public function __construct( Validator $validator, Form $form ) {
		$this->validator = $validator;
		$this->form = $form;
		$this->form->set_fields();
	}

	/**
	 * Get the list of expected field keys that should be included in the raw data
	 *
	 * @return array
	 *
	 * @since 1.0
	 */
	public function get_expected_fields() {
		$expected_fields = [
			'evge_user_comments', // honeypot field
			'event_id',
			'user_id',
		];

		// Add all form field slugs
		foreach ($this->form->get_fields() as $field) {
			$expected_fields[] = 'evge_' . $field->get_slug();
		}

		return $expected_fields;
	}

	public function get_fields() {
		return $this->form->get_fields();
	}

	/**
	 * @param array $raw_data
	 *
	 * @return mixed|void
	 *
	 * @since 1.0
	 */
	public function set_raw_data( $raw_data ) {
		$this->raw_data = $raw_data;
	}

	/**
	 * @return array
	 *
	 * @since 1.0
	 */
	public function get_raw_data() {
		return $this->raw_data;
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 *
	 * @since 1.0
	 */
	public function set_single_raw_datum( $key, $value ) {
		$this->raw_data[ $key ] = $value;
	}

	/**
	 * @return array|mixed
	 *
	 * @since 1.0
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 *
	 * @since 1.0
	 */
	public function set_single_datum( $key, $value ) {
		$this->data[ $key ] = $value;
	}

	/**
	 * @return mixed|void
	 *
	 * @since 1.0
	 */
	public function normalize() {
		foreach ( $this->raw_data as $key => $value ) {
			// submission data is prefixed to avoid conflicts
			// we should ignore any data that doesn't have the evge prefix for non-admin submissions
			if ( strpos( $key, 'evge_' ) === 0 ) {
				$field_name = str_replace( 'evge_', '', $key ); //

				$this->raw_data[ $field_name ] = $value;
			}
		}
	}

	/**
	 * Check if a field is required for this submission type
	 * Override in subclasses to use different requirement logic
	 *
	 * @param \WPEventGenius\Common\Registration\Field\Field $field The field to check
	 * @return bool True if the field is required
	 *
	 * @since 1.0
	 */
	protected function is_field_required( $field ) {
		return $field->main_is_required();
	}

	/**
	 * @return mixed|void
	 *
	 * @since 1.0
	 */
	public function validate() {
		$submission_data = $this->raw_data;

		// check spam honeypot, error if not empty
		if ( ! empty( $submission_data['evge_user_comments'] ) ) {
			$this->add_error( 'evge_user_comments', 'honeypot' );
		}
		if ( empty( $submission_data['event_id'] ) ) {
			$this->add_error( 'event_id', 'Missing Event ID' );
		}

		$event_id               = (int) $submission_data['event_id'];
		$this->data['event_id'] = $event_id;

		if ( ! empty( $this->raw_data['user_id'] ) ) {
			$this->data['user_id'] = absint( $this->raw_data['user_id'] );
		}
		if ( ! empty( $this->raw_data['quantity'] ) ) {
			$this->data['quantity'] = absint( $this->raw_data['quantity'] );
		}

		foreach ( $this->get_fields() as $field ) {
			$value = $field->parse_value( $submission_data );
			if ( empty( $value ) && ! $this->is_field_required( $field ) ) {
				$this->data[ $field->get_slug() ] = $value;
			} elseif ( ! $field->is_valid( $this->validator, $value ) ) {
				$this->add_error( $field->get_slug(), $field->get_label() );
			} else {
				$this->data[ $field->get_slug() ] = $value;
			}
		}
	}

	/**
	 * @return mixed|void
	 *
	 * @since 1.0
	 */
	public function sanitize() {
		// Get all valid fields from the form
		$valid_fields = $this->get_fields();
		$sanitized_data = array();

		// Always preserve these system fields with basic sanitization
		$system_fields = ['event_id', 'user_id', 'quantity'];
		foreach ($system_fields as $field) {
			if (isset($this->data[$field])) {
				$sanitized_data[$field] = absint($this->data[$field]);
			}
		}

		// Only process fields that are part of the form
		foreach ($valid_fields as $field) {
			$field_slug = $field->get_slug();
			
			// Skip if the field isn't in submitted data
			if (!isset($this->data[$field_slug])) {
				continue;
			}

			$input_value = $this->data[$field_slug];
			$type = $field->get_type();
			
			switch ($type) {
				case 'textarea':
					// Allow newlines and basic HTML
					$new_val = wp_kses(wp_unslash($input_value), [
						'br' => [],
						'p' => [],
						'strong' => [],
						'em' => [],
					]);
					break;

				case 'checkbox':
					// Handle array of checkbox values
					if (is_array($input_value)) {
						$new_val = array_map(function($value) {
							return sanitize_text_field(wp_unslash($value));
						}, $input_value);
					} else {
						$new_val = sanitize_text_field(wp_unslash($input_value));
					}
					break;

				case 'single-checkbox':
					// Ensure boolean value
					$new_val = !empty($input_value);
					break;

				case 'email':
					$new_val = sanitize_email(wp_unslash($input_value));
					break;

				case 'phone':
					// Keep only digits, plus, parentheses, and dashes
					$new_val = preg_replace('/[^\d\+\-\(\)]/', '', wp_unslash($input_value));
					break;

				case 'select':
				case 'radio':
					// Ensure value matches one of the allowed options
					$new_val = sanitize_text_field(wp_unslash($input_value));
					$valid_options = array_column($field->get_options(), 'value');
					if (!in_array($new_val, $valid_options, true)) {
						$new_val = ''; // Invalid option submitted
					}
					break;

				case 'text':
				default:
					$new_val = sanitize_text_field(wp_unslash($input_value));
					break;
			}

			$sanitized_data[$field_slug] = $new_val;
		}

		// Replace the data with only sanitized values
		$this->data = $sanitized_data;
	}

	/**
	 * @return bool
	 *
	 * @since 1.0
	 */
	public function is_valid() {
		return ! empty( $this->data ) && empty( $this->errors );
	}

	/**
	 * @param string $field
	 * @param bool $type
	 *
	 * @return mixed|void
	 *
	 * @since 1.0
	 */
	public function add_error( $field, $type = false ) {
		$this->errors[ $field ] = $type;
	}

	/**
	 * @return array|mixed
	 *
	 * @since 1.0
	 */
	public function get_errors() {
		return $this->errors;
	}

	/**
	 * @return bool
	 *
	 * @since 1.0
	 */
	public function is_edit() {
		return false;
	}

	/**
	 * @since 1.0
	 */
	public function add_user_data() {
	}

}
