<?php

namespace WPEventGenius\Common\Registration\Communicator;

use WPEventGenius\Common\Utils\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

/**
 * Communicator for handling pending registration status
 * Used when registration is submitted but requires additional action (like payment)
 */
class CommunicatorAfterPending extends Communicator {
	
	/**
	 * Execute the communicator actions
	 */
	public function execute() {
		$this->send_emails();
	}

	/**
	 * Send appropriate emails for pending status
	 */
	public function send_emails() {
		if ( $this->event->get( 'send_notification_email' ) === 'enabled' ) {
			$this->send_registration_notification_email();
		}

		// Send pending confirmation email
		if ( $this->event->get( 'send_confirmation_email' ) === 'enabled' ) {
			$this->send_pending_confirmation_email();
		}
	}

	/**
	 * Send pending confirmation email to registrant
	 */
	public function send_pending_confirmation_email() {
		$to_email = $this->get_emailing_context( 'confirmation' );
		$success = false;
		foreach ( $to_email as $registration_obj ) {
			$recipient = $registration_obj->get_registration_data();

			if ( empty( $recipient['email'] ) ) {
				return true;
			}
			
			$type = 'confirmation';
			$confirmation_message = new \WPEventGenius\Common\Email\ConfirmationEmail( new \WPEventGenius\Common\Utils\Placeholders( $registration_obj, $this->event ) );
			$confirmation_message->set_type( $type );
			$confirmation_message->set_from_address( $this->event->get_email_from_address() );
			$confirmation_message->set_from_name( $this->event->get_email_from_name( $type ) );
			$confirmation_message->set_recipient( $recipient['email'] );
			$confirmation_message->set_subject( $this->event->get_email_subject( 'confirmation_pending' ) );
			
			// Set reply-to to the first notification recipient for confirmations
			$notification_recipients = $this->event->get_notification_recipients();
			if (!empty($notification_recipients[0])) {
				$confirmation_message->set_reply_to($notification_recipients[0]);
			}

			$confirmation_message->generate_headers( array() );
			$confirmation_message->set_content( array( 'body' => $this->event->get_email_content( 'confirmation_pending' ) ) );
			$confirmation_message->set_message_header( array() );
			$confirmation_message->set_message_body();
			$confirmation_message->set_message_footer( array() );

			$success = $confirmation_message->send();
		}

		return $success;
	}

	/**
	 * Get the response HTML for pending status
	 * 
	 * @param array $data Additional data
	 * @return string HTML response
	 */
	public function get_response_html( $data = array() ) {
		return $this->event->get_success_message( 'pending' );
	}
} 