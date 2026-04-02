<?php
namespace WPEventGenius\Admin\Page\Registrations\FormBuilder;

use WPEventGenius\Admin\Services\PaymentMethodAdmin;
use WPEventGenius\Common\Registration\Field\FieldHandler;
use WPEventGenius\Common\Registration\Form\Form;
use WPEventGenius\Common\Utils\Defaults;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SettingsPage extends BasePage {

	protected $active_subtab = 'settings';

	public static function text_area_fields() {
		return array();
	}

	public static function rich_editor_fields() {
		return array(
			'confirmation_email_content',
			'notification_email_content',
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

	public function page_title() {
		return __( 'Form Builder', 'event-genius' );
	}

	public function build() {
		parent::build();

		$this->settings();
	}

	public function settings() {

		/**
		 * Payment Methods
		 */
		add_settings_section(
			'evge_payment_methods',
			__( 'Payments', 'event-genius' ),
			array( $this, 'section_callback' ),
			'evge_payment_methods' // must match do_settings_section
		);

		$args = array(
			'id'        => 'payment_methods',
			'label'       => '',
			'default'     => '',
			'description' => '',
			'callback'    => array( PaymentMethodAdmin::class, 'payment_method_setting_callback' ),
			'class'       => '',
			'page'        => 'evge_payment_methods',
			'section'     => 'evge_payment_methods'
		);
		$this->add_setting_field( $args );

		add_settings_section(
			'evge_payment_settings_general',
			__( 'General', 'event-genius' ),
			array( $this, 'section_callback' ),
			'evge_payment_settings_general' // must match do_settings_section
		);

		$args = array(
			'id' => 'accept_payments',
			'label' => __( 'Accept Payments', 'event-genius' ),
			'overwrite' => 'event',
			'default' => Defaults::get( 'accept_payments' ), // 'enabled', 'disabled'
			'callback' => 'toggle_field',
			'page' => 'evge_payment_settings_general',
			'section' => 'evge_payment_settings_general'
		);
		$this->add_setting_field( $args );

		/**
		 * When is a registration confirmed? (right below form name in template)
		 */
		add_settings_section(
			'evge_registration_confirmation_flow',
			__( 'Confirmation', 'event-genius' ),
			array( $this, 'section_callback' ),
			'evge_registration_confirmation_flow'
		);

		$args = array(
			'id' => 'confirmation_condition',
			'label' => __( 'When is a registration confirmed?', 'event-genius' ),
			'default' => Defaults::get( 'confirmation_condition' ),
			'description' => '',
			'callback' => 'select_field',
			'options' => array(
				'payment_complete' => __( 'After registration, automatically', 'event-genius' ),
				'manual_only' => __( 'Manual review only', 'event-genius' ),
			),
			'tooltip' => __( 'Payment complete: Registrations are confirmed once payment is received. For free events, they’re confirmed as soon as they register. Manual review only: Registrations stay pending until you approve them. Send the confirmation email from the registration when you’re ready.', 'event-genius' ),
			'tooltip_next_to_label' => true,
			'page' => 'evge_registration_confirmation_flow',
			'section' => 'evge_registration_confirmation_flow',
			'class' => 'evge-confirmation-condition-select',
		);
		$this->add_setting_field( $args );

		/**
		 * Registration Form Settings - Messages
		 */
		add_settings_section(
			'evge_registration_messages',
			__( 'Messages and Text', 'event-genius' ),
			array( $this, 'section_callback' ),
			'evge_registration_messages' // must match do_settings_section
		);

		$args = array(
			'id' => 'confirmed_modal_message',
			'label' => __( 'Confirmed Message', 'event-genius' ),
			'default' => Defaults::get( 'confirmed_modal_message' ),
			'settings'    => array(
				'quicktags'     => array( 'buttons' => 'strong,em,del,ul,ol,li,close,link,img' ),
				'tinymce'       => array(
					'toolbar1' => 'formatselect,bold,italic,underline,blockquote,bullist,numlist,link,unlink,forecolor,undo,redo,spellchecker',
					'toolbar2' => '',
				),
				'textarea_rows' => '8',
				'wpautop'       => true,
			),
			'columns'     => '60',
			'preview'     => true,
			'include_reference'      => true,
			'callback' => 'rich_editor',
			'page' => 'evge_registration_messages',
			'section' => 'evge_registration_messages',
			'class' => 'evge-message-for-payment',
		);
		$this->add_setting_field( $args );

		$args = array(
			'id' => 'pending_approval_modal_message',
			'label' => __( 'Pending Approval Message', 'event-genius' ),
			'default' => Defaults::pending_approval_modal_message(),
			'settings' => array(
				'quicktags' => array( 'buttons' => 'strong,em,del,ul,ol,li,close,link,img' ),
				'tinymce' => array(
					'toolbar1' => 'formatselect,bold,italic,underline,blockquote,bullist,numlist,link,unlink,forecolor,undo,redo,spellchecker',
					'toolbar2' => '',
				),
				'textarea_rows' => '6',
				'wpautop' => true,
			),
			'columns' => '60',
			'preview' => true,
			'include_reference' => true,
			'callback' => 'rich_editor',
			'page' => 'evge_registration_messages',
			'section' => 'evge_registration_messages',
			'class' => 'evge-message-for-manual',
		);
		$this->add_setting_field( $args );


		add_settings_section(
			'evge_registration_buttons',
			__( 'Buttons', 'event-genius' ),
			array( $this, 'section_callback' ),
			'evge_registration_buttons' // must match do_settings_section
		);

		$args = array(
			'id'        => 'form_register_button',
			'label'       => __( '"Register" button', 'event-genius' ),
			'default'     => Defaults::get( 'form_register_button_text' ),
			'description' => '',
			'callback'    => 'buttons_with_preview',
			'class'       => '',
			'page'        => 'evge_registration_buttons',
			'section'     => 'evge_registration_buttons'
		);
		$this->add_setting_field( $args );
	}

	public static function get_option_slug() {
		return 'evge_registration_settings';
	}


	public function content() {
		$form_id = $this->form_id;
		$field_handler = $this->field_handler;
		$form = $this->form;
		$current_fields = $this->form->get_fields();
		include_once( EVGE_ADMIN_TEMPLATE_PATH . 'evge/registrations/form-builder/settings.php' );
	}


}
