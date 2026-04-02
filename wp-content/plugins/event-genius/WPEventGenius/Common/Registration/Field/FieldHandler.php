<?php
namespace WPEventGenius\Common\Registration\Field;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class FieldHandler {

	public function __construct() {}

	public function available_types() {
		$options_array = array(
			'text' => __( 'Text', 'event-genius' ),
			'checkbox' => __( 'Checkbox', 'event-genius' ),
			'radio' => __( 'Radio', 'event-genius' ),
			'select' => __( 'Select (dropdown)', 'event-genius' ),
			'textarea' => __( 'Textarea (paragraph)', 'event-genius' ),
			'single-checkbox' => __( 'Single Checkbox', 'event-genius' ),
			'email' => __( 'Email', 'event-genius' ),
			'phone' => __( 'Phone', 'event-genius' ),
		);

		$options_array = $this->additional_available_types( $options_array );
		
		return $options_array;
	}

	/**
	 * Add additional available field types
	 * Override in child classes to add custom field types
	 * 
	 * @param array $options_array Array of field type options
	 * @return array Modified array with additional field types
	 */
	protected function additional_available_types( $options_array ) {
		return $options_array;
	}

	public function query_fields( $by = 'all', $args = array() ) {
		if ( $by === 'all' ) {
			$field_db_data = $this->get_all_db_fields();
		} elseif ( $by === 'field_id' ) {
			$field_db_data = $this->get_field_db_data_by_field_id( $args['field_id'] );
		} else {
			$field_db_data = $this->get_field_db_data_by_form_id( $args['form_id'] );
		}

		return $field_db_data;
	}

	protected function get_all_db_fields() {
		$all_fields = get_option( 'evge_all_fields', array() );

		if ( ! empty( $all_fields ) ) {
			// Convert to field ID keyed array if needed (backwards compatibility)
			$all_fields = $this->normalize_fields_array_keys( $all_fields );
			
			// Ensure backwards compatibility: add show_in_attendee_list if not set
			foreach ( $all_fields as $field_id => $field ) {
				if ( ! isset( $field['show_in_attendee_list'] ) ) {
					$slug = isset( $field['slug'] ) ? $field['slug'] : '';
					$all_fields[ $field_id ]['show_in_attendee_list'] = in_array( $slug, array( 'first', 'last' ), true );
				}
			}
			
			// Save normalized structure if it was converted
			update_option( 'evge_all_fields', $all_fields );
			
			return $all_fields;
		}
		$field_db_data = array(
			1 => array(
				'id' => 1,
				'type' => 'text',
				'slug' => 'first',
				'label' => 'First',
				'placeholder' => '',
				'default' => '',
				'value' => '',
				'options' => array(),
				'link' => '',
				'main_show' => true,
				'main_required' => true,
				'guest_show'    => true,
				'guest_required' => true,
				'show_in_attendee_list' => true,
				'validation' => array(
					'type' => 'length',
					'max' => 'none',
					'min' => 1
				),
				'error_message' => 'This field is required',
				'special_validation' => array(),
				'misc' => array(),
			),
			2 => array(
				'id' => 2,
				'type' => 'text',
				'slug' => 'last',
				'label' => 'Last',
				'placeholder' => '',
				'default' => '',
				'value' => '',
				'options' => array(),
				'link' => '',
				'main_show' => true,
				'main_required' => true,
				'guest_show'    => true,
				'guest_required' => true,
				'show_in_attendee_list' => true,
				'validation' => array(
					'type' => 'length',
					'max' => 'none',
					'min' => 1
				),
				'error_message' => 'This field is required',
				'special_validation' => array(),
				'misc' => array(),
			),
			3 => array(
				'id' => 3,
				'type' => 'email',
				'slug' => 'email',
				'placeholder' => '',
				'label' => 'Email',
				'default' => '',
				'value' => '',
				'options' => array(),
				'link' => '',
				'main_show' => true,
				'main_required' => true,
				'guest_show'    => true,
				'guest_required' => true,
				'show_in_attendee_list' => false,
				'validation' => array(
					'type' => 'email'
				),
				'error_message' => 'Please enter an email address',
				'special_validation' => array(
					'before_submit' =>
						array(
							'type' => 'server',
							'callback' => array(
								'self',
								'duplicate_check'
							),
						),
					'after_submit' => array(
						'type' => 'on_input',
						'callback' => array(
							'self',
							'duplicate_check'
						)
					),
				),
				'misc' => array(
					'repeat_check' => true
				),
			)
		);

		$field_db_data = $this->add_field_db_data( $field_db_data );

		update_option( 'evge_all_fields', $field_db_data );

		return $field_db_data;
	}

