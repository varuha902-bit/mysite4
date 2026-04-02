<?php

namespace WPEventGenius\Common\Services;

use WPEventGenius\Common\Event\Event;
use WPEventGenius\Common\Event\EventPost;
use WPEventGenius\Common\Event\RegistrationCounter;
use WPEventGenius\Common\Database;
use WPEventGenius\Common\Registration\Field\FieldHandler;
use WPEventGenius\Common\Registration\Form\Form;
use WPEventGenius\Common\Registration\Payment\Cart\Cart;
use WPEventGenius\Common\Registration\Payment\PaymentHandler;
use WPEventGenius\Common\Registration\Registrar\RegistrarAfterRegisters;
use WPEventGenius\Common\Registration\Registration\RegistrationGroup;
use WPEventGenius\Common\Registration\Submission\NewMainSubmission;
use WPEventGenius\Common\Registration\EventGoer\LoggedInEventGoer;
use WPEventGenius\Common\Registration\EventGoer\VisitorEventGoer;
use WPEventGenius\Common\Calendars\AttendeeList;

use WPEventGenius\Common\Registration\Submission\Validator;
use WPEventGenius\Common\Registration\Communicator\CommunicatorAfterCancel;
use WPEventGenius\Common\Registration\Communicator\CommunicatorAfterConfirmed;
use WPEventGenius\Common\Registration\Communicator\CommunicatorAfterError;
use WPEventGenius\Common\Registration\Communicator\CommunicatorAfterPending;
use WPEventGenius\Common\Registration\Communicator\CommunicatorAfterPendingApproval;


if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

/**
 * Factory class for creating registration-related objects
 * Handles creation of free vs pro versions of objects
 */
class RegistrationObjectFactory {

	/**
	 * Check if pro version is active
	 * 
	 * @return bool
	 */
	private function is_pro() {
		return ! evge_is_free_version();
	}

	/**
	 * Check if standard version is active
	 * 
	 * @return bool
	 */
	private function is_standard() {
		return function_exists( 'evge_is_standard_tier' ) && evge_is_standard_tier();
	}

	/**
	 * Create an Event object
	 * 
	 * @param int $event_id
	 * @return Event
	 */
	public function create_event( $event_id ) {
		if ( $this->is_pro() && class_exists( 'WPEventGenius\Pro\Event\EventPro' ) ) {
			return new \WPEventGenius\Pro\Event\EventPro( $event_id );
		}
		return new Event( $event_id );
	}

	/**
	 * Create an EventPost object
	 * 
	 * @param int|array $event_id Single event ID or array of event IDs for bulk registration
	 * @return EventPost|BulkEventPost Returns BulkEventPost for multiple events (Premium tier), otherwise EventPost
	 */
	public function create_event_post( $event_id ) {
		// Normalize to array to check if bulk registration
		$event_ids = is_array( $event_id ) ? $event_id : array( $event_id );
		$is_bulk = count( $event_ids ) > 1;

		// Check for Premium tier bulk registration
		if ( $is_bulk && function_exists( 'evge_is_premium_tier' ) && evge_is_premium_tier() ) {
			if ( class_exists( 'WPEventGenius\Premium\Event\BulkEventPost' ) ) {
				return new \WPEventGenius\Premium\Event\BulkEventPost( $event_ids );
			}
		}

		// Single event or Premium tier not available - use standard EventPost
		$single_event_id = is_array( $event_id ) ? $event_id[0] : $event_id;
		
		if ( $this->is_pro() && class_exists( 'WPEventGenius\Pro\Event\EventPostPro' ) ) {
			return new \WPEventGenius\Pro\Event\EventPostPro( $single_event_id );
		}
		return new EventPost( $single_event_id );
	}

	/**
	 * Create a RegistrationCounter object
	 * 
	 * @param int $event_id The event ID to get counts for
	 * @param Database $database Database instance
	 * @return RegistrationCounter|RegistrationCounterPro
	 */
	public function create_registration_counter( $event_id, Database $database ) {
		if ( $this->is_pro() && class_exists( 'WPEventGenius\Pro\Event\RegistrationCounterPro' ) ) {
			return new \WPEventGenius\Pro\Event\RegistrationCounterPro( $event_id, $database );
		}
		return new RegistrationCounter( $event_id, $database );
	}

