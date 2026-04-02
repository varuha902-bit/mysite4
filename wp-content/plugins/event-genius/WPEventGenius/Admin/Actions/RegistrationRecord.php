<?php

namespace WPEventGenius\Admin\Actions;

use WPEventGenius\Common\Database;
use WPEventGenius\Common\Utils\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class RegistrationRecord {

	protected $registration_id;

	protected $database;

	protected $raw_registration_data;

	protected $payment_data;

	public function __construct( Database $db, $registration_id ) {
		$this->database = $db;
		$this->registration_id = $registration_id;
		$query_data = $this->database->query_registration_record( $this->registration_id );
		$this->raw_registration_data = array();
		if ( ! empty( $query_data[0] ) ) {
			$this->raw_registration_data = Utils::hydrate_meta_cache( $query_data[0] );
		}

		$where = array(
			array(
				'column' => 'registration_id',
				'value' => $registration_id,
				'compare' => '=',
				'type' => 'int'
			)
		);
		$this->payment_data = $this->database->payment_query( $where );
	}

	public function get_registration_data() {
		if ( empty( $this->raw_registration_data ) ) {
			return array();
		}
		return $this->raw_registration_data;
	}

	public function get_payment_data() {
		if ( empty( $this->payment_data ) ) {
			return array();
		}
		return $this->payment_data[0];
	}

	public function get_registration_id() {
		return $this->registration_id;
	}

	/**
	 * Get the event ID for this registration
	 * 
	 * @return int|false The event ID or false if not found
	 */
	public function get_event_id() {
		if ( empty( $this->raw_registration_data ) || ! isset( $this->raw_registration_data['event_id'] ) ) {
			return false;
		}
		return (int) $this->raw_registration_data['event_id'];
	}

	public function delete() {
		// Get registration data before deletion for the action
		$registration_data = $this->get_registration_data();
		
		$this->database->delete_registration( $this->registration_id );
		
		/**
		 * Fires after a registration has been deleted
		 * 
		 * @param int $registration_id The deleted registration ID
		 * @param array $registration_data The registration data before deletion
		 */
		do_action( 'evge_registration_deleted', $this->registration_id, $registration_data );
	}

	public function create( $column_value, $form_fields ) {
		$insert_id = $this->database->insert_registration( $column_value, $form_fields );
		if (empty($insert_id)) {
			return false;
		}
		$this->registration_id = $insert_id;

		return $insert_id;
	}

	public function update( $standard_data, $meta_data ) {
		// Get registration data before update for the action
		$old_registration_data = $this->get_registration_data();
		
		$this->database->update_registration( $this->registration_id, $standard_data, $meta_data );
		
		// Get updated registration data
		$query_data = $this->database->query_registration_record( $this->registration_id );
		$new_registration_data = array();
		if ( ! empty( $query_data[0] ) ) {
			$new_registration_data = \WPEventGenius\Common\Utils\Utils::hydrate_meta_cache( $query_data[0] );
		}
		
		/**
		 * Fires after a registration has been updated
		 * 
		 * @param int $registration_id The updated registration ID
		 * @param array $old_registration_data The registration data before update
		 * @param array $new_registration_data The registration data after update
		 */
		do_action( 'evge_registration_updated', $this->registration_id, $old_registration_data, $new_registration_data );
	}

	/**
	 * Get related registrations that share the same user ID or email address
	 *
	 * @return array Array of related registration records
	 */
	public function get_related_registrations() {
		if (empty($this->raw_registration_data)) {
			return [];
		}

		$related_registrations = [];
		
		// Get registrations by user ID if it exists
		if (!empty($this->raw_registration_data['user_id'])) {
			$user_where = [
				[
					'column' => 'user_id',
					'value' => $this->raw_registration_data['user_id'],
					'compare' => '=',
					'type' => 'int'
				]
			];
			$user_registrations = $this->database->registration_joined_query(
				$user_where,
				['payments'],
				'registration_date DESC'
			);
			if (!empty($user_registrations)) {
				$related_registrations = array_merge($related_registrations, $user_registrations);
			}
		}
		
		// Get registrations by email if it exists
		if (!empty($this->raw_registration_data['email'])) {
			$email_registrations = $this->database->search_registrations($this->raw_registration_data['email']);
			if (!empty($email_registrations)) {
				$related_registrations = array_merge($related_registrations, $email_registrations);
			}
		}
		
		// Remove duplicates and current registration
		$filtered_registrations = [];
		$seen_ids = [];
		foreach ($related_registrations as $registration) {
			$reg_id = $registration['id'];
			if ($reg_id != $this->registration_id && !isset($seen_ids[$reg_id])) {
				$seen_ids[$reg_id] = true;
				$filtered_registrations[] = Utils::hydrate_meta_cache($registration);
			}
		}
		
		return $filtered_registrations;
	}

	/**
	 * Get additional guest registrations for this registration
	 * If this is a guest registration, returns all related guests including the main registration
	 * If this is a main registration, returns all additional guests
	 *
	 * @return array Array of guest registration records with 'is_main' flag
	 */
	public function get_additional_guest_registrations() {
		if (empty($this->raw_registration_data)) {
			return [];
		}

		$parent_id = isset($this->raw_registration_data['parent']) ? (int) $this->raw_registration_data['parent'] : 0;
		$main_registration_id = $parent_id > 0 ? $parent_id : $this->registration_id;
		
		// Get all guest registrations for this group (including main)
		$where = [
			[
				'column' => 'parent',
				'value' => $main_registration_id,
				'compare' => '=',
				'type' => 'int'
			]
		];
		
		$guest_registrations = $this->database->registration_joined_query(
			$where,
			['payments'],
			'registration_date ASC'
		);

		// Add the main registration to the list
		$main_where = [
			[
				'column' => 'id',
				'value' => $main_registration_id,
				'compare' => '=',
				'type' => 'int'
			]
		];
		
		$main_registrations = $this->database->registration_joined_query(
			$main_where,
			['payments'],
			'registration_date ASC'
		);

		// Combine all registrations
		$all_registrations = array_merge($main_registrations, $guest_registrations);

		// Hydrate meta cache and add is_main flag
		$hydrated_guests = [];
		foreach ($all_registrations as $registration) {
			$hydrated_registration = Utils::hydrate_meta_cache($registration);
			$hydrated_registration['is_main'] = ($registration['id'] == $main_registration_id);
			$hydrated_guests[] = $hydrated_registration;
		}

		// Sort by registration date, with main registration first
		usort($hydrated_guests, function($a, $b) {
			if ($a['is_main'] && !$b['is_main']) return -1;
			if (!$a['is_main'] && $b['is_main']) return 1;
			return strtotime($a['registration_date']) - strtotime($b['registration_date']);
		});

		return $hydrated_guests;
	}

}