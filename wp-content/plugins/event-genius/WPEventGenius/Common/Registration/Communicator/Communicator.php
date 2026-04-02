<?php

namespace WPEventGenius\Common\Registration\Communicator;


use WPEventGenius\Common\Event\Event;
use WPEventGenius\Common\Registration\Registration\RegistrationGroup;

use WPEventGenius\Common\Utils\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class Communicator {
	protected $registration_group;

	protected $event;

	protected $updating_from_edit;

	protected $notification_sent = false;

	public function __construct( RegistrationGroup $registration_group, Event $event ) {
		$this->registration_group = $registration_group;
		$this->event              = $event;
	}

	/**
	 * Execute the communicator actions
	 * This method can be overridden by Pro classes to add additional functionality
	 */
	public function execute() {
	}

	public function event() {
		return $this->event;
	}

	public function registration_group() {
		return $this->registration_group;
	}

	public function send_registration_notification_email() {
		$to_email = $this->get_emailing_context( 'notification' );
		$success = false;
		foreach ( $to_email as $registration_obj ) {
			$notification_message = $this->prepare_notification_email( $registration_obj );
			$notification_message = $this->filter_notification_email( $notification_message, $registration_obj );

			$success = $notification_message->send();
		}

		$this->notification_sent = true;

		return $success;
	}

	protected function prepare_notification_email( $registration_obj ) {
		$recipients = $this->event->get_notification_recipients();

		if ( empty( $recipients ) ) {
			return true;
		}
		$type = 'notification';
		$factory = new \WPEventGenius\Common\Services\EmailObjectFactory();
		$notification_message = $factory->create_notification_email( $this->create_placeholders( $registration_obj, $this->event, 'email', true ) );
		$notification_message->set_type( $type );
		$notification_message->set_from_address( $this->event->get_email_from_address() );
		$notification_message->set_from_name( $this->event->get_email_from_name( $type ) );
		foreach ( $recipients as $recipient ) {
			$notification_message->set_recipient( $recipient );
		}
		
		// Get the base subject and apply filters
		$base_subject = $this->event->get_email_subject( $type );
		$filtered_subject = $this->filter_subject( $base_subject, $type, array( 'recipients' => $recipients ) );
		$notification_message->set_subject( $filtered_subject );
					
		// Set reply-to to the registration email for notifications
		$registration_data = $registration_obj->get_registration_data();
		
		if (!empty($registration_data['email'])) {
			$name = '';
			$first = isset( $registration_data['first'] ) ? $registration_data['first'] : '';
			$last = isset( $registration_data['last'] ) ? $registration_data['last'] : '';
			if (!empty($first) || !empty($last)) {
				$name = trim($first . ' ' . $last);
			}
			$notification_message->set_reply_to($registration_data['email'], $name);
		}

		$notification_message->generate_headers( array() );
		$notification_message->set_content( array( 'body' => $this->event->get_email_content( $type ) ) );
		$notification_message->set_message_header( array() );
		$notification_message->set_message_body();
		$notification_message->set_message_footer( array() );

		return $notification_message;
	}

	protected function filter_notification_email( $notification_message, $registration_obj = null ) {
		return $notification_message;
	}

	public function send_registration_confirmation_email() {
		$to_email = $this->get_emailing_context( 'confirmation' );
		$success = false;
		foreach ( $to_email as $registration_obj ) {
			$recipient = $registration_obj->get_registration_data();

			if ( empty( $recipient['email'] ) ) {
				return true;
			}
			$type = 'confirmation';
			$factory = new \WPEventGenius\Common\Services\EmailObjectFactory();
			$confirmation_message = $factory->create_confirmation_email( $this->create_placeholders( $registration_obj, $this->event ) );
			$confirmation_message->set_type( $type );
			$confirmation_message->set_from_address( $this->event->get_email_from_address() );
			$confirmation_message->set_from_name( $this->event->get_email_from_name( $type ) );
			$confirmation_message->set_recipient( $recipient['email'] );
			
			// Get the base subject and apply filters
			$base_subject = $this->event->get_email_subject( $type );
			$filtered_subject = $this->filter_subject( $base_subject, $type, array( 'recipient' => $recipient ) );
			$confirmation_message->set_subject( $filtered_subject );
						
			// Set reply-to to the first notification recipient for confirmations
			$notification_recipients = $this->event->get_notification_recipients();
			if (!empty($notification_recipients[0])) {
				$confirmation_message->set_reply_to($notification_recipients[0]);
			}

			$confirmation_message->generate_headers( array() );
			$confirmation_message->set_content( array( 'body' => $this->event->get_email_content( $type ) ) );
			$confirmation_message->set_message_header( array() );
			$confirmation_message->set_message_body();
			$confirmation_message->set_message_footer( array() );

			$success = $confirmation_message->send();
		}

		return $success;
	}

	public function get_response_html( $data = array() ) {
		return 'Success!';
	}

	/**
	 * Filter the email subject for specific scenarios
	 * Can be overridden by child classes to modify subjects
	 * 
	 * @param string $subject The original subject
	 * @param string $type The email type (notification, confirmation, etc.)
	 * @param array $context Additional context data
	 * @return string The filtered subject
	 */
	public function filter_subject( $subject, $type, $context = array() ) {
		// Default implementation - no filtering
		// Child classes can override this to add prefixes, suffixes, etc.
		return $subject;
	}

	protected function disable_notification() {
		return false;
	}


	public function get_emailing_context( $email_type = 'confirmation' ) {
		$to_email = array( $this->registration_group->get_main() );

		// Check if we should include additional guests based on email type and form settings
		$form = $this->event->get_form();
		if ( $form ) {
			$include_guests = false;
			
			if ( $email_type === 'confirmation' ) {
				$include_guests = ( $form->get_confirmation_email_recipients() === 'all_guests' );
			} elseif ( $email_type === 'notification' ) {
				$include_guests = ( $form->get_notification_email_recipients() === 'all_guests' );
			} else {
				// For other email types, use the legacy setting
				$include_guests = $form->should_send_additional_guest_emails();
			}
			
			if ( $include_guests ) {
				// Add additional guest registrations to the email context
				$guest_registrations = $this->registration_group->get_additional_guest_registrations();
				foreach ( $guest_registrations as $guest_registration ) {
					$to_email[] = $guest_registration;
				}
			}
		}

		return $to_email;
	}

	protected function get_context() {
		return 'generic';
	}

	/**
	 * Create placeholders object using factory
	 * 
	 * @param Registration $registration
	 * @param Event $event
	 * @param string $context
	 * @param bool $show_admin_placeholders
	 * @return \WPEventGenius\Common\Utils\Placeholders
	 */
	protected function create_placeholders( $registration, $event, $context = 'email', $show_admin_placeholders = false ) {
		$factory = new \WPEventGenius\Common\Services\RegistrationObjectFactory();
		return $factory->create_placeholders( $registration, $event, $context, $show_admin_placeholders );
	}

	/**
	 * Determine whether the response HTML should be escaped
	 * Override this method in child classes to control escaping behavior
	 * 
	 * @return bool Whether to apply wp_kses_post and nl2br to the response HTML
	 */
	public function should_escape() {
		return true; // Default behavior - escape by default
	}

	/**
	 * Optionally send notification email when submission has file attachments.
	 *
	 * @return bool False (no email sent)
	 */
	public function maybe_send_attachment_driven_notification() {
		return false;
	}

}