	/**
	 * Create a Form object
	 * 
	 * @param int $form_id
	 * @return Form
	 */
	public function create_form( $form_id ) {
		$field_handler = $this->create_field_handler();
		
		// Use Pro form if Pro is active
		if ( $this->is_pro() && class_exists( 'WPEventGenius\Pro\Registration\Form\FormPro' ) ) {
			return new \WPEventGenius\Pro\Registration\Form\FormPro( $form_id, $field_handler );
		}
		
		// Use StandardFormPro if Standard tier is active
		if ( $this->is_standard() && class_exists( 'WPEventGenius\Standard\Registration\Form\StandardFormPro' ) ) {
			return new \WPEventGenius\Standard\Registration\Form\StandardFormPro( $form_id, $field_handler );
		}
		
		// Fall back to base Form class
		return new Form( $form_id, $field_handler );
	}

	/**
	 * Create a NewMainSubmission object
	 * 
	 * @param Validator $validator
	 * @param Form $event_form
	 * @return NewMainSubmission
	 */
	public function create_main_submission( $validator, $event_form ) {
		if ( $this->is_pro() && class_exists( 'WPEventGenius\Pro\Registration\Submission\ProMainSubmission' ) ) {
			return new \WPEventGenius\Pro\Registration\Submission\ProMainSubmission( $validator, $event_form );
		}
		return new NewMainSubmission( $validator, $event_form );
	}

	/**
	 * Create a ProMainEditSubmission object
	 * 
	 * @param Validator $validator
	 * @param Form $event_form
	 * @param int $registration_id
	 * @param array $edit_criteria
	 * @return \WPEventGenius\Pro\Registration\Submission\ProMainEditSubmission
	 * @throws \Exception If Pro is not available or ProMainEditSubmission class not found
	 */
	public function create_edit_submission( $validator, $event_form, $registration_id = 0, $edit_criteria = array() ) {
		if ( $this->is_pro() && class_exists( 'WPEventGenius\Pro\Registration\Submission\ProMainEditSubmission' ) ) {
			return new \WPEventGenius\Pro\Registration\Submission\ProMainEditSubmission( $validator, $event_form, $registration_id, $edit_criteria );
		}
		
		// Edit submissions are Pro-only feature
		throw new \Exception( 'Edit submissions require Event Genius Pro. Please upgrade to use this feature.' );
	}

	/**
	 * Create a RegistrarAfterRegisters object
	 * 
	 * @param RegistrationGroup $registration_group
	 * @param Event $event
	 * @return RegistrarAfterRegisters
	 */
	public function create_registrar( $registration_group, $event ) {
		if ( $this->is_pro() && class_exists( 'WPEventGenius\Pro\Registration\Registrar\ProRegistrarAfterRegisters' ) ) {
			return new \WPEventGenius\Pro\Registration\Registrar\ProRegistrarAfterRegisters( $registration_group, $event );
		}
		return new RegistrarAfterRegisters( $registration_group, $event );
	}

	/**
	 * Create a PaymentHandler object
	 * 
	 * @param Cart $cart
	 * @return PaymentHandler
	 */
	public function create_payment_handler( $cart ) {
		if ( $this->is_pro() && class_exists( 'WPEventGenius\Pro\Registration\Payment\ProPaymentHandler' ) ) {
			return new \WPEventGenius\Pro\Registration\Payment\ProPaymentHandler( $cart );
		}
		return new PaymentHandler( $cart );
	}

	/**
	 * Create a Cart object
	 * 
	 * @return Cart
	 */
	public function create_cart() {
		if ( $this->is_pro() && class_exists( 'WPEventGenius\Pro\Registration\Payment\Cart\ProCart' ) ) {
			return new \WPEventGenius\Pro\Registration\Payment\Cart\ProCart();
		}
		return new Cart();
	}

	/**
	 * Create a RegistrationGroup object
	 * 
	 * @param \WPEventGenius\Common\Database $database
	 * @return RegistrationGroup
	 */
	public function create_registration_group( $database ) {
		if ( $this->is_pro() && class_exists( 'WPEventGenius\Pro\Registration\Registration\ProRegistrationGroup' ) ) {
			return new \WPEventGenius\Pro\Registration\Registration\ProRegistrationGroup( $database );
		}
		return new RegistrationGroup( $database );
	}

