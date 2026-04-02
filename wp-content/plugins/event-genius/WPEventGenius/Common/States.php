<?php

namespace WPEventGenius\Common;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class States {

	protected $state_option;

	public function __construct() {
		$this->state_option = get_option( 'evge_states', array() );

		if ( ! is_array( $this->state_option ) ) {
			$this->state_option = array();
		}

		if ( ! isset( $this->state_option['first_install'] ) ) {
			$this->state_option['first_install'] = time();
			update_option( 'evge_states', $this->state_option );
		}
	}

	public function get_state( $key ) {
		if ( isset( $this->state_option[ $key ] ) ) {
			return $this->state_option[ $key ];
		}
		return false;
	}

	public function set_state( $key, $value ) {
		$this->state_option[ $key ] = $value;
		update_option( 'evge_states', $this->state_option );
	}

	public function dismiss_notice( $key ) {
		$notice_states = $this->get_state( 'notices' );
		if ( ! is_array( $notice_states ) ) {
			$notice_states = array(
				'dismissed' => array(),
			);
		}
		if ( ! is_array( $notice_states['dismissed'] ) ) {
			$notice_states['dismissed'] = array();
		}
		if ( ! in_array( $key, $notice_states['dismissed'] ) ) {
			$notice_states['dismissed'][] = $key;
		}
		$this->set_state( 'notices', $notice_states );

	}

	/**
	 * Undismiss a notice (remove it from dismissed list)
	 * 
	 * @param string $key Notice key to undismiss
	 */
	public function undismiss_notice( $key ) {
		$notice_states = $this->get_state( 'notices' );
		if ( ! is_array( $notice_states ) ) {
			$notice_states = array(
				'dismissed' => array(),
			);
		}
		if ( ! is_array( $notice_states['dismissed'] ) ) {
			$notice_states['dismissed'] = array();
		}
		
		// Remove the key from dismissed array
		$notice_states['dismissed'] = array_values( array_diff( $notice_states['dismissed'], array( $key ) ) );
		
		$this->set_state( 'notices', $notice_states );
	}
}