	/**
	 * Normalize fields array to use field IDs as keys
	 * Converts numeric-indexed arrays to field ID keyed arrays for backwards compatibility
	 * 
	 * @param array $fields Array of fields (may be numeric-indexed or field ID keyed)
	 * @return array Array with field IDs as keys
	 */
	protected function normalize_fields_array_keys( $fields ) {
		if ( empty( $fields ) ) {
			return $fields;
		}

		// Check if already using field IDs as keys
		// Sample a few keys to determine if array is already normalized
		$sample_keys = array_slice( array_keys( $fields ), 0, 3, true );
		$is_normalized = true;
		
		foreach ( $sample_keys as $key ) {
			if ( ! isset( $fields[ $key ]['id'] ) || (string) $key !== (string) $fields[ $key ]['id'] ) {
				$is_normalized = false;
				break;
			}
		}
		
		if ( $is_normalized ) {
			// Already normalized
			return $fields;
		}

		// Convert numeric-indexed array to field ID keyed array
		$normalized = array();
		foreach ( $fields as $field ) {
			if ( isset( $field['id'] ) ) {
				$normalized[ $field['id'] ] = $field;
			}
		}

		return $normalized;
	}

	protected function add_field_db_data( $field_db_data ) {

		return $field_db_data;
	}

	protected function add_single_in_form_field_db_data( $single_field_db_data ) {
		return $single_field_db_data;
	}
	
	/**
	 * Add additional form field database data
	 * Override in child classes to add custom form field data
	 * 
	 * @param array $field_db_data Array of form field database data
	 * @return array Modified array with additional form field data
	 */
	protected function add_in_form_field_db_data( $field_db_data ) {
		return $field_db_data;
	}

	protected function get_field_db_data_by_form_id( $form_id ) {
		$form_option_fields = get_option( 'evge_form', array() );



		if ( ! empty( $form_option_fields ) ) {
			return $this->hydrate_form_fields( $form_option_fields );
		}
		$field_db_data = array(
			array(
				'id' => 1,
				'main_required' => true,
				'guest_required' => true,
				'show_in_attendee_list' => true,
			),
			array(
				'id' => 2,
				'main_required' => true,
				'guest_required' => true,
				'show_in_attendee_list' => true,
			),
			array(
				'id' => 3,
				'main_required' => true,
				'guest_required' => true,
				'show_in_attendee_list' => false,
			)
		);

		$field_db_data = $this->add_in_form_field_db_data( $field_db_data );

		update_option( 'evge_form', $field_db_data );


		return $this->hydrate_form_fields( $field_db_data );
	}

	protected function hydrate_form_fields( $field_db_data ) {
		$all_fields = $this->get_all_db_fields();

		$return_fields = array();
		foreach ( $field_db_data as $field ) {
			$field_id = isset( $field['id'] ) ? $field['id'] : null;
			if ( $field_id && isset( $all_fields[ $field_id ] ) ) {
				$to_add = $all_fields[ $field_id ];
				$to_add['main_required'] = $field['main_required'];
				$to_add['guest_required'] = $field['guest_required'];
				// Handle backwards compatibility: default to true for 'first' and 'last', false for others
				if ( isset( $field['show_in_attendee_list'] ) ) {
					$to_add['show_in_attendee_list'] = $field['show_in_attendee_list'];
				} else {
					$slug = isset( $to_add['slug'] ) ? $to_add['slug'] : '';
					$to_add['show_in_attendee_list'] = in_array( $slug, array( 'first', 'last' ), true );
				}
				$return_fields[] = $to_add;
			}
		}

		$return_fields = $this->additional_hydrate_form_fields( $return_fields, $field_db_data );

		return $return_fields;
	}
	
	/**
	 * Additional hydration for form fields
	 * Override in child classes to add custom field properties during hydration
	 * 
	 * @param array $return_fields Array of hydrated form fields
	 * @return array Modified array with additional field properties
	 */
	protected function additional_hydrate_form_fields( $return_fields, $field_db_data ) {
		return $return_fields;
	}

	protected function get_field_db_data_by_field_id( $field_id ) {
		$field_data = $this->get_all_db_fields();

		$field_db_data = array();
		if ( isset( $field_data[ $field_id ] ) ) {
			$field_db_data = $field_data[ $field_id ];
			$field_db_data = $this->add_single_in_form_field_db_data( $field_db_data );
		}

		return $field_db_data;
	}

