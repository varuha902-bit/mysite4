<?php
namespace WPEventGenius\Common\Registration\Payment\Cart;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class DiscountItem extends Item {
	public function get_calculated_amount() {
		return $this->meta['calculated'];
	}
}