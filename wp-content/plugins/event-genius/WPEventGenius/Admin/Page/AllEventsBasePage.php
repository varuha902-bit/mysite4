<?php
namespace WPEventGenius\Admin\Page;

use WPEventGenius\Common\Queries\EventQuery;
use WPEventGenius\Common\Queries\RegistrationQuery;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AllEventsBasePage extends BasePage {

    protected $active_tab = 'all-events';



	public function __construct() {
	}

	public function build() {
	}



    public function page_title() {
		return __( 'All Events', 'event-genius' );
	}

	public function navigation_args() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$active_tab = ! empty( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) : $this->active_tab;
		
		// Also check for tab parameter (used by Series and other tabs)
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['tab'] ) ) {
			$active_tab = sanitize_key( wp_unslash( $_GET['tab'] ) );
		}

		$navigation_args = array(
			'active_tab' => $active_tab,
			'nav_items' => array(
				array(
					'id' => 'all-events',
					'title' => __( 'All Events', 'event-genius' ),
					'url' => $this->nav_link( 'evge-all-events', array( 'tab' => 'all-events' ) ),
					'capability' => 'edit_evge_events'
				),
				array(
					'id' => 'calendars',
					'title' => __( 'Calendars', 'event-genius' ),
					'url' => admin_url( 'admin.php?page=evge-all-events&tab=calendars' ),
					'capability' => 'edit_evge_events'
				),
				array(
					'id' => EVGE_EVENT_CATEGORY_TYPE,
					'title' => __( 'Categories', 'event-genius' ),
					'url' => admin_url( 'edit-tags.php?taxonomy=' . EVGE_EVENT_CATEGORY_TYPE ),
					'capability' => 'manage_evge_categories'
				),
				array(
					'id' => EVGE_EVENT_TAG_TYPE,
					'title' => __( 'Tags', 'event-genius' ),
					'url' => admin_url( 'edit-tags.php?taxonomy=' . EVGE_EVENT_TAG_TYPE ),
					'capability' => 'manage_evge_tags'
				),
				array(
					'id' => 'organizers',
					'title' => __( 'Organizers', 'event-genius' ),
					'url' => admin_url( 'admin.php?page=evge-all-events&tab=organizers' ),
					'capability' => 'edit_evge_organizers'
				),
				array(
					'id' => 'venues',
					'title' => __( 'Venues', 'event-genius' ),
					'url' => admin_url( 'admin.php?page=evge-all-events&tab=venues' ),
					'capability' => 'edit_evge_venues'
				),
			)
		);

		// Apply filter to allow Pro version to add additional tabs
		$navigation_args = apply_filters( 'evge_all_events_navigation_args', $navigation_args );

		return $navigation_args;
	}

	public function navigation( $active_tab ) {
		$navigation_args = $this->navigation_args();
		$navigation_args = $this->filter_navigation_by_capability($navigation_args);
		$this->navigation_html( $navigation_args );
	}


	public function content() {
		$page = $this;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = ! empty( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';

        if ( $tab === 'calendars' ) {
	        include_once( EVGE_ADMIN_TEMPLATE_PATH . 'evge/calendars.php' );
        } else {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$subtab = isset($_GET['subtab']) ? sanitize_key(wp_unslash($_GET['subtab'])) : 'registrations';

	        if ( $subtab === 'single' ) {
		        include_once( EVGE_ADMIN_TEMPLATE_PATH . 'registration/partials/single-registration.php' );
	        } else {
		        include_once( EVGE_ADMIN_TEMPLATE_PATH . 'evge/all-events.php' );
	        }
        }
    }

    /**
     * Generate the HTML for no events message
     * 
     * @param string $reset_url The URL to show all events
     * @return string The HTML for the no events message
     */
    public function get_no_events_message($reset_url) {
        ob_start();
        ?>
        <div class="evge-admin-no-events-message">
            <?php if ($this->has_any_events()): ?>
                <p><?php esc_html_e('No events found that match your filters.', 'event-genius'); ?></p>
                <a href="<?php echo esc_url($reset_url); ?>" class="button button-primary"><?php esc_html_e('Show All Events', 'event-genius'); ?></a>
            <?php else: ?>
                <p><?php esc_html_e('Hey there! Get started by creating your first event.', 'event-genius'); ?></p>
                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=' . EVGE_EVENT_POST_TYPE)); ?>" class="button button-primary"><?php esc_html_e('Create New Event', 'event-genius'); ?></a>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

}
