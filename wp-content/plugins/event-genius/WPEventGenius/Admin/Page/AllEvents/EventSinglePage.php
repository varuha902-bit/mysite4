<?php
namespace WPEventGenius\Admin\Page\AllEvents;

use WPEventGenius\Common\Queries\EventQuery;
use WPEventGenius\Common\Utils\Icon;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class EventSinglePage extends RegistrationOverviewPage {

	protected $active_tab = 'event-single';

	protected $event_id;

	protected $subtab;

	public function __construct( $event_id ) {
		$this->event_id = $event_id;
		$this->subtab = 'submissions';
	}

	public function build() {
		do_action( 'evge_admin_action_listener' );

		$this->sanitized_params = $this->base_params();
		$args = array( 'ID' => $this->event_id );
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! empty( $_POST['evge_single_search'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification
			$search = sanitize_text_field( wp_unslash( $_POST['evge_single_search'] ) );
			$args['reg_search'] = $search;
		}
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! empty( $_GET['subsubtab'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification
			if ( 'payments' === $_GET['subsubtab'] ) {
				$args['payments'] = true;
			}
			// phpcs:ignore WordPress.Security.NonceVerification
			if ( 'attendance' === $_GET['subsubtab'] ) {
				$args['attendance'] = true;
			}
			// phpcs:ignore WordPress.Security.NonceVerification
			$this->subtab = sanitize_key( wp_unslash( $_GET['subsubtab'] ) );
		}

        if ( ! empty( $this->sanitized_params['registration_status'] ) ) {
            $args['statuses'] = $this->sanitized_params['registration_status'];
        }

		// Handle attendance status filter (Standard tier only)
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['attendance_status'] ) && function_exists( 'evge_is_standard_tier' ) && evge_is_standard_tier() ) {
			$args['attendance_status'] = sanitize_key( wp_unslash( $_GET['attendance_status'] ) );
			$args['attendance'] = true; // Ensure attendance join is included
			// When filtering by attendance status, ensure we're on the attendance tab
			if ( empty( $this->subtab ) || $this->subtab !== 'attendance' ) {
				$this->subtab = 'attendance';
			}
		}

		if ( ! empty( $this->sanitized_params['group'] ) ) {
			$args['group'] = $this->sanitized_params['group'];
		}

		$event_query = new EventQuery( $args );
		$event_query->add_wp_query();
		$event_query->add_all( $args );
		$event_query->hydrate();

		$this->events = $event_query->get_events();
	}

	public function get_event() {
		return $this->events[0];

	}

	public function get_subtab() {
		return $this->subtab;

	}

	public function identity() {
		return 'single';
	}

	public function action_button() {

	}
	public function page_title() {
		return __( 'Registration Details', 'event-genius' );
	}

	public function navigation( $active_tab ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$back_page = ! empty( $_GET['back_page'] ) ? sanitize_key( wp_unslash( $_GET['back_page'] ) ) : 'evge-all-events';

		// Determine the back link URL and label based on the back_page parameter
		$back_url = $this->get_back_url( $back_page );
		$back_label = $this->get_back_label( $back_page );

		?>
        <div class="evge-nav-link-wrap">
            <a id="evge-back-overview" class="evge-event-details-actions-button evge-admin-secondary-button button action" href="<?php echo esc_url( $back_url ); ?>"><span class="evge-icon-text"><?php 
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo Icon::get( 'left-chevron' ); ?>
				<?php echo esc_html( $back_label ); ?></span></a>
        </div>
            <?php
	}

	/**
	 * Get the back URL based on the back_page parameter
	 *
	 * @param string $back_page The back page parameter
	 * @return string The back URL
	 */
	private function get_back_url( $back_page ) {
		switch ( $back_page ) {
			case 'evge-registrations':
				return $this->nav_link( 'evge-registrations', array( 'tab' => 'registrations', 'group' => '' ) );
			case 'evge-all-events':
			default:
				return $this->nav_link( 'evge-all-events', array( 'id' => '', 'tab' => '', 'subtab' => '', 'group' => '' ) );
		}
	}

	/**
	 * Get the back label based on the back_page parameter
	 *
	 * @param string $back_page The back page parameter
	 * @return string The back label
	 */
	private function get_back_label( $back_page ) {
		switch ( $back_page ) {
			case 'evge-registrations':
				return __( 'Back to Registrations', 'event-genius' );
			case 'evge-all-events':
			default:
				return __( 'Back to Events', 'event-genius' );
		}
	}

	public function content() {
		$page = $this;
	?>
		<div class="evge-admin-page">
			<?php include_once( EVGE_ADMIN_TEMPLATE_PATH . 'registration/partials/single-registration.php' ); ?>
		</div>
		<?php
		do_action( 'evge_admin_modal' );

	}

	public function template_part( $part, $event ) {
		$page = $this;
		switch ( $part ) {
			case 'actions':
				include( EVGE_ADMIN_TEMPLATE_PATH . 'registration/partials/single/actions.php' );
				break;
		}
	}
}
