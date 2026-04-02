<?php

namespace WPEventGenius\Admin\Actions;

use WPEventGenius\Common\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class PaymentRecord {

	protected $transaction_id;

	protected $database;


	protected $payment_data;

	public function __construct( Database $db, $transaction_id ) {
		$this->database = $db;
		$this->transaction_id = $transaction_id;
		$this->payment_data = $this->database->query_payment_record( $this->transaction_id );
	}

	public function get_payment_data() {
		if ( empty( $this->payment_data ) ) {
			return array();
		}
		return $this->payment_data;
	}

	public function delete() {
		$this->database->delete_payment( $this->transaction_id );
	}

	public function create( $registration_id, $column_value ) {
		$this->database->insert_payment( $registration_id, $column_value );
	}

	public function update( $standard_data ) {
		$this->database->update_payment( $this->transaction_id, $standard_data );
	}


	/**
	 * Add webhook event to payment history
	 * 
	 * @param array $webhook_data
	 */
	public function add_webhook_event( $webhook_data ) {
		$to_insert = array(
			'webhook_event' => wp_json_encode( $webhook_data )
		);
		
		// Store webhook event as individual meta entry
		return $this->insert_payment_meta( $this->transaction_id, $to_insert );
	}
}