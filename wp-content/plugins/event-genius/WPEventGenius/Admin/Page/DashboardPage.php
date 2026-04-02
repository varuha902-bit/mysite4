<?php
namespace WPEventGenius\Admin\Page;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class DashboardPage extends BasePage {

	public function __construct() {
	}

	public function build() {
	}

    public function page_title() {
		return __( 'Dashboard', 'event-genius' );
	}

	public function navigation($activeTab) {
	}

	/**
	 * Check if any events exist (any status)
	 *
	 * @return bool True if events exist, false otherwise
	 */
	public function has_events() {
		$event_counts = wp_count_posts( EVGE_EVENT_POST_TYPE );
		$total_events = $event_counts->publish + $event_counts->draft + $event_counts->pending + $event_counts->future + $event_counts->trash;
		return $total_events > 0;
	}

	public function content() {
		if ( $this->has_events() ) {
			// Show normal dashboard
			include_once( EVGE_ADMIN_TEMPLATE_PATH . 'evge/dashboard.php' );
		} else {
			// Show empty state
			include_once( EVGE_ADMIN_TEMPLATE_PATH . 'evge/dashboard-empty-state.php' );
		}
    }


}
