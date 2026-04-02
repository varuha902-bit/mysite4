<?php
namespace WPEventGenius\Admin\Page\AllEvents;

use WPEventGenius\Admin\Page\AllEventsBasePage;
use WPEventGenius\Common\Queries\EventQuery;
use WPEventGenius\Common\Queries\RegistrationQuery;
use WPEventGenius\Common\Utils\Icon;
use WPEventGenius\Admin\CustomPostTypes\EventsWPListTable;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class AllEventsPage extends AllEventsBasePage {

	protected $events;

	protected $registrations;

	protected $sanitized_params;

	protected $type;

	protected $event_list_table;

	public function __construct() {
	}

	public function build() {
		$this->sanitized_params = $this->base_params();

		if ( ! empty( $this->sanitized_params['s'] )
		     && ! empty( $this->sanitized_params['stype'] )
		     && $this->sanitized_params['stype'] === 'registrations' ) {
			$registration_query = new RegistrationQuery( $this->build_registration_query_args() );
			$registration_query->build();
			$registration_query->hydrate();
			$this->type = 'registrations';
			$this->registrations = $registration_query->get_registrations();
		}

		update_user_meta( get_current_user_id(), 'evge_all_events_view', 'list' );

	}

	public function build_wp_list_table() {
		if (!class_exists('WP_List_Table')) {
			require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
		}
		$this->event_list_table = new EventsWPListTable();
		$this->event_list_table->prepare_items();
	}

	public function get_event_list_table() {
		return $this->event_list_table;
	}

	public function has_any_events() {
		if ( ! empty( $this->event_list_table ) ) {
			return $this->event_list_table->has_any_items();
		}
		return ! empty( $this->events );
	}

	public function page_title() {
		return __( 'All Events', 'event-genius' );
	}

	public function action_button() {
		?>
        <a href="<?php echo esc_url( admin_url('post-new.php?post_type=' . EVGE_EVENT_POST_TYPE) ); ?>" class="button evge-admin-secondary-button">+ <?php esc_html_e('Add New', 'event-genius'); ?></a>
		<?php
	}


	public function get_events() {
		return $this->events;
	}

	public function get_sanitized_params() {
		return $this->sanitized_params;
	}

	public function template_part( $part, $event ) {
		$page = $this;
		switch ( $part ) {
			case 'actions':
				include( EVGE_ADMIN_TEMPLATE_PATH . 'registration/partials/overview/actions.php' );
				break;
		}
	}

	public function event_pagination( $events ) {
        if ( empty( $events ) ) {
            return;
        }
		$paged = ! empty( $this->sanitized_params['paged'] ) ? $this->sanitized_params['paged'] : 1;
		$prev_url = '';
		$next_url = '';
		if ( $paged > 1 ) {
			$prev = $paged - 1;
			$prev_url = $this->nav_link( 'evge-registrations', array( 'paged' => $prev ) );
		}

		if ( count( $events ) >= EVGE_ADMIN_EVENTS_PER_PAGE ) {
			$next = $paged + 1;
			$next_url = $this->nav_link( 'evge-registrations', array( 'paged' => $next ) );
		}

		if ( ! empty( $prev_url ) || ! empty( $next_url ) ) :
			?>
			<div class="evge-event-pagination">
				<?php if ( ! empty( $prev_url )  ) : ?>
					<a class="evge-icon-link" href="<?php echo esc_url( $prev_url ); ?>"><div class="evge-icon-text">
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo Icon::get( 'left-carat' ); ?><span><?php esc_html_e( 'Previous', 'event-genius' ); ?></span></div></a>
				<?php endif; ?>
				<?php if ( ! empty( $next_url ) ) : ?>
					<a class="evge-icon-link" href="<?php echo esc_url( $next_url ); ?>"><div class="evge-icon-text"><span><?php esc_html_e( 'Next', 'event-genius' ); ?></span><?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo Icon::get( 'right-carat' ); ?></div></a>
				<?php endif; ?>
			</div>
		<?php
		endif;

	}

	public function identity() {
		if ( $this->type === 'registrations' ) {
			return 'reglist';
		}
		return 'overview';
	}
	public function content() {
		$page = $this;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = ! empty( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$view = ! empty( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : get_user_meta( get_current_user_id(), 'evge_all_events_view', true );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$subtab = ! empty( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : '';

        if ( $subtab === 'single' ) {
            include_once( EVGE_ADMIN_TEMPLATE_PATH . 'registration/partials/single-registration.php' );
        } else {
	        if ( $view === 'grid' ) {
		        include_once( EVGE_ADMIN_TEMPLATE_PATH . 'evge/all-events/all-events-grid.php' );
	        } else {
				$this->build_wp_list_table();
		        include_once( EVGE_ADMIN_TEMPLATE_PATH . 'evge/all-events/all-events-list.php' );
	        }
        }
	}

}
