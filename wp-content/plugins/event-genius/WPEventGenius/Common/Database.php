<?php

namespace WPEventGenius\Common;

use WPEventGenius\Common\Utils\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class Database {

	protected const REGISTRATIONS_TABLE = 'evge_registrations';

	protected const REGISTRATIONS_META_TABLE = 'evge_registrations_meta';

	protected const PAYMENTS_TABLE = 'evge_payments';


	protected const PAYMENTS_META_TABLE = 'evge_payments_meta';

	protected const EVENTS_TABLE = 'evge_events';

	protected const EVENT_SERIES_RELATIONSHIPS_TABLE = 'evge_event_series_relationships';

	protected const EVENT_VENUE_RELATIONSHIPS_TABLE = 'evge_event_venue_relationships';

	protected const EVENT_ORGANIZER_RELATIONSHIPS_TABLE = 'evge_event_organizer_relationships';

	public function __construct() {

	}

	public function search_event_registrations( $event_id, $value ) {
		global $wpdb;
		$registrations_meta_table = $wpdb->prefix . self::REGISTRATIONS_META_TABLE;
		$registrations_table = $wpdb->prefix . self::REGISTRATIONS_TABLE;

		// Direct DB call is necessary for custom tables that don't have WordPress API functions
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$results = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT m.*, r.* FROM $registrations_meta_table AS m LEFT JOIN $registrations_table AS r ON m.registration_id = r.id 
				WHERE m.meta_value = %s 
				AND r.event_id = %d
				AND r.status != %s",
				$value, 
				$event_id,
				'canceled'
			),
			ARRAY_A
		);

		return $results;
	}

	public function search_registrations( $value, $statuses = array() ) {
		global $wpdb;
		$registrations_table = $wpdb->prefix . self::REGISTRATIONS_TABLE;
		$registrations_meta_table = $wpdb->prefix . self::REGISTRATIONS_META_TABLE;

		$status_string = !empty($statuses) ? $this->mysql_escape_in_clause( implode( ',', $statuses ) ) : '';

		if ( $status_string === 'active' ) {
			$statuses = array('confirmed', 'pending');
			$status_string = $this->mysql_escape_in_clause( implode( ',', $statuses ) );
		}

		if ( empty( $statuses ) ) {
			// Direct DB call is necessary for custom tables that don't have WordPress API functions
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$results = $wpdb->get_results(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT m.*, r.* FROM $registrations_meta_table AS m INNER JOIN $registrations_table AS r ON m.registration_id = r.id 
					WHERE m.meta_value LIKE %s
					AND m.meta_key IN ('first','last','email','phone')
					GROUP BY r.id
					ORDER BY r.registration_date DESC",
					'%' . $wpdb->esc_like( $value ) . '%'
				),
				ARRAY_A
			);
		} else {
			// Direct DB call is necessary for custom tables that don't have WordPress API functions
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$results = $wpdb->get_results(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT m.*, r.* FROM $registrations_meta_table AS m INNER JOIN $registrations_table AS r ON m.registration_id = r.id 
					WHERE m.meta_value LIKE %s
					AND m.meta_key IN ('first','last','email','phone')
					AND r.status IN ('" . $status_string . "')
					GROUP BY r.id
					ORDER BY r.registration_date DESC",
					'%' . $wpdb->esc_like( $value ) . '%'
				),
				ARRAY_A
			);
		}

		
		return $results;
	}

	public function search_registration_meta( $value, $event_id, $statuses = array() ) {
		global $wpdb;
		$registrations_meta_table = $wpdb->prefix . self::REGISTRATIONS_META_TABLE;
		$registrations_table = $wpdb->prefix . self::REGISTRATIONS_TABLE;
		$status_string = $this->mysql_escape_in_clause( implode( ',', $statuses ) );

		if ( ! empty( $event_id ) ) {
			if ( empty( $statuses ) ) {			
				// Direct DB call is necessary for custom tables that don't have WordPress API functions
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$results = $wpdb->get_results(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						"SELECT * FROM $registrations_meta_table AS m LEFT JOIN $registrations_table AS r ON m.registration_id = r.id
						WHERE m.meta_value LIKE %s
						AND m.meta_key IN ('first','last','email','phone') 
						AND r.event_id = %d 
						GROUP BY m.registration_id ORDER BY m.registration_id DESC LIMIT 200",
						'%' . $wpdb->esc_like( $value ) . '%',
						absint( $event_id ),	
					),
					ARRAY_A
				);
			} else {
				// Direct DB call is necessary for custom tables that don't have WordPress API functions
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$results = $wpdb->get_results(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						"SELECT * FROM $registrations_meta_table AS m LEFT JOIN $registrations_table AS r ON m.registration_id = r.id
						WHERE m.meta_value LIKE %s
						AND m.meta_key IN ('first','last','email','phone') 
						AND r.event_id = %d 
						AND r.status IN ('" . $status_string . "')
						GROUP BY m.registration_id ORDER BY m.registration_id DESC LIMIT 200",
						'%' . $wpdb->esc_like( $value ) . '%',
						absint( $event_id ),
					),
					ARRAY_A
				);
			}

		} else {
			if ( empty( $statuses ) ) {
				// Direct DB call is necessary for custom tables that don't have WordPress API functions
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$results = $wpdb->get_results(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						"SELECT * FROM $registrations_meta_table AS m LEFT JOIN $registrations_table AS r ON m.registration_id = r.id
						WHERE m.meta_value LIKE %s
						AND m.meta_key IN ('first','last','email','phone')
						GROUP BY m.registration_id ORDER BY m.registration_id DESC LIMIT 200",
						'%' . $wpdb->esc_like( $value ) . '%',
					),
					ARRAY_A
				);
			} else {
				// Direct DB call is necessary for custom tables that don't have WordPress API functions
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$results = $wpdb->get_results(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT * FROM $registrations_meta_table AS m LEFT JOIN $registrations_table AS r ON m.registration_id = r.id
					WHERE m.meta_value LIKE %s
					AND m.meta_key IN ('first','last','email','phone')
					AND r.status IN ('" . $status_string . "')	
					GROUP BY m.registration_id ORDER BY m.registration_id DESC LIMIT 200",
					'%' . $wpdb->esc_like( $value ) . '%',
				),
				ARRAY_A
				);
			}

		}

		return $results;
	}

	public function query_registration_meta_by_key( $registration_id, $meta_key ) {
		global $wpdb;
		$registrations_meta_table = $wpdb->prefix . self::REGISTRATIONS_META_TABLE;
		$registrations_table = $wpdb->prefix . self::REGISTRATIONS_TABLE;

		// Direct DB call is necessary for custom tables that don't have WordPress API functions
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$results = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM $registrations_meta_table AS m LEFT JOIN $registrations_table AS r ON m.registration_id = r.id
				WHERE m.meta_key = %s
				AND registration_id = %d",
				$meta_key,
				$registration_id
			),
			ARRAY_A
		);

		if ( ! empty( $results[0]['meta_value'] ) ) {
			return $results[0]['meta_value'];
		}

		return '';
	}

	public function query_registration_record( $registration_id ) {
		global $wpdb;
		$registrations_table = $wpdb->prefix . self::REGISTRATIONS_TABLE;

		// Direct DB call is necessary for custom tables that don't have WordPress API functions
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$results = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM $registrations_table as r
				WHERE r.id = %d
				ORDER BY r.registration_date DESC LIMIT 100",
				$registration_id
			),
			ARRAY_A
		);

		return $results;
	}

	public function registration_query( $where, $order_by = '', $limit = '', $offset = '', $include_canceled = false ) {
		global $wpdb;
		$registrations_table = $wpdb->prefix . self::REGISTRATIONS_TABLE;

		$escaped_where = $this->escaped_where( $where );

		if ( empty( $escaped_where ) ) {
			return array();
		}

		if ( ! $include_canceled ) {
			$escaped_where .= ' AND status != "canceled"';
		}

		// Parse order_by into column and direction
		$order_column = 'registration_date';
		$order_direction = 'DESC';
		
		if (!empty($order_by)) {
			$parts = explode(' ', trim($order_by));
			if (count($parts) >= 1) {
				$order_column = sanitize_key($parts[0]);
			}
			if (count($parts) >= 2) {
				$direction = strtoupper(trim($parts[1]));
				if ($direction === 'ASC' || $direction === 'DESC') {
					$order_direction = $direction;
				}
			}
		}

		// Set default values for empty parameters
		$limit = !empty($limit) ? absint($limit) : 5000;
		$offset = !empty($offset) ? absint($offset) : 0;

		// Direct DB call is necessary for custom tables that don't have WordPress API functions
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $registrations_table AS r 
			WHERE $escaped_where
			ORDER BY $order_column $order_direction
			LIMIT %d
			OFFSET %d",
			$limit,
			$offset
		), ARRAY_A );

		return $results;
	}

	public function registration_joined_query( $where, $to_join = array(), $order_by = '', $limit = '', $offset = '', $include_canceled = false ) {
		global $wpdb;
		$registrations_table = $wpdb->prefix . self::REGISTRATIONS_TABLE;
		$to_join_sql = array();

		if ( in_array( 'payments', $to_join, true ) ) {
			$payments_table = $wpdb->prefix . self::PAYMENTS_TABLE;
			$to_join_sql[] = "LEFT JOIN $payments_table AS p ON r.id = p.registration_id ";
		}

		if ( in_array( 'attendance', $to_join, true ) && evge_is_standard_tier() ) {
			$attendance_table = $wpdb->prefix . 'evge_attendance_status';
			$to_join_sql[] = "LEFT JOIN $attendance_table AS a ON r.id = a.registration_id AND r.event_id = a.event_id ";
		}

		// Extract attendance_status filter before processing WHERE clause
		// (it's not a column on the registrations table, so it needs special handling)
		$attendance_status_filter = null;
		$filtered_where = array();
		if ( ! empty( $where ) ) {
			foreach ( $where as $item ) {
				if ( isset( $item['column'] ) && $item['column'] === 'attendance_status' ) {
					$attendance_status_filter = sanitize_key( $item['value'] );
					continue; // Skip this item - we'll handle it separately
				}
				$filtered_where[] = $item;
			}
		}

		$escaped_where = $this->escaped_where( $filtered_where, 'r.' );

		if ( empty( $escaped_where ) ) {
			return array();
		}

		if ( ! $include_canceled ) {
			$escaped_where .= ' AND r.status != "canceled"';
		}

		$select_fields = array();
		if ( in_array( 'payments', $to_join, true ) ) {
			$select_fields[] = 'p.payment_status';
			$select_fields[] = 'p.payment_gross';
			$select_fields[] = 'p.gateway';
			$select_fields[] = 'p.transaction_id';
			$select_fields[] = 'p.payment_date';
			$select_fields[] = 'p.invoice_id';
			$select_fields[] = 'p.payment_id';
			$select_fields[] = 'p.business';
			$select_fields[] = 'p.currency_code';
		}

		if ( in_array( 'attendance', $to_join, true ) && evge_is_standard_tier() ) {
			$select_fields[] = 'a.status AS attendance_status';
			$select_fields[] = 'a.quantity AS attendance_quantity';
			$select_fields[] = 'a.checked_in_at';
			$select_fields[] = 'a.checked_in_by';
		}

		if ( ! empty( $select_fields ) ) {
			$select = "SELECT r.*, " . implode( ', ', $select_fields ) . " FROM $registrations_table AS r ";
		} else {
			$select = "SELECT * FROM $registrations_table AS r ";
		}

		$sql = $select;

		if ( ! empty( $to_join_sql ) ) {
		foreach ( $to_join_sql as $join ) {
			$sql .= " $join";
			}
		}
		$sql .= " WHERE $escaped_where";

		// Apply attendance status filter if specified (Standard tier only)
		// This needs to be added after the WHERE clause is built and only if attendance table is joined
		if ( ! is_null( $attendance_status_filter ) && in_array( 'attendance', $to_join, true ) && function_exists( 'evge_is_standard_tier' ) && evge_is_standard_tier() ) {
			if ( $attendance_status_filter === 'unknown' ) {
				// Unknown means no attendance record exists (IS NULL)
				$sql .= " AND a.status IS NULL";
			} else {
				// Specific status: attended, noshow, excused
				$sql .= $wpdb->prepare( " AND a.status = %s", $attendance_status_filter );
			}
		}

		// Parse order_by into column and direction
		$order_column = 'r.registration_date';
		$order_direction = 'DESC';
		
		if (!empty($order_by)) {
			$parts = explode(' ', trim($order_by));
			if (count($parts) >= 1) {
				$order_column = 'r.' . sanitize_key($parts[0]);
			}
			if (count($parts) >= 2) {
				$direction = strtoupper(trim($parts[1]));
				if ($direction === 'ASC' || $direction === 'DESC') {
					$order_direction = $direction;
				}
			}
		}

		// Set default values for empty parameters
		$limit = !empty($limit) ? absint($limit) : 5000;
		$offset = !empty($offset) ? absint($offset) : 0;

		// Direct DB call is necessary for custom tables that don't have WordPress API functions
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$results = $wpdb->get_results( $wpdb->prepare(
			"$sql
			ORDER BY $order_column $order_direction
			LIMIT %d
			OFFSET %d",
			$limit,
			$offset
		), ARRAY_A );

		return $results;
	}

	public function event_registration_quantity( $event_id, $exclude_pending = false ) {
		global $wpdb;
		$registrations_table = $wpdb->prefix . self::REGISTRATIONS_TABLE;

		if ($exclude_pending) {
			// Direct DB call is necessary for custom tables that don't have WordPress API functions
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$count = $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT SUM(quantity) FROM $registrations_table 
					WHERE event_id = %d 
					AND status != 'canceled'
					AND status != 'pending'",
					$event_id
				)
			);
		} else {
			// Direct DB call is necessary for custom tables that don't have WordPress API functions
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$count = $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT SUM(quantity) FROM $registrations_table 
					WHERE event_id = %d 
					AND status != 'canceled'",
					$event_id
				)
			);
		}

		return (int) $count;
	}

	public function set_status( $entry_id, $status ) {
		global $wpdb;
		$registrations_table = $wpdb->prefix . self::REGISTRATIONS_TABLE;

		// Direct DB call is necessary for custom tables that don't have WordPress API functions
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $wpdb->update( $registrations_table, array( 'status' => $status ), array( 'id' => $entry_id ), array( '%s' ), array( '%d' ) );
	}


	public function insert_registration( $column_value, $form_fields ) {
		global $wpdb;
		$registrations_table = $wpdb->prefix . self::REGISTRATIONS_TABLE;

		$registration_meta = array();
		foreach ( $form_fields as $field ) {
			if ( ! empty( $column_value[ $field->get_slug() ] ) ) {
				$registration_meta[ $field->get_slug() ] = $column_value[ $field->get_slug() ];
			}
		}
		$column_value['meta_cache'] = wp_json_encode( $registration_meta );

		if ( ! empty( $column_value['_event_info'] ) ) {
			$registration_meta['_event_info'] = $column_value['_event_info'];
		}

		if ( empty( $column_value['registration_date'] ) ) {
			$column_value['registration_date'] = $this->column_defaults( 'registration_date' );
		}
		if ( empty( $column_value['registration_type'] ) ) {
			$column_value['registration_type'] = $this->column_defaults( 'registration_type' );
		}
		if ( empty( $column_value['action_key'] ) ) {
			$column_value['action_key'] = $this->column_defaults( 'action_key' );
		}
		if ( empty( $column_value['confirmation_code'] ) ) {
			$column_value['confirmation_code'] = $this->column_defaults( 'confirmation_code' );
		}
		if ( empty( $column_value['new'] ) ) {
			$column_value['new'] = $this->column_defaults( 'new' );
		}

		$formats = array();
		foreach ( $column_value as $column => $value ) {
			if ( $this->registration_type_exists( $column ) ) {
				$formats[] = $this->registration_type( $column );
			} else {
				unset ( $column_value[ $column ] );
			}
		}

		// Direct DB call is necessary for custom tables that don't have WordPress API functions
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert( $registrations_table, $column_value, $formats);
		$insert_id = $wpdb->insert_id;

		$this->sync_registration_meta( $insert_id, $registration_meta );

		return $insert_id;
	}

	public function sync_registration_meta( $insert_id, $registration_meta ) {
		global $wpdb;
		$registrations_meta_table = $wpdb->prefix . self::REGISTRATIONS_META_TABLE;

		foreach ($registration_meta as $meta_key => $meta_value) {
			// Delete existing meta entries for this key first
			// Direct DB call is necessary for custom tables that don't have WordPress API functions
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->delete(
				$registrations_meta_table,
				array(
					'registration_id' => $insert_id,
					'meta_key' => $meta_key
				),
				array('%d', '%s')
			);

			// Handle array values (for checkbox fields)
			if(is_array($meta_value)) {
				foreach ($meta_value as $single_value) {
					// Direct DB call is necessary for custom tables that don't have WordPress API functions
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
					$wpdb->insert(
						$registrations_meta_table,
						array(
							'registration_id' => $insert_id,
							'meta_key' => $meta_key,
							'meta_value' => $single_value
						),
						array('%d', '%s', '%s')
					);
				}
			} else {
				// Direct DB call is necessary for custom tables that don't have WordPress API functions
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->insert(
					$registrations_meta_table,
					array(
						'registration_id' => $insert_id,
						'meta_key' => $meta_key,
						'meta_value' => $meta_value
					),
					array('%d', '%s', '%s')
				);
			}
		}
	}

	public function update_registration( $registration_id, $standard_data, $registration_meta ) {
		global $wpdb;

		if ( ! empty( $registration_meta ) ) {
			$this->sync_registration_meta( $registration_id, $registration_meta );
		}

		$registrations_table = $wpdb->prefix . self::REGISTRATIONS_TABLE;

		$to_update = array();
		$format = array();
		if ( ! empty( $standard_data ) ) {
			foreach ( $standard_data as $column => $value ) {
				if ( $this->registration_type_exists( $column ) ) {
					$to_update[ $column ] = $value;
					$format[] = $this->registration_type( $column );
				}
			}
		}

		$to_update['meta_cache'] = wp_json_encode( $this->query_meta_cache( $registration_id )  );
		$format[] = $this->registration_type( 'meta_cache' );
		// Direct DB call is necessary for custom tables that don't have WordPress API functions
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $wpdb->update( $registrations_table, $to_update, array( 'id' => $registration_id ), $format, array( '%d' ) );
	}

	public function query_meta_cache($registration_id) {
		global $wpdb;
		$registrations_meta_table = $wpdb->prefix . self::REGISTRATIONS_META_TABLE;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM $registrations_meta_table WHERE registration_id = %d",
				$registration_id
			),
			ARRAY_A
		);

		$return = array();
		foreach ($results as $result) {
			$meta_key = $result['meta_key'];
			$meta_value = $result['meta_value'];

			// If this key already exists, convert to or add to array
			if (isset($return[$meta_key])) {
				if (!is_array($return[$meta_key])) {
					// Convert existing single value to array
					$return[$meta_key] = array($return[$meta_key]);
				}
				// Add new value to array
				$return[$meta_key][] = $meta_value;
			} else {
				// First occurrence of this key
				$return[$meta_key] = $meta_value;
			}
		}

		return $return;
	}

	public function delete_registration( $registration_id ) {
		global $wpdb;
		$registrations_table = $wpdb->prefix . self::REGISTRATIONS_TABLE;
		$registrations_meta_table = $wpdb->prefix . self::REGISTRATIONS_META_TABLE;
		$payments_table = $wpdb->prefix . self::PAYMENTS_TABLE;

		$wpdb->delete( $registrations_table, array( 'id' => $registration_id ), array( '%d' ) );
		$wpdb->delete( $registrations_meta_table, array( 'registration_id' => $registration_id ), array( '%d' ) );
		$wpdb->delete( $payments_table, array( 'registration_id' => $registration_id ), array( '%d' ) );
	}

	protected function get_registration_column_types() {
		$column_types = array(
			'id' => '%d',
			'parent' => '%d',
			'event_id' => '%d',
			'series_id' => '%d',
			'registration_date' => '%s',
			'user_id' => '%d',
			'quantity' => '%d',
			'registration_type' => '%s',
			'status' => '%s',
			'action_key' => '%s',
			'confirmation_code' => '%s',
			'new' => '%d',
			'meta_cache' => '%s',
		);
		return $column_types;
	}

	/**
	 * Get the standard columns for the evge_registrations table
	 * 
	 * @return array Array of standard column names
	 */
	public function get_registration_standard_columns() {
		$column_types = $this->get_registration_column_types();
		return array_keys( $column_types );
	}
	protected function registration_type_exists( $column ) {
		$column_types = $this->get_registration_column_types();
		return ! empty( $column_types[ $column ] );
	}
	protected function registration_type( $column ) {
		$column_types = $this->get_registration_column_types();
		if ( empty( $column_types[ $column ] ) ) {
			return '%s';
		}
		return $column_types[ $column ];
	}

	protected function column_defaults( $column ) {
		$column_types = array(
			'registration_date' => gmdate( 'Y-m-d H:i:s' ),
			'registration_type' => 'event',
			'action_key' => Utils::generate_key( 20 ),
			'confirmation_code' => Utils::generate_key( 10 ),
			'new' => 1,
			'payment_date' => gmdate( 'Y-m-d H:i:s' ),
			'invoice_id' => Utils::generate_key( 10 ),
		);

		if ( ! empty( $column_types[ $column ] ) ) {
			return $column_types[ $column ];
		}
		return '';
	}

	public function insert_payment( $registration_id, $column_value ) {
		global $wpdb;
		$payments_table = $wpdb->prefix . self::PAYMENTS_TABLE;
		$column_value['registration_id'] = $registration_id;

		if ( empty( $column_value['payment_date'] ) ) {
			$column_value['payment_date'] = $this->column_defaults( 'payment_date' );
		}
		if ( empty( $column_value['invoice_id'] ) ) {
			$column_value['invoice_id'] = $this->column_defaults( 'invoice_id' );
		}

		$formats = array();
		foreach ( $column_value as $column => $value ) {
			if ( $this->payment_type_exists( $column ) ) {
				$formats[] = $this->payment_type( $column );
			} else {
				unset ( $column_value[ $column ] );
			}
		}

		// Direct DB call is necessary for custom tables that don't have WordPress API functions
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert( $payments_table, $column_value, $formats);
		$insert_id = $wpdb->insert_id;

		return $insert_id;
	}

	public function sync_payment_meta( $insert_id, $registration_meta ) {
		global $wpdb;
		$payments_meta_table = $wpdb->prefix . self::PAYMENTS_META_TABLE;
		foreach ( $registration_meta as $meta_key => $meta_value ) {
			// Direct DB call is necessary for custom tables that don't have WordPress API functions
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert( $payments_meta_table, array(
				'transaction_id' => $insert_id,
				'meta_key' => $meta_key,
				'meta_value' => $meta_value,
			), array( '%d', '%s', '%s' ) );
		}
	}

	/**
	 * Insert payment meta data
	 * 
	 * @param int $transaction_id Payment transaction ID
	 * @param array $meta_data Array of meta key/value pairs
	 * @return bool
	 */
	public function insert_payment_meta( $transaction_id, $meta_data ) {
		global $wpdb;
		$payments_meta_table = $wpdb->prefix . self::PAYMENTS_META_TABLE;
		
		foreach ( $meta_data as $meta_key => $meta_value ) {
			// Direct DB call is necessary for custom tables that don't have WordPress API functions
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$result = $wpdb->insert(
				$payments_meta_table,
				array(
					'transaction_id' => $transaction_id,
					'meta_key' => $meta_key,
					'meta_value' => $meta_value,
				),
				array( '%d', '%s', '%s' )
			);
			
			if ( false === $result ) {
				return false;
			}
		}
		
		return true;
	}

	public function update_payment( $transaction_id, $column_value ) {
		global $wpdb;
		$payments_table = $wpdb->prefix . self::PAYMENTS_TABLE;

		$to_update = array();
		$format = array();
		if ( ! empty( $column_value ) ) {
			foreach ( $column_value as $column => $value ) {
				if ( $this->payment_type_exists( $column ) ) {
					$to_update[ $column ] = $value;
					$format[] = $this->payment_type( $column );
				}
			}
		}

		// Direct DB call is necessary for custom tables that don't have WordPress API functions
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $wpdb->update( $payments_table, $to_update, array( 'transaction_id' => $transaction_id ), $format, array( '%s' ) );
	}

	public function delete_payment( $transaction_id ) {
		global $wpdb;
		$payments_table = $wpdb->prefix . self::PAYMENTS_TABLE;

		// Direct DB call is necessary for custom tables that don't have WordPress API functions
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->delete( $payments_table, array( 'transaction_id' => $transaction_id ), array( '%s' ) );
	}

	public function payment_query( $where, $order_by = '', $limit = '', $offset = '' ) {
		global $wpdb;
		$registration_table = $wpdb->prefix . self::REGISTRATIONS_TABLE;
		$payments_table = $wpdb->prefix . self::PAYMENTS_TABLE;

		$escaped_where = $this->escaped_where( $where, 'p.' );

		if ( empty( $escaped_where ) ) {
			return array();
		}

		// Parse order_by into column and direction
		$order_column = 'r.registration_date';
		$order_direction = 'DESC';
		
		if (!empty($order_by)) {
			$parts = explode(' ', trim($order_by));
			if (count($parts) >= 1) {
				$order_column = 'p.' . sanitize_key($parts[0]);
			}
			if (count($parts) >= 2) {
				$direction = strtoupper(trim($parts[1]));
				if ($direction === 'ASC' || $direction === 'DESC') {
					$order_direction = $direction;
				}
			}
		}

		// Set default values for empty parameters
		$limit = !empty($limit) ? absint($limit) : 5000;
		$offset = !empty($offset) ? absint($offset) : 0;

		// Direct DB call is necessary for custom tables that don't have WordPress API functions
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT r.*, p.* FROM $registration_table AS r  
			LEFT JOIN $payments_table AS p ON r.id = p.registration_id
			WHERE $escaped_where
			ORDER BY $order_column $order_direction
			LIMIT %d
			OFFSET %d",
			$limit,
			$offset
		), ARRAY_A );

		return $results;
	}

	public function query_payment_record( $transaction_id ) {
		global $wpdb;
		$payments_table = $wpdb->prefix . self::PAYMENTS_TABLE;

		// Direct DB call is necessary for custom tables that don't have WordPress API functions
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$results = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM $payments_table as p
				WHERE p.transaction_id = %s
				ORDER BY p.payment_date DESC LIMIT 1",
				$transaction_id
			),
			ARRAY_A
		);

		if ( ! empty( $results ) ) {
			return $results[0];
		}

		return array();
	}

	/**
	 * Query payment record by payment_id
	 * 
	 * @param string $payment_id
	 * @return array
	 */
	public function query_payment_record_by_payment_id( $payment_id ) {
		global $wpdb;
		$payments_table = $wpdb->prefix . self::PAYMENTS_TABLE;

		// Direct DB call is necessary for custom tables that don't have WordPress API functions
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$results = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM $payments_table as p
				WHERE p.payment_id = %s
				ORDER BY p.payment_date DESC LIMIT 1",
				$payment_id
			),
			ARRAY_A
		);

		if ( ! empty( $results ) ) {
			return $results[0];
		}

		return array();
	}

	/**
	 * Query payment meta data by transaction ID
	 * 
	 * @param int $transaction_id
	 * @return array
	 */
	public function query_payment_meta( $transaction_id ) {
		global $wpdb;
		$payments_meta_table = $wpdb->prefix . self::PAYMENTS_META_TABLE;

		// Direct DB call is necessary for custom tables that don't have WordPress API functions
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_key, meta_value FROM $payments_meta_table 
				WHERE transaction_id = %d",
				$transaction_id
			),
			ARRAY_A
		);

		$meta_data = array();
		foreach ( $results as $row ) {
			$meta_data[ $row['meta_key'] ] = $row['meta_value'];
		}

		return $meta_data;
	}

	/**
	 * Update payment meta data
	 * 
	 * @param int $transaction_id
	 * @param array $meta_data
	 * @return bool
	 */
	public function update_payment_meta( $transaction_id, $meta_data ) {
		global $wpdb;
		$payments_meta_table = $wpdb->prefix . self::PAYMENTS_META_TABLE;

		foreach ( $meta_data as $meta_key => $meta_value ) {
			// Check if meta key already exists
			$existing = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM $payments_meta_table 
					WHERE transaction_id = %d AND meta_key = %s",
					$transaction_id,
					$meta_key
				)
			);

			if ( $existing ) {
				// Update existing meta
				$result = $wpdb->update(
					$payments_meta_table,
					array( 'meta_value' => $meta_value ),
					array( 
						'transaction_id' => $transaction_id,
						'meta_key' => $meta_key
					),
					array( '%s' ),
					array( '%d', '%s' )
				);
			} else {
				// Insert new meta
				$result = $wpdb->insert(
					$payments_meta_table,
					array(
						'transaction_id' => $transaction_id,
						'meta_key' => $meta_key,
						'meta_value' => $meta_value,
					),
					array( '%d', '%s', '%s' )
				);
			}

			if ( false === $result ) {
				return false;
			}
		}

		return true;
	}


	protected function get_payment_column_types() {
		$column_types = array(
			'transaction_id' => '%d',
			'registration_id' => '%d',
			'payment_date' => '%s',
			'invoice_id' => '%s',
			'payment_id' => '%s',
			'gateway' => '%s',
			'business' => '%s',
			'payment_status' => '%s',
			'payment_gross' => '%f',
			'currency_code' => '%s',
		);
		return $column_types;
	}
	protected function payment_type_exists( $column ) {
		$column_types = $this->get_payment_column_types();
		return ! empty( $column_types[ $column ] );
	}
	protected function payment_type( $column ) {
		$column_types = $this->get_payment_column_types();
		if ( empty( $column_types[ $column ] ) ) {
			return '%s';
		}
		return $column_types[ $column ];
	}

	public function create_or_update_registrations_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . self::REGISTRATIONS_TABLE;

		$max_index_length = 191;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            parent bigint(20) unsigned NOT NULL DEFAULT 0,
			event_id bigint(20) unsigned NOT NULL DEFAULT 0,
            series_id bigint(20) unsigned NOT NULL DEFAULT 0,
            registration_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            quantity int(10) unsigned NOT NULL DEFAULT 0,
			registration_type varchar(80) NOT NULL DEFAULT 'event',
            status varchar(20) NOT NULL DEFAULT 'pending',
            action_key varchar(20) NOT NULL DEFAULT '',
            confirmation_code varchar(20) NOT NULL DEFAULT '',
            new TINYINT(1) NOT NULL DEFAULT 1,
            meta_cache LONGTEXT NOT NULL,
			PRIMARY KEY  (id),
            KEY event_id (event_id),
            KEY series_id (series_id),
            KEY user_id (user_id),
            KEY registration_type (registration_type(80)),
            KEY event_id_type (event_id, registration_type(80))
			) $charset_collate;";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		\dbDelta($sql);

		return $this->registrations_table_exists();
	}

	public function create_or_update_registrations_meta_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . self::REGISTRATIONS_META_TABLE;

		$max_index_length = 191;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            registration_id bigint(20) unsigned NOT NULL DEFAULT 0,
			meta_key varchar(255) NOT NULL DEFAULT '',
            meta_value LONGTEXT NOT NULL,
			PRIMARY KEY  (id),
            KEY registration_id (registration_id),
            KEY meta_key (meta_key($max_index_length))
			) $charset_collate;";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		\dbDelta($sql);

		return $this->registrations_meta_table_exists();
	}

	public function create_or_update_payments_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . self::PAYMENTS_TABLE;

		$max_index_length = 191;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
            transaction_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            registration_id bigint(20) unsigned NOT NULL DEFAULT 0,
            payment_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            invoice_id varchar(255) NOT NULL DEFAULT '',
            payment_id varchar(255) NOT NULL DEFAULT '',
			gateway varchar(255) NOT NULL DEFAULT '',
            business varchar(255) NOT NULL DEFAULT '',
            payment_status varchar(20) NOT NULL DEFAULT 'pending',
            payment_gross FLOAT(10,2) NOT NULL,
            currency_code VARCHAR(5) NOT NULL DEFAULT '',
			PRIMARY KEY  (transaction_id),
            KEY registration_id (registration_id),
            KEY payment_status (payment_status)
			) $charset_collate;";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		\dbDelta($sql);

		return $this->payments_table_exists();
	}

	public function create_or_update_payments_meta_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . self::PAYMENTS_META_TABLE;

		$max_index_length = 191;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            transaction_id bigint(20) unsigned NOT NULL DEFAULT 0,
			meta_key varchar(255) NOT NULL DEFAULT '',
            meta_value LONGTEXT NOT NULL,
			PRIMARY KEY  (id),
            KEY transaction_id (transaction_id),
            KEY meta_key (meta_key($max_index_length))
			) $charset_collate;";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		\dbDelta($sql);

		return $this->payments_meta_table_exists();
	}

	protected function registrations_table_exists()
	{
		global $wpdb;
		$table_name = $wpdb->prefix . self::REGISTRATIONS_TABLE;

		// Table name is a class constant with prefix - safe from SQL injection
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name );
	}

	protected function registrations_meta_table_exists()
	{
		global $wpdb;
		$table_name = $wpdb->prefix . self::REGISTRATIONS_META_TABLE;

		// Table name is a class constant with prefix - safe from SQL injection
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name );
	}

	protected function payments_table_exists()
	{
		global $wpdb;
		$table_name = $wpdb->prefix . self::PAYMENTS_TABLE;

		// Table name is a class constant with prefix - safe from SQL injection
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name );
	}

	protected function payments_meta_table_exists()
	{
		global $wpdb;
		$table_name = $wpdb->prefix . self::PAYMENTS_META_TABLE;

		// Table name is a class constant with prefix - safe from SQL injection
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name );
	}

	protected function escaped_where( $where, $prefix = '' ) {
		$clauses = array();

		if ( ! empty( $where ) ) {
			foreach ( $where as $item ) {
				if ( $item['compare'] === '=' ) {
					if ( $item['column'] === '1' ) {
						$clauses[] = '1 = 1';
					} elseif ( $item['type'] === 'string' ) {
						$clauses[] = $prefix . sanitize_key( $item['column'] ) . ' = "' . esc_sql( trim( $item['value'], '"' ) ) . '"';
					} else {
						if ( is_array( $item['value'] ) ) {
							$clauses[] = $prefix . sanitize_key( $item['column'] ) . ' IN (' . $this->mysql_sanitize_integer_in_clause( $item['value'] ) . ')';
						} else {
							$clauses[] = $prefix . sanitize_key( $item['column'] ) . ' = ' . (int) $item['value'];
						}
					}
				} elseif ( $item['compare'] === '!=' ) {
					if ( $item['type'] === 'string' ) {
						$clauses[] = $prefix . sanitize_key( $item['column'] ) . " NOT IN ('" . $this->mysql_escape_in_clause( trim( $item['value'], '"' ) ) . "')";
					} else {
						$clauses[] = $prefix . sanitize_key( $item['column'] ) . ' NOT IN (' . $this->mysql_sanitize_integer_in_clause( $item['value'] ) . ')';
					}
				} elseif ( $item['compare'] === '>' ) {
					if ( $item['type'] === 'date' ) {
						$clauses[] = $prefix . sanitize_key( $item['column'] ) . ' > "' .  esc_sql( $item['value'] ) . '"';
					} else {
						$clauses[] = $prefix . sanitize_key( $item['column'] ) . ' > ' . (int) $item['value'];
					}
				}
			}
		}

		if ( ! empty( $clauses ) ) {
			return implode( ' AND ', $clauses );
		}

		return '';
	}

	/**
	 * @param $string_clause
	 *
	 * @return string
	 * @since 2.19.6
	 */
	private function mysql_escape_in_clause( $string_clause ) {
		$clause_array = explode( ',', $string_clause );

		$escaped_array = array();
		foreach ( $clause_array as $single_in_term ) {
			$trimmed_spaces        = trim( $single_in_term ); // trim white space
			$trimmed_single_quotes = trim( $trimmed_spaces, "'" ); // trim single quotes
			$escaped_array[]       = esc_sql( $trimmed_single_quotes ); // escape
		}

		return implode( "','", $escaped_array );
	}

	/**
	 * @param $string_clause_or_array
	 *
	 * @return int|string
	 * @since 2.19.6
	 */
	private function mysql_sanitize_integer_in_clause( $string_clause_or_array ) {
		if ( is_array( $string_clause_or_array ) ) {
			$clause_array = $string_clause_or_array;
		} elseif ( is_int( $string_clause_or_array ) ) {
			return intval( $string_clause_or_array );
		} elseif ( is_string( $string_clause_or_array ) ) {
			$clause_array = explode( ',', $string_clause_or_array );
		} else {
			return 0; // not something we can safely make an IN statement with
		}

		$sanitized_integer_array = array();
		foreach ( $clause_array as $single_in_term ) {
			$single_in_term            = trim( $single_in_term ); // trim white space
			$sanitized_integer_array[] = intval( $single_in_term ); // typecast int
		}

		return implode( ',', $sanitized_integer_array );
	}

	public function registration_count_query($where, $include_canceled = false) {
		global $wpdb;
		$table = $wpdb->prefix . self::REGISTRATIONS_TABLE;

		$escaped_where = $this->escaped_where($where);

		if (empty($escaped_where)) {
			return 0;
		}

		if (!$include_canceled) {
			$escaped_where .= ' AND status != "canceled"';
		}

		$sql = "SELECT COUNT(*) FROM $table AS r 
				WHERE $escaped_where";

		// We can't use prepare here as we've already built the query with sanitized components
		// phpcs:ignore 
		$count = $wpdb->get_var($sql);

		return intval($count);
	}

	public function get_registration_quantity_for_period($start_date, $end_date = null) {
		global $wpdb;
		$table = $wpdb->prefix . self::REGISTRATIONS_TABLE;

		if ($end_date === null) {
			return (int) $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT COALESCE(SUM(quantity), 0) FROM {$table} 
					WHERE registration_date > %s 
					AND status != 'canceled'",
					$start_date
				)
			);
		} else {
			return (int) $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT COALESCE(SUM(quantity), 0) FROM {$table} 
					WHERE registration_date > %s 
					AND registration_date <= %s 
					AND status != 'canceled'",
					$start_date,
					$end_date
				)
			);
		}
	}

	/**
	 * Get registration counts by status for a specific event
	 *
	 * @param int $event_id The event ID to get counts for
	 * @return array Array of counts by status
	 */
	public function get_registration_status_counts($event_or_series_id, $column = 'event_id') {
		global $wpdb;
		$table = $wpdb->prefix . self::REGISTRATIONS_TABLE;
		
		if ( $column === 'event_id' ) {
			$sql = "SELECT status, SUM(quantity) as count FROM {$table} 
				WHERE event_id = %d 
				GROUP BY status";
		} else {
			$sql = "SELECT status, SUM(quantity) as count FROM {$table} 
				WHERE series_id = %d 
				GROUP BY status";
		}
		$results = $wpdb->get_results( $wpdb->prepare( $sql, $event_or_series_id ) );
		
		// Initialize default counts
		$counts = [
			'confirmed' => 0,
			'pending' => 0,
			'canceled' => 0
		];
		
		// Fill in actual counts
		foreach ($results as $row) {
			if (isset($counts[$row->status])) {
				$counts[$row->status] = (int) $row->count;
			}
		}
		
		return $counts;
	}

	public function insert_registration_meta($registration_id, $meta_data) {
		global $wpdb;
		$table = $wpdb->prefix . self::REGISTRATIONS_META_TABLE;
		
		foreach ($meta_data as $key => $value) {
			// Handle array values (for checkbox fields)
			if (is_array($value)) {
				foreach ($value as $single_value) {
					// Direct DB call is necessary for custom tables that don't have WordPress API functions
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
					$wpdb->insert(
						$table,
						array(
							'registration_id' => $registration_id,
							'meta_key' => $key,
							'meta_value' => $single_value
						),
						array('%d', '%s', '%s')
					);
				}
			} else {
				// Direct DB call is necessary for custom tables that don't have WordPress API functions
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->insert(
					$table,
					array(
						'registration_id' => $registration_id,
						'meta_key' => $key,
						'meta_value' => $value
					),
					array('%d', '%s', '%s')
				);
			}
		}
	}

	public function update_registration_meta($registration_id, $meta_data) {
		global $wpdb;
		$table = $wpdb->prefix . self::REGISTRATIONS_META_TABLE;
		
		foreach ($meta_data as $key => $value) {
			// Delete existing meta entries for this key
			// Direct DB call is necessary for custom tables that don't have WordPress API functions
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->delete(
				$table,
				array(
					'registration_id' => $registration_id,
					'meta_key' => $key
				),
				array('%d', '%s')
			);
			
			// Insert new values
			if (is_array($value)) {
				foreach ($value as $single_value) {
					// Direct DB call is necessary for custom tables that don't have WordPress API functions
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
					$wpdb->insert(
						$table,
						array(
							'registration_id' => $registration_id,
							'meta_key' => $key,
							'meta_value' => $single_value
						),
						array('%d', '%s', '%s')
					);
				}
			} else {
				// Direct DB call is necessary for custom tables that don't have WordPress API functions
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->insert(
					$table,
					array(
						'registration_id' => $registration_id,
						'meta_key' => $key,
						'meta_value' => $value
					),
					array('%d', '%s', '%s')
				);
			}
		}
	}

	public function create_or_update_events_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . self::EVENTS_TABLE;
		
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			timing_id INT(11) NOT NULL AUTO_INCREMENT,
			post_id INT(11) NOT NULL,
			start_datetime_local DATETIME NOT NULL,
			end_datetime_local DATETIME NOT NULL, 
			start_datetime_utc DATETIME NOT NULL,
			end_datetime_utc DATETIME NOT NULL,
			timezone VARCHAR(50) NOT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (timing_id),
			KEY post_id (post_id),
			KEY start_datetime_utc (start_datetime_utc)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);

		return $this->events_table_exists();
	}

	public function create_or_update_event_series_relationships_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . self::EVENT_SERIES_RELATIONSHIPS_TABLE;
		
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			relationship_id INT(11) NOT NULL AUTO_INCREMENT,
			series_id INT(11) NOT NULL,
			post_id INT(11) NOT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (relationship_id),
			KEY series_id (series_id),
			KEY post_id (post_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);

		return $this->event_series_relationships_table_exists();
	}

	public function create_or_update_event_venue_relationships_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . self::EVENT_VENUE_RELATIONSHIPS_TABLE;
		
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			relationship_id INT(11) NOT NULL AUTO_INCREMENT,
			event_id INT(11) NOT NULL,
			venue_id INT(11) NOT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (relationship_id),
			UNIQUE KEY event_venue (event_id, venue_id),
			KEY event_id (event_id),
			KEY venue_id (venue_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);

		return $this->event_venue_relationships_table_exists();
	}

	public function create_or_update_event_organizer_relationships_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . self::EVENT_ORGANIZER_RELATIONSHIPS_TABLE;
		
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			relationship_id INT(11) NOT NULL AUTO_INCREMENT,
			event_id INT(11) NOT NULL,
			organizer_id INT(11) NOT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (relationship_id),
			UNIQUE KEY event_organizer (event_id, organizer_id),
			KEY event_id (event_id),
			KEY organizer_id (organizer_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);

		return $this->event_organizer_relationships_table_exists();
	}

	protected function events_table_exists() {
		global $wpdb;
		$table_name = $wpdb->prefix . self::EVENTS_TABLE;
		
		// Table name is a class constant with prefix - safe from SQL injection
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name);
	}

	protected function event_series_relationships_table_exists() {
		global $wpdb;
		$table_name = $wpdb->prefix . self::EVENT_SERIES_RELATIONSHIPS_TABLE;
		
		// Table name is a class constant with prefix - safe from SQL injection
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name);
	}

	protected function event_venue_relationships_table_exists() {
		global $wpdb;
		$table_name = $wpdb->prefix . self::EVENT_VENUE_RELATIONSHIPS_TABLE;
		
		// Table name is a class constant with prefix - safe from SQL injection
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name);
	}

	protected function event_organizer_relationships_table_exists() {
		global $wpdb;
		$table_name = $wpdb->prefix . self::EVENT_ORGANIZER_RELATIONSHIPS_TABLE;
		
		// Table name is a class constant with prefix - safe from SQL injection
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name);
	}

	/**
	 * Check if a relationship exists between series and event
	 * 
	 * @param int $series_id The series ID
	 * @param int $event_id The event ID
	 * @return bool Whether the relationship exists
	 */
	public function series_relationship_exists($series_id, $event_id) {
		global $wpdb;
		$table = $wpdb->prefix . self::EVENT_SERIES_RELATIONSHIPS_TABLE;
		
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT COUNT(*) FROM $table WHERE series_id = %d AND post_id = %d",
				$series_id, 
				$event_id
			)
		);
		
		return !empty($exists);
	}
	
	/**
	 * Insert a relationship between series and event
	 * 
	 * @param int $series_id The series ID
	 * @param int $event_id The event ID
	 * @return bool|int False on failure, number of rows affected on success
	 */
	public function insert_series_relationship($series_id, $event_id) {
		global $wpdb;
		$table = $wpdb->prefix . self::EVENT_SERIES_RELATIONSHIPS_TABLE;
		
		// Direct DB call is necessary for custom tables that don't have WordPress API functions
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $wpdb->insert(
			$table,
			array(
				'series_id' => $series_id,
				'post_id' => $event_id
			),
			array('%d', '%d')
		);
	}
	
	/**
	 * Delete a relationship between series and event
	 * 
	 * @param int $series_id The series ID
	 * @param int $event_id The event ID
	 * @return bool|int False on failure, number of rows affected on success
	 */
	public function delete_series_relationship($series_id, $event_id) {
		global $wpdb;
		$table = $wpdb->prefix . self::EVENT_SERIES_RELATIONSHIPS_TABLE;
		
		// Direct DB call is necessary for custom tables that don't have WordPress API functions
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $wpdb->delete(
			$table,
			array(
				'series_id' => $series_id,
				'post_id' => $event_id
			),
			array('%d', '%d')
		);
	}
	
	/**
	 * Delete all relationships for a series
	 * 
	 * @param int $series_id The series ID
	 * @return bool|int False on failure, number of rows affected on success
	 */
	public function delete_all_series_relationships($series_id) {
		global $wpdb;
		$table = $wpdb->prefix . self::EVENT_SERIES_RELATIONSHIPS_TABLE;
		
		// Direct DB call is necessary for custom tables that don't have WordPress API functions
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $wpdb->delete(
			$table,
			array('series_id' => $series_id),
			array('%d')
		);
	}
	
	/**
	 * Get all event IDs in a series from the relationships table
	 * 
	 * @param int $series_id The series ID
	 * @return array Array of event post IDs
	 */
	public function get_series_event_ids_from_table($series_id) {
		global $wpdb;
		$table = $wpdb->prefix . self::EVENT_SERIES_RELATIONSHIPS_TABLE;
		
		// Direct DB call is necessary for custom tables that don't have WordPress API functions
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $wpdb->get_col(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT post_id FROM $table WHERE series_id = %d",
				$series_id
			)
		);
	}
	
	/**
	 * Delete event data from custom tables
	 * 
	 * @param int $event_id The event ID
	 * @return bool Success status
	 */
	public function delete_event_custom_data($event_id) {
		global $wpdb;
		$table = $wpdb->prefix . self::EVENTS_TABLE;	
		
		// Direct DB call is necessary for custom tables that don't have WordPress API functions
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $wpdb->delete(
			$table,
			array('post_id' => $event_id),
			array('%d')
		);
	}

	/**
	 * Start a database transaction
	 */
	public function start_transaction() {
		global $wpdb;
		$wpdb->query('START TRANSACTION');
	}
	
	/**
	 * Commit a database transaction
	 */
	public function commit_transaction() {
		global $wpdb;
		$wpdb->query('COMMIT');
	}
	
	/**
	 * Rollback a database transaction
	 */
	public function rollback_transaction() {
		global $wpdb;
		$wpdb->query('ROLLBACK');
	}

	/**
	 * Update or insert event timing data
	 */
	public function sync_event_timing($post_id, $start_date, $end_date, $timezone) {
		global $wpdb;
		$table = $wpdb->prefix . self::EVENTS_TABLE;

		// Get existing timing record
		$existing = $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM $table WHERE post_id = %d",
				$post_id
			)
		);

		// Convert dates to UTC for storage
		$start_utc = $start_date->utc_timestamp();
		$end_utc = $end_date->utc_timestamp();

		$data = array(
			'post_id' => $post_id,
			'start_datetime_local' => $start_date->format('Y-m-d H:i:s'),
			'end_datetime_local' => $end_date->format('Y-m-d H:i:s'),
			'start_datetime_utc' => gmdate('Y-m-d H:i:s', $start_utc),
			'end_datetime_utc' => gmdate('Y-m-d H:i:s', $end_utc),
			'timezone' => $timezone
		);

		if ($existing) {
			// Update existing record
			// Direct DB call is necessary for custom tables that don't have WordPress API functions
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			return $wpdb->update(
				$table,
				$data,
				array('timing_id' => $existing->timing_id),
				array('%s', '%s', '%s', '%s', '%s'),
				array('%d')
			);
		} else {
			// Insert new record
			// Direct DB call is necessary for custom tables that don't have WordPress API functions
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			return $wpdb->insert(
				$table,
				$data,
				array('%d', '%s', '%s', '%s', '%s', '%s')
			);
		}
	}

	/**
	 * Delete event timing data
	 */
	public function delete_event_timing($post_id) {
		global $wpdb;
		$table = $wpdb->prefix . self::EVENTS_TABLE;
		
		// Direct DB call is necessary for custom tables that don't have WordPress API functions
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $wpdb->delete(
			$table,
			array('post_id' => $post_id),
			array('%d')
		);
	}

	public function get_series_event_count($series_id) {
		global $wpdb;
		$table = $wpdb->prefix . self::EVENT_SERIES_RELATIONSHIPS_TABLE;
		
		// Direct DB call is necessary for custom tables that don't have WordPress API functions
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$count = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT COUNT(*) FROM $table WHERE series_id = %d",
				$series_id
			)
		);
		
		return absint($count);
	}

	/**
	 * Get the series ID for an event from the relationships table
	 * 
	 * @param int $event_id The event ID
	 * @return int|false The series ID if found, false otherwise
	 */
	public function get_series_id_for_event($event_id) {
		global $wpdb;
		$table = $wpdb->prefix . self::EVENT_SERIES_RELATIONSHIPS_TABLE;
		
		// Direct DB call is necessary for custom tables that don't have WordPress API functions
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$series_id = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT series_id FROM $table WHERE post_id = %d LIMIT 1",
				$event_id
			)
		);
		
		return $series_id ? (int)$series_id : false;
	}

	/**
	 * Clean up orphaned records in the events and relationships tables
	 * Removes any rows where the post_id, venue_id, or organizer_id doesn't exist in wp_posts table
	 * 
	 * @param int $limit Maximum number of records to process per table. Defaults to 1000.
	 * @return array Array containing counts of deleted records from each table
	 */
	public function cleanup_orphaned_event_records($limit = 1000) {
		global $wpdb;
		$events_table = $wpdb->prefix . self::EVENTS_TABLE;
		$series_relationships_table = $wpdb->prefix . self::EVENT_SERIES_RELATIONSHIPS_TABLE;
		$venue_relationships_table = $wpdb->prefix . self::EVENT_VENUE_RELATIONSHIPS_TABLE;
		$organizer_relationships_table = $wpdb->prefix . self::EVENT_ORGANIZER_RELATIONSHIPS_TABLE;
		
		$deleted_counts = array(
			'events' => 0,
			'series_relationships' => 0,
			'venue_relationships' => 0,
			'organizer_relationships' => 0
		);

		$limit = absint($limit);
		if ($limit < 1) {
			$limit = 1000; // Ensure a valid limit
		}


		// Delete orphaned records from events table
		// Direct DB call is necessary for custom tables that don't have WordPress API functions
		// phpcs:ignore
		$deleted_counts['events'] = $wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"DELETE FROM $events_table 
				WHERE post_id NOT IN (
					SELECT ID FROM {$wpdb->posts}
				)
				LIMIT %d",
				$limit
			)
		);

		// Delete orphaned records from series relationships table where post_id doesn't exist
		// Direct DB call is necessary for custom tables that don't have WordPress API functions
		// phpcs:ignore
		$deleted_counts['series_relationships'] = $wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"DELETE FROM $series_relationships_table 
				WHERE post_id NOT IN (
					SELECT ID FROM {$wpdb->posts}
				)
				LIMIT %d",
				$limit
			)
		);

		// Also delete relationships where series_id doesn't exist
		// phpcs:ignore
		$deleted_counts['series_relationships'] += $wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"DELETE FROM $series_relationships_table 
				WHERE series_id NOT IN (
					SELECT ID FROM {$wpdb->posts}
				)
				LIMIT %d",
				$limit
			)
		);

		// Delete venue relationships where event doesn't exist
		// phpcs:ignore 
		$deleted_counts['venue_relationships'] = $wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"DELETE FROM $venue_relationships_table 
				WHERE event_id NOT IN (
					SELECT ID FROM {$wpdb->posts}
				)
				LIMIT %d",
				$limit
			)
		);

		// Delete venue relationships where venue doesn't exist
		// phpcs:ignore 
		$deleted_counts['venue_relationships'] += $wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"DELETE FROM $venue_relationships_table 
				WHERE venue_id NOT IN (
					SELECT ID FROM {$wpdb->posts}
				)
				LIMIT %d",
				$limit
			)
		);


		// Delete organizer relationships where event doesn't exist
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$deleted_counts['organizer_relationships'] = $wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"DELETE FROM $organizer_relationships_table 
				WHERE event_id NOT IN (
					SELECT ID FROM {$wpdb->posts}
				)
				LIMIT %d",
				$limit
			)
		);

		// Delete organizer relationships where organizer doesn't exist
		// phpcs:ignore 
		$deleted_counts['organizer_relationships'] += $wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"DELETE FROM $organizer_relationships_table 
				WHERE organizer_id NOT IN (
					SELECT ID FROM {$wpdb->posts}
				)
				LIMIT %d",
				$limit
			)
		);

		return $deleted_counts;
	}

	/**
	 * Sync venue relationship for an event
	 * 
	 * @param int $event_id The event ID
	 * @param int $venue_id The venue ID
	 * @return bool|int False on failure, number of rows affected on success
	 */
	public function sync_event_venue_relationship($event_id, $venue_id) {
		global $wpdb;
		$table = $wpdb->prefix . self::EVENT_VENUE_RELATIONSHIPS_TABLE;
		
		// Check if the relationship already exists and is the same
		$current_venue_id = $this->get_event_venue_id($event_id);
		
		// If the venue ID is the same (including both being empty/false), no update needed
		if ($current_venue_id === ($venue_id ? (int)$venue_id : false)) {
			return true;
		}
		
		// Delete existing relationships for this event
		// Direct DB call is necessary for custom tables that don't have WordPress API functions
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->delete(
			$table,
			array('event_id' => $event_id),
			array('%d')
		);
		
		// Insert new relationship
		if ($venue_id) {
			// Direct DB call is necessary for custom tables that don't have WordPress API functions
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			return $wpdb->insert(
				$table,
				array(
					'event_id' => $event_id,
					'venue_id' => $venue_id
				),
				array('%d', '%d')
			);
		}
		
		return true;
	}

	/**
	 * Sync organizer relationship for an event
	 * 
	 * @param int $event_id The event ID
	 * @param int|array $organizer_ids Single organizer ID or array of organizer IDs
	 * @return bool|int False on failure, number of rows affected on success
	 */
	public function sync_event_organizer_relationship($event_id, $organizer_ids) {
		global $wpdb;
		$table = $wpdb->prefix . self::EVENT_ORGANIZER_RELATIONSHIPS_TABLE;
		
		// Normalize input to array and remove any empty values
		$organizer_ids = array_filter((array)$organizer_ids);
		sort($organizer_ids); // Sort for consistent comparison
		
		// Get current organizer IDs
		$current_organizer_ids = $this->get_event_organizer_ids($event_id);
		sort($current_organizer_ids); // Sort for consistent comparison
		
		// If the organizer IDs are exactly the same, no update needed
		if ($current_organizer_ids === $organizer_ids) {
			return true;
		}
		
		// Delete existing relationships for this event
		// Direct DB call is necessary for custom tables that don't have WordPress API functions
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->delete(
			$table,
			array('event_id' => $event_id),
			array('%d')
		);
		
		// If no organizers to add, we're done
		if (empty($organizer_ids)) {
			return true;
		}
		
		// Insert new relationships
		$success = true;
		foreach ($organizer_ids as $organizer_id) {
			if ($organizer_id) {
				// Direct DB call is necessary for custom tables that don't have WordPress API functions
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$result = $wpdb->insert(
					$table,
					array(
						'event_id' => $event_id,
						'organizer_id' => $organizer_id
					),
					array('%d', '%d')
				);
				if ($result === false) {
					$success = false;
				}
			}
		}
		
		return $success;
	}

	/**
	 * Get venue ID for an event
	 * 
	 * @param int $event_id The event ID
	 * @return int|false The venue ID if found, false otherwise
	 */
	public function get_event_venue_id($event_id) {
		global $wpdb;
		$table = $wpdb->prefix . self::EVENT_VENUE_RELATIONSHIPS_TABLE;
		
		// Direct DB call is necessary for custom tables that don't have WordPress API functions
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$venue_id = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT venue_id FROM $table WHERE event_id = %d LIMIT 1",
				$event_id
			)
		);
		
		return $venue_id ? (int)$venue_id : false;
	}

	/**
	 * Get organizer IDs for an event
	 * 
	 * @param int $event_id The event ID
	 * @return array Array of organizer IDs
	 */
	public function get_event_organizer_ids($event_id) {
		global $wpdb;
		$table = $wpdb->prefix . self::EVENT_ORGANIZER_RELATIONSHIPS_TABLE;
		
		// Direct DB call is necessary for custom tables that don't have WordPress API functions
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $wpdb->get_col(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT organizer_id FROM $table WHERE event_id = %d ORDER BY relationship_id",
				$event_id
			)
		);
	}

	/**
	 * Get all event IDs associated with a venue
	 * 
	 * @param int $venue_id The venue ID
	 * @return array Array of event IDs
	 */
	public function get_events_by_venue_id($venue_id) {
		global $wpdb;
		$table = $wpdb->prefix . self::EVENT_VENUE_RELATIONSHIPS_TABLE;
		
		// Direct DB call is necessary for custom tables that don't have WordPress API functions
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $wpdb->get_col(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT event_id FROM $table WHERE venue_id = %d",
				$venue_id
			)
		);
	}

	/**
	 * Get all event IDs associated with an organizer
	 * 
	 * @param int $organizer_id The organizer ID
	 * @return array Array of event IDs
	 */
	public function get_events_by_organizer_id($organizer_id) {
		global $wpdb;
		$table = $wpdb->prefix . self::EVENT_ORGANIZER_RELATIONSHIPS_TABLE;
		
		// Direct DB call is necessary for custom tables that don't have WordPress API functions
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $wpdb->get_col(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT event_id FROM $table WHERE organizer_id = %d",
				$organizer_id
			)
		);
	}

	/**
	 * Remove venue from all associated events' post meta
	 * 
	 * @param int $venue_id The venue ID
	 * @return void
	 */
	public function remove_venue_from_events_meta($venue_id) {
		$event_ids = $this->get_events_by_venue_id($venue_id);
		foreach ($event_ids as $event_id) {
			delete_post_meta($event_id, 'evge_venue');
		}
	}

	/**
	 * Remove organizer from all associated events' post meta
	 * 
	 * @param int $organizer_id The organizer ID
	 * @return void
	 */
	public function remove_organizer_from_events_meta($organizer_id) {
		$event_ids = $this->get_events_by_organizer_id($organizer_id);
		foreach ($event_ids as $event_id) {
			$organizer_ids = get_post_meta($event_id, 'evge_organizer', false);
			$organizer_ids = array_diff($organizer_ids, array($organizer_id));
			
			if (empty($organizer_ids)) {
				delete_post_meta($event_id, 'evge_organizer');
			} else {
				delete_post_meta($event_id, 'evge_organizer');
				foreach ($organizer_ids as $remaining_organizer_id) {
					add_post_meta($event_id, 'evge_organizer', $remaining_organizer_id);
				}
			}
		}
	}

	/**
	 * Get registration ID by invoice ID from payments table
	 * 
	 * @param string $invoice_id
	 * @return int|false Registration ID if found, false otherwise
	 */
	public function get_registration_id_by_invoice_id( $invoice_id ) {
		global $wpdb;
		$payments_table = $wpdb->prefix . self::PAYMENTS_TABLE;

		// Direct DB call is necessary for custom tables that don't have WordPress API functions
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$registration_id = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT registration_id FROM $payments_table WHERE invoice_id = %s LIMIT 1",
				$invoice_id
			)
		);

		return $registration_id ? absint( $registration_id ) : false;
	}
}