	/**
	 * Create a communicator object based on type
	 * 
	 * @param string $type The type of communicator ('confirmed', 'error', 'pending', etc.)
	 * @param RegistrationGroup $registration_group
	 * @param Event $event
	 * @param array $additional_args Additional arguments for the communicator
	 * @return object The appropriate communicator instance
	 */
	public function create_communicator( $type, $registration_group, $event, $additional_args = array() ) {
		$type = strtolower( $type );
		
		$attachments = isset( $additional_args['attachments'] ) ? $additional_args['attachments'] : array();
		switch ( $type ) {
			case 'confirmed':
				if ( $this->is_pro() && class_exists( 'WPEventGenius\Pro\Registration\Communicator\ProCommunicatorAfterConfirmed' ) ) {
					$communicator = new \WPEventGenius\Pro\Registration\Communicator\ProCommunicatorAfterConfirmed( $registration_group, $event );
					if (!empty($attachments)) {
						$communicator->set_attachments( $attachments );
					}
					return $communicator;
				}
				return new CommunicatorAfterConfirmed( $registration_group, $event );
				
			case 'error':
				if ( $this->is_pro() && class_exists( 'WPEventGenius\Pro\Registration\Communicator\ProCommunicatorAfterError' ) ) {
					$communicator = new \WPEventGenius\Pro\Registration\Communicator\ProCommunicatorAfterError( $registration_group, $event );
					if (!empty($attachments)) {
						$communicator->set_attachments( $attachments );
					}
					return $communicator;
				}
				return new CommunicatorAfterError( $registration_group, $event );
				
			case 'pending_approval':
				return new CommunicatorAfterPendingApproval( $registration_group, $event );

			case 'pending':
				if ( $this->is_pro() && class_exists( 'WPEventGenius\Pro\Registration\Communicator\ProCommunicatorAfterPending' ) ) {
					$should_trigger_payment = isset( $additional_args['should_trigger_payment'] ) ? $additional_args['should_trigger_payment'] : false;
					$communicator = new \WPEventGenius\Pro\Registration\Communicator\ProCommunicatorAfterPending( $registration_group, $event, $should_trigger_payment );
					if (!empty($attachments)) {
						$communicator->set_attachments( $attachments );
					}
					return $communicator;
				}
				return new CommunicatorAfterPending( $registration_group, $event );
				
			case 'canceled':
				if ( $this->is_pro() && class_exists( 'WPEventGenius\Pro\Registration\Communicator\ProCommunicatorAfterCancel' ) ) {
					return new \WPEventGenius\Pro\Registration\Communicator\ProCommunicatorAfterCancel( $registration_group, $event );
				}
				return new CommunicatorAfterCancel( $registration_group, $event );
				
			case 'checkout':
				if ( $this->is_pro() && class_exists( 'WPEventGenius\Pro\Registration\Communicator\ProCommunicatorCheckout' ) ) {
					$communicator = new \WPEventGenius\Pro\Registration\Communicator\ProCommunicatorCheckout( $registration_group, $event );
					if (!empty($attachments)) {
						$communicator->set_attachments( $attachments );
					}
					return $communicator;
				}
				// Fallback to pending communicator for free version
				return new CommunicatorAfterPending( $registration_group, $event );
				
			case 'edit':
				if ( $this->is_pro() && class_exists( 'WPEventGenius\Pro\Registration\Communicator\ProCommunicatorAfterEdit' ) ) {
					$communicator = new \WPEventGenius\Pro\Registration\Communicator\ProCommunicatorAfterEdit( $registration_group, $event );
					if (!empty($attachments)) {
						$communicator->set_attachments( $attachments );
					}
					return $communicator;
				}
				return new \WPEventGenius\Common\Registration\Communicator\CommunicatorAfterEdit( $registration_group, $event );
				
			default:
				// Allow for custom communicator types in pro version
				if ( $this->is_pro() ) {
					$pro_class_name = 'WPEventGenius\Pro\Registration\Communicator\ProCommunicator' . ucfirst( $type );
					if ( class_exists( $pro_class_name ) ) {
						return new $pro_class_name( $registration_group, $event );
					}
				}
				
				// Fallback to error communicator for unknown types
				return new CommunicatorAfterError( $registration_group, $event );
		}
	}

