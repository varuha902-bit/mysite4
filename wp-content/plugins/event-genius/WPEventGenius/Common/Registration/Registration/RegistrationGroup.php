<?php
/**
 * Object used to storing connected registration records
 *
 * @since 2.21
 */
namespace WPEventGenius\Common\Registration\Registration;

use WPEventGenius\Common\Database;
use WPEventGenius\Common\Event\Event;
use WPEventGenius\Common\Registration\Form\Form;
use WPEventGenius\Common\Registration\Payment\Cart\Cart;
use WPEventGenius\Common\Registration\Payment\Cart\LineItem;
use WPEventGenius\Common\Registration\Payment\Gateways\MiscItem;
use WPEventGenius\Common\Registration\Payment\PaymentHandler;
use WPEventGenius\Common\Registration\Payment\Record;
use WPEventGenius\Common\Registration\Submission\SubmissionGroup;
use WPEventGenius\Common\Utils\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class RegistrationGroup {

	/**
	 * @var Database
	 *
	 * @since 2.21
	 */
	protected $db;

	/**
	 * @var Registration
	 *
	 * @since 2.21
	 */
	protected $main_registration;

	/**
	 * @var array Array of additional guest registrations
	 *
	 * @since 2.21
	 */
	protected $additional_guest_registrations = array();

	/**
	 * @var PaymentHandler
	 */
	protected $payment_handler;

	protected $payment_records;

	/**
	 * @param Database $database
	 *
	 * @since 2.21
	 */
	public function __construct( Database $database ) {
		$this->db = $database;
	}

	public function build_payment( Payment $payment ) {
		$this->payment = $payment;
		$event_quantity = $this->calculate_total_guest_count();
		$event = new Event( $this->get_main()->get_registration_data( 'event_id' ) );
		$event_cost = $event->get_registration_cost( 'event' );

		$line_items = array(
			'event' => array(
				'label' => $event->get( 'title' ),
				'quantity' => $event_quantity,
				'cost' => $event_cost
			)
		);

		$line_items = apply_filters( 'evge_payment_line_items', $line_items, $this );

		$fee_setting = Settings::get( 'fees' );
		$line_item_cost = 0;
		foreach ( $line_items as $key => $line_item ) {
			$line_item_cost += $line_item['cost'] * $line_item['quantity'];
		}
		$fee_cost = (float) $line_item_cost * ((float) $fee_setting['percent_cost'] / 100) + (float) $fee_setting['flat_cost'];
		$misc_items = array(
			'fee' => array(
				'label' => $fee_setting['label'],
				'quantity' => 1,
				'cost' => round( $fee_cost, 2 )
			)
		);

		$misc_items = apply_filters( 'evge_payment_misc_items', $misc_items, $line_items, $this );

		foreach ( $line_items as $key => $line_item ) {
			$this->payment->add_line_item( $key, new LineItem( $line_item['label'], $line_item['quantity'], $line_item['cost'] ) );
		}

		foreach ( $misc_items as $key => $misc_item ) {
			$this->payment->add_misc_item( $key, new MiscItem( $misc_item['label'], $misc_item['quantity'], $misc_item['cost'] ) );
		}
	}

	public function build_payment_handler( PaymentHandler $payment_handler ) {
		if ( ! empty( $this->payment_handler ) ) {
			return;
		}

		if ( ! $this->main_registration ) {
			return;
		}

		$this->payment_handler = $payment_handler;

		$event_quantity = $this->calculate_total_guest_count();
		$event = new Event( $this->get_main()->get_registration_data( 'event_id' ) );
		$event_cost = $event->get_registration_cost( 'event' );

		$line_items = array(
			'event' => array(
				'label' => $event->get( 'title' ),
				'quantity' => $event_quantity,
				'cost' => $event_cost
			)
		);

		$line_items = apply_filters( 'evge_payment_line_items', $line_items, $this );

		$fee_setting = $this->payment_handler->get_fee();
		$line_item_cost = 0;
		foreach ( $line_items as $key => $line_item ) {
			$line_item_cost += (float)$line_item['cost'] * (int)$line_item['quantity'];
		}
		$fee_cost = (float) $line_item_cost * ((float) $fee_setting['percent_cost'] / 100) + (float) $fee_setting['flat_cost'];
		$misc_items = array(
			'fee' => array(
				'label' => $fee_setting['label'],
				'quantity' => 1,
				'cost' => round( $fee_cost, 2 )
			)
		);

		$misc_items = apply_filters( 'evge_payment_misc_items', $misc_items, $line_items, $this );

		$payment_data = array();
		foreach ( $line_items as $key => $line_item ) {
			$this->payment_handler->cart()->add_line_item( $key, new LineItem( $line_item['label'], $line_item['quantity'], $line_item['cost'], $line_item ) );
		}

		foreach ( $misc_items as $key => $misc_item ) {
			$this->payment_handler->cart()->add_misc_item( $key, new MiscItem( $misc_item['label'], $misc_item['quantity'], $misc_item['cost'], $misc_item ) );
		}

		$where = array(
			array(
				'column' => 'registration_id',
				'value' => $this->main_registration->get_entry_id(),
				'compare' => '=',
				'type' => 'int'
			)
		);
		$payment_records_db = $this->db->payment_query( $where );

		$this->payment_records = array();
		foreach ( $payment_records_db as $payment_record ) {
			$this->payment_records[] = new Record( $payment_record );
		}

	}

	public function set_payment_record( $payment_record ) {
		// Check if a payment record with the same invoice ID already exists
		$invoice_id = $payment_record->get_invoice_id();
		if ( ! empty( $invoice_id ) ) {
			foreach ( $this->payment_records as $index => $existing_record ) {
				if ( $existing_record->get_invoice_id() === $invoice_id ) {
					// Replace the existing record with the new one
					$this->payment_records[$index] = $payment_record;
					return;
				}
			}
		}
		
		// If no matching invoice ID found, add as new record
		$this->payment_records[] = $payment_record;
	}

	/**
	 * @return PaymentHandler
	 *
	 * @since 1.0
	 */
	public function payment_handler() {
		return $this->payment_handler;
	}

	/**
	 * @return Registration
	 *
	 * @since 2.21
	 */
	public function get_main() {
		return $this->main_registration;
	}

	/**
	 * Get additional guest registrations
	 * 
	 * @return array Array of additional guest registrations
	 * 
	 * @since 2.21
	 */
	public function get_additional_guest_registrations() {
		return $this->additional_guest_registrations;
	}

	/**
	 * Get additional guest registrations in order by guest number
	 * 
	 * @return array Array of additional guest registrations ordered by guest number
	 * 
	 * @since 2.21
	 */
	public function get_additional_guest_registrations_ordered() {
		// The array is already sorted by guest number in load_additional_guest_registrations
		// but let's ensure it's sorted here as well for safety
		$guests = $this->additional_guest_registrations;
		ksort( $guests );
		return $guests;
	}

	public function set_additional_guest_registrations( $additional_guest_registrations ) {
		$this->additional_guest_registrations = $additional_guest_registrations;
	}

	/**
	 * Get total count of all registrations (main + guests)
	 * 
	 * @return int Total count of registrations
	 * 
	 * @since 2.21
	 */
	public function get_total_registration_count() {
		if ( empty( $this->additional_guest_registrations ) ) {
			return $this->main_registration->get_guest_count();
		}
		return 1 + count( $this->additional_guest_registrations ); // 1 for main + guest count
	}

	/**
	 * Get the count of additional guest registrations
	 * 
	 * @return int Count of additional guest registrations
	 * 
	 * @since 2.21
	 */
	public function get_guest_registration_count() {
		return count( $this->additional_guest_registrations );
	}

	/**
	 * Remove an additional guest registration by guest number
	 * 
	 * @param int $guest_number The guest number to remove
	 * 
	 * @since 2.21
	 */
	public function remove_additional_guest_registration( $guest_number ) {
		if ( isset( $this->additional_guest_registrations[ $guest_number ] ) ) {
			unset( $this->additional_guest_registrations[ $guest_number ] );
		}
	}

	/**
	 * Add or update an additional guest registration
	 * 
	 * @param int $guest_number The guest number
	 * @param \WPEventGenius\Common\Registration\Registration\BaseRegistration $guest_registration The guest registration
	 * 
	 * @since 2.21
	 */
	public function set_additional_guest_registration( $guest_number, $guest_registration ) {
		$this->additional_guest_registrations[ $guest_number ] = $guest_registration;
	}

	/**
	 * Load additional guest registrations for a main registration
	 * 
	 * @param int $main_entry_id The main registration entry ID
	 * 
	 * @since 2.21
	 */
	protected function load_additional_guest_registrations( $main_entry_id ) {
		// Query for registrations where parent = main_entry_id
		$where = array(
			array(
				'column' => 'parent',
				'value'  => $main_entry_id,
				'compare' => '=',
				'type' => 'int'
			)
		);
		$guest_registrations = $this->db->registration_query( $where );



		// Array to store guest registrations with their numbers for sorting
		$guests_with_numbers = array();

		$guest_number_counter = 999;
		foreach ( $guest_registrations as $guest_data ) {

			$guest_registration = new BaseRegistration( $this->db );
			$guest_registration->set_entry_id( $guest_data['id'] );
			$guest_registration->build();

			// Get the stored guest number from meta, fallback to counter if not found
			$guest_number = $guest_registration->get_registration_data( 'guest_number' );
			if ( empty( $guest_number ) ) {
				// Fallback for existing registrations that don't have guest_number meta
				$guest_number = $guest_number_counter;
				$guest_number_counter++;
			}
			
			$guests_with_numbers[ $guest_number ] = $guest_registration;
		}

		// Sort by guest number to maintain proper order
		ksort( $guests_with_numbers );

		// reset the guest number to be sequential
		$guest_number_counter = 1;
		$additional_guest_registrations = array();
		foreach ( $guests_with_numbers as $guest_registration ) {
			$additional_guest_registrations[ $guest_number_counter ] = $guest_registration;
			$guest_number_counter++;
		}


		// Store in the additional_guest_registrations array
		$this->additional_guest_registrations = $additional_guest_registrations;
	}

	/**
	 * @return Database
	 *
	 * @since 2.21
	 */
	public function db() {
		return $this->db;
	}

	/**
	 * @param int $entry_id
	 *
	 * @since 2.21
	 */
	public function set_from_existing( $entry_id ) {

		$where = array(
			array(
				'column' => 'id',
				'value'  => $entry_id,
				'compare' => '=',
				'type' => 'int'
			)
		);
		$returned = $this->db->registration_query( $where );

		foreach ( $returned as $existing ) {
			if ( empty( $this->main_registration ) ) {
				$this->main_registration = new MainRegistration( $this->db );
				$this->main_registration->build( $existing );
				$this->main_registration->set_entry_id( $existing['id'] );
			}
		}

		// Load additional guest registrations
		$this->load_additional_guest_registrations( $entry_id );
	}

	/**
	 * @param SubmissionGroup $submission_group
	 *
	 * @since 2.21
	 */
	public function set_from_submission_group( SubmissionGroup $submission_group ) {
		$main_submission         = $submission_group->get_main_submission();
		$this->main_registration = new MainRegistration( $this->db );
		$main_registration_data  = $main_submission->get_data();
		$this->main_registration->set_submission_data( $main_registration_data );
		if ( $main_submission->is_edit() ) {
			$this->main_registration->set_entry_id( $main_registration_data['entry_id'] );
		}

		// Handle additional guest submissions if they exist
		$this->process_additional_guest_submissions( $submission_group );
	}

	/**
	 * Process additional guest submissions from the submission group
	 * 
	 * @param SubmissionGroup $submission_group The submission group containing guest submissions
	 * 
	 * @since 2.21
	 */
	protected function process_additional_guest_submissions( SubmissionGroup $submission_group ) {
		// Check if this is a ProSubmissionGroup with additional guest submissions
		if ( method_exists( $submission_group, 'get_additional_guest_submissions' ) ) {
			$additional_guest_submissions = $submission_group->get_additional_guest_submissions();
			
			foreach ( $additional_guest_submissions as $guest_number => $guest_submission ) {
				$this->create_guest_registration( $guest_number, $guest_submission );
			}
		}
	}

	/**
	 * Create a guest registration from a guest submission
	 * 
	 * @param int $guest_number The guest number
	 * @param \WPEventGenius\Common\Registration\Submission\Submission $guest_submission The guest submission
	 * 
	 * @since 2.21
	 */
	protected function create_guest_registration( $guest_number, $guest_submission ) {
		$guest_registration = new \WPEventGenius\Standard\Registration\Registration\AdditionalGuestRegistration( 
			$this->db, 
			$this->main_registration->get_entry_id() 
		);
		
		$guest_data = $guest_submission->get_data();
		
		// Set the guest registration data
		$guest_registration->set_submission_data( $guest_data );
		
		// Add guest number as meta data
		$guest_data['guest_number'] = $guest_number;
		$guest_registration->set_submission_data( $guest_data );
		
		// For edit submissions, set the entry ID if available
		if ( $guest_submission->is_edit() && isset( $guest_data['entry_id'] ) ) {
			$guest_registration->set_entry_id( $guest_data['entry_id'] );
		}
		
		// Ensure parent ID is set correctly
		if ( method_exists( $guest_registration, 'set_parent_id' ) ) {
			$guest_registration->set_parent_id( $this->main_registration->get_entry_id() );
		}
		
		// Store the guest registration
		$this->additional_guest_registrations[ $guest_number ] = $guest_registration;
	}

	/**
	 * @param string $status
	 *
	 * @since 2.21
	 */
	public function set_status( $status ) {
		$this->main_registration->set_status( $status );
		foreach ( $this->additional_guest_registrations as $guest_registration ) {
			$guest_registration->set_status( $status );
		}
	}


	public function set_datum( $key, $value ) {
		$this->main_registration->add_submission_data( $key, $value );
		foreach ( $this->additional_guest_registrations as $guest_registration ) {
			$guest_registration->add_submission_data( $key, $value );
		}
	}

	/**
	 * @return array
	 *
	 * @since 2.21
	 */
	public function generate_email_data() {
		if ( empty( $this->main_registration ) ) {
			return array();
		}

		return $this->main_registration->generate_email_data();
	}

	public function pending_payment_record() {
		if ( empty( $this->payment_records ) ) {
			return false;
		}

		$pending_payment = false;
		foreach ( $this->payment_records as $payment_record ) {
			if ( $payment_record->get_payment_status() === 'pending' 
			|| $payment_record->get_payment_status() === 'offline' 
			|| $payment_record->get_payment_status() === 'abandoned'
			) {
				$pending_payment = $payment_record;
			}
			if ( $payment_record->get_payment_status() === 'complete' || $payment_record->get_payment_status() === 'processing' ) {
				return false;
			}
		}

		return $pending_payment;
	}

	public function get_payment_record_by_status( $status = '' ) {
		if ( empty( $this->payment_records ) ) {
			return false;
		}

		foreach ( $this->payment_records as $payment_record ) {
			if ( empty( $status ) || $payment_record->get_payment_status() === $status ) {
				return $payment_record;
			}
		}
		return false;
	}

	/**
	 * @param Form $form
	 *
	 * @since 2.21
	 */
	public function insert_or_update_all( $form ) {

		$insert_id = $this->main_registration->insert_or_update( $form );

		if ( $insert_id ) {
			$this->main_registration->set_entry_id( $insert_id );
		}

		// Insert or update additional guest registrations
		foreach ( $this->additional_guest_registrations as $guest_number => $guest_registration ) {
			// Ensure parent ID is set to main registration ID
			$guest_registration->set_parent_id( $insert_id );

			$guest_insert_id = $guest_registration->insert_or_update( $form );
			
			if ( $guest_insert_id ) {
				$guest_registration->set_entry_id( $guest_insert_id );
			}
		}
	}

	public function update_payment( $payment_record ) {
		// Check if a payment record with the same invoice ID already exists
		$invoice_id = $payment_record->get_invoice_id();
		$existing_record = null;
		$existing_index = null;
		
		if ( ! empty( $invoice_id ) && ! empty( $this->payment_records ) ) {
			foreach ( $this->payment_records as $index => $record ) {
				if ( $record->get_invoice_id() === $invoice_id ) {
					$existing_record = $record;
					$existing_index = $index;
					break;
				}
			}
		}

		if ( empty( $this->payment_records ) ) {
			// No existing records, insert new one
			$payment_id = $this->db()->insert_payment( $this->main_registration->get_entry_id(), $payment_record->get_payment_data() );
			$payment_record->set_transaction_id( $payment_id );
			$this->payment_records[] = $payment_record;
			return $payment_id;
		} elseif ( $existing_record ) {
			// Found existing record with same invoice ID, update it
			$payment_id = $this->db()->update_payment( $existing_record->get_transaction_id(), $payment_record->get_payment_data() );
			$payment_record->set_transaction_id( $existing_record->get_transaction_id() );
			$this->payment_records[$existing_index] = $payment_record;
			return $payment_id;
		} else {
			// Update by transaction ID (existing behavior)
			return $this->db()->update_payment( $payment_record->get_transaction_id(), $payment_record->get_payment_data() );
		}
	}

	public function get_payment_status() {
		if ( empty( $this->payment_records ) ) {
			return 'none';
		}

		foreach ( $this->payment_records as $payment_record ) {
			if ( $payment_record->get_payment_status() === 'complete' ) {
				return 'complete';
			}
		}

		return $this->payment_records[0]->get_payment_status();
	}

	/**
	 * @return mixed|void
	 *
	 * @since 2.21
	 */
	public function calculate_total_guest_count() {
		$main_registration_count = 1; // count the main registration

		$main_registration_guests_count = $this->main_registration->get_guest_count();
		
		// Add additional guest registrations count
		$additional_guest_count = count( $this->additional_guest_registrations );

		$args = array(
			'main_registration_count'         => $main_registration_count,
			'main_registration_guests_count'  => $main_registration_guests_count,
			'additional_guest_count'          => $additional_guest_count,
		);

		$total_count = $main_registration_guests_count + $additional_guest_count;

		return apply_filters( 'evge_total_guest_count', $total_count, $args, $this );
	}

	/**
	 * @return bool
	 *
	 * @since 2.21
	 */
	public function has_cost() {

		if ( empty( $this->payment_handler ) ) {
			$this->build_payment_handler( new PaymentHandler( new Cart() ) );
		}

		return $this->payment_handler->calculate_total() > 0;
	}

	public function get_event_status() {
		if ( ! isset( $this->main_registration ) ) {
			return '';
		}
		$main_data = $this->main_registration->get_entries_data();

		return $main_data['status'];
	}


	public function not_found() {
		return ! isset( $this->main_registration );
	}

	/**
	 * Whether to use custom confirmation actions instead of default behavior
	 * 
	 * When this returns true, the gateway will fire the 'evge_custom_payment_actions' hook
	 * instead of proceeding with normal confirmation actions (emails, etc.).
	 * 
	 * @return bool True to use custom actions, false to use default actions
	 */
	public function use_custom_confirmation_actions() {
		return false;
	}

}
