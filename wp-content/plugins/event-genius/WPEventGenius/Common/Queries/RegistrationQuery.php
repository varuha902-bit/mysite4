<?php
namespace WPEventGenius\Common\Queries;


use WPEventGenius\Common\Database;
use WPEventGenius\Common\Event\EventPost;
use WPEventGenius\Common\Registration\Form\Form;
use WPEventGenius\Common\Utils\Formatter;
use WPEventGenius\Common\Utils\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class RegistrationQuery {

	protected $args;

	protected $registrations;

	protected $associated_events;

	protected $database;

	public function __construct( $args = array() ) {
		if ( empty( $args ) ) {
			$args = array();
		}

		$this->args = $args;

		if ( empty( $this->args['s'] ) ) {
			$this->args['per_page'] = ! empty( $this->args['per_page'] ) ? $this->args['per_page'] : 50;
			if ( empty( $this->args['paged'] ) ) {
				$this->args['paged'] = 1;
			}
			if ( empty( $this->args['orderby'] ) ) {
				$this->args['orderby'] = 'registration_date DESC';
			}
			if ( empty( $this->args['where'] ) ) {
				$this->args['where'] = array(
					array(
						'column' => '1',
						'value' => '1',
						'compare' => '=',
						'type' => 'string'
					),
				);
			}
		}

		$this->database = new Database();

		$this->associated_events = array();
	}

	public function build() {
		if ( ! empty( $this->args['s'] ) ) {
			$statuses = array();
			if ( ! empty( $this->args['status'] ) ) {
				if ( $this->args['status'] === 'active' ) {
					$statuses = array('confirmed', 'pending');
				} else {
					$statuses = $this->args['status'];
				}
			}

			$this->registrations = $this->database->search_registrations( $this->args['s'], $statuses );
		} else {
			$include_canceled = false;
			if ( ! empty( $this->args['registration_status'] ) ) {
				$include_canceled = $this->args['registration_status'] === 'canceled';

				if ( ! $include_canceled ) {
					if ( $this->args['registration_status'] !== 'active' ) {
						$this->args['where'][] = array(
							'column' => 'status',
							'value' => $this->args['registration_status'],
							'compare' => '=',
							'type' => 'string'
						);
					}

				} elseif ( $this->args['registration_status'] === 'canceled' ) {
					$this->args['where'][] = array(
						'column' => 'status',
						'value' => 'canceled',
						'compare' => '=',
						'type' => 'string'
					);
				}
			}

			if ( ! empty( $this->args['qtype'] ) && $this->args['qtype'] === 'custom' ) {
				$this->args['where'][] = array(
					'column' => 'registration_date',
					'value' => $this->args['start'],
					'compare' => '>',
					'type' => 'date'
				);

				$this->args['orderby'] = 'registration_date ASC';
			}

			$this->registrations = $this->database->registration_joined_query( $this->args['where'], array( 'payments' ), $this->args['orderby'], $this->args['per_page'], $this->args['per_page'] * ($this->args['paged'] - 1), $include_canceled );
		}

	}

	public function hydrate() {
		$hydrated_registrations = array();
		foreach ( $this->registrations as $registration ) {
			if ( ! empty( $registration['meta_cache'] ) ) {
				$registration['meta_cache'] = json_decode( $registration['meta_cache'], true );
				foreach ( $registration['meta_cache'] as $key => $value ) {
					$registration[ $key ] = $value;
				}
			}


			if ( empty( $this->associated_events[ $registration['event_id'] ] ) ) {
				$event = new EventPost( $registration['event_id'] );
				$this->associated_events[ $registration['event_id'] ] = $event;
			}

			$registration = Utils::maybe_add_group_data( $registration, $this->associated_events[ $registration['event_id'] ] );

			if ( evge_is_free_tier() ) {
				// Calculate cost from event during hydration (for free tier optimization)
				$event_post = $this->associated_events[ $registration['event_id'] ];
				$quantity = isset( $registration['quantity'] ) ? intval( $registration['quantity'] ) : 1;
				$payment_gross = isset( $registration['payment_gross'] ) ? $registration['payment_gross'] : 0;
				
				// If payment_gross is set and greater than 0, use it; otherwise calculate from event cost
				if ( ! empty( $payment_gross ) && (float) $payment_gross > 0 ) {
					$registration['payment_gross'] = (float) $payment_gross;
				} else {
					$event_cost = $event_post->get_the_cost_amount();
					if ( ! empty( $event_cost ) && is_numeric( $event_cost ) ) {
						$registration['payment_gross'] = (float) $event_cost * $quantity;
					} else {
						$registration['payment_gross'] = 0;
					}
				}
			}

			$registration['quantity_cost'] = Formatter::get_quantity_cost_display( $registration );

			$hydrated_registrations[] = $registration;
		}
		$this->registrations = $hydrated_registrations;

	}

	public function get_registrations() {
		return $this->registrations;
	}

	public function get_associated_events() {
		return $this->associated_events;
	}

	public function get_associated_event( $event_id ) {
		if ( empty( $this->associated_events[ $event_id ] ) ) {
			return false;
		}
		return $this->associated_events[ $event_id ];
	}

	public function get_total_count() {
		$where = ! empty( $this->args['where'] ) ? $this->args['where'] : array();
		return $this->database->registration_count_query( $where );
	}

	public function get_status_counts() {
		global $wpdb;
		
		$counts = $wpdb->get_results(
			"SELECT status, COUNT(*) as count 
			FROM {$wpdb->prefix}evge_registrations 
			GROUP BY status",
			ARRAY_A
		);
		
		$formatted_counts = array();
		foreach ($counts as $count) {
			$formatted_counts[$count['status']] = (int) $count['count'];
		}
		
		return $formatted_counts;
	}


}