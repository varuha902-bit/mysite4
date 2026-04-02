<?php
namespace WPEventGenius\Admin\Page\AllEvents;

use WPEventGenius\Admin\Page\AllEventsBasePage;
use WPEventGenius\Admin\CustomPostTypes\php;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OrganizersPage extends AllEventsBasePage {

	protected $active_tab = 'organizers';

	public function __construct() {
	}

	public function build() {
	}

	public function page_title() {
		return __('Organizers', 'event-genius');
	}

	public function action_button() {
		?>
		<a href="<?php echo esc_url( admin_url('post-new.php?post_type=' . EVGE_ORGANIZER_POST_TYPE) ); ?>" class="button evge-admin-secondary-button">+ <?php esc_html_e('Add New', 'event-genius'); ?></a>
		<?php
	}

	public function post_status_links() {
		// Get counts for each post status
		$organizer_counts = wp_count_posts( EVGE_ORGANIZER_POST_TYPE );
		$published_count = $organizer_counts->publish;
		$draft_count = $organizer_counts->draft;
		$trash_count = $organizer_counts->trash;
		$counts = array(
			'all' => 0,
			'publish' => $organizer_counts->publish,
			'draft' => $organizer_counts->draft,
			'trash' => $organizer_counts->trash
		);
		$total_organizers = array_sum($counts);
		// Determine the current status
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_status = isset( $_GET['post_status'] ) ? sanitize_key( wp_unslash( $_GET['post_status'] ) ) : 'all';

		$possible_statuses = array(
			array(
				'status' => 'all',
				'count' => $total_organizers,
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
			$url = add_query_arg( array( 'page' => 'evge-all-events', 'tab' => 'organizers', 'post_status' => $status['status'] ) );
			$html[] = '<li class="' . esc_attr( $status['status'] ) . '"><a href="' . esc_url( $url ) . '" class="' . esc_attr( $classes ) . '">' . esc_html( $status['label'] ) . ' <span class="' . esc_attr( $count ) .'">(' . esc_html( $status['count'] ) . ')</span></a></li>';
		}
		echo wp_kses_post( implode(' | ', $html) );

	}

	public function content() {
		$page = $this;
		include_once(EVGE_ADMIN_TEMPLATE_PATH . 'evge/all-events/organizers.php');
	}
}