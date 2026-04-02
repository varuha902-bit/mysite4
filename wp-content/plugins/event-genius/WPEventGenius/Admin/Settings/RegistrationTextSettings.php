<?php

namespace WPEventGenius\Admin\Settings;

use WPEventGenius\Common\Utils\Defaults;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RegistrationTextSettings extends BaseSettings {

	protected $page = 'evge_event_settings';

	protected $tab = 'text-registration';

	public static function text_area_fields() {
		return array();
	}

	public static function rich_editor_fields() {
		return array(
			'cancel_request_instructions',
			'cancel_request_email_content',
            'cancel_success_message',
            'cancel_request_success_message',
			'cancel_notification_email_content',
			'confirmed_modal_message',
			'pending_approval_modal_message',
			'edit_success_message',
		);
	}

	public static function checkbox_fields() {
		return array();
	}

	public function settings() {
		$this->register_setting();

		add_settings_section(
			'evge_registration_text',
			'',
			array( $this, 'section_callback' ),
			'evge_registration_text'
		);

		$args = array(
			'id' => 'registration_form',
			'label' => __( 'Registration Form', 'event-genius' ),
			'callback' => 'message',
			'page' => 'evge_registration_text',
			'section' => 'evge_registration_text',
			'message' => sprintf( 
                /* translators: 1: opening link tag to form builder, 2: closing link tag */
                __( 'Use our %1$sForm Builder%2$s to edit registration form related text', 'event-genius' ), 
                '<a href="' . esc_url( add_query_arg( array( 'page' => 'evge-registrations', 'tab' => 'forms' ), admin_url( 'admin.php' ) ) ) . '">', 
                '</a>' 
            ),
		);
		$this->add_setting_field( $args );

		$text_settings = array(
			'registration_capacity_text' => array(
				'id' => 'registration_capacity_text',
				'label' => __('Registration Capacity Text', 'event-genius'),
				'tooltip' => __('Text that appears above the register button related to event capacity. Supports placeholders {remaining}, {capacity}, and {count}.', 'event-genius'),
				'type' => 'text_field',
				'class' => '',
				'section' => 'evge_registration_cancel_text',
			),
			'registration_capacity_text_singular' => array(
				'id' => 'registration_capacity_text_singular',
				'label' => __('Registration Capacity Text (Singular)', 'event-genius'),
				'tooltip' => __('Text that appears above the register button when there is only 1 spot remaining or 1 registration (placeholder is detected). Supports placeholders {remaining}, {capacity}, and {count}.', 'event-genius'),
				'type' => 'text_field',
				'class' => '',
				'section' => 'evge_registration_cancel_text',
			),
			'registration_capacity_text_short' => array(
				'id' => 'registration_capacity_text_short',
				'label' => __('Registration Capacity Text (Short)', 'event-genius'),
				'tooltip' => __('Short text for registration capacity display. Supports placeholders {remaining}, {capacity}, and {count}.', 'event-genius'),
				'type' => 'text_field',
				'class' => '',
				'section' => 'evge_registration_cancel_text',
			),
			'registration_not_open_text' => array(
				'id' => 'registration_not_open_text',
				'label' => __('Registration Not Open Text', 'event-genius'),
				'tooltip' => __('Text that appears instead of the register button when registration has not yet opened. Supports placeholders {open-date}, {open-countdown}', 'event-genius'),
				'type' => 'text_field',
				'class' => '',
				'section' => 'evge_registration_cancel_text',
			),
			'registration_not_open_text_short' => array(
				'id' => 'registration_not_open_text_short',
				'label' => __('Registration Not Open Text (Short)', 'event-genius'),
				'tooltip' => __('Short text displayed in event details when registration has not yet opened. Supports placeholders {open-date}, {open-countdown}', 'event-genius'),
				'type' => 'text_field',
				'class' => '',
				'section' => 'evge_registration_cancel_text',
			),
			'registration_closed_text' => array(
				'id' => 'registration_closed_text',
				'label' => __('Registration Closed Text', 'event-genius'),
				'tooltip' => __('Text that appears instead of the register button when registration has closed.', 'event-genius'),
				'type' => 'text_field',
				'class' => '',
				'section' => 'evge_registration_cancel_text',
			),
			'registration_closed_text_short' => array(
				'id' => 'registration_closed_text_short',
				'label' => __('Registration Closed Text (Short)', 'event-genius'),
				'tooltip' => __('Short text displayed in event details when registration has closed.', 'event-genius'),
				'type' => 'text_field',
				'class' => '',
				'section' => 'evge_registration_cancel_text',
			),
			'registration_filled_text' => array(
				'id' => 'registration_filled_text',
				'label' => __('Registration Filled Text', 'event-genius'),
				'tooltip' => __('Text that appears instead of the register button when registration has filled.', 'event-genius'),
				'type' => 'text_field',
				'class' => '',
				'section' => 'evge_registration_cancel_text',
			),
			'registration_filled_text_short' => array(
				'id' => 'registration_filled_text_short',
				'label' => __('Registration Filled Text (Short)', 'event-genius'),
				'tooltip' => __('Short text displayed in event details when registration has filled.', 'event-genius'),
				'type' => 'text_field',
				'class' => '',
				'section' => 'evge_registration_cancel_text',
			),
			'registration_available_text' => array(
				'id' => 'registration_available_text',
				'label' => __('Registration Available Text', 'event-genius'),
				'tooltip' => __('Text displayed in the registration pill when registration is available for events or series.', 'event-genius'),
				'type' => 'text_field',
				'class' => '',
				'section' => 'evge_registration_cancel_text',
			),
			'already_registered' => array(
				'id' => 'already_registered',
				'label' => __('Already Registered?', 'event-genius'),
				'tooltip' => __('Text for the button that appears below the registration button that opens a modal to allow registration changes.', 'event-genius'),
				'type' => 'text_field',
				'class' => '',
				'section' => 'evge_registration_cancel_text',
				'priority' => 10,
			),
			'cancel_request_instructions' => array(
				'id' => 'cancel_request_instructions',
				'label' => __('Cancel Request Instructions', 'event-genius'),
				'type' => 'rich_editor',
				'settings' => array(
					'quicktags'     => array( 'buttons' => 'strong,em,del,ul,ol,li,close,link,img' ),
					'tinymce'       => array(
						'toolbar1' => 'formatselect,bold,italic,underline,blockquote,bullist,numlist,link,unlink,forecolor,undo,redo,spellchecker',
						'toolbar2' => '',
					),
					'textarea_rows' => '8',
					'wpautop'       => true,
				),
				'class' => '',
				'section' => 'evge_registration_cancel_text',
				'priority' => 20,
			),
			'cancel_request_field_label' => array(
				'id' => 'cancel_request_field_label',
				'label' => __('Cancel Request Field Label', 'event-genius'),
				'tooltip' => __('Label for the field that allows the user to enter their email address to request a cancellation.', 'event-genius'),
				'type' => 'text_field',
				'class' => '',
				'section' => 'evge_registration_cancel_text',
				'priority' => 20,
			),
			'cancel_request_submit_button_text' => array(
				'id' => 'cancel_request_submit_button_text',
				'label' => __('Cancel Request Button Text', 'event-genius'),
				'tooltip' => __('Text for the button that submits the cancellation request.', 'event-genius'),
				'type' => 'text_field',
				'class' => '',
				'section' => 'evge_registration_cancel_text',
				'priority' => 20,
			),
			'cancel_request_success_message' => array(
				'id' => 'cancel_request_success_message',
				'label' => __('Cancellation Request Success Message', 'event-genius'),
				'type' => 'rich_editor',
				'settings' => array(
					'quicktags'     => array( 'buttons' => 'strong,em,del,ul,ol,li,close,link,img' ),
					'tinymce'       => array(
						'toolbar1' => 'formatselect,bold,italic,underline,blockquote,bullist,numlist,link,unlink,forecolor,undo,redo,spellchecker',
						'toolbar2' => '',
					),
					'textarea_rows' => '5',
					'wpautop'       => true,
				),
				'class' => '',
				'section' => 'evge_registration_cancel_text',
				'priority' => 20,
			),
			'cancel_button_text' => array(
				'id' => 'cancel_button_text',
				'label' => __('Registration Cancel Button', 'event-genius'),
				'tooltip' => __('Found in the cancellation confirmation email. Text for the button that allows the user to cancel their registration.', 'event-genius'),
				'type' => 'text_field',
				'class' => '',
				'section' => 'evge_registration_cancel_text',
				'priority' => 20,
			),
			'cancel_registration_button_text' => array(
				'id' => 'cancel_registration_button_text',
				'label' => __('Cancel Registration Button Text', 'event-genius'),
				'tooltip' => __('Text for the button that allows logged-in users to cancel their registration in the management modal.', 'event-genius'),
				'type' => 'text_field',
				'class' => '',
				'section' => 'evge_registration_cancel_text',
				'priority' => 20,
			),
			'cancel_success_message' => array(
				'id' => 'cancel_success_message',
				'label' => __('Cancellation Success Message', 'event-genius'),
				'type' => 'rich_editor',
				'settings' => array(
					'quicktags'     => array( 'buttons' => 'strong,em,del,ul,ol,li,close,link,img' ),
					'tinymce'       => array(
						'toolbar1' => 'formatselect,bold,italic,underline,blockquote,bullist,numlist,link,unlink,forecolor,undo,redo,spellchecker',
						'toolbar2' => '',
					),
					'textarea_rows' => '5',
					'wpautop'       => true,
				),
				'class' => '',
				'section' => 'evge_registration_cancel_text',
				'priority' => 20,
			),
			'cancel_confirm_button_text' => array(
				'id' => 'cancel_confirm_button_text',
				'label' => __('Confirm Cancellation Button Text', 'event-genius'),
				'tooltip' => __('Text for the button that confirms the user wants to cancel their registration.', 'event-genius'),
				'type' => 'text_field',
				'class' => '',
				'section' => 'evge_registration_cancel_text',
				'priority' => 20,
			),
			'cancel_none_found_message' => array(
				'id' => 'cancel_none_found_message',
				'label' => __('No Cancellation Record Found Message', 'event-genius'),
				'tooltip' => __('Message that appears when no registration is found for the email address entered.', 'event-genius'),
				'type' => 'text_field',
				'class' => 'large-text',
				'section' => 'evge_registration_cancel_text',
				'priority' => 20,
			),
			'cancel_confirm_needed_message' => array(
				'id' => 'cancel_confirm_needed_message',
				'label' => __('Cancellation Confirmation Message', 'event-genius'),
				'type' => 'rich_editor',
				'settings' => array(
					'quicktags'     => array( 'buttons' => 'strong,em,del,ul,ol,li,close,link,img' ),
					'tinymce'       => array(
						'toolbar1' => 'formatselect,bold,italic,underline,blockquote,bullist,numlist,link,unlink,forecolor,undo,redo,spellchecker',
						'toolbar2' => '',
					),
					'textarea_rows' => '5',
					'wpautop'       => true,
				),
				'class' => '',
				'section' => 'evge_registration_cancel_text',
				'priority' => 20,
			),
			'cancel_no_results' => array(
				'id' => 'cancel_no_results',
				'label' => __('No Cancellation Results Found Message', 'event-genius'),
				'tooltip' => __('Message that appears when no registration is found after clicking the cancellation confirm button.', 'event-genius'),
				'type' => 'text_field',
				'class' => 'large-text',
				'section' => 'evge_registration_cancel_text',
				'priority' => 20,
			),

		);

		$text_settings = apply_filters( 'evge_registration_status_text_settings', $text_settings );
		
		// Add confirmation email resent message at the bottom
		$text_settings['confirmation_email_resent_message'] = array(
			'id' => 'confirmation_email_resent_message',
			'label' => __('Confirmation Email Resent Message', 'event-genius'),
			'tooltip' => __('Message that appears when a confirmation email has been successfully resent.', 'event-genius'),
			'type' => 'text_field',
			'class' => '',
			'section' => 'evge_registration_cancel_text',
			'priority' => 30,
		);
		
		// Add field not editable message with low priority
		$text_settings['field_not_editable_message'] = array(
			'id' => 'field_not_editable_message',
			'label' => __('Field Not Editable Message', 'event-genius'),
			'tooltip' => __('Message that appears when a field cannot be edited during registration editing.', 'event-genius'),
			'type' => 'text_field',
			'class' => '',
			'section' => 'evge_registration_cancel_text',
			'priority' => 40,
		);
		
		$args = array(
			'id' => 'registration_status',
			'label' => __( 'Registration Status', 'event-genius' ),
			'callback' => 'multi_text_field',
			'text_fields' => $text_settings,
			'page' => 'evge_registration_text',
			'section' => 'evge_registration_text',
			/* translators: 1: opening link tag to form builder, 2: closing link tag */
			'message' => sprintf( __( 'Use our %1$sForm Builder%2$s to edit registration form related text', 'event-genius' ), 
                '<a href="' . esc_url( add_query_arg( array( 'page' => 'evge-registrations', 'tab' => 'forms' ), admin_url( 'admin.php' ) ) ) . '">', 
                '</a>' 
            ),
		);
		$this->add_setting_field( $args );

		add_settings_section(
			'evge_registration_text_status',
			'',
			array( $this, 'section_callback' ),
			'evge_registration_text_status'
		);

		add_settings_section(
			'evge_registration_cancel_text',
			__( 'Cancellation', 'event-genius' ),
			array( $this, 'section_callback' ),
			'evge_registration_cancel_text'
		);
	}


}
