<?php


namespace WPEventGenius\Common\Utils;
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}
class FilterParams {

	protected $sanitized_params;

	public function __construct() {
		$this->sanitized_params = $this->base_params();
	}

	public function base_params() {
		$sanitized_params = array();

		foreach ( $this->allowed_query_params_and_sanitization() as $param => $type ) {
			//phpcs:ignore
			if ( isset( $_REQUEST[ $param ] ) ) {
				//phpcs:ignore
				$sanitized_params[ $param ] = $this->sanitize_param( $_REQUEST[ $param ], $type );
			}

		}

		return $sanitized_params;
	}

	public function get_sanitized_params() {
		return $this->sanitized_params;
	}

	protected function sanitize_param( $param, $type ) {
		switch ( $type ) {
			case 'key':
				return sanitize_key( $param );
			case 'text':
				return sanitize_text_field( wp_unslash( $param ) );
			case 'int':
				return absint( $param );
			case 'date':
				return gmdate( 'Y-m-d H:i:s', strtotime( $param ) );
		}

		return '';
	}

	protected function allowed_query_params_and_sanitization() {
		return array(
			'tab' => 'key',
			'view' => 'key',
			'off' => 'key',
			'start' => 'date',
			'startt' => 'int',
			'page' => 'key',
			'paged' => 'int',
			'cat' => 'int',
			'tag' => 'int',
			'event' => 'int',
			'id' => 'int',
			'rtype' => 'key',
			'qtype' => 'key',
			'with' => 'key',
			's' => 'text',
			'stype' => 'key',
			'orderby' => 'key',
			'order' => 'key',
			'per_page' => 'int',
			'post_status' => 'key',
			'registration_status' => 'key',
			'group' => 'int',
		);
	}

	protected function build_event_query_args() {
		$args = array();
		if ( ! empty( $this->sanitized_params['s'] ) && ! empty( $this->sanitized_params['stype'] ) && $this->sanitized_params['stype'] === 'events' ) {
			$args['s'] = $this->sanitized_params['s'];
		}

		if ( ! empty( $this->sanitized_params['qtype'] ) ) {
			$args['qtype'] = $this->sanitized_params['qtype'];
		}

		if ( ! empty( $this->sanitized_params['paged'] ) ) {
			$args['paged'] = $this->sanitized_params['paged'];
		}

		return $args;
	}

	protected function build_registration_query_args() {
		$args = array();
		if ( ! empty( $this->sanitized_params['s'] ) && ! empty( $this->sanitized_params['stype'] ) && $this->sanitized_params['stype'] === 'registrations' ) {
			$args['s'] = $this->sanitized_params['s'];
		}

		return $args;
	}

}