<?php
namespace WPEventGenius\Admin\Page\AllEvents;

use WPEventGenius\Admin\Page\AllEventsBasePage;
use WPEventGenius\Admin\CustomPostTypes\VenuesWPListTable;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class VenuesPage extends AllEventsBasePage {

	protected $active_tab = 'venues';

	public function __construct() {
	}

	public function build() {
	}

	public function page_title() {
		return __('Venues', 'event-genius');
	}

	public function action_button() {
		?>
		<a href="<?php echo esc_url( admin_url('post-new.php?post_type=' . EVGE_VENUE_POST_TYPE ) ); ?>" class="button evge-admin-secondary-button">+ <?php esc_html_e('Add New', 'event-genius'); ?></a>
		<?php
	}

	public function post_status_links() {
		// Get counts for each post status
		$venue_counts = wp_count_posts( EVGE_VENUE_POST_TYPE );
		$published_count = $venue_counts->publish;
		$draft_count = $venue_counts->draft;
		$trash_count = $venue_counts->trash;
		$counts = array(
			'all' => 0,
			'publish' => $venue_counts->publish,
			'draft' => $venue_counts->draft,
			'trash' => $venue_counts->trash
		);
		$total_venues = array_sum($counts);
		// Determine the current status
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_status = isset( $_GET['post_status'] ) ? sanitize_key( wp_unslash( $_GET['post_status'] ) ) : 'all';

		$possible_statuses = array(
			array(
				'status' => 'all',
				'count' => $total_venues,
				'label' => __('All', 'event-genius')
			),
			array(
				'status' => 'publish',
				'count' => $published_count,
				'label' => __('Published', 'event-genius')
			),
			array(
				'status' => 'draft',
				'count' => $draft_count,
				'label' => __('Draft', 'event-genius')
			),
			array(
				'status' => 'trash',
				'count' => $trash_count,
				'label' => __('Trash', 'event-genius')
			)
		);
		$html = array();
		foreach ( $possible_statuses as $status ) {
			$classes = array();
			if ( $status['status'] === $current_status ) {
				$classes[] = 'current';
			}
			$classes = implode( ' ', $classes );
			$count = $status['count'];
			$url = add_query_arg( array( 'page' => 'evge-all-events', 'tab' => 'venues', 'post_status' => $status['status'] ) );
			$html[] = '<li class="' . esc_attr( $status['status'] ) . '"><a href="' . esc_url( $url ) . '" class="' . esc_attr( $classes ) . '">' . esc_html( $status['label'] ) . ' <span class="' . esc_attr( $count ) .'">(' . esc_html( $status['count'] ) . ')</span></a></li>';
		}
		echo wp_kses_post( implode(' | ', $html) );

	}

	public function content() {
		$page = $this;
		include_once(EVGE_ADMIN_TEMPLATE_PATH . 'evge/all-events/venues.php');
	}
}
