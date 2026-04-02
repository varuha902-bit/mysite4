<?php

namespace WPEventGenius\Admin\Services;

use WPEventGenius\Admin\Page\BasePage;
use WPEventGenius\Common\Event\EventPost;
use WPEventGenius\Common\Queries\EventQuery;
use WPEventGenius\Common\Queries\RegistrationQuery;
use WPEventGenius\Common\Utils\Formatter;
use WPEventGenius\Common\Utils\Icon;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class DashboardPageService {
	public function __construct() {
	}

	public function init_hooks() {
		add_action( 'wp_ajax_evge_upcoming_events', array( $this, 'upcoming_events' ) );
		add_action( 'wp_ajax_evge_latest_registrations', array( $this, 'latest_registrations' ) );

	}

	public function upcoming_events() {
		check_ajax_referer( 'evge-admin', 'nonce' );

		if ( ! current_user_can( 'edit_evge_events' ) ) {
			wp_send_json_error( 'no permission' );
		}

		$return = array(
			'html' => '<div class="evge-fade-in evge-dashboard-list" style="display: none;">'
		);
		$page = new BasePage();
		$args = array(
			'posts_per_page' => 5,
		);
		$event_query = new EventQuery( $args );
		$event_query->add_wp_query();
		$event_query->add_all();
		$event_query->hydrate();

		$events = $event_query->get_events();
		
		if ( empty( $events ) ) {
			$return['html'] .= '<div class="evge-dashboard-empty-message">';
			$return['html'] .= '<p>' . esc_html__( 'No upcoming events.', 'event-genius' ) . '</p>';
			$return['html'] .= '</div>';
		} else {
			foreach ( $events as $event ) {
				$event_post = new EventPost( $event->ID );
				$return['html'] .= '<div class="evge-upcoming-event evge-dashboard-grid-row">';
					$return['html'] .= '<div class="evge-dashboard-grid-col">';
						$return['html'] .= '<div class="evge-dashboard-grid-item">';
							$return['html'] .= '<div class="evge-identity">' . esc_html( $event->post_title ) . '</div>';
							$return['html'] .= '<div>' . $event_post->recurrence_display() . esc_html( $event->details['date_summary'] ) . '</div>';
						$return['html'] .= '</div>';
					$return['html'] .= '</div>';
					$return['html'] .= '<div class="evge-dashboard-grid-col evge-dashboard-action-col">';
						$return['html'] .= '<a href="' . esc_url( $event->details['edit_link'] ) . '">' . esc_html__( 'Edit', 'event-genius' ) . '</a> | ';
						$return['html'] .= '<a href="' . esc_url( get_the_permalink( $event->ID ) ) . '">' . esc_html__( 'View', 'event-genius' ) . '</a> | ';
						$return['html'] .= '<a href="' . esc_url( $page->nav_link( 'evge-all-events', array( 'id' => $event->ID,  'subtab' => 'single' ) ) ) . '">' . esc_html__( 'Registrations', 'event-genius' ) . '</a>';
					$return['html'] .= '</div>';
				$return['html'] .= '</div>';
			}
		}
		$return['html'] .= '</div>';


		wp_send_json_success( $return );

	}

	public function latest_registrations() {
		check_ajax_referer( 'evge-admin', 'nonce' );

		if ( ! current_user_can( 'manage_evge_registrations' ) ) {
			wp_send_json_error( 'no permission' );
		}

		$page = new BasePage();

		$return = array(
			'html' => '<div class="evge-fade-in evge-dashboard-list" style="display: none;">'
		);

		$args = array(
			'per_page' => 5,
		);
		$registration_query = new RegistrationQuery( $args );
		$registration_query->build();
		$registration_query->hydrate();

		$registrations = $registration_query->get_registrations();
		
		if ( empty( $registrations ) ) {
			$return['html'] .= '<div class="evge-dashboard-empty-message">';
			$return['html'] .= '<p>' . esc_html__( 'No recent registrations.', 'event-genius' ) . '</p>';
			$return['html'] .= '</div>';
		} else {
			foreach ( $registrations as $registration ) {
				$event_post = $registration_query->get_associated_event( $registration['event_id'] );
				
				// Check if this is an additional guest registration (has a parent)
				$parent_id = isset( $registration['parent'] ) ? (int) $registration['parent'] : 0;
				
				if ( $parent_id > 0 ) {
					// This is an additional guest registration - show tooltip instead of cost
					$cost_quantity = '<span title="' . esc_attr__( 'This guest is part of a registration group with a single payment.', 'event-genius' ) . '">' . 
									esc_html__( 'Group Member', 'event-genius' ) . '</span>';
				} else {
					// This is a main registration - calculate group cost and quantity
					$registration_group = \WPEventGenius\Common\Utils\Utils::get_registration_group_from_id( $registration['id'] );
					
					if ( ! $registration_group ) {
						// Fallback to individual calculation if group not found
						$quantity = isset( $registration['quantity'] ) ? (int) $registration['quantity'] : 1;
						$cost = 0;
						if ( ! empty( $event_post ) ) {
							$event_cost = $event_post->get_the_cost_amount();
							$cost = $event_cost * $quantity;
						}
						if ( ! empty( $cost ) ) {
							if ( $quantity > 1 ) {
								$cost_quantity =  sprintf('%1$s (%2$s)', $event_post->currency_symbol_before() . number_format_i18n($cost, 2 ) . $event_post->currency_symbol_after(), $quantity );
							} else {
								$cost_quantity =  $event_post->currency_symbol_before() . number_format_i18n($cost, 2 ) . $event_post->currency_symbol_after();
							}
						}
					} else {
						// Get group total quantity
						$total_quantity = $registration_group->calculate_total_guest_count();
						
						// If there's a payment amount, use that
						if ( ! empty( $registration['payment_gross'] ) ) {
							$cost = $registration['payment_gross'];
						} else {
							// Use the existing payment handler to calculate proper cost
							if ( empty( $registration_group->payment_handler() ) ) {
								$registration_group->build_payment_handler( new \WPEventGenius\Common\Registration\Payment\PaymentHandler( new \WPEventGenius\Common\Registration\Payment\Cart\Cart() ) );
							}
							$cost = $registration_group->payment_handler()->calculate_total();
						}
						
						if ( ! empty( $cost ) ) {
							if ( $total_quantity > 1 ) {
								$cost_quantity =  sprintf('%1$s (%2$s)', $event_post->currency_symbol_before() . number_format_i18n($cost, 2 ) . $event_post->currency_symbol_after(), $total_quantity );
							} else {
								$cost_quantity =  $event_post->currency_symbol_before() . number_format_i18n($cost, 2 ) . $event_post->currency_symbol_after();
							}
						}
					}
				}
				$return['html'] .= '<div class="evge-latest-registration evge-dashboard-grid-row">';
				$return['html'] .= '<div class="evge-dashboard-grid-col">';
					$return['html'] .= '<div class="evge-dashboard-grid-item">';
						$return['html'] .= '<div class="evge-identity">' . esc_html( Formatter::identity( $registration ) ) . '</div>';
						// Use filter to get the appropriate title
						$title = apply_filters( 'evge_registration_display_title', get_the_title( $registration['event_id'] ), $registration['event_id'], false );
						$return['html'] .= '<div>' . esc_html( $title ) . '</div>';
						if ( ! empty( $cost ) ) {
							$cost_display = esc_html( $cost_quantity );
							// Add payment status icon if available and event requires payments
							if ( ! empty( $registration['payment_status'] ) && $event_post && $event_post->get_accept_payments() ) {
								$cost_display .= \WPEventGenius\Common\Utils\Formatter::get_payment_status_display( $registration['payment_status'] );
							}
							$return['html'] .= '<div class="evge-icon-text">' . $cost_display . '</div>';
						}
					$return['html'] .= '</div>';
				$return['html'] .= '</div>';
				$return['html'] .= '<div class="evge-dashboard-grid-col evge-dashboard-action-col">';
					$return['html'] .= '<a href="' . esc_url( $page->nav_link( 'evge-registrations', array( 'registration_id' => $registration['id'], 'tab' => 'registrations' ) ) ) . '">' . esc_html__( 'Manage', 'event-genius' ) . '</a>';
				$return['html'] .= '</div>';

				$return['html'] .= '</div>';
			}
		}
		$return['html'] .= '</div>';


		wp_send_json_success( $return );

	}
}