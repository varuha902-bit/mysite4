<?php

namespace WPEventGenius\Common\Registration\Communicator;

use WPEventGenius\Common\Email\ActionEmail;
use WPEventGenius\Common\Event\Event;
use WPEventGenius\Common\Registration\Registration\Registration;
use WPEventGenius\Common\Registration\Registration\RegistrationGroup;

use WPEventGenius\Common\Utils\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class CommunicatorAfterCancelRequest extends Communicator {

	protected $registration;

	public function __construct( RegistrationGroup $registration_group, Event $event, Registration $registration ) {
		parent::__construct( $registration_group, $event );
		$this->registration = $registration;
	}

	public function execute() {
		$this->send_emails();
	}

	public function send_emails() {
		$this->send_cancel_request_email();
	}

	public function send_cancel_request_email() {
		$to_email = array( $this->registration );
		$success = false;
		foreach ( $to_email as $registration_obj ) {
			$recipient = $registration_obj->get_registration_data();

			if ( empty( $recipient['email'] ) ) {
				return true;
			}
			$type = 'confirmation';
			$confirmation_message = new ActionEmail( $this->create_placeholders( $registration_obj, $this->event, 'action' ) );
			$confirmation_message->set_type( $type );
			$confirmation_message->set_from_address( $this->event->get_email_from_address() );
			$confirmation_message->set_from_name( $this->event->get_email_from_name( 'cancel_request' ) );
			$confirmation_message->set_recipient( $recipient['email'] );
			$confirmation_message->set_subject( $this->event->get_email_subject( 'cancel_request' ) );
			$confirmation_message->generate_headers( array() );
			$confirmation_message->set_content( array( 'body' => $this->event->get_email_content( 'cancel_request' ) ) );
			$confirmation_message->set_message_header( array() );
			$confirmation_message->set_message_body();

			$confirmation_message->set_message_footer( array() );

			$success = $confirmation_message->send();
		}

		return $success;
	}
}
