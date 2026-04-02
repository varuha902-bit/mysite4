<?php
namespace WPEventGenius\Common\Registration\Form;

use WPEventGenius\Common\Registration\Field\EmailType;
use WPEventGenius\Common\Registration\Field\FieldHandler;
use WPEventGenius\Common\Registration\Field\TextType;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class Form {

	protected $id;

	protected $fields;

	protected $field_handler;

	protected $field_settings;

	public function __construct( $id, FieldHandler $field_handler ) {
		$this->id = $id;

		$this->fields = array();

		$this->field_handler = $field_handler;
		
		$this->field_settings = null; // Will be loaded on demand
	}

	public function get_id() {
		return $this->id;
	}

	/**
	 * Get the field handler for this form
	 * 
	 * @return FieldHandler The field handler instance
	 */
	public function get_field_handler() {
		return $this->field_handler;
	}

	public function set_fields() {
		$field_db_data = $this->field_handler->query_fields( 'form_id', array( 'form_id' => $this->id ) );

		foreach ( $field_db_data as $field_db_datum ) {
			$this->fields[ $field_db_datum['id'] ] = $this->field_handler->convert_field( $field_db_datum );
		}
	}

	public function get_fields() {
		return $this->fields;
	}

	/**
	 * Get a field by its slug
	 * 
	 * @param string $slug The field slug to find
	 * @return object|false The field object or false if not found
	 */
	public function get_field_by_slug( $slug ) {
		// Ensure fields are loaded
		$this->ensure_fields_loaded();
		
		if ( empty( $this->fields ) ) {
			return false;
		}
		
		foreach ( $this->fields as $field ) {
			if ( method_exists( $field, 'get_slug' ) && $field->get_slug() === $slug ) {
				return $field;
			}
		}
		
		return false;
	}

	/**
	 * Get a field by its ID
	 * 
	 * @param int $id The field ID to find
	 * @return object|false The field object or false if not found
	 */
	public function get_field_by_id( $id ) {
		// Ensure fields are loaded
		$this->ensure_fields_loaded();
		
		return isset( $this->fields[ $id ] ) ? $this->fields[ $id ] : false;
	}

	/**
	 * Ensure fields are loaded before accessing them
	 */
	private function ensure_fields_loaded() {
		if ( empty( $this->fields ) ) {
			$this->set_fields();
		}
	}

	/**
	 * Load field settings for this form
	 * Base implementation uses global settings
	 */
	protected function load_field_settings() {
		if ( $this->field_settings === null ) {
			$this->field_settings = array();
		}
	}

	/**
	 * Get a field setting value
	 * 
	 * @param string $key Setting key
	 * @param mixed $default Default value if setting not found
	 * @return mixed Setting value or default
	 */
	public function get_field_setting( $key ) {
		$this->load_field_settings();
		
		if ( isset( $this->field_settings[ $key ] ) ) {
			return $this->field_settings[ $key ];
		}
		
		// Fall back to global settings
		return \WPEventGenius\Common\Utils\Settings::get( $key );
	}

	/**
	 * Get email template ID for a specific email type
	 * 
	 * @param string $type Email type (confirmation, notification, etc.)
	 * @return int|false Template ID or false if not found
	 */
	public function get_email_template_id( $type ) {
		$template_key = $type . '_email_template';
		$template_id = $this->get_field_setting( $template_key );
		
		return $template_id ? intval( $template_id ) : false;
	}

	/**
	 * Get email setting for this form
	 * 
	 * @param string $key Setting key
	 * @return mixed Setting value or null if not found
	 */
	public function get_email_setting( $key ) {
		return $this->get_field_setting( $key );
	}

	/**
	 * Get email content for a specific email type
	 * 
	 * @param string $type Email type (confirmation, notification, etc.)
	 * @return string Email content
	 */
	public function get_email_content( $type ) {
		$content_key = $type . '_email_content';
		return $this->get_field_setting( $content_key );
	}

	/**
	 * Get email subject for a specific email type
	 * 
	 * @param string $type Email type (confirmation, notification, etc.)
	 * @return string Email subject
	 */
	public function get_email_subject( $type ) {
		// Map email types to their subject setting keys
		$subject_keys = array(
			'confirmation' => 'confirmation_subject_new_reg',
			'notification' => 'notification_subject_new_reg',
			'cancel_request' => 'cancel_request_subject',
			'cancel_notification' => 'cancel_notification_subject',
			'cancel_confirmation' => 'cancel_confirmation_subject',
			'confirmation_pending' => 'confirmation_subject_pending_reg'
		);
		
		$subject_key = isset( $subject_keys[ $type ] ) ? $subject_keys[ $type ] : $type . '_subject';
		return $this->get_field_setting( $subject_key );
	}

	/**
	 * Check if a specific email type should be sent
	 * 
	 * @param string $type Email type (confirmation, notification, etc.)
	 * @return bool Whether to send the email
	 */
	public function should_send_email( $type ) {
		$send_key = 'send_' . $type . '_email';
		$send_setting = $this->get_field_setting( $send_key );
		return $send_setting === 'enabled';
	}

	/**
	 * Get from name for a specific email type
	 * 
	 * @param string $type Email type (confirmation, notification, etc.)
	 * @return string From name
	 */
	public function get_email_from_name( $type ) {
		$from_name_key = $type . '_from_name';
		$from_name = $this->get_field_setting( $from_name_key );
		
		// Fall back to notification_from_name for cancel types
		if ( empty( $from_name ) && in_array( $type, array( 'cancel_request', 'cancel_notification', 'cancel_confirmation' ) ) ) {
			$from_name = $this->get_field_setting( 'notification_from_name' );
		}
		
		return $from_name ?: $this->get_field_setting( 'notification_from_name' );
	}

	/**
	 * Get from email address
	 * 
	 * @return string From email address
	 */
	public function get_email_from_address() {
		$custom_enabled = $this->get_field_setting( 'email_from_address_custom' );
		
		if ( $custom_enabled === 'enabled' ) {
			return $this->get_field_setting( 'email_from_address' );
		}
		
		return get_option( 'admin_email' );
	}

	/**
	 * Get success message for a specific action
	 * 
	 * @param string $action Action type (cancel, pending, etc.)
	 * @return string Success message
	 */
	public function get_success_message( $action ) {
		$message_keys = array(
			'cancel' => 'cancel_success_message',
			'pending' => 'pending_modal_message',
			'pending_approval' => 'pending_approval_modal_message',
		);
		
		$message_key = isset( $message_keys[ $action ] ) ? $message_keys[ $action ] : $action . '_success_message';
		$message = $this->get_field_setting( $message_key );
		
		if ( $message ) {
			return $message;
		}
		
		// Default messages (pending uses default; checkout modal is shown when payment required)
		$defaults = array(
			'cancel' => __( 'Your registration has been canceled.', 'event-genius' ),
			'pending' => __( 'Your registration is pending. You will receive a confirmation email once your payment is complete.', 'event-genius' ),
			'pending_approval' => \WPEventGenius\Common\Utils\Defaults::pending_approval_modal_message(),
		);
		
		return isset( $defaults[ $action ] ) ? $defaults[ $action ] : __( 'Success!', 'event-genius' );
	}

	/**
	 * Get confirmed modal message for this form
	 * 
	 * @return string Confirmed modal message
	 */
	public function get_confirmed_modal_message() {
		return $this->get_field_setting( 'confirmed_modal_message' );
	}

	/**
	 * Get form submit button text for this form
	 * 
	 * @return string Submit button text
	 */
	public function get_submit_button_text() {
		return $this->get_field_setting( 'form_submit_button_text' );
	}

	/**
	 * Get submit button text color for this form
	 * 
	 * @return string Button text color
	 */
	public function get_submit_button_text_color() {
		return $this->get_field_setting( 'submit_button_text_color' );
	}

	/**
	 * Get submit button background color for this form
	 * 
	 * @return string Button background color
	 */
	public function get_submit_button_background_color() {
		return $this->get_field_setting( 'submit_button_background_color' );
	}

	/**
	 * Get submit button border color for this form
	 * 
	 * @return string Button border color
	 */
	public function get_submit_button_border_color() {
		return $this->get_field_setting( 'submit_button_border_color' );
	}

	/**
	 * Get form register button text for this form
	 * 
	 * @return string Register button text
	 */
	public function get_register_button_text() {
		return $this->get_field_setting( 'form_register_button_text' );
	}

	/**
	 * Get register button text color for this form
	 * 
	 * @return string Button text color
	 */
	public function get_register_button_text_color() {
		return $this->get_field_setting( 'form_register_button_text_color' );
	}

	/**
	 * Get register button background color for this form
	 * 
	 * @return string Button background color
	 */
	public function get_register_button_background_color() {
		return $this->get_field_setting( 'form_register_button_background_color' );
	}

	/**
	 * Get register button border color for this form
	 * 
	 * @return string Button border color
	 */
	public function get_register_button_border_color() {
		return $this->get_field_setting( 'form_register_button_border_color' );
	}

	/**
	 * Check if separate guest notifications are enabled
	 * 
	 * @return bool True if separate guest notifications are enabled
	 */
	public function should_send_additional_guest_emails() {
		$setting = $this->get_field_setting( 'additional_guest_emails' );
		// Default to true if setting is not set
		return $setting !== 'disabled';
	}

	/**
	 * Get the additional guest summary template
	 * 
	 * @return string The template for additional guest summaries
	 */
	public function get_additional_guest_summary_template() {
		$template = $this->get_field_setting( 'additional_guest_summary_template' );
		// Fallback to default if not set
		return $template ?: "{guest-identity}\n{all-fields}\n";
	}

	/**
	 * Get the guest identity label template
	 * 
	 * @return string The template for guest identity labels
	 */
	public function get_guest_identity_label() {
		$label = $this->get_field_setting( 'guest_identity_label' );
		// Fallback to default if not set
		return $label ?: 'Guest {number}';
	}

	/**
	 * Get the add guest button label
	 * 
	 * @return string The label for the add guest button
	 */
	public function get_add_guest_button_label() {
		$label = $this->get_field_setting( 'add_guest_button_label' );
		// Fallback to default if not set
		return $label ?: 'Add Guest';
	}

	/**
	 * Get guest registration type for this form
	 * 
	 * @return string Guest registration type (none, simple_count, additional_guests)
	 */
	public function get_guest_registration_type() {
		$type = $this->get_field_setting( 'guest_registration_type' );
		// Default to 'none' if not set
		return $type ?: 'none';
	}

	/**
	 * Get minimum guest count for this form
	 * 
	 * @return int Minimum number of additional guests required
	 */
	public function get_min_guest_count() {
		$min_count = $this->get_field_setting( 'min_guest_count' );
		// Default to 0 if not set
		return $min_count ? intval( $min_count ) : 0;
	}

	/**
	 * Get maximum guest count for this form
	 * 
	 * @return int Maximum number of additional guests allowed
	 */
	public function get_max_guest_count() {
		$max_count = $this->get_field_setting( 'max_guest_count' );
		// Default to 10 if not set
		return $max_count ? intval( $max_count ) : 10;
	}

	/**
	 * Get confirmation email recipients setting for this form
	 * 
	 * @return string Confirmation email recipients ('main_only' or 'all_guests')
	 */
	public function get_confirmation_email_recipients() {
		$recipients = $this->get_field_setting( 'confirmation_email_recipients' );
		// Default to 'all_guests' if not set
		return $recipients ?: 'all_guests';
	}

	/**
	 * Get notification email recipients setting for this form
	 * 
	 * @return string Notification email recipients ('main_only' or 'all_guests')
	 */
	public function get_notification_email_recipients() {
		$recipients = $this->get_field_setting( 'notification_email_recipients' );
		// Default to 'all_guests' if not set
		return $recipients ?: 'all_guests';
	}

}