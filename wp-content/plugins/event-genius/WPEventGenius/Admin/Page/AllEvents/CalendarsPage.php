<?php
namespace WPEventGenius\Admin\Page\AllEvents;

use WPEventGenius\Admin\Page\AllEventsBasePage;
use WPEventGenius\Admin\Services\CalendarBuilderService;
use WPEventGenius\Common\Utils\Icon;
use WPEventGenius\Common\Utils\Settings;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CalendarsPage extends AllEventsBasePage {
	protected $active_tab = 'calendars';

    protected $active_subtab = 'events';

	public function __construct() {
	}

	public function build() {


	}

    public function page_title() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$calendar_id = isset( $_GET['calendar_id'] ) ? sanitize_key( $_GET['calendar_id'] ) : 0;
		
		// If editing a calendar, return the calendar name
		if ( ! empty( $calendar_id ) ) {
			if ( $calendar_id === 'default' ) {
				return __( 'Default (Archive)', 'event-genius' );
			} else {
				$calendar = get_term( $calendar_id, 'evge_calendar' );
				if ( $calendar && ! is_wp_error( $calendar ) ) {
					return $calendar->name;
				}
			}
		}
		
		// Otherwise return default title
		return __( 'Calendars', 'event-genius' );
	}

	/**
	 * Get calendar color for the current calendar being edited
	 * 
	 * @return string|false Calendar color or false if not editing a calendar
	 */
	public function get_calendar_color() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$calendar_id = isset( $_GET['calendar_id'] ) ? sanitize_key( $_GET['calendar_id'] ) : 0;
		
		if ( empty( $calendar_id ) ) {
			return false;
		}
		
		if ( $calendar_id === 'default' ) {
			$settings = Settings::get('default_calendar_settings');
			if ( ! empty( $settings ) && is_string( $settings ) ) {
				$settings = json_decode( $settings, true );
			}
		} else {
			$settings = get_term_meta( $calendar_id, 'evge_calendar_settings', true );
		}
		
		if ( ! is_array( $settings ) ) {
			$settings = [];
		}
		
		$settings = wp_parse_args( $settings, [
			'color' => '#3498db'
		] );
		
		return isset( $settings['color'] ) ? $settings['color'] : '#3498db';
	}

	public function action_button() {
		?>
		<form method="post" class="evge-add-calendar-form" style="display: inline;">
			<?php wp_nonce_field('evge_add_calendar', 'evge_calendar_nonce'); ?>
			<input type="hidden" name="action" value="evge_add_calendar">
			<button type="submit" class="button evge-admin-secondary-button">
				+ <?php esc_html_e('Add New', 'event-genius'); ?>
			</button>
		</form>
		<?php
	}

	public function content() {
		$page = $this;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended		
		$calendar_id = isset( $_GET['calendar_id'] ) ? sanitize_key($_GET['calendar_id']) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search_query = isset($_GET['list_search']) ? sanitize_text_field(wp_unslash($_GET['list_search'])) : '';
		$per_page     = 6;

// Base admin URL
		$base_url = admin_url('admin.php?page=evge-all-events&tab=calendars');

		if ( empty( $calendar_id ) ) {
			include_once EVGE_PLUGIN_PATH . 'admin/templates/evge/all-events/calendars.php';
		} else {
			if ( $calendar_id === 'default' ) {
				$settings = Settings::get('default_calendar_settings');
			} else {
				$settings = get_term_meta($calendar_id, 'evge_calendar_settings', true) ?: [];
				if ( ! is_array( $settings ) ) {
					$settings = [];
				}
			}
	
			$settings = wp_parse_args($settings, [
				'view_type' => 'month',
				'events_per_page' => 12,
				'filters' => [],
				'color' => '#3498db'
			]);

			$calendar_builder = new CalendarBuilderService();
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended	
			$subtab = isset( $_GET['subtab'] ) ? sanitize_text_field( wp_unslash( $_GET['subtab'] ) ) : 'settings';

			if ( $subtab === 'settings' ) {
				include EVGE_PLUGIN_PATH . 'admin/templates/evge/calendars/calendar-builder.php';
			} else {
				include EVGE_PLUGIN_PATH . 'admin/templates/evge/calendars/calendar-events.php';
			}
		}

		include_once trailingslashit( EVGE_PLUGIN_PATH ) . 'templates/event-genius/common/modal.php';
	}

    public function sub_navigation( $sub_navigation_args ) {

    }

    public function builder_top($settings, $calendar_name) {
        $base_url = admin_url('admin.php?page=evge-all-events&tab=calendars');
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $calendar_id = isset( $_GET['calendar_id'] ) ? sanitize_key( $_GET['calendar_id'] ) : 0;

        ?>
        <a href="<?php echo esc_url(remove_query_arg('calendar_id', $base_url)); ?>"
           class="evge-left-icon evge-admin-secondary-button button action evge-tiny-icon">
            <span class="evge-icon-text">
				<?php 
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo Icon::get('left-chevron'); ?>
				<?php esc_html_e('Back to Calendars', 'event-genius'); ?>
			</span>
        </a>

        <?php
        $this->custom_sub_navigation($this->sub_navigation_args());
        ?>
        <?php
    }

    public function custom_sub_navigation($sub_navigation_args) {
        ?>
        <div id="evge-calendar-builder-nav" class="evge-main-admin-subnav">
            <div class="evge-main-admin-subnav-inner">

                <?php
                foreach ( $sub_navigation_args['nav_items'] as $nav_item ) :
                    $active_tab_class = $sub_navigation_args['active_subtab'] === $nav_item['id'] ? ' evge-subnav-tab-active' : '';
                    ?>
                    <div class="evge-main-admin-subnav-item">
                        <a href="<?php echo esc_url( $nav_item['url'] ); ?>" class="evge-subnav-tab<?php echo esc_attr( $active_tab_class ); ?>"><?php echo esc_html( $nav_item['title'] ); ?></a>
                    </div>
                <?php
                endforeach;
                ?>
            </div>
        </div>
        <?php
    }

	public function sub_navigation_args() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $calendar_id = isset($_GET['calendar_id']) ? sanitize_key($_GET['calendar_id']) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $active_subtab = isset($_GET['subtab']) ? sanitize_text_field(wp_unslash($_GET['subtab'])) : 'settings';
		$sub_navigation_args = array(
			'active_subtab' => $active_subtab,
			'nav_items' => array(
                array(
					'id' => 'settings',
					'title' => __('Settings', 'event-genius'),
					'url' => $this->nav_link('evge-all-events', array('tab' => 'calendars', 'calendar_id' => $calendar_id, 'subtab' => 'settings'))
				),array(
					'id' => 'events',
					'title' => __('Events', 'event-genius'),
					'url' => $this->nav_link('evge-all-events', array('tab' => 'calendars', 'calendar_id' => $calendar_id, 'subtab' => 'events'))
				),

			)
		);

		return $sub_navigation_args;

	}

}
