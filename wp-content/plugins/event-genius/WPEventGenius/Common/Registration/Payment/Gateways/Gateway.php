<?php
namespace WPEventGenius\Common\Registration\Payment\Gateways;

use WPEventGenius\Common\Database;
use WPEventGenius\Common\Registration\Registration\RegistrationGroup;
use WPEventGenius\Common\Utils\Settings;
use WPEventGenius\Common\Utils\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

abstract class Gateway {

	protected $record;

	public function listeners() {
	}

	public function set_record( Record $record ) {
		$this->record = $record;
	}

	public function get_record() {
		return $this->record;
	}

	public function checkout_html() {
	}

	public function generate_invoice_id( RegistrationGroup $registration_group ) {
		$entry_id = $registration_group->get_main()->get_entry_id();
		// Utils::generate_invoice_number() now handles the filter internally
		return Utils::generate_invoice_number( $entry_id, $registration_group );
	}


	public function process_pending_payment( $registration_group, $subject, $message ) {
		$record = $registration_group->pending_payment_record();

		$this->send_generic_notification( $registration_group, $record, array( 'subject' => $subject, 'message' => $message ) );
	}

	/**
	 * Process completed payment
	 * 
	 * @param \WPEventGenius\Common\Registration\Registration\RegistrationGroup $registration_group
	 * @param \WPEventGenius\Common\Registration\Payment\Record $payment_record
	 */
	protected function process_completed_payment( $registration_group, $payment_record, $gateway_data = array() ) {
		// Update payment record
		$payment_record->set_payment_status( 'complete' );
		$payment_record->set_payment_date( gmdate( 'Y-m-d H:i:s' ) );
		
		$registration_group->update_payment( $payment_record );

		$this->event_confirmation_actions( $registration_group );

		$this->after_payment_completed( $registration_group, $payment_record );
	}

	/**
	 * Send confirmation email
	 * 
	 * @param \WPEventGenius\Common\Registration\Registration\RegistrationGroup $registration_group
	 */
	protected function event_confirmation_actions( $registration_group ) {
		// Check if this registration group uses custom confirmation actions
		if ( $registration_group->use_custom_confirmation_actions() ) {
			// Fire hook for custom payment actions (e.g., bulk orders)
			do_action( 'evge_custom_payment_actions', $registration_group, $this );
			return;
		}
		
		// Get the event
		$event_id = $registration_group->get_main()->get_registration_data( 'event_id' );
		if ( ! $event_id ) {
			return;
		}

		// Update registration status for all registrations in the group
		$database = new \WPEventGenius\Common\Database();
		
		// Update main registration status
		$main_entry_id = $registration_group->get_main()->get_entry_id();
		$database->set_status( $main_entry_id, 'confirmed' );
		
		// Update additional guest registrations status
		$additional_guests = $registration_group->get_additional_guest_registrations();
		foreach ( $additional_guests as $guest_registration ) {
			$guest_entry_id = $guest_registration->get_entry_id();
			if ( $guest_entry_id ) {
				$database->set_status( $guest_entry_id, 'confirmed' );
			}
		}

		$event = new \WPEventGenius\Common\Event\Event( $event_id );
		
		// Create communicator to send confirmation email
		$communicator = new \WPEventGenius\Common\Registration\Communicator\CommunicatorAfterConfirmed( $registration_group, $event );
		$communicator->execute();

		// Send receipt email if enabled
		$this->send_receipt_email( $registration_group, $event );
		
		// Send payment notification email if enabled
		$this->send_payment_notification_email( $registration_group, $event );
	}

	public function get_notification_email_args() {
		$receipt_not_message = Settings::get( 'receipt_not_message' );
		$receipt_not_subject = Settings::get( 'receipt_not_subject' );

		return array(
			'subject' => $receipt_not_subject,
			'message' => $receipt_not_message,
		);
	}

	public function get_receipt_email_args() {
		$message = Settings::get( 'receipt_message' );
		$subject = Settings::get( 'receipt_subject' );

		return array(
			'subject' => $subject,
			'message' => $message,
		);
	}

	public function after_payment_completed( $registration_group, $payment_record ) {
		do_action( 'evge_after_payment_completed', $registration_group, $payment_record );
	}

	/**
	 * Send receipt email to customer
	 * 
	 * @param \WPEventGenius\Common\Registration\Registration\RegistrationGroup $registration_group
	 * @param \WPEventGenius\Common\Event\Event $event
	 */
	protected function send_receipt_email( $registration_group, $event ) {
		// Check if receipt emails are enabled
		if ( ! \WPEventGenius\Pro\Utils\ProSettings::get( 'send_receipt_email' ) ) {
			return;
		}

		$registration_data = $registration_group->get_main()->get_registration_data();
		if ( empty( $registration_data['email'] ) ) {
			return;
		}

		$payment_record = $registration_group->get_payment_record_by_status( 'complete' );
		if ( ! $payment_record ) {
			return;
		}

		// Create receipt email
		$receipt_message = new \WPEventGenius\Pro\Email\ReceiptEmail( 
			new \WPEventGenius\Common\Utils\Placeholders( $registration_group->get_main(), $event ) 
		);

		// Set email properties
		$receipt_message->set_type( 'receipt' );
		$receipt_message->set_from_name( \WPEventGenius\Pro\Utils\ProSettings::get( 'receipt_from_name' ) ?: get_bloginfo( 'name' ) );
		$receipt_message->set_recipient( $registration_data['email'] );
		$receipt_message->set_subject( \WPEventGenius\Pro\Utils\ProSettings::get( 'receipt_email_subject' ) );

		// Generate receipt content
		$receipt_content = $this->generate_receipt_content( $registration_group, $event, $payment_record );
		$receipt_message->set_content( array( 'body' => $receipt_content ) );

		// Set up email
		$receipt_message->generate_headers( array() );
		$receipt_message->set_message_header( array() );
		$receipt_message->set_message_body();
		$receipt_message->set_message_footer( array() );

		// Send the email
		$receipt_message->send();
	}

	/**
	 * Send payment notification email to administrators
	 * 
	 * @param \WPEventGenius\Common\Registration\Registration\RegistrationGroup $registration_group
	 * @param \WPEventGenius\Common\Event\Event $event
	 */
	protected function send_payment_notification_email( $registration_group, $event ) {
		// Check if payment notifications are enabled
		if ( ! \WPEventGenius\Pro\Utils\ProSettings::get( 'send_payment_notification' ) ) {
			return;
		}

		$payment_record = $registration_group->get_payment_record_by_status( 'complete' );
		if ( ! $payment_record ) {
			return;
		}

		// Get notification recipients
		$recipients = $event->get_notification_recipients();
		if ( empty( $recipients ) ) {
			return;
		}

		// Create notification email
		$notification_message = new \WPEventGenius\Common\Email\NotificationEmail( 
			new \WPEventGenius\Common\Utils\Placeholders( $registration_group->get_main(), $event, 'email', true ) 
		);

		// Set email properties
		$notification_message->set_type( 'notification' );
		$notification_message->set_from_name( \WPEventGenius\Pro\Utils\ProSettings::get( 'payment_notification_from_name' ) ?: get_bloginfo( 'name' ) );
		$notification_message->set_subject( \WPEventGenius\Pro\Utils\ProSettings::get( 'payment_notification_subject' ) );

		// Add all recipients
		foreach ( $recipients as $recipient ) {
			$notification_message->set_recipient( $recipient );
		}

		// Generate payment notification content
		$notification_content = $this->generate_payment_notification_content( $registration_group, $event, $payment_record );
		$notification_message->set_content( array( 'body' => $notification_content ) );

		// Set up email
		$notification_message->generate_headers( array() );
		$notification_message->set_message_header( array() );
		$notification_message->set_message_body();
		$notification_message->set_message_footer( array() );

		// Send the email
		$notification_message->send();
	}

	/**
	 * Generate receipt email content
	 * 
	 * @param \WPEventGenius\Common\Registration\Registration\RegistrationGroup $registration_group
	 * @param \WPEventGenius\Common\Event\Event $event
	 * @param \WPEventGenius\Common\Registration\Payment\Record $payment_record
	 * @return string
	 */
	protected function generate_receipt_content( $registration_group, $event, $payment_record ) {
		// Get the receipt template content
		$custom_receipt_template = locate_template( 'event-genius/pro/email/receipt.php', false, false );
		$receipt_template = $custom_receipt_template ? $custom_receipt_template : EVGE_PLUGIN_PATH . 'templates/event-genius/pro/email/receipt.php';
		
		// Pass variables to template
		$payment_record = $payment_record;
		$registration_group = $registration_group;
		$event = $event;

		// Generate the receipt template content
		ob_start();
		include $receipt_template;
		$receipt_template_content = ob_get_contents();
		ob_end_clean();

		// Build the final receipt content with heading, body, and template
		ob_start();
		?>
		<div class="evge-receipt" style="padding: 14px; font-family: sans-serif;">
			<h3 style="color: #333; margin: 0 0 15px 0; font-size: 24px; border-bottom: 1px solid #eaeaea; padding-bottom: 10px;">
				<?php echo wp_kses_post( \WPEventGenius\Pro\Utils\ProSettings::get( 'receipt_email_heading' ) ); ?>
			</h3>
			<?php echo wp_kses_post( \WPEventGenius\Pro\Utils\ProSettings::get( 'receipt_email_body' ) ); ?>
		</div>
		<?php
		$receipt_email_body = ob_get_contents();
		ob_end_clean();

		// Replace {receipt} placeholder with the receipt template content
		$receipt_html = str_replace( '{receipt}', $receipt_template_content, $receipt_email_body );

		return $receipt_html;
	}

	/**
	 * Generate payment notification email content
	 * 
	 * @param \WPEventGenius\Common\Registration\RegistrationGroup $registration_group
	 * @param \WPEventGenius\Common\Event\Event $event
	 * @param \WPEventGenius\Common\Registration\Payment\Record $payment_record
	 * @return string
	 */
	protected function generate_payment_notification_content( $registration_group, $event, $payment_record ) {
		// Get the receipt template content (same as customer receipt)
		$custom_receipt_template = locate_template( 'event-genius/pro/email/receipt.php', false, false );
		$receipt_template = $custom_receipt_template ? $custom_receipt_template : EVGE_PLUGIN_PATH . 'templates/event-genius/pro/email/receipt.php';
		
		// Pass variables to template
		$payment_record = $payment_record;
		$registration_group = $registration_group;
		$event = $event;

		// Generate the receipt template content
		ob_start();
		include $receipt_template;
		$receipt_template_content = ob_get_contents();
		ob_end_clean();

		// Get the payment notification body from settings
		$notification_body = \WPEventGenius\Pro\Utils\ProSettings::get( 'payment_notification_body' );
		
		// Replace {receipt} placeholder with the receipt template content
		$notification_content = str_replace( '{receipt}', $receipt_template_content, $notification_body );

		return $notification_content;
	}
}
