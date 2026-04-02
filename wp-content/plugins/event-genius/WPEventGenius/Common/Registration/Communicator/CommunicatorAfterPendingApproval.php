<?php

namespace WPEventGenius\Common\Registration\Communicator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Communicator for manual-review-only flow.
 * Registration is pending until organizer approves. No confirmation email on submit;
 * only notification email to organizer (if enabled). Response shows pending-approval message.
 */
class CommunicatorAfterPendingApproval extends Communicator {

	/**
	 * Execute the communicator actions
	 */
	public function execute() {
		$this->send_emails();
	}

	/**
	 * Send only notification email to organizer (if enabled). No confirmation email to registrant.
	 */
	public function send_emails() {
		if ( $this->event->get( 'send_notification_email' ) === 'enabled' ) {
			$this->send_registration_notification_email();
		}
	}

	/**
	 * Get the response HTML for pending-approval status
	 *
	 * @param array $data Additional data
	 * @return string HTML response
	 */
	public function get_response_html( $data = array() ) {
		return $this->event->get_success_message( 'pending_approval' );
	}
}
