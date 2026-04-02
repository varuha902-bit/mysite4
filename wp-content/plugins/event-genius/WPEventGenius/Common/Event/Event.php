<?php
namespace WPEventGenius\Common\Event;


use WPEventGenius\Common\Registration\Field\FieldHandler;
use WPEventGenius\Common\Registration\Form\Form;
use WPEventGenius\Common\Services\RegistrationObjectFactory;
use WPEventGenius\Common\Utils\DateFormatter;
use WPEventGenius\Common\Utils\Settings;
use WPEventGenius\Common\Event\VenuePost;
use WPEventGenius\Common\Event\EventPost;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class Event {

	protected $post_id;

	protected $form;
	protected $post_meta;


	protected $event_meta;

	public function __construct( $post_id ) {
		$this->post_id = $post_id;
		$this->post_meta = get_post_meta( $post_id );
	}

	public function get_post_id() {
		return $this->post_id;
	}
	public function get_confirmation_condition() {
		$from_form = $this->get_form()->get_field_setting( 'confirmation_condition' );

		if ( in_array( $from_form, array( 'payment_complete', 'manual_only' ), true ) ) {
			return $from_form;
		}
		return 'payment_complete';
	}

	public function get_form() {
		if ( ! isset( $this->form ) ) {
			$factory = new RegistrationObjectFactory();
			$form_id = ! empty( $this->post_meta['evge_registration_form'] ) ? $this->post_meta['evge_registration_form'][0] : 1;
			$this->form = $factory->create_form( $form_id );
			$this->form->set_fields();
		}
		return $this->form;
	}

	public function has_cost() {
		// Check if there's an actual positive cost associated with the event
		$cost_amount = $this->get_registration_cost();
		
		// Return true only if there's a valid cost amount greater than 0
		return ! empty( $cost_amount ) && is_numeric( $cost_amount ) && (float) $cost_amount > 0;
	}

	public function get_meta() {
		return array();
	}

	public function get( $key ) {
		$event = new EventPost( $this->post_id );
		$venue = new VenuePost( $event->get_the_venue_id() );
		switch ( $key ) {
			case 'title' :
				return get_the_title( $this->post_id );
			case 'venue' :
				return $venue->get_the_title();
			case 'venue-address' :
				return $venue->get_the_street_address();
			case 'venue-city' :
				return $venue->get_the_city();
			case 'venue-state' :
				return $venue->get_the_state();
			case 'venue-zip' :
				return $venue->get_the_postal_code();
			case 'venue-country' :
				return $venue->get_the_country();
			case 'venue-phone' :
				return $venue->get_the_phone();
			case 'venue-website' :
				return $venue->get_the_website();
			case 'venue-map' :
				return $venue->get_the_map_url();
			case 'send_notification_email' :
				return Settings::get( 'send_notification_email' );
			case 'notification_recipients' :
				return $this->get_notification_recipients();
			case 'notification_from_address' :
				return $this->get_notification_from_address();
			case 'notification_from_name' :
				return $this->get_notification_from_name();
			case 'notification_email_content' :
				return $this->get_notification_content();
			case 'send_confirmation_email' :
				return Settings::get( 'send_confirmation_email' );
			case 'confirmation_from_address' :
				return $this->get_confirmation_from_address();
			case 'confirmation_from_name' :
				return $this->get_confirmation_from_name();
			case 'confirmation_email_content' :
				return $this->get_confirmation_email_content();
			case 'offline_content' :
				return $this->get_offline_content();
			case 'event_link' :
				return get_the_permalink( $this->post_id );
		}
		return '';
	}

	public function get_start_date( $context = 'none' ) {
		if ( ! empty( $this->post_meta['evge_start_date'] ) ) {
			return DateFormatter::date_format( $this->post_meta['evge_start_date'][0], $context );
		}
		return '';

	}
	public function get_start_time( $context = 'none' ) {
		if ( ! empty( $this->post_meta['evge_start_time'] ) ) {
			return DateFormatter::time_format( $this->post_meta['evge_start_time'][0], $context );
		}

		return '';
	}

	public function get_registration_cost( $type = 'event' ) {
		// Check if the cost amount exists and is valid
		if ( ! empty( $this->post_meta['evge_cost_amount'] ) && isset( $this->post_meta['evge_cost_amount'][0] ) ) {
			$cost_amount = $this->post_meta['evge_cost_amount'][0];
			
			// Return the cost amount if it's numeric and greater than or equal to 0
			if ( is_numeric( $cost_amount ) && (float) $cost_amount >= 0 ) {
				return (float) $cost_amount;
			}
		}
		
		// Return 0 if no valid cost amount is found
		return 0;
	}

	public function get_currency_code() {
		return 'USD';
	}

	public function get_accept_payments() {
		// Get the payment status from event meta or settings
		$payment_status = ! empty( $this->post_meta['evge_payment_status'][0] ) ? $this->post_meta['evge_payment_status'][0] : Settings::get( 'accept_payments' );

		// Normalize the payment status - handle both string and boolean values
		$is_enabled = false;
		if ( is_string( $payment_status ) ) {
			$is_enabled = ( $payment_status === 'enabled' );
		} elseif ( is_bool( $payment_status ) ) {
			$is_enabled = $payment_status;
		} else {
			// For legacy compatibility, treat truthy values as enabled
			$is_enabled = (bool) $payment_status;
		}

		// If payments are enabled, check if any active gateways exist
		if ( $is_enabled ) {
			$active_gateways = EVGE()->active_gateway_services();

			if ( empty( $active_gateways ) ) {
				return false; // No active gateways, so disable payments
			}
		}
		
		return $is_enabled;
	}

	public function get_notification_recipients() {
		// Get form-specific setting first, then fall back to global setting
		$form = $this->get_form();
		$recipients = $form ? $form->get_field_setting( 'notification_recipients' ) : Settings::get( 'notification_recipients' );
		
		// If still empty, fall back to admin email
		if ( empty( $recipients ) ) {
			return array( get_option( 'admin_email' ) );
		}
		
		// Handle array format (from form settings) or string format (from global settings)
		if ( is_array( $recipients ) ) {
			// If it's already an array, join it back to a string for placeholder processing
			$recipients = implode( ', ', $recipients );
		}
		
		// Process placeholders in the recipients string
		$recipients = $this->process_recipient_placeholders( $recipients );
		
		// split the recipients by comma and trim any whitespace
		$recipients = array_map( 'trim', explode( ',', $recipients ) );
		
		// Remove empty values and validate emails
		$recipients = array_filter( $recipients, function( $email ) {
			return ! empty( $email ) && is_email( $email );
		} );
		
		// If no valid recipients remain, fall back to admin email
		if ( empty( $recipients ) ) {
			return array( get_option( 'admin_email' ) );
		}
		
		return array_values( $recipients );
	}
	
	/**
	 * Process placeholders in recipient email addresses
	 * 
	 * @param string $recipients_string The recipients string with potential placeholders
	 * @return string The processed recipients string with placeholders replaced
	 */
	protected function process_recipient_placeholders( $recipients_string ) {
		// Replace {organizer-email} placeholder with comma-separated organizer emails
		if ( strpos( $recipients_string, '{organizer-email}' ) !== false ) {
			$organizer_emails = $this->get_organizer_emails();
			$organizer_emails_string = ! empty( $organizer_emails ) ? implode( ', ', $organizer_emails ) : '';
			
			// Replace the placeholder
			$recipients_string = str_replace( '{organizer-email}', $organizer_emails_string, $recipients_string );
		}
		
		return $recipients_string;
	}
	
	/**
	 * Get all organizer email addresses for this event
	 * 
	 * @return array Array of organizer email addresses
	 */
	protected function get_organizer_emails() {
		$event_post = new EventPost( $this->post_id );
		$organizer_ids = $event_post->get_organizer_ids();
		
		if ( empty( $organizer_ids ) ) {
			return array();
		}
		
		$organizer_emails = array();
		foreach ( $organizer_ids as $organizer_id ) {
			$organizer_post = new \WPEventGenius\Common\Event\OrganizerPost( $organizer_id );
			$email = $organizer_post->get_the_email();
			
			// Only add valid, non-empty emails
			if ( ! empty( $email ) && is_email( $email ) ) {
				$organizer_emails[] = $email;
			}
		}
		
		// Remove duplicates
		return array_unique( $organizer_emails );
	}

	public function get_notification_from_address() {
		return Settings::get( 'email_from_address' );
	}

	public function get_notification_from_name() {
		return Settings::get( 'notification_from_name' );
	}

	public function get_notification_content() {
		return Settings::get( 'notification_email_content' );
	}

	public function get_confirmation_from_address() {
		return Settings::get( 'email_from_address' );
	}

	public function get_confirmation_from_name() {
		return Settings::get( 'confirmation_from_name' );
	}

	public function get_confirmation_email_content() {
		return Settings::get( 'confirmation_email_content' );
	}

	public function get_offline_content() {
		return Settings::get( 'offline_content' );
	}

	/**
	 * Get email subject for a specific email type
	 * 
	 * @param string $type Email type (confirmation, notification, etc.)
	 * @return string Email subject
	 */
	public function get_email_subject( $type ) {
		$form = $this->get_form();
		return $form ? $form->get_email_subject( $type ) : '';
	}

	/**
	 * Get email content for a specific email type
	 * 
	 * @param string $type Email type (confirmation, notification, etc.)
	 * @return string Email content
	 */
	public function get_email_content( $type ) {
		$form = $this->get_form();
		return $form ? $form->get_email_content( $type ) : '';
	}

	/**
	 * Get from name for a specific email type
	 * 
	 * @param string $type Email type (confirmation, notification, etc.)
	 * @return string From name
	 */
	public function get_email_from_name( $type ) {
		$form = $this->get_form();
		return $form ? $form->get_email_from_name( $type ) : $this->get_notification_from_name();
	}

	/**
	 * Get from email address
	 * 
	 * @return string From email address
	 */
	public function get_email_from_address() {
		$form = $this->get_form();
		return $form ? $form->get_email_from_address() : get_option( 'admin_email' );
	}

	/**
	 * Get success message for a specific action
	 * 
	 * @param string $action Action type (cancel, pending, etc.)
	 * @return string Success message
	 */
	public function get_success_message( $action ) {
		$form = $this->get_form();
		return $form ? $form->get_success_message( $action ) : __( 'Success!', 'event-genius' );
	}

	/**
	 * Check if a specific email type should be sent
	 * 
	 * @param string $type Email type (confirmation, notification, etc.)
	 * @return bool Whether to send the email
	 */
	public function should_send_email( $type ) {
		$form = $this->get_form();
		return $form ? $form->should_send_email( $type ) : true;
	}

}