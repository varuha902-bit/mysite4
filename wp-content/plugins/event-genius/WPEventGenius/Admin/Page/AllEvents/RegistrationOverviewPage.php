<?php
namespace WPEventGenius\Admin\Page\AllEvents;

use WPEventGenius\Common\Queries\EventQuery;
use WPEventGenius\Common\Queries\RegistrationQuery;
use WPEventGenius\Common\Registration\Field\FieldHandler;
use WPEventGenius\Common\Registration\Form\Form;
use WPEventGenius\Common\Utils\Icon;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class RegistrationOverviewPage extends AllEventsPage {

	protected $active_tab = 'all-events';

	protected $events;

	protected $registrations;


	protected $sanitized_params;

	protected $type;

	protected $has_any_items = false;

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
		} else {
			$event_query = new EventQuery( $this->build_event_query_args() );
			$event_query->add_wp_query();
			$event_query->add_all(array('max' => 10));
			$event_query->hydrate();
			$this->type = 'events';
			$this->events = $event_query->get_events();

			// If no events found with filters, check if any exist without filters
			if (empty($this->events)) {
				$unfiltered_query = new EventQuery([
					'posts_per_page' => 1,
					'post_status' => 'any'
				]);
				$unfiltered_query->add_wp_query();
				$unfiltered_query->hydrate();
				$this->has_any_items = count($unfiltered_query->get_events()) > 0;
			} else {
				$this->has_any_items = true;
			}
		}
		update_user_meta( get_current_user_id(), 'evge_all_events_view', 'grid' );

	}

	public function identity() {
		if ( $this->type === 'registrations' ) {
			return 'reglist';
		}
		return 'overview';
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
		$paged = ! empty( $this->sanitized_params['paged'] ) ? $this->sanitized_params['paged'] : 1;
		$prev_url = '';
		$next_url = '';
		if ( $paged > 1 ) {
			$prev = $paged - 1;
			$prev_url = $this->nav_link( 'evge-all-events', array( 'paged' => $prev ) );
		}


		if ( count( $events ) >= EVGE_ADMIN_EVENTS_PER_PAGE ) {
			$next = $paged + 1;
			$next_url = $this->nav_link( 'evge-all-events', array( 'paged' => $next ) );
		}

		if ( ! empty( $prev_url ) || ! empty( $next_url ) ) :
		?>
		<div class="evge-event-pagination">
			<?php if ( ! empty( $prev_url )  ) : ?>
			<a class="evge-icon-link" href="<?php echo esc_url( $prev_url ); ?>"><div class="evge-icon-text">
				<?php 
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo Icon::get( 'left-carat' ); ?>
				<span><?php esc_html_e( 'Previous', 'event-genius' ); ?></span>
			</div></a>
			<?php endif; ?>
			<?php if ( ! empty( $next_url ) ) : ?>
				<a class="evge-icon-link" href="<?php echo esc_url( $next_url ); ?>"><div class="evge-icon-text">
					<span><?php esc_html_e( 'Next', 'event-genius' ); ?></span>
					<?php 
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo Icon::get( 'right-carat' ); ?>
				</div></a>
			<?php endif; ?>
		</div>
		<?php
		endif;

	}


	public function get_sanitized_params() {
		return $this->sanitized_params;
	}

	public function get_events() {
		return $this->events;
	}

	/**
	 * Check if any events exist in the system
	 * 
	 * @return bool Whether any events exist
	 */
	public function has_any_events() {
		return $this->has_any_items;
	}
}
