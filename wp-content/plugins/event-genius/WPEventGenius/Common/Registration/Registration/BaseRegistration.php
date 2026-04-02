<?php
/**
 * Object related to a single registration record. Usually used by RegistrationGroup
 *
 * @since 2.21
 */
namespace WPEventGenius\Common\Registration\Registration;

use WPEventGenius\Common\Database;
use WPEventGenius\Common\Registration\Form\Form;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class BaseRegistration implements Registration {

	/**
	 * @var Database
	 */
	protected $database;

	/**
	 * @var int
	 */
	protected $entry_id = 0;

	/**
	 * @var array
	 */
	protected $submission_data = array();

	/**
	 * @var array
	 */
	protected $entries_data = array();

	/**
	 * @var string
	 */
	protected $status;

	/**
	 * @var array
	 */
	protected $transactions = array();

	/**
	 * @param Database $database
	 */
	public function __construct( Database $database ) {
		$this->database = $database;
	}

	/**
	 * @param array $data
	 */
	public function build( $data = array() ) {
		if ( empty( $data ) && ! empty( $this->entry_id ) ) {
			$this->set_entries_data( $this->query_registration_record_with_meta() );
		} else {
			$this->set_entries_data( $this->normalize_db_data( $data ) );
		}

		if ( ! empty( $this->entries_data['status'] ) ) {
			$this->status = $this->entries_data['status'];
		}
	}

	/**
	 * @param int $entry_id
	 */
	public function set_entry_id( $entry_id ) {
		$this->entry_id = $entry_id;
	}

	public function get_entry_id() {
		return $this->entry_id;
	}

	public function get_registration_data( $key = false ) {
		$return = array();
		if ( ! empty( $this->submission_data ) ) {
			$return = $this->submission_data;
		} elseif ( ! empty( $this->entries_data ) ) {
			$return = $this->entries_data;
		}

		if ( $key ) {
			if ( isset( $return[ $key ] ) ) {
				return $return[ $key ];
			}
			return $this->database->query_registration_meta_by_key( $this->entry_id, $key );
		}

		return $return;
	}

	/**
	 * @param array $submission_data
	 */
	public function set_submission_data( $submission_data ) {
		$this->submission_data = $submission_data;
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function add_submission_data( $key, $value ) {
		$this->submission_data[ $key ] = $value;
	}

	public function get_submission_data() {
		return $this->submission_data;
	}

	public function get_entries_data() {
		return $this->entries_data;
	}

	/**
	 * @param array $entries_data
	 */
	public function set_entries_data( $entries_data ) {
		$this->entries_data = $entries_data;
	}

	/**
	 * @param string $status
	 */
	public function set_status( $status ) {
		$this->status = $status;
	}

	/**
	 * @param int $event_id
	 *
	 * @return bool
	 */
	public function has_cost( $event_id = 0 ) {
		return false;
	}

	/**
	 * @return array
	 */
	public function generate_email_data() {
		$submission_data = $this->submission_data;
		if ( empty( $this->entries_data ) && ! empty( $this->entry_id ) ) {
			$this->entries_data = $this->query_registration_record_with_meta();
			$maybe_unconnected = $this->database->get_entry_datum( $this->entry_id, 'unconnected_event_id' );
			if ( ! empty( $maybe_unconnected ) ) {
				$this->entries_data['unconnected_event_id'] = $maybe_unconnected['entry_value'];
			}
		}

		return array_merge( $submission_data, $this->entries_data );
	}

	/**
	 * Queries the registration record and metadata for this registration
	 * 
	 * @return array Registration data including metadata
	 */
	protected function query_registration_record_with_meta() {
		$registration = $this->database->query_registration_record($this->entry_id);

		if (empty($registration)) {
			return array();
		}

		// Get the first record since we're querying by ID
		$registration = $registration[0];

		if ( ! empty( $registration['meta_cache'] ) ) {
			$registration = $this->normalize_db_data( $registration );
		} else {
			// Get any additional metadata
			$meta_cache = $this->database->query_meta_cache($this->entry_id);

			$registration = array_merge($registration, $meta_cache);
		}

		if ( isset( $registration['meta_cache'] ) ) {
			unset( $registration['meta_cache'] );
		}
		
		return $registration;
	}

	/**
	 * @param Form $form
	 *
	 * @return int
	 */
	public function insert_or_update( $form ) {
		$to_insert_or_update = $this->submission_data;

		$form_fields = $form->get_fields();

		if ( empty( $this->entry_id ) ) {
			if ( ! empty( $this->status ) ) {
				$to_insert_or_update['status'] = $this->status;
			}

			$entry_id = $this->database->insert_registration( $to_insert_or_update, $form_fields );
			if ( ! empty( $entry_id ) ) {
				$this->entry_id = $entry_id;
				$this->submission_data = array();
				$this->build();
			}
		} else {
			if ( ! empty( $this->status ) ) {
				$to_insert_or_update['status'] = $this->status;
			}
			
			// Get old registration data before update for the action
			$old_registration_data = $this->query_registration_record_with_meta();
			
			// Separate submission data into standard and meta data
			$standard_data = $this->separate_standard_and_meta_data( $to_insert_or_update );
			$registration_meta = $this->separate_meta_data( $to_insert_or_update, $form_fields );
			
			$this->database->update_registration( $this->entry_id, $standard_data, $registration_meta );
			
			// Get new registration data after update
			$new_registration_data = $this->query_registration_record_with_meta();
			
			/**
			 * Fires after a registration has been updated
			 * 
			 * @param int $registration_id The updated registration ID
			 * @param array $old_registration_data The registration data before update
			 * @param array $new_registration_data The registration data after update
			 */
			do_action( 'evge_registration_updated', $this->entry_id, $old_registration_data, $new_registration_data );
		}

		return $this->entry_id;
	}

	/**
	 * Separate submission data into standard columns and meta data
	 * 
	 * @param array $submission_data The submission data to separate
	 * @return array Standard data for evge_registrations table
	 */
	protected function separate_standard_and_meta_data( $submission_data ) {
		// Get standard columns from the database class
		$standard_columns = $this->database->get_registration_standard_columns();
		
		$standard_data = array();
		
		foreach ( $submission_data as $key => $value ) {
			if ( in_array( $key, $standard_columns ) ) {
				$standard_data[ $key ] = $value;
			}
		}
		
		return $standard_data;
	}

	/**
	 * Extract meta data from submission data for form fields
	 * 
	 * @param array $submission_data The submission data
	 * @param array $form_fields The form fields
	 * @return array Meta data for evge_registrations_meta table
	 */
	protected function separate_meta_data( $submission_data, $form_fields ) {
		$registration_meta = array();
		
		// Get standard columns from the database class
		$standard_columns = $this->database->get_registration_standard_columns();
		
		// Get form field slugs
		$form_field_slugs = array();
		foreach ( $form_fields as $field ) {
			$form_field_slugs[] = $field->get_slug();
		}
		
		// Extract meta data (everything that's not a standard column but is a form field)
		foreach ( $submission_data as $key => $value ) {
			if ( ! in_array( $key, $standard_columns ) && in_array( $key, $form_field_slugs ) ) {
				$registration_meta[ $key ] = $value;
			}
		}
		
		return $registration_meta;
	}

	/**
	 * @return int
	 */
	public function get_guest_count() {
		$working_data = ! empty( $this->submission_data ) ? $this->submission_data : $this->entries_data;
		if ( empty( $working_data ) ) {
			return 0;
		}

		if ( empty( $working_data['quantity'] ) ) {
			return 0;
		}

		return absint( $working_data['quantity'] );
	}

	/**
	 * Check if this registration is a guest registration based on parent column
	 * 
	 * @return bool True if parent is not 0, false otherwise
	 */
	public function is_guest_registration() {
		$working_data = ! empty( $this->entries_data ) ? $this->entries_data : $this->submission_data;
		if ( empty( $working_data ) || ! isset( $working_data['parent'] ) ) {
			$this->set_entries_data( $this->query_registration_record_with_meta() );
			$working_data = ! empty( $this->entries_data ) ? $this->entries_data : $this->submission_data;
		}

		return isset( $working_data['parent'] ) && $working_data['parent'] > 0;
	}

	public function get_action_key() {
		$working_data = ! empty( $this->entries_data ) ? $this->entries_data : $this->submission_data;
		if ( empty( $working_data ) || empty( $working_data['action_key'] ) ) {
			$this->set_entries_data( $this->query_registration_record_with_meta() );
			$working_data = ! empty( $this->entries_data ) ? $this->entries_data : $this->submission_data;
			if ( empty( $working_data['action_key'] ) ) {
				return false;
			}
		}

		if ( empty( $working_data['action_key'] ) ) {
			return false;
		}

		return $working_data['action_key'];
	}

	/**
	 * @return array
	 */
	public function get_transactions() {
		return $this->transactions;
	}

	/**
	 * @param string $transaction
	 */
	private function add_transaction( $transaction ) {
		$this->transactions[] = $transaction;
	}

	/**
	 * @param array $raw_data
	 *
	 * @return array
	 */
	private function normalize_db_data( $raw_data ) {
		$registration = array();
		if ( isset( $raw_data['meta_cache'] ) ) {
			$registration = $raw_data;

			$meta_cache = json_decode( $raw_data['meta_cache'], true );

			if ( ! empty( $meta_cache ) ) {
				$registration = array_merge( $raw_data, $meta_cache );
				unset( $registration['meta_cache'] );
			}
		}

		return $registration;
	}

	/**
	 * Set a single piece of registration meta (insert or update)
	 * 
	 * @param string $key The meta key
	 * @param mixed $value The meta value
	 * @return bool Success status
	 */
	public function set_registration_meta( $key, $value ) {
		if ( empty( $this->entry_id ) ) {
			return false;
		}

		// Check if the meta key already exists
		$existing_value = $this->database->query_registration_meta_by_key( $this->entry_id, $key );
		
		if ( $existing_value !== '' ) {
			// Meta exists, update it
			return $this->database->update_registration_meta( $this->entry_id, array( $key => $value ) );
		} else {
			// Meta doesn't exist, insert it
			return $this->database->insert_registration_meta( $this->entry_id, array( $key => $value ) );
		}
	}
}
