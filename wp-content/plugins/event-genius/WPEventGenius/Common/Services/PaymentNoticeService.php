<?php

namespace WPEventGenius\Common\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Payment Notice Service
 * 
 * Handles all payment-related notices and warnings for the Event Genius plugin.
 * This service is responsible for determining when and how to display payment notices
 * based on the plugin version and configuration.
 */
class PaymentNoticeService {

	/**
	 * Check if payment gateway configuration notice should be shown
	 * 
	 * @param \WPEventGenius\Common\Event\EventPost $event The event object
	 * @return bool True if notice should be shown, false otherwise
	 */
	public static function should_show_payment_gateway_notice( $event ) {
		// Don't show payment notices in free version
		if ( defined( 'EVGE_FREE_VERSION' ) && EVGE_FREE_VERSION ) {
			return false;
		}
		
		// Check if event needs payment gateway configuration
		if ( ! $event->needs_payment_gateway_configuration() ) {
			return false;
		}

		// Only show to users who can configure payment gateways
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Display payment gateway configuration notice if needed
	 * 
	 * @param \WPEventGenius\Common\Event\EventPost $event The event object
	 */
	public static function maybe_show_payment_gateway_notice( $event ) {
		if ( ! self::should_show_payment_gateway_notice( $event ) ) {
			return;
		}

		// Add a notice about payment gateway configuration
		FrontEndAdminNoticeService::add_notice(
			'payment_gateway_config',
			'warning',
			__( 'Payment Gateway Configuration Required', 'event-genius' ),
			sprintf(
				/* translators: %s: Event title */
				__( 'One or more of your events has a cost but no payment gateways are configured. Attendees will not be able to pay for this event.', 'event-genius' ),
				$event->get_the_title()
			),
			array(
				'text' => __( 'Configure Payment Gateways', 'event-genius' ),
				'url'  => admin_url( 'admin.php?page=evge-settings&tab=payments' ),
			)
		);
	}

	/**
	 * Check if any payment-related notices should be shown for an event
	 * 
	 * @param \WPEventGenius\Common\Event\EventPost $event The event object
	 */
	public static function check_and_show_payment_notices( $event ) {
		// Check and show payment gateway configuration notice
		self::maybe_show_payment_gateway_notice( $event );
		
		// Future: Add other payment-related notices here
		// For example: payment method availability, currency configuration, etc.
	}
}
