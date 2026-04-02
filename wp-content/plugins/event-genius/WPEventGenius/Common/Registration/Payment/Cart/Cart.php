<?php
namespace WPEventGenius\Common\Registration\Payment\Cart;

use WPEventGenius\Common\Registration\Payment\Gateways\MiscItem;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class Cart {

	protected $line_items;

	protected $misc_items;

	protected $discount_items;

	public function __construct() {
		$this->line_items = array();
		$this->misc_items = array();
		$this->discount_items = array();
	}

	public function get_line_items() {
		return $this->line_items;
	}

	public function get_misc_items() {
		return $this->misc_items;
	}

	public function get_discount_items() {
		return $this->discount_items;
	}

	public function add_line_item( $slug, LineItem $line_item ) {
		$this->line_items[ $slug ] = $line_item;
	}

	public function add_misc_item( $slug, MiscItem $misc_item ) {
		$this->misc_items[ $slug ] = $misc_item;
	}

	public function add_discount_item( $slug, DiscountItem $discount_item ) {
		$this->discount_items[ $slug ] = $discount_item;
	}

	public function calculate_subtotal() {
		$cost = 0;
		foreach ( $this->line_items as $line_item ) {
			$cost += $line_item->get_calculated_amount();
		}
		foreach ( $this->discount_items as $discount_item ) {
			$cost -= $discount_item->get_calculated_amount();
		}
		return round( $cost, 2 );
	}

	public function calculate_total() {
		$cost = $this->calculate_subtotal();
		foreach ( $this->misc_items as $misc_item ) {
			$cost += $misc_item->get_calculated_amount();
		}
		return round( $cost, 2 );
	}


}
