<?php

namespace WPEventGenius\Common\Registration\Communicator;

use WPEventGenius\Common\Email\ActionEmail;
use WPEventGenius\Common\Email\NotificationEmail;
use WPEventGenius\Common\Utils\Placeholders;
use WPEventGenius\Common\Utils\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

/**
 * Communicator for handling canceled registration status
 * Used when registration is canceled (e.g., due to payment refund, admin action, etc.)
 */
class CommunicatorAfterCancel extends Communicator {
	
	/**
	 * Execute the communicator actions
	 */
	public function execute() {
		$this->send_emails();
	}

	/**
	 * Send appropriate emails for canceled status
	 */
	public function send_emails() {
		// Send cancellation notification email to admins if enabled
		if ( Settings::get( 'send_cancel_notification_email' ) === 'enabled' ) {
			$this->send_cancel_notification_email();
		}

		// Send cancellation confirmation email to registrant if enabled
		if ( Settings::get( 'send_cancel_confirmation_email' ) === 'enabled' ) {
			$this->send_cancel_confirmation_email();
		}
	}

	/**
	 * Send cancellation notification email to event administrators
	 */
	private function send_cancel_notification_email() {
		$recipients = $this->event->get_notification_recipients();
		if ( empty( $recipients ) ) {
			return false;
		}

		$to_email = $this->get_emailing_context();
		$success = false;
		
		foreach ( $to_email as $registration_obj ) {
			$type = 'notification';
			$notification_message = new NotificationEmail( new Placeholders( $registration_obj, $this->event, 'email', true ) );
			$notification_message->set_type( $type );
			
			if ( Settings::get( 'email_from_address_custom' ) === 'enabled' ) {
				$notification_message->set_from_address( Settings::get( 'email_from_address' ) );
			}
			
			$notification_message->set_from_name( $this->event->get( $type . '_from_name' ) );
			$notification_message->set_subject( Settings::get( 'cancel_notification_subject' ) );

			// Add all recipients
			foreach ( $recipients as $recipient ) {
				$notification_message->set_recipient( $recipient );
			}
			
			// Set reply-to to the registration email for notifications
			$registration_data = $registration_obj->get_registration_data();
			if ( ! empty( $registration_data['email'] ) ) {
				$name = '';
				$first = isset( $registration_data['first'] ) ? $registration_data['first'] : '';
				$last = isset( $registration_data['last'] ) ? $registration_data['last'] : '';
				if ( ! empty( $first ) || ! empty( $last ) ) {
					$name = trim( $first . ' ' . $last );
				}
				$notification_message->set_reply_to( $registration_data['email'], $name );
			}

			$notification_message->generate_headers( array() );
			$notification_message->set_content( array( 'body' => Settings::get( 'cancel_notification_email_content' ) ) );
			$notification_message->set_message_header( array() );
			$notification_message->set_message_body();
			$notification_message->set_message_footer( array() );

			$success = $notification_message->send();
		}

		return $success;
	}

	/**
	 * Send cancellation confirmation email to registrant
	 */
	private function send_cancel_confirmation_email() {
		$to_email = $this->get_emailing_context();
		$success = false;
		
		foreach ( $to_email as $registration_obj ) {
			$recipient = $registration_obj->get_registration_data();

			if ( empty( $recipient['email'] ) ) {
				return true;
			}
			
			$type = 'confirmation';
			$confirmation_message = new ActionEmail( new Placeholders( $registration_obj, $this->event, 'action' ) );
			$confirmation_message->set_type( $type );
			
			if ( Settings::get( 'email_from_address_custom' ) === 'enabled' ) {
				$confirmation_message->set_from_address( Settings::get( 'email_from_address' ) );
			}
			
			$confirmation_message->set_from_name( $this->event->get( $type . '_from_name' ) );
			$confirmation_message->set_recipient( $recipient['email'] );
			$confirmation_message->set_subject( Settings::get( 'cancel_confirmation_subject' ) );
			
			// Set reply-to to the first notification recipient for confirmations
			$notification_recipients = $this->event->get_notification_recipients();
			if ( ! empty( $notification_recipients[0] ) ) {
				$confirmation_message->set_reply_to( $notification_recipients[0] );
			}

			$confirmation_message->generate_headers( array() );
			$confirmation_message->set_content( array( 'body' => Settings::get( 'cancel_confirmation_email_content' ) ) );
			$confirmation_message->set_message_header( array() );
			$confirmation_message->set_message_body();
			$confirmation_message->set_message_footer( array() );

			$success = $confirmation_message->send();
		}

		return $success;
	}

	/**
	 * Get the response HTML for canceled status
	 * 
	 * @param array $data Additional data
	 * @return string HTML response
	 */
	public function get_response_html( $data = array() ) {
		return Settings::get( 'cancel_success_message' ) ?: __( 'Your registration has been canceled.', 'event-genius' );
	}
} 