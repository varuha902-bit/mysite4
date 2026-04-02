<?php

namespace WPEventGenius\Admin\Services\Gateways;

use WPEventGenius\Admin\Settings\RegistrationSettings;
use WPEventGenius\Common\Utils\Defaults;
use WPEventGenius\Common\Utils\Settings;
use WPEventGenius\Admin\Services\Gateways\GatewayAdminInterface;
use WPEventGenius\Pro\Utils\ProDefaults;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OfflineAdmin implements GatewayAdminInterface {
	public function __construct() {

	}

	public function init_hooks() {
		add_action( 'evge_admin_payment_gateway_settings_offline', array( $this, 'the_tab_html' ) );
		add_action( 'admin_init', array( $this, 'settings_fields' ) );
	}

	public function details() {
		// Get enabled status from payment gateway settings using ProSettings if available, otherwise use defaults
		$enabled = false; // Default to disabled
		
		if (class_exists('\WPEventGenius\Pro\Utils\ProSettings')) {
			$payment_gateways = \WPEventGenius\Pro\Utils\ProSettings::get( 'payment_gateways' );
			
			if ( ! empty( $payment_gateways ) && is_array( $payment_gateways ) ) {
				if ( isset( $payment_gateways['offline'] ) && isset( $payment_gateways['offline']['enabled'] ) ) {
					$enabled = $payment_gateways['offline']['enabled'];
				}
			}
		}

		$details = array(
			'identity_key'  => 'offline',
			'name'          => __( 'Offline', 'event-genius' ),
			'gateway_key'   => 'offline',
			'description'   => __( 'Process payments offline. Great for check payments or payments completed at the event itself.', 'event-genius' ),
			'enabled'       => $enabled,
			'is_configured' => $this->is_configured(),
		);

		return $details;
	}

	/**
	 * Check if Offline gateway is properly configured
	 * 
	 * @return bool
	 */
	private function is_configured() {
		return true;
	}

	public function settings_fields() {
		// Check if Pro classes are available, otherwise skip settings
		if (!class_exists('\WPEventGenius\Admin\Pro\Settings\ProSettings')) {
			return; // Skip settings in free version
		}
		
		$class      = new \WPEventGenius\Admin\Pro\Settings\ProSettings();
        $notice = '';

		// Ensure the ProSettings are properly registered
		$class->settings();
        
		// Checkout Settings Section
		add_settings_section(
			'evge_offline_payment_checkout',
			__( 'Checkout', 'event-genius' ),
			array( $class, 'notice' ),
			'evge_offline_payment_checkout',
            array( 'notice' => $notice )
		);

		$args = array(
				'id'        => 'offline_payment_option_label',
				'label'       => __( 'Payment Option Label', 'event-genius' ),
				'example'     => '',
				'description' => '',
				'callback'    => 'text_field',
				'class'       => 'text_field',
				'page'        => 'evge_offline_payment_checkout',
				'section'     => 'evge_offline_payment_checkout',
				'default'     => Defaults::get( 'offline_payment_option_label' ),
		);
		$class->add_setting_field( $args );

		$args = array(
				'id'        => 'offline_payment_option_description',
				'label'       => __( 'Payment Option Description', 'event-genius' ),
				'example'     => '',
				'description' => '',
				'callback'    => 'text_field',
				'class'       => 'regular-text',
				'page'        => 'evge_offline_payment_checkout',
				'section'     => 'evge_offline_payment_checkout',
				'default'     => Defaults::get( 'offline_payment_option_description' ),
		);
		$class->add_setting_field( $args );

		$args = array(
				'id'        => 'offline_payment_button_text',
				'label'       => __( 'Button Text', 'event-genius' ),
				'example'     => '',
				'description' => '',
				'callback'    => 'text_field',
				'class'       => 'evge-medium-text',
				'page'        => 'evge_offline_payment_checkout',
				'section'     => 'evge_offline_payment_checkout',
				'default'     => Defaults::get( 'offline_payment_button_text' ),
		);
		$class->add_setting_field( $args );

		$args = array(
				'id'        => 'offline_payment_additional_instructions',
				'label'       => __( 'Additional Instructions', 'event-genius' ) . $class->tooltip( __( 'These instructions will appear in the checkout modal when registrants select the offline payment option, before they submit their registration.', 'event-genius' ) ),
				'example'     => '',
				'description' => __( 'Additional instructions that will be displayed to attendees during the offline payment process.', 'event-genius' ),
				'callback'    => 'rich_editor',
				'class'       => '',
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
				'page'        => 'evge_offline_payment_checkout',
				'section'     => 'evge_offline_payment_checkout',
				'default'     => Defaults::get( 'offline_payment_additional_instructions')
		);
		$class->add_setting_field( $args );

		// After Confirmation Settings Section
		add_settings_section(
			'evge_offline_payment_after_confirmation',
			__( 'After Payment Selection', 'event-genius' ),
			array( $class, 'notice' ),
			'evge_offline_payment_after_confirmation',
            array( 'notice' => $notice )
		);


		$args = array(
				'id'        => 'offline_registration_status',
				'label'       => __( 'Registration Status', 'event-genius' ) . $class->tooltip( __( 'Choose whether registrations should be marked as confirmed or pending when offline payment is selected.', 'event-genius' ) ),
				'example'     => '',
				'callback'    => 'select_field',
				'class'       => '',
				'options'     => array(
					'confirmed' => __( 'Confirmed', 'event-genius' ),
					'pending'   => __( 'Pending', 'event-genius' ),
				),
				'page'        => 'evge_offline_payment_after_confirmation',
				'section'     => 'evge_offline_payment_after_confirmation',
				'default'     => 'confirmed',
		);
		$class->add_setting_field( $args );

		$args = array(
				'id'        => 'offline_payment_instructions',
				'label'       => __( 'Payment Success Message', 'event-genius' ) . $class->tooltip( __( 'This message will appear in the checkout modal after registrants submit their registration with offline payment selected, replacing the payment form.', 'event-genius' ) ),
				'example'     => '',
				'description' => __( 'These instructions will appear after the attendee chooses to pay offline.', 'event-genius' ),
				'callback'    => 'rich_editor',
				'class'       => '',
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
				'page'        => 'evge_offline_payment_after_confirmation',
				'section'     => 'evge_offline_payment_after_confirmation',
				'default'     => Defaults::get( 'offline_payment_instructions')
		);
		$class->add_setting_field( $args );

		
		// Email Settings Section
		add_settings_section(
			'evge_offline_payment_email_settings',
			__( 'Email Settings', 'event-genius' ),
			array( $class, 'notice' ),
			'evge_offline_payment_email_settings',
            array( 'notice' => $notice )
		);

		$args = array(
				'id'        => 'offline_payment_instruction_subject',
				'label'       => __( 'Payment Instruction Email Subject', 'event-genius' ) . $class->tooltip( __( 'This email is automatically sent to registrants after they submit their registration with offline payment selected. It can be used to provide instructions on how to complete their payment.', 'event-genius' ) ),
				'example'     => '',
				'description' => __( 'The subject line for payment instruction emails.', 'event-genius' ),
				'callback'    => 'text_field',
				'class'       => 'regular-text',
				'page'        => 'evge_offline_payment_email_settings',
				'section'     => 'evge_offline_payment_email_settings',
				'default'     => Defaults::get( 'offline_payment_instruction_subject' ),
		);
		$class->add_setting_field( $args );

		$args = array(
				'id'        => 'offline_content',
				'label'       => __( 'Payment Instruction Email Content', 'event-genius' ),
				'example'     => '',
				'description' => __( 'This content will be sent in the payment instruction email when "Offline" is chosen as the payment method. Use placeholders to personalize the message.', 'event-genius' ),
				'callback'    => 'rich_editor',
				'class'       => '',
				'settings'    => array(
					'quicktags'     => array( 'buttons' => 'strong,em,del,ul,ol,li,close,link,img' ),
					'tinymce'       => array(
						'toolbar1' => 'formatselect,bold,italic,underline,blockquote,bullist,numlist,link,unlink,forecolor,undo,redo,spellchecker',
						'toolbar2' => '',
					),
					'textarea_rows' => '12',
					'wpautop'       => true,
				),
				'columns'     => '60',
				'preview'     => true,
				'include_reference'      => true,
				'reference_type' => 'email',
				'page'        => 'evge_offline_payment_email_settings',
				'section'     => 'evge_offline_payment_email_settings',
				'default'     => Defaults::get( 'offline_content')
		);
		$class->add_setting_field( $args );


		$args = array(
				'id'        => 'offline_payment_pending_notification_subject',
				'label'       => __( 'Pending Notification Subject', 'event-genius' ) . $class->tooltip( __( 'This email is automatically sent to event administrators and organizers when a registrant submits their registration with offline payment selected. It can be used to help track manual payments.', 'event-genius' ) ),
				'example'     => '',
				'description' => __( 'The subject line for offline payment pending notifications.', 'event-genius' ),
				'callback'    => 'text_field',
				'class'       => 'regular-text',
				'page'        => 'evge_offline_payment_email_settings',
				'section'     => 'evge_offline_payment_email_settings',
				'default'     => Defaults::get( 'offline_payment_pending_notification_subject' ),
		);
		$class->add_setting_field( $args );

		$args = array(
				'id'        => 'offline_payment_pending_notification_body',
				'label'       => __( 'Pending Notification Content', 'event-genius' ),
				'example'     => '',
				'description' => __( 'The content for offline payment pending notifications sent to administrators.', 'event-genius' ),
				'callback'    => 'rich_editor',
				'class'       => '',
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
				'reference_type' => 'email',
				'page'        => 'evge_offline_payment_email_settings',
				'section'     => 'evge_offline_payment_email_settings',
				'default'     => Defaults::get( 'offline_payment_pending_notification_body' ),
		);
		$class->add_setting_field( $args );

		$args = array(
				'id'        => 'offline_payment_instructions_resent_message',
				'label'       => __( 'Payment Instructions Resent Message', 'event-genius' ) . $class->tooltip( __( 'Message that appears when payment instructions have been successfully resent to a registrant.', 'event-genius' ) ),
				'example'     => '',
				'description' => __( 'Message displayed when payment instructions are successfully resent.', 'event-genius' ),
				'callback'    => 'text_field',
				'class'       => 'regular-text',
				'page'        => 'evge_offline_payment_email_settings',
				'section'     => 'evge_offline_payment_email_settings',
				'default'     => ProDefaults::get( 'offline_payment_instructions_resent_message' ),
		);
		$class->add_setting_field( $args );

	}



	public function the_tab_html() {
		settings_errors();
		$return_url = add_query_arg( array( 'page' => 'evge-settings', 'tab' => 'payments' ), admin_url( 'admin.php' ) );
		?>
		<div class="evge-settings-section-wrap evge-configuration-header">
			<h2><?php 
			/* translators: %s: Gateway name (e.g., "Offline", "PayPal Standard") */
			printf( esc_html__( '%s Configuration', 'event-genius' ), __( 'Offline', 'event-genius' ) ); 
			?><?php \WPEventGenius\Common\Utils\Notices::documentation( __( 'How to use offline payments', 'event-genius' ), '', 'https://wpeventgenius.com/docs/offline-payments-setup/?utm_campaign=evge-pro&utm_source=offline-settings&utm_medium=documentation-notice&utm_content=setup-guide' ); ?></h2>
		</div>

		<form method="post" action="options.php">
			<input type="hidden" name="tab" value="payments" />
			<?php settings_fields( 'evge_pro_settings' ); ?>
			<div class="evge-settings-section-wrap evge-pro-settings-section">
				<?php do_settings_sections( 'evge_offline_payment_checkout' ); ?>
			</div>
			<div class="evge-settings-section-wrap evge-pro-settings-section">
				<?php do_settings_sections( 'evge_offline_payment_after_confirmation' ); ?>
			</div>
			<div class="evge-settings-section-wrap evge-pro-settings-section">
				<?php do_settings_sections( 'evge_offline_payment_email_settings' ); ?>
			</div>
            <input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes', 'event-genius' ); ?>" />
        </form>
		<?php
	}

}