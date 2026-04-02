<?php
namespace WPEventGenius\Common\Registration\Field;

use WPEventGenius\Common\Registration\Submission\Validator;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class BaseFieldType implements Field {

	protected $id;

	protected $type;

	protected $slug;

	protected $label;

	protected $placeholder;

	protected $default;

	protected $value;

	protected $options;

	protected $link;

	protected $main_show;

	protected $main_required;

	protected $guest_show;

	protected $guest_required;

	protected $show_in_attendee_list;

	protected $validation;

	protected $error_message;

	protected $special_validation;

	protected $misc;

	public function __construct( $args ) {
		$this->id = $args['id'];
		$this->type = $args['type'];
		$this->slug = $args['slug'];
		$this->label = $args['label'];
		$this->placeholder = $args['placeholder'];
		$this->default = $args['default'];
		$this->value = $args['value'];
		$this->options = $args['options'];
		$this->link = isset( $args['link'] ) ? $args['link'] : '';
		$this->main_show = (bool)$args['main_show'];
		$this->main_required = (bool)$args['main_required'];
		$this->guest_show = (bool)$args['guest_show'];
		$this->guest_required = (bool)$args['guest_required'];
		$this->show_in_attendee_list = (bool)$args['show_in_attendee_list'];
		$this->validation = $args['validation'];
		$this->error_message = $args['error_message'];
		$this->special_validation = $args['special_validation'];
		$this->misc = $args['misc'];
	}

	public function get_type() {
		return 'text';
	}

	public function get_id(){
		return $this->id;
	}

	public function get_slug(){
		return $this->slug;
	}

	public function get_label(){
		return $this->label;
	}

	public function get_placeholder(){
		return $this->placeholder;
	}

	public function get_default(){
		return $this->default;
	}

	public function get_value( $registration_data = array() ) {
		if ( ! empty( $registration_data[ $this->get_slug() ] ) ){
			if ( is_array( $registration_data[ $this->get_slug() ] ) ) {
				return implode( ', ', $registration_data[ $this->get_slug() ] );
			}
			return $registration_data[ $this->get_slug() ];
		}
		return $this->value;
	}

	public function has_type_mismatch( $registration_data = array() ) {
		if ( empty( $registration_data[ $this->get_slug() ] ) ) {
			return false;
		}

		$value = $registration_data[ $this->get_slug() ];
		$type = $this->get_type();

		switch ($type) {
			case 'checkbox':
				// Checkbox should be array
				return !is_array($value) && !is_string($value);
			case 'radio':
			case 'select':
				// Radio and select should be string
				return is_array($value);
			case 'single-checkbox':
				// Single checkbox should be boolean or string
				return !is_bool($value) && !is_string($value);
			default:
				// Text, email, phone, textarea should be string
				return is_array($value);
		}
	}

	public function get_type_mismatch_warning() {
		$type = $this->get_type();
		$label = $this->get_label();
		
		switch ($type) {
			case 'checkbox':
				return sprintf(
					__('Warning: The field "%s" was converted from a single value to multiple values. This may cause data loss.', 'event-genius'),
					$label
				);
			case 'radio':
			case 'select':
				return sprintf(
					__('Warning: The field "%s" was converted from multiple values to a single value. This may cause data loss.', 'event-genius'),
					$label
				);
			case 'single-checkbox':
				return sprintf(
					__('Warning: The field "%s" was converted to a checkbox format. This may cause data loss.', 'event-genius'),
					$label
				);
			default:
				return sprintf(
					__('Warning: The field "%s" was converted from multiple values to a single value. This may cause data loss.', 'event-genius'),
					$label
				);
		}
	}

	public function get_options(){
		return $this->options;
	}

	public function get_link(){
		return $this->link;
	}

	public function main_should_show(){
		return $this->main_show;
	}

	public function guest_should_show(){
		return $this->guest_show;
	}

	public function main_is_required(){
		return $this->main_required;
	}

	public function guest_is_required(){
		return $this->guest_required;
	}

	public function show_in_attendee_list(){
		return $this->show_in_attendee_list;
	}

	public function get_validation(){
		return $this->validation;
	}

	public function get_error_message( $type = 'default' ){
		return $this->error_message;
	}

	public function get_special_validation(){
		return $this->special_validation;
	}

	public function get_type_attribute() {
		return 'text';
	}
	public function get_misc() {
		return $this->misc;
	}
	public function is_valid( Validator $validator, $value ) {
		$min = isset( $this->validation['min'] ) ? $this->validation['min'] : 0;
		$max = isset( $this->validation['max'] ) ? $this->validation['max'] : 'none';

		return $validator->length( $value, $min, $max );
	}

	public function hidden_label() {
		return false;
	}

	public function parse_value( $submission_data ) {
		if ( ! empty( $submission_data[ $this->get_slug() ] ) ){
			return $submission_data[ $this->get_slug() ];
		}

		return '';
	}

	/**
	 * Get additional data attributes for the field
	 * Override in Pro classes to add custom data attributes
	 * 
	 * @return string HTML data attributes
	 */
	public function additional_wrapper_data_attributes() {
		
	}

	public function additional_input_attributes() {
		
	}

	public function is_hidden( $flags = array() ) {
		if ( ! empty( $flags ) && ! empty( $flags['is_admin'] ) ) {
			return false;
		}
		// Check guest/main visibility based on flags
		$is_guest_form = ! empty( $flags['is_guest'] );
		
		if ( $is_guest_form ) {
			// For guest forms, check if field should show for guests
			// First check if field has guest_include_type (Pro fields)
			if ( method_exists( $this, 'get_guest_include_type' ) ) {
				$guest_include_type = $this->get_guest_include_type();
				// Hide if field is marked as "main_only"
				if ( $guest_include_type === 'main_only' ) {
					return true;
				}
			}
			
			// Fall back to guest_should_show() method
			if ( ! $this->guest_should_show() ) {
				return true;
			}
		} else {
			// For main forms, check if field should show for main registration
			// First check if field has guest_include_type (Pro fields)
			if ( method_exists( $this, 'get_guest_include_type' ) ) {
				$guest_include_type = $this->get_guest_include_type();
				// Hide if field is marked as "guests_only"
				if ( $guest_include_type === 'guests_only' ) {
					return true;
				}
			}
			
			// Fall back to main_should_show() method
			if ( ! $this->main_should_show() ) {
				return true;
			}
		}
		
		return false;
	}
}