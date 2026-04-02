<?php
namespace WPEventGenius\Common\Registration\Payment\Gateways;

use WPEventGenius\Common\Registration\Payment\Record;
use WPEventGenius\Common\Utils\Settings;
use WPEventGenius\Common\Utils\Utils;
use WPEventGenius\Pro\Utils\ProSettings;
use WPEventGenius\Pro\Utils\ProPlaceholders;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class Offline extends Gateway {

	protected $record;

	public function create_payment( $registration_group, $skip_confirmation = false ) {
		$payment_record = $registration_group->pending_payment_record();

		if ( $registration_group->pending_payment_record() ) {
			$payment_record = $registration_group->pending_payment_record();

			if ( $payment_record->get_gateway_name() !== 'offline' ) {
				$payment_record->set_payment_status( 'offline' );
				$payment_record->set_gateway_name( 'offline' );
				$payment_record->set_payment_id( 'offline-' . $registration_group->get_main()->get_entry_id() . '-' . time() );
				$payment_record->set_business( 'Offline' );
			}
			$payment_record->set_payment_gross( $registration_group->payment_handler()->calculate_total() );
			$payment_record->set_payment_date( gmdate( 'Y-m-d H:i:s' ) );
			$success = $registration_group->update_payment( $payment_record );
		} else {
			// Use generate_invoice_id() to ensure evge_invoice_id filter is applied
			$invoice_id = $this->generate_invoice_id( $registration_group );
			
			$args = array(
				'gateway'        => 'offline',
				'payment_date'   => gmdate( 'Y-m-d H:i:s' ),
				'invoice_id'     => $invoice_id,
				'payment_id'     => 'offline-' . $registration_group->get_main()->get_entry_id() . '-' . time(),
				'business'       => 'Offline',
				'payment_status' => 'offline',
				'payment_gross'  => $registration_group->payment_handler()->calculate_total(),
				'currency_code'  => 'USD',
			);
			$payment_record = new Record( $args );

			$success = $registration_group->update_payment( $payment_record );
		}

		// For offline payments, we don't call process_completed_payment
		// Instead, we handle the offline payment confirmation actions separately

		if ( ! $skip_confirmation ) {
			$this->event_confirmation_actions( $registration_group );
		}

		return $payment_record;
	}

	public function request_response( $status, $registration_group ) {
		return array(
			'status' => $status,
			'html'    => wp_kses_post( nl2br( ProSettings::get( 'offline_payment_instructions', '' ) ) ),
		);
	}

	public function surcharge_setting() {

		if ( ! class_exists( '\WPEventGenius\Pro\Utils\ProSettings' ) ) {
			return array(
				'label'        => __( 'Processing Fee', 'event-genius' ),
				'percent_cost' => 0,
				'flat_cost'    => 0,
			);
		}

		$surcharges = array(
			'label'        => ProSettings::get( 'tax_surcharge_amount_offline_label', __( 'Processing Fee', 'event-genius' ) ),
			'percent_cost' => ProSettings::get( 'tax_surcharge_amount_offline', 0 ),
			'flat_cost'    => ProSettings::get( 'tax_surcharge_amount_offline_flat', 0 ),
		);

		return $surcharges;
	}

	public function identity_key() {
		return 'offline';
	}

	/**
	 * Override event confirmation actions to use specialized offline payment email system
	 * 
	 * @param \WPEventGenius\Common\Registration\RegistrationGroup $registration_group
	 */
	protected function event_confirmation_actions( $registration_group ) {
		// Skip Pro-specific functionality in free version
		if ( ! class_exists( '\WPEventGenius\Pro\Utils\ProSettings' ) ) {
			return;
		}
		
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

		// Get the configured registration status for offline payments
		$registration_status = ProSettings::get( 'offline_registration_status' );
		
		// Update registration status for all registrations in the group
		$database = new \WPEventGenius\Common\Database();
		
		// Update main registration status
		$main_entry_id = $registration_group->get_main()->get_entry_id();
		$database->set_status( $main_entry_id, $registration_status );
		
		// Update additional guest registrations status
		$additional_guests = $registration_group->get_additional_guest_registrations();
		foreach ( $additional_guests as $guest_registration ) {
			$guest_entry_id = $guest_registration->get_entry_id();
			if ( $guest_entry_id ) {
				$database->set_status( $guest_entry_id, $registration_status );
			}
		}

		$event = new \WPEventGenius\Common\Event\Event( $event_id );
		
		// If status is confirmed, send confirmation email using communicator
		if ( 'confirmed' === $registration_status ) {
			$communicator = new \WPEventGenius\Common\Registration\Communicator\CommunicatorAfterConfirmed( $registration_group, $event );
			$communicator->execute();
		} else {
			// Send normal registration notification email if enabled using communicator
			if ( $event->get( 'send_notification_email' ) === 'enabled' ) {
				$this->send_notification_email_via_communicator( $registration_group, $event );
			}
		}
		
		// Send offline payment instruction email to registrant
		$this->send_offline_payment_instruction_email( $registration_group, $event );
		
		// Send offline payment pending notification to administrators
		$this->send_offline_payment_pending_notification( $registration_group, $event );
	}

	/**
	 * Send offline payment instruction email to registrant
	 * 
	 * @param \WPEventGenius\Common\Registration\Registration\RegistrationGroup $registration_group
	 * @param \WPEventGenius\Common\Event\Event $event
	 */
	protected function send_offline_payment_instruction_email( $registration_group, $event ) {
		// Skip Pro-specific functionality in free version
		if ( ! class_exists( '\WPEventGenius\Pro\Email\OfflinePaymentInstructionEmail' ) ) {
			return;
		}
		
		// Check if offline payment instruction emails are enabled
		if ( \WPEventGenius\Common\Utils\Settings::get( 'send_confirmation_email' ) !== 'enabled' ) {
			return;
		}

		$registration_data = $registration_group->get_main()->get_registration_data();
		if ( empty( $registration_data['email'] ) ) {
			return;
		}


		// Create offline payment instruction email
		$instruction_message = new \WPEventGenius\Pro\Email\OfflinePaymentInstructionEmail( 
			new ProPlaceholders( $registration_group->get_main(), $event ) 
		);

		// Set email properties
		$instruction_message->set_recipient( $registration_data['email'] );
		
		// Set subject from settings
		$subject = ProSettings::get( 'offline_payment_instruction_subject', __( 'Payment Instructions - {event-title}', 'event-genius' ) );
		$instruction_message->set_subject( $subject );

		// Set reply-to to the first notification recipient
		$notification_recipients = $event->get_notification_recipients();
		$reply_to = ! empty( $notification_recipients[0] ) ? $notification_recipients[0] : '';
		if ( \WPEventGenius\Common\Utils\Settings::get( 'email_from_address_custom' ) === 'enabled' ) {
			$instruction_message->set_from_address( \WPEventGenius\Common\Utils\Settings::get( 'email_from_address' ) );
		}
		// Set up email
		$instruction_message->generate_headers( array( 'reply_to' => $reply_to ) );
		
		// Set content from settings
		$content = ProSettings::get( 'offline_content', '' );
		$instruction_message->set_content( array( 'body' => $content ) );
		
		$instruction_message->set_message_header( array() );
		$instruction_message->set_message_body();
		$instruction_message->set_message_footer( array() );

		// Send the email
		$instruction_message->send();
	}

	/**
	 * Send offline payment pending notification to administrators
	 * 
	 * @param \WPEventGenius\Common\Registration\Registration\RegistrationGroup $registration_group
	 * @param \WPEventGenius\Common\Event\Event $event
	 */
	protected function send_offline_payment_pending_notification( $registration_group, $event ) {
		// Skip Pro-specific functionality in free version
		if ( ! class_exists( '\WPEventGenius\Pro\Email\OfflinePaymentPendingNotificationEmail' ) ) {
			return;
		}
		
		// Check if offline payment pending notifications are enabled
		if ( \WPEventGenius\Common\Utils\Settings::get( 'send_notification_email' ) !== 'enabled' ) {
			return;
		}

		// Get notification recipients
		$recipients = $event->get_notification_recipients();
		if ( empty( $recipients ) ) {
			return;
		}

		// Create offline payment pending notification email
		$notification_message = new \WPEventGenius\Pro\Email\OfflinePaymentPendingNotificationEmail( 
			new ProPlaceholders( $registration_group->get_main(), $event, 'email', true ) 
		);

		// Set email properties
		$subject = ProSettings::get( 'offline_payment_pending_notification_subject' );
		$notification_message->set_subject( $subject );

		// Add all recipients
		foreach ( $recipients as $recipient ) {
			$notification_message->set_recipient( $recipient );
		}
		if ( \WPEventGenius\Common\Utils\Settings::get( 'email_from_address_custom' ) === 'enabled' ) {
			$notification_message->set_from_address( \WPEventGenius\Common\Utils\Settings::get( 'email_from_address' ) );
		}
		$notification_message->set_from_name( $event->get( 'notification_from_name' ) );
		// Set up email
		$notification_message->generate_headers( array() );
		
		// Set content from settings
		$content = ProSettings::get( 'offline_payment_pending_notification_body' );
		$notification_message->set_content( array( 'body' => $content ) );
		
		$notification_message->set_message_header( array() );
		$notification_message->set_message_body();
		$notification_message->set_message_footer( array() );

		// Send the email
		$notification_message->send();
	}

	/**
	 * Send normal registration notification email using communicator
	 * 
	 * @param \WPEventGenius\Common\Registration\Registration\RegistrationGroup $registration_group
	 * @param \WPEventGenius\Common\Event\Event $event
	 */
	protected function send_notification_email_via_communicator( $registration_group, $event ) {
		// Skip Pro-specific functionality in free version
		if ( ! class_exists( '\WPEventGenius\Pro\Utils\ProSettings' ) ) {
			return;
		}
		
		// Use the Pro notification communicator for sending notification emails
		$communicator = new \WPEventGenius\Pro\Registration\Communicator\ProNotificationCommunicator( $registration_group, $event );
		$communicator->execute();
	}

}