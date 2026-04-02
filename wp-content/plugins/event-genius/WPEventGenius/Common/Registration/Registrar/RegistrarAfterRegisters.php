<?php

namespace WPEventGenius\Common\Registration\Registrar;

use WPEventGenius\Common\Event\Event;
use WPEventGenius\Common\Registration\Registration\RegistrationGroup;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class RegistrarAfterRegisters extends Registrar {

	public function calculate_status() {

		$confirmation_condition = $this->event->get_confirmation_condition();

		if ( ! $this->registration_is_open() ) {
			$this->add_report( 'status', 'not_open' );
			return 'error';
		}

		if ( $this->registration_deadline_has_passed() ) {
			$this->add_report( 'status', 'deadline' );
			return 'error';
		}

		if ( 'manual_only' === $confirmation_condition ) {
			return 'pending';
		}

		if ( 'payment_complete' === $confirmation_condition ) {
			if ( evge_is_free_tier() ) {
				return 'confirmed';
			}
			if ( ! $this->event->get_accept_payments() ) {
				return 'confirmed';
			}
			if ( $this->event->has_cost() ) {
				return 'pending';
			}
			if ( $this->registration_group->has_cost() ) {
				return 'pending';
			}
			return 'confirmed';
		}

		// Legacy: registers = immediate confirm; completes_payment = pending if payment required
		if ( 'registers' === $confirmation_condition ) {
			return 'confirmed';
		}

		return 'pending';
	}

	public function get_own_context() {
		return 'registers';
	}

	public function too_many_guests_for_capacity() {
		return false;
	}


	public function do_pending_status_entry_updates() {
		$confirmation_condition = $this->event->get_confirmation_condition();

		if ( 'verifies_email' === $confirmation_condition ) {
			$main_submission_data = $this->registration_group->get_main()->get_submission_data();
			if ( ! empty( $main_submission_data['email'] ) ) {
				$verify_email_data = $this->registration_group->db()->get_verify_email_status( $main_submission_data['email'] );
				$email_status      = isset( $verify_email_data[0] ) ? $verify_email_data[0]['status'] : 'none';
				if ( $email_status === 'none' ) {
					return $this->registration_group->db()->insert_pending_email_verify( $main_submission_data['email'] );
				}
				$verify_email_data['action'] = 'confirm';
				if ( $this->waiting_list_is_open() ) {
					$verify_email_data['verify_only'] = '1';
				}
				return $verify_email_data;
			}
		}

		return array();
	}
}
