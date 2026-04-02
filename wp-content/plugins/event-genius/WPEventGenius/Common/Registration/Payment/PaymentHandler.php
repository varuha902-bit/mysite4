<?php
namespace WPEventGenius\Common\Registration\Payment;


use WPEventGenius\Common\Registration\Payment\Cart\Cart;
use WPEventGenius\Common\Registration\Payment\Cart\DiscountItem;
use WPEventGenius\Common\Registration\Payment\Cart\LineItem;
use WPEventGenius\Common\Registration\Payment\Gateways\MiscItem;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class PaymentHandler {

	/**
	 * @var Cart
	 */
	protected $cart;

	protected $status;

	protected $currency_code;

	public function __construct( Cart $cart ) {
		$this->cart = $cart;
	}

	public function set_currency_code( $currency_code ) {
		$this->currency_code = $currency_code;
	}

	public function calculate_subtotal(){
		return $this->cart->calculate_subtotal();
	}

	public function calculate_total(){
		return $this->cart->calculate_total();
	}

	public function set_status( $status ){
		$this->status = $status;
	}

	public function get_status(){
		return $this->status;
	}

	public function get_currency_code(){
		return $this->currency_code;
	}

	public function set_invoice(){}

	public function get_invoice(){}

	public function add_line_item( $key, $line_item ){
		$this->cart->add_line_item( $key, $line_item );
	}

	public function add_misc_item( $key, $misc_item ){
		$this->cart->add_misc_item( $key, $misc_item );
	}

	public function add_discount_item( $key, $misc_item ){
		$this->cart->add_discount_item( $key, $misc_item );
	}
	public function cart(){
		return $this->cart;
	}

	private function discount_applies( $rule, $payment_data ) {

		$data_set = $rule['data_group'] === 'user_data' ? $payment_data['user_data'] : $payment_data['transaction'];

		if ( isset( $data_set[ $rule['data_key'] ] ) && $rule['relationship'] === '>' ) {
			return ( (float)$data_set[ $rule['data_key'] ] > (float)$rule['compare'] );
		} elseif ( isset( $data_set[ $rule['data_key'] ] ) && $rule['relationship'] === '<' ) {
			return ( (float)$data_set[ $rule['data_key'] ] < (float)$rule['compare'] );
		} elseif ( isset( $data_set[ $rule['data_key'] ] ) && $rule['relationship'] === '=' ) {
			return ( $data_set[ $rule['data_key'] ] === $rule['compare'] );
		}

		return false;
	}

	private function calculate_discount( $atts, $line_item ) {
		$line_item_cost = (float) $line_item['cost'] * $line_item['quantity'];

		if ( $atts['type'] === 'percent' ) {
			$discount = round( $line_item_cost * ( $atts['amount'] / 100 ), 2 );
		} else {
			$discount = round( $atts['amount'] * $line_item['quantity'], 2 );
		}

		return $discount;
	}

	public function get_fee() {
		return array(
			'percent_cost' => 0,
			'flat_cost'    => 0,
			'label'        => ''
		);
	}

}
