<?php
namespace WPEventGenius\Admin\Page\Registrations\FormBuilder;

use WPEventGenius\Admin\Services\PaymentMethodAdmin;
use WPEventGenius\Common\Utils\Defaults;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EmailPage extends BasePage {

	protected $active_subtab = 'emails';

	public static function rich_editor_fields() {
		return array(
			'confirmation_email_content',
			'notification_email_content',
			'cancel_request_email_content',
			'cancel_success_message',
			'cancel_request_success_message',
			'cancel_notification_email_content',
			'cancel_confirmation_email_content',
			'confirmed_modal_message',
			'additional_guest_summary_template',
		);
	}

	public function page_title() {
		return __( 'Form Builder', 'event-genius' );
	}

	public function build() {
		parent::build();

		$this->settings();
	}

	public function settings() {
		add_settings_section(
			'evge_registration_email_confirmation',
			'',
			array( $this, 'section_callback' ),
			'evge_registration_email_confirmation' // must match do_settings_section
		);

		$this->add_setting_field( array(
			'id' => 'notification_recipients',
			'label' => __( 'Notification Recipients', 'event-genius' ),
			'text_fields' => array(
				array(
					'id' => 'notification_recipients',
					'label' => __( 'Notification Recipients', 'event-genius' ),
					'type' => 'text_field',
					'tooltip' => __( 'For notifications when someone registers or cancels. Enter email addresses separated by commas.', 'event-genius' ),
					'class' => '',
				),
			),
			'callback' => 'multi_text_field',
			'page' => 'evge_registration_email_confirmation',
			'section' => 'evge_registration_email_confirmation'
		) );

		$text_fields = array(
			array(
				'id' => 'send_confirmation_email',
				'label' => __( 'Send Confirmation Email', 'event-genius' ),
				'default' => Defaults::get( 'send_confirmation_email' ), // 'enabled', 'disabled'
				'tooltip' => __( 'This is an email sent to the person registering after they are confirmed.', 'event-genius' ),
				'type' => 'toggle_field',
				'class' => '',
			),
			array(
				'id' => 'confirmation_from_name',
				'label' => __( 'From Name', 'event-genius' ),
				'tooltip' => __( 'The name that appears as to who sent the email.', 'event-genius' ),
				'show_label' => true,
				'type' => 'text_field',
				'class' => 'evge-short',
			),
			array(
				'id' => 'confirmation_subject_new_reg',
				'label' => __( 'Subject', 'event-genius' ),
				'tooltip' => __( 'The subject line of the email.', 'event-genius' ),
				'show_label' => true,
				'type' => 'text_field',
				'class' => '',
			),
			array(
				'id' => 'confirmation_email_content',
				'label' => __( 'Email Message', 'event-genius' ),
				'tooltip' => __( 'The body of the email.', 'event-genius' ),
				'type' => 'rich_editor',
				'settings' => array(
					'quicktags'     => array( 'buttons' => 'strong,em,del,ul,ol,li,close,link,img' ),
					'tinymce'       => array(
						'toolbar1' => 'formatselect,bold,italic,underline,blockquote,bullist,numlist,link,unlink,forecolor,undo,redo,spellchecker',
						'toolbar2' => '',
					),
					'textarea_rows' => '15',
					'wpautop'       => true,
				),
				'columns'     => '60',
				'preview'     => true,
				'include_reference'      => true,
			),
		);
		$args = array(
			'id' => 'confirmation_email',
			'label' => __( 'Confirmation Email', 'event-genius' ),
			'text_fields' => $text_fields,
			'callback' => 'multi_text_field',
			'page' => 'evge_registration_email_confirmation',
			'section' => 'evge_registration_email_confirmation'
		);
		$this->add_setting_field( $args );

		add_settings_section(
			'evge_registration_email_notification',
			'',
			array( $this, 'section_callback' ),
			'evge_registration_email_notification' // must match do_settings_section
		);

		$text_fields = array(
			array(
				'id' => 'send_notification_email',
				'label' => __( 'Send Notification Email', 'event-genius' ),
				'default' => Defaults::get( 'send_notification_email' ), // 'enabled', 'disabled'
				'tooltip' => __( 'This is an email sent to event administrators to notify of a new registration.', 'event-genius' ),
				'type' => 'toggle_field',
				'class' => '',
			),
			array(
				'id' => 'notification_from_name',
				'label' => __( 'From Name', 'event-genius' ),
				'tooltip' => __( 'The name that appears as to who sent the email.', 'event-genius' ),
				'show_label' => true,
				'type' => 'text_field',
				'class' => 'evge-short',
			),
			array(
				'id' => 'notification_subject_new_reg',
				'label' => __( 'Subject', 'event-genius' ),
				'tooltip' => __( 'The subject line of the email.', 'event-genius' ),
				'show_label' => true,
				'type' => 'text_field',
				'class' => '',
			),
			array(
				'id' => 'notification_email_content',
				'label' => __( 'Email Message', 'event-genius' ),
				'tooltip' => __( 'The subject line of the email.', 'event-genius' ),
				'type' => 'rich_editor',
				'settings' => array(
					'quicktags'     => array( 'buttons' => 'strong,em,del,ul,ol,li,close,link,img' ),
					'tinymce'       => array(
						'toolbar1' => 'formatselect,bold,italic,underline,blockquote,bullist,numlist,link,unlink,forecolor,undo,redo,spellchecker',
						'toolbar2' => '',
					),
					'textarea_rows' => '15',
					'wpautop'       => true,
				),
				'columns'     => '60',
				'preview'     => true,
				'include_reference'      => true,
			),
		);
		$args = array(
			'id' => 'notification_email',
			'label' => __( 'Notification Email', 'event-genius' ),
			'text_fields' => $text_fields,
			'callback' => 'multi_text_field',
			'page' => 'evge_registration_email_notification',
			'section' => 'evge_registration_email_notification'
		);
		$this->add_setting_field( $args );




		add_settings_section(
			'evge_registration_email_cancel_request',
			'',
			array( $this, 'section_callback' ),
			'evge_registration_email_cancel_request' // must match do_settings_section
		);

		$text_fields = array(
			array(
				'id' => 'cancel_request_subject',
				'label' => __( 'Subject', 'event-genius' ),
				'tooltip' => __( 'The subject line of the email.', 'event-genius' ),
				'show_label' => true,
				'type' => 'text_field',
				'class' => '',
			),
			array(
				'id' => 'cancel_request_email_content',
				'label' => __( 'Email Message', 'event-genius' ),
				'tooltip' => __( 'The subject line of the email.', 'event-genius' ),
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
				'columns'     => '60',
				'preview'     => true,
				'include_reference'      => true,
			),
		);
		$args = array(
			'id' => 'cancel_request_email',
			'label' => __( 'Cancel Request Email', 'event-genius' ),
			'text_fields' => $text_fields,
			'callback' => 'multi_text_field',
			'page' => 'evge_registration_email_cancel_request',
			'section' => 'evge_registration_email_cancel_request'
		);
		$this->add_setting_field( $args );



		add_settings_section(
			'evge_registration_email_cancel_notification',
			'',
			array( $this, 'section_callback' ),
			'evge_registration_email_cancel_notification' // must match do_settings_section
		);

		$text_fields = array(
			array(
				'id' => 'send_cancel_notification_email',
				'label' => __( 'Send Cancellation Notification Email', 'event-genius' ),
				'default' => Defaults::get( 'send_cancel_notification_email' ), // 'enabled', 'disabled'
				'tooltip' => __( 'This is an email sent to event admins as a notification that a registrant canceled.', 'event-genius' ),
				'type' => 'toggle_field',
				'class' => '',
			),
			array(
				'id' => 'cancel_notification_subject',
				'label' => __( 'Subject', 'event-genius' ),
				'tooltip' => __( 'The subject line of the email.', 'event-genius' ),
				'show_label' => true,
				'type' => 'text_field',
				'class' => '',
			),
			array(
				'id' => 'cancel_notification_email_content',
				'label' => __( 'Email Message', 'event-genius' ),
				'tooltip' => __( 'The subject line of the email.', 'event-genius' ),
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
				'columns'     => '60',
				'preview'     => true,
				'include_reference'      => true,
			),
		);
		$args = array(
			'id' => 'cancel_notification_email',
			'label' => __( 'Cancel Notification Email', 'event-genius' ),
			'text_fields' => $text_fields,
			'callback' => 'multi_text_field',
			'page' => 'evge_registration_email_cancel_notification',
			'section' => 'evge_registration_email_cancel_notification'
		);
		$this->add_setting_field( $args );



		add_settings_section(
			'evge_registration_email_cancel_confirmation',
			'',
			array( $this, 'section_callback' ),
			'evge_registration_email_cancel_confirmation' // must match do_settings_section
		);

		$text_fields = array(
			array(
				'id' => 'send_cancel_confirmation_email',
				'label' => __( 'Send Cancellation Confirmation Email', 'event-genius' ),
				'default' => Defaults::get( 'send_cancel_confirmation_email' ), // 'enabled', 'disabled'
				'tooltip' => __( 'This is an email sent to the person canceling to confirm that they are no longer registered.', 'event-genius' ),
				'type' => 'toggle_field',
				'class' => '',
			),
			array(
				'id' => 'cancel_confirmation_subject',
				'label' => __( 'Subject', 'event-genius' ),
				'tooltip' => __( 'The subject line of the email.', 'event-genius' ),
				'show_label' => true,
				'type' => 'text_field',
				'class' => '',
			),
			array(
				'id' => 'cancel_confirmation_email_content',
				'label' => __( 'Email Message', 'event-genius' ),
				'tooltip' => __( 'The subject line of the email.', 'event-genius' ),
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
				'columns'     => '60',
				'preview'     => true,
				'include_reference'      => true,
			),
		);
		$args = array(
			'id' => 'cancel_confirmation_email',
			'label' => __( 'Cancel Confirmation Email', 'event-genius' ),
			'text_fields' => $text_fields,
			'callback' => 'multi_text_field',
			'page' => 'evge_registration_email_cancel_confirmation',
			'section' => 'evge_registration_email_cancel_confirmation'
		);
		$this->add_setting_field( $args );



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
		 * Registration Form Settings
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
			'section' => 'evge_registration_messages'
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
	}

	public static function get_option_slug() {
		return 'evge_registration_settings';
	}

	public function content() {
		$form_id = $this->form_id;
		$field_handler = $this->field_handler;
		$form = $this->form;
		$current_fields = $this->form->get_fields();
		include_once( EVGE_ADMIN_TEMPLATE_PATH . 'evge/registrations/form-builder/emails.php' );
	}

	public function before_settings_sections() {

	}


}