	public function create_generic_field() {
		$field = array(
			'type' => 'text',
			'default' => '',
			'value' => '',
			'placeholder' => '',
			'options' => array(),
			'link' => '',
			'main_show' => true,
			'main_required' => true,
			'guest_show'    => true,
			'guest_required' => true,
			'show_in_attendee_list' => false,
			'validation' => array(
				'type' => 'length',
				'max' => 'none',
				'min' => 1
			),
			'error_message' => 'This field is required',
			'special_validation' => array(),
			'misc' => array(),
		);

		$all_fields = $this->get_all_db_fields();

		// Find the next available field ID
		$max_id = 0;
		foreach ( $all_fields as $field_id => $existing_field ) {
			$field_id_int = (int) $field_id;
			if ( $field_id_int > $max_id ) {
				$max_id = $field_id_int;
			}
		}
		$new_field_id = $max_id + 1;
		
		$field['id'] = $new_field_id;
		$field['slug'] = 'custom_' . $new_field_id;
		$field['label'] = 'New Field ' . $new_field_id;

		// Store using field ID as key
		$all_fields[ $new_field_id ] = $field;

		update_option( 'evge_all_fields', $all_fields );

		return $field;
	}

	public function convert_field( $field_db_datum ) {
			switch ( $field_db_datum['type'] ) {
				case 'checkbox' :
					$new_field = new CheckboxType( $field_db_datum );
					break;
				case 'phone' :
					$new_field = new PhoneType( $field_db_datum );
					break;
				case 'radio' :
					$new_field = new RadioType( $field_db_datum );
					break;
				case 'textarea' :
					$new_field = new TextAreaType( $field_db_datum );
					break;
				case 'select' :
					$new_field = new SelectType( $field_db_datum );
					break;
				case 'single-checkbox' :
					$new_field = new SingleCheckboxType( $field_db_datum );
					break;
				case 'email' :
					$new_field = new EmailType( $field_db_datum );
					break;
				case 'number' :
					$new_field = new TextType( $field_db_datum ); // Use TextType as base for free version
					break;
				case 'submit' :
					$new_field = new SubmitType( $field_db_datum );
					break;
				default :
					$new_field = new TextType( $field_db_datum );
					break;
			}

			return $new_field;
	}

	public function update_form( $form_id, $field_data ) {
		$form_data = array();
		foreach ( $field_data as $field ) {
			$form_data[] = array(
				'id' => $field['id'],
				'main_required' => $field['required'],
				'guest_required' => $field['required'],
				'show_in_attendee_list' => $field['show_in_attendee_list'],
			);
		}

		update_option( 'evge_form', $form_data );

		return $form_data;
	}

	public function update_field( $field_id, $field_data ) {

		$all_fields = $this->get_all_db_fields();
		$updated = false;
		
		if ( isset( $all_fields[ $field_id ] ) ) {
			foreach ( $field_data as $key => $value ) {
				$all_fields[ $field_id ][ $key ] = $value;
			}
			$updated = $all_fields[ $field_id ];
			update_option( 'evge_all_fields', $all_fields );
		}

		return $updated;
	}

	public function delete_field( $field_id ) {

		$all_fields = $this->get_all_db_fields();
		
		// Delete field using field ID as key (prevents array key reset issues)
		if ( isset( $all_fields[ $field_id ] ) ) {
			unset( $all_fields[ $field_id ] );
			update_option( 'evge_all_fields', $all_fields );
		}

		$form_data = get_option( 'evge_form', array() );

		// Remove field from form data (form data uses numeric indices, but we match by field ID)
		foreach ( $form_data as $index => $field ) {
			if ( isset( $field['id'] ) && (string)$field['id'] === (string)$field_id ) {
				unset( $form_data[ $index ] );
				break;
			}
		}

		// Re-index form_data array to maintain sequential numeric indices
		$form_data = array_values( $form_data );
		update_option( 'evge_form', $form_data );

		return true;
	}

	public function parse_options( $raw_options ){
		$options = array();
		if ( ! empty( $raw_options ) ) {
			$exploded = explode( "\n", $raw_options );

			foreach ( $exploded as $index => $option ) {
				$option_label = trim( $option );
				$option_value = trim( $option );
				$option_checked = $index === 0;
				$option_cost = 0;
				$options[] = array(
					'label' => $option_label,
					'value' => $option_value,
					'checked' => $option_checked,
					'cost' => $option_cost,
				);
			}
		}
		return $options;
	}

	public function options_textarea( $raw_options ){
		$options_textarea = '';

		foreach ( $raw_options as $option ) {
			$options_textarea .= $option['label'] . "\n";
		}


		return $options_textarea;
	}


}