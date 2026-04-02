<?php

namespace WPEventGenius\Common\Registration\Communicator;

use WPEventGenius\Common\Utils\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class CommunicatorAfterConfirmed extends Communicator {
	public function execute() {
		$this->send_emails();
	}

	public function send_emails() {
		if ( $this->event->get( 'send_notification_email' ) === 'enabled' ) {
			$this->send_registration_notification_email();
		}

		if ( $this->event->get( 'send_confirmation_email' ) === 'enabled' ) {
			$this->send_registration_confirmation_email();
		}
	}

	public function get_response_html( $data = array() ) {
		// Get the form from the event to access form-specific settings
		$form = $this->event->get_form();
		return $form->get_confirmed_modal_message();
	}
}