	/**
	 * Create a FieldHandler object
	 * 
	 * @return FieldHandler
	 */
	public function create_field_handler() {
		// Check for Standard tier first (highest priority)
		if ( $this->is_standard() && class_exists( 'WPEventGenius\Standard\Registration\Field\FieldHandlerProStandard' ) ) {
			return new \WPEventGenius\Standard\Registration\Field\FieldHandlerProStandard();
		}
		
		// Then check for Pro tier
		if ( $this->is_pro() && class_exists( 'WPEventGenius\Pro\Registration\Field\FieldHandlerPro' ) ) {
			return new \WPEventGenius\Pro\Registration\Field\FieldHandlerPro();
		}
		
		// Fallback to base FieldHandler
		return new FieldHandler();
	}

	/**
	 * Create a SubmissionGroup object
	 * 
	 * @param Submission $main_submission
	 * @return \WPEventGenius\Common\Registration\Submission\SubmissionGroup
	 */
	public function create_submission_group( $main_submission, $additional_guest_submissions = array() ) {
		if ( $this->is_pro() && class_exists( 'WPEventGenius\Pro\Registration\Submission\ProSubmissionGroup' ) ) {
			return new \WPEventGenius\Pro\Registration\Submission\ProSubmissionGroup( $main_submission, $additional_guest_submissions );
		}
		return new \WPEventGenius\Common\Registration\Submission\SubmissionGroup( $main_submission );
	}

	/**
	 * Create a Placeholders object
	 * 
	 * @param Registration $registration
	 * @param Event $event
	 * @param string $context
	 * @param bool $show_admin_placeholders
	 * @return \WPEventGenius\Common\Utils\Placeholders
	 */
	public function create_placeholders( $registration, $event, $context = 'email', $show_admin_placeholders = false ) {
		if ( $this->is_pro() && class_exists( 'WPEventGenius\Pro\Utils\ProPlaceholders' ) ) {
			return new \WPEventGenius\Pro\Utils\ProPlaceholders( $registration, $event, $context, $show_admin_placeholders );
		}
		return new \WPEventGenius\Common\Utils\Placeholders( $registration, $event, $context, $show_admin_placeholders );
	}

	/**
	 * Create a LoggedInEventGoer object
	 * 
	 * @param int $user_id The user ID
	 * @param Database $database Database instance
	 * @return LoggedInEventGoer|LoggedInEventGoerPro
	 */
	public function create_logged_in_event_goer( $user_id, Database $database ) {
		if ( $this->is_pro() && class_exists( 'WPEventGenius\Pro\Registration\EventGoer\LoggedInEventGoerPro' ) ) {
			return new \WPEventGenius\Pro\Registration\EventGoer\LoggedInEventGoerPro( $user_id, $database );
		}
		return new LoggedInEventGoer( $user_id, $database );
	}

	/**
	 * Create a VisitorEventGoer object
	 * 
	 * @param int $user_id The user ID (0 for visitors)
	 * @param Database $database Database instance
	 * @return VisitorEventGoer|VisitorEventGoerPro
	 */
	public function create_visitor_event_goer( $user_id, Database $database ) {
		if ( $this->is_pro() && class_exists( 'WPEventGenius\Pro\Registration\EventGoer\VisitorEventGoerPro' ) ) {
			return new \WPEventGenius\Pro\Registration\EventGoer\VisitorEventGoerPro( $user_id, $database );
		}
		return new VisitorEventGoer( $user_id, $database );
	}

	/**
	 * Create an AttendeeList object
	 * 
	 * @param Database $database Database instance
	 * @param Event $event Event object
	 * @param array $args Arguments for the attendee list
	 * @return AttendeeList|AttendeeListPro
	 */
	public function create_attendee_list( Database $database, \WPEventGenius\Common\Event\Event $event, $args = array() ) {
		if ( $this->is_pro() && class_exists( 'WPEventGenius\Pro\Calendars\AttendeeListPro' ) ) {
			return new \WPEventGenius\Pro\Calendars\AttendeeListPro( $database, $event, $args );
		}
		return new AttendeeList( $database, $event, $args );
	}

	/**
	 * Create a FormSettingsService object
	 * 
	 * @return \WPEventGenius\Standard\Services\FormSettingsService|false
	 */
	public function create_form_settings_service() {
		if ( $this->is_standard() && class_exists( 'WPEventGenius\Standard\Services\FormSettingsService' ) ) {
			return new \WPEventGenius\Standard\Services\FormSettingsService();
		}
		
		// Return false if not in the right tier
		return false;
	}
} 