<?php
namespace WPEventGenius\Common\Registration\Payment\Cart;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

abstract class Item {
	/**
	 * @var string
	 */
	protected $label;
	/**
	 * @var int
	 */
	protected $quantity;
	/**
	 * @var float
	 */
	protected $amount;

	protected $meta;

	public function __construct( $label, $quantity, $amount, $meta ) {
		$this->label = $label;
		$this->quantity = $quantity;
		$this->amount = $amount;
		$this->meta = $meta;
	}

	public function get_label() {
		return $this->label;
	}

	public function get_quantity() {
		return $this->quantity;
	}

	public function get_amount() {
		return $this->amount;
	}

	public function get_meta() {
		return $this->meta;
	}

	public function get_calculated_amount() {
		return (float)$this->amount * (float)$this->quantity;
	}


}