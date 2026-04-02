<?php
namespace WPEventGenius\Common\Registration\Payment\Gateways;

use WPEventGenius\Common\Utils\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class GatewayService {

	protected $record;

	public function listeners() {
	}

	public function enqueue_js() {
	}

	public function handle_api_response() {
	}

	public function handle_create_request( $registration_group, $data ) {
	}

	public function button_atts() {
		$styles = '';

		$button_bg_color = str_replace( '#', '', (string) ( Settings::get( 'payment_button_bg_color' ) ?? '' ) );
		$button_text_color = str_replace( '#', '', (string) ( Settings::get( 'payment_button_text_color' ) ?? '' ) );

		if ( ! empty( $button_bg_color ) ) {
			$styles .= 'background-color: #' . esc_attr( $button_bg_color ) . ';';
		}
		if ( ! empty( $button_text_color ) ) {
			$styles .= 'color: #' . esc_attr( $button_text_color ) . ';';
		}

		$button_hover_class = ! empty( $styles ) ? ' evge-custom-hover' : '';

		$return = array(
			'style' => ! empty( $styles ) ? ' style="' . $styles . '"' : '',
			'class' => $button_hover_class
		);

		return $return;
	}
}
