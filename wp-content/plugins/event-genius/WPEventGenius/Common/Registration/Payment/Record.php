<?php
namespace WPEventGenius\Common\Registration\Payment;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class Record {

	protected $gateway_name;

	protected $payment_date;

	protected $invoice_id;

	protected $payment_id;

	protected $business;

	protected $payment_status;

	protected $payment_gross;

	protected $currency_code;

	protected $transaction_id;

	public function __construct( $args ) {
		$this->set_gateway_name( $args['gateway'] );
		$this->set_payment_date( $args['payment_date'] );
		$this->set_invoice_id( $args['invoice_id'] );
		$this->set_payment_id( $args['payment_id'] );
		$this->set_business( $args['business'] );
		$this->set_payment_status( $args['payment_status'] );
		$this->set_payment_gross( $args['payment_gross'] );
		$this->set_currency_code( $args['currency_code'] );
		if ( ! empty( $args['transaction_id'] ) ) {
			$this->set_transaction_id( $args['transaction_id'] );

		}
	}

	public function set_gateway_name( $gateway_name ) {
		$this->gateway_name = $gateway_name;
	}

	public function set_payment_date( $payment_date ) {
		$this->payment_date = $payment_date;
	}

	public function set_invoice_id( $invoice_id ) {
		$this->invoice_id = $invoice_id;
	}

	public function set_payment_id( $payment_id ) {
		$this->payment_id = $payment_id;
	}

	public function set_business( $business ) {
		$this->business = $business;
	}

	public function set_payment_status( $payment_status ) {
		$this->payment_status = $payment_status;
	}

	public function set_payment_gross( $payment_gross ) {
		$this->payment_gross = $payment_gross;
	}

	public function set_currency_code( $currency_code ) {
		$this->currency_code = $currency_code;
	}

	public function set_transaction_id( $transaction_id ) {
		$this->transaction_id = $transaction_id;
	}

	public function get_gateway_name() {
		return $this->gateway_name;
	}

	public function get_payment_date() {
		return $this->payment_date;
	}

	public function get_invoice_id() {
		return $this->invoice_id;
	}

	public function get_payment_id() {
		return $this->payment_id;
	}

	public function get_business() {
		return $this->business;
	}

	public function get_payment_status() {
		return $this->payment_status;
	}

	public function get_payment_gross() {
		return $this->payment_gross;
	}

	public function get_currency_code() {
		return $this->currency_code;
	}

	public function get_transaction_id() {
		return $this->transaction_id;
	}

	public function get_payment_data() {
		return array(
			'transaction_id' => $this->get_transaction_id(),
			'gateway' => $this->get_gateway_name(),
			'payment_date' => $this->get_payment_date(),
			'invoice_id' => $this->get_invoice_id(),
			'payment_id' => $this->get_payment_id(),
			'business' => $this->get_business(),
			'payment_status' => $this->get_payment_status(),
			'payment_gross' => $this->get_payment_gross(),
			'currency_code' => $this->get_currency_code(),
		);

	}

}