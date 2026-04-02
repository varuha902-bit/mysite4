<?php
namespace WPEventGenius\Admin\FormBuilder;

use WPEventGenius\Common\Registration\Field\EmailType;
use WPEventGenius\Common\Registration\Field\FieldHandler;
use WPEventGenius\Common\Registration\Field\TextType;
use WPEventGenius\Common\Utils\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class AllFields {

	protected $field_handler;

	protected $fields = array();

	public function __construct( FieldHandler $field_handler ) {
		$this->field_handler = $field_handler;
		$this->set_fields();
	}

	public function set_fields() {
		$field_db_data = $this->field_handler->query_fields();

		foreach ( $field_db_data as $field_db_datum ) {
			$this->fields[ $field_db_datum['id'] ] = $this->field_handler->convert_field( $field_db_datum );
		}
	}

	public function get_fields() {
		return $this->fields;
	}

	public function get_submit_button() {
		$submit_field = array(
			'type' => 'submit_button',
			'id' => 'submit-button',
			'slug' => 'submit-button',
			'label' => 'Submit',
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


		// Use global settings as fallback
		$submit_field['misc']['form_submit_button_text'] = Settings::get('form_submit_button_text');
		$submit_field['misc']['submit_button_background_color'] = Settings::get('submit_button_background_color');
		$submit_field['misc']['submit_button_text_color'] = Settings::get('submit_button_text_color');
		$submit_field['misc']['submit_button_border_color'] = Settings::get('submit_button_border_color');
		

		$field = $this->field_handler->convert_field( $submit_field );
		return $field;
	}


}