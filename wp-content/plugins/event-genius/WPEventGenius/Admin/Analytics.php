<?php
namespace WPEventGenius\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class Analytics {
	protected $database;

	public function __construct( $database ) {
		$this->database = $database;
	}

	/**
	 * Get total revenue based on payment status
	 *
	 * @param string|array $status Payment status(es) to include
	 * @return float Total revenue
	 */
	public function get_total_revenue($status = 'completed') {
		global $wpdb;
		$payments_table = $wpdb->prefix . 'evge_payments';
		$registrations_table = $wpdb->prefix . 'evge_registrations';

		if (is_array($status)) {
			// Create placeholders for each status
			$placeholders = implode(',', array_fill(0, count($status), '%s'));
			return (float) $wpdb->get_var($wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared	
				"SELECT SUM(p.payment_gross) FROM {$payments_table} p JOIN {$registrations_table} r ON p.registration_id = r.id WHERE p.payment_status IN ($placeholders)
				AND r.status != %s",
				array_merge($status, ['canceled'])
			));
		} else {
			return (float) $wpdb->get_var($wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared	
				"SELECT SUM(p.payment_gross) FROM {$payments_table} p JOIN {$registrations_table} r ON p.registration_id = r.id
				WHERE p.payment_status = %s
				AND r.status != %s",
				$status, 'canceled'
			));
		}

	}

	/**
	 * Compare registration trends between two time periods
	 *
	 * @param int $days Number of days to compare
	 * @return array Comparison results
	 */
	public function get_registration_trend($days = 30) {
		$end_date = current_time('mysql');
		$start_date = gmdate('Y-m-d H:i:s', strtotime("-" . ($days * 2) . " days"));
		$mid_date = gmdate('Y-m-d H:i:s', strtotime("-{$days} days"));

		// Get quantities for both periods
		$current_count = $this->database->get_registration_quantity_for_period($mid_date);
		$previous_count = $this->database->get_registration_quantity_for_period($start_date, $mid_date);

		// Calculate percentage change
		if ($previous_count === 0) {
			$percent_change = $current_count > 0 ? 100 : 0;
		} else {
			$percent_change = (($current_count - $previous_count) / abs($previous_count)) * 100;
		}

		return [
			'current_period' => [
				'start_date' => $mid_date,
				'end_date' => $end_date,
				'count' => $current_count
			],
			'previous_period' => [
				'start_date' => $start_date,
				'end_date' => $mid_date,
				'count' => $previous_count
			],
			'percent_change' => round($percent_change, 2),
			'is_increase' => $current_count >= $previous_count
		];
	}

	/**
	 * Get total quantity of registrations
	 *
	 * @param bool $include_canceled Whether to include canceled registrations
	 * @return int Total quantity
	 */
	public function get_total_registration_quantity($include_canceled = false) {
		global $wpdb;
		$registrations_table = $wpdb->prefix . 'evge_registrations';

		if (!$include_canceled) {
			return 	(int) $wpdb->get_var($wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared	
				"SELECT SUM(quantity) FROM {$registrations_table} WHERE status != %s",
				'canceled'
			));
		} else {
			// phpcs:ignore 
			return (int) $wpdb->get_var("SELECT SUM(quantity) FROM {$registrations_table}");
		}
	}

	/**
	 * Get total number of events
	 *
	 * @return int Total event count
	 */
	public function get_total_events() {
		$args = array(
			'post_type' => 'evge_event',
			'post_status' => 'any',
			'posts_per_page' => -1,
			'fields' => 'ids',
		);
		$query = new \WP_Query($args);
		return $query->found_posts;
	}

	/**
	 * Get number of upcoming events
	 *
	 * @return int Count of upcoming events
	 */
	public function get_upcoming_events_count() {
		$current_date = current_time('Y-m-d H:i:s');
		$args = array(
			'post_type' => 'evge_event',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'fields' => 'ids',
			'meta_query' => array(
				array(
					'key' => 'evge_event_start_date',
					'value' => $current_date,
					'compare' => '>=',
					'type' => 'DATETIME',
				),
			),
		);
		$query = new \WP_Query($args);
		return $query->found_posts;
	}

	/**
	 * Compare revenue trends between two time periods
	 *
	 * @param int $days Number of days to compare
	 * @return array Comparison results
	 */
	public function get_revenue_trend($days = 30) {
		global $wpdb;
		$payments_table = $wpdb->prefix . 'evge_payments';
		$registrations_table = $wpdb->prefix . 'evge_registrations';
		
		$end_date = current_time('mysql');
		$start_date = gmdate('Y-m-d H:i:s', strtotime("-" . ($days * 2) . " days"));
		$mid_date = gmdate('Y-m-d H:i:s', strtotime("-{$days} days"));

		// Get revenue for current period
		$current_revenue = (float) $wpdb->get_var($wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT COALESCE(SUM(p.payment_gross), 0) FROM {$payments_table} p 
			JOIN {$registrations_table} r ON p.registration_id = r.id 
			WHERE p.payment_status IN ('completed', 'offline')
			AND r.status != %s
			AND p.payment_date > %s",
			'canceled',
			$mid_date
		));

		// Get revenue for previous period
		$previous_revenue = (float) $wpdb->get_var($wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT COALESCE(SUM(p.payment_gross), 0) FROM {$payments_table} p 
			JOIN {$registrations_table} r ON p.registration_id = r.id 
			WHERE p.payment_status IN ('completed', 'offline')
			AND r.status != %s
			AND p.payment_date > %s
			AND p.payment_date <= %s",
			'canceled',
			$start_date,
			$mid_date
		));

		// Calculate percentage change
		if ($previous_revenue === 0) {
			$percent_change = $current_revenue > 0 ? 100 : 0;
		} else {
			$percent_change = (($current_revenue - $previous_revenue) / abs($previous_revenue)) * 100;
		}

		return [
			'current_period' => [
				'start_date' => $mid_date,
				'end_date' => $end_date,
				'revenue' => $current_revenue
			],
			'previous_period' => [
				'start_date' => $start_date,
				'end_date' => $mid_date,
				'revenue' => $previous_revenue
			],
			'percent_change' => round($percent_change, 2),
			'is_increase' => $current_revenue >= $previous_revenue
		];
	}

	/**
	 * Get pending revenue (revenue awaiting payment)
	 *
	 * @return float Pending revenue amount
	 */
	public function get_pending_revenue() {
		global $wpdb;
		$payments_table = $wpdb->prefix . 'evge_payments';
		$registrations_table = $wpdb->prefix . 'evge_registrations';

		return (float) $wpdb->get_var($wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT COALESCE(SUM(p.payment_gross), 0) FROM {$payments_table} p 
			JOIN {$registrations_table} r ON p.registration_id = r.id 
			WHERE p.payment_status = %s
			AND r.status != %s",
			'pending',
			'canceled'
		));
	}
}
