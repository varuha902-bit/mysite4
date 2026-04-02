<?php
namespace WPEventGenius\Admin\CustomPostTypes;

use WP_List_Table;
use WP_Error;
use WPEventGenius\Common\Queries\EventQuery;
use WPEventGenius\Common\Utils\FilterParams;
use WPEventGenius\Common\Utils\Icon;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class EventsWPListTable extends WP_List_Table {

	protected $event_query;
	protected $has_any_items = false;
	public function __construct() {
		parent::__construct([
			'singular' => 'event',
			'plural'   => 'events',
			'ajax'     => false
		]);
	}

	public function get_columns() {
		$columns = array(
			'cb'         => '<input type="checkbox" />',
			'title'      => __('Title', 'event-genius'),
			'date' => __('Date', 'event-genius'),
			'venue'      => __('Venue', 'event-genius'),
			'organizer'  => __('Organizer', 'event-genius'),
			'attendance' => __('Attendance', 'event-genius'),
		);
		return $columns;
	}

	public function column_default($item, $column_name) {

		switch ($column_name) {
			case 'attendance':
				if ( $item->details['allow_registration'] !== 'enabled' ) {
					return '';
				}
				return isset($item->details['registration_quantity'], $item->details['capacity_display']) 
					? (int)$item->details['registration_quantity'] . ' / ' . esc_html( $item->details['capacity_display'] )
					: 'N/A';
			case 'date':
				return wp_kses_post( $item->details['date_summary'] );
			case 'venue':
				if ( empty( $item->details['venue_id'] ) ) {
					return '';
				}
				return '<a href="'. esc_url( get_edit_post_link( $item->details['venue_id'] ) ) . '">' . esc_html( get_the_title( $item->details['venue_id'] ) ) . '</a>';
			case 'organizer':
				if ( empty( $item->details['organizer_id'] ) ) {
					return '';
				}
				return '<a href="'. esc_url( get_edit_post_link( $item->details['organizer_id'] ) ) . '">' . esc_html( get_the_title( $item->details['organizer_id'] ) ) . '</a>';
			default:
				return '';
		}
	}
	public function column_title($item) {
		$actions = array();
		$edit_link = get_edit_post_link($item->ID);
		$post_meta = get_post_meta($item->ID);
		$is_recurrence = isset($post_meta['evge_is_recurrence']) ? absint($post_meta['evge_is_recurrence'][0]) : false;
		$series_id = isset($post_meta['evge_series_id']) ? absint($post_meta['evge_series_id'][0]) : false;
		$is_template = false;

		if ($series_id) {
			$series_repo = new \WPEventGenius\Common\Series\EventSeriesRepository(new \WPEventGenius\Common\Database());
			$template_id = $series_repo->get_template_event_id($series_id);
			$is_template = ($template_id == $item->ID);
		}

		$recurrence_icon = '';
		if ($is_recurrence) {
			$edit_link = add_query_arg('recurrence_id', $item->ID, (string)get_edit_post_link($is_recurrence));
			$recurrence_icon = ' ' . Icon::get('recurring');
		}
		$title = '<strong>';

		if ('trash' !== $item->post_status && ! $is_recurrence) {
			$title .= '<a class="row-title" href="' . esc_url($edit_link) . '">';
		}
		$title .= esc_html($item->post_title) . $recurrence_icon;
		if ('trash' !== $item->post_status && ! $is_recurrence) {
			$title .= '</a>';
		}

		// Add post status if not published
		if ($item->post_status !== 'publish') {
			$title .= ' — <span class="post-state">' . esc_html(get_post_status_object($item->post_status)->label) . '</span>';
		}

		$title .= '</strong>';

		if ('trash' === $item->post_status) {
			$actions['untrash'] = '<a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=evge-all-events&action=untrash&id=' . $item->ID), 'untrash_post_' . $item->ID)) . '">' . esc_html__('Restore', 'event-genius') . '</a>';
			$actions['delete'] = '<a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=evge-all-events&action=delete&id=' . $item->ID), 'delete_post_' . $item->ID)) . '" class="submitdelete">' . esc_html__('Delete Permanently', 'event-genius') . '</a>';
		} elseif ($is_recurrence) {
			$actions['edit_series'] = '<a href="' . esc_url($edit_link) . '">' . esc_html__('Edit Recurrences', 'event-genius') . '</a>';
			$actions['view'] = '<a href="' . esc_url(get_permalink($item->ID)) . '">' . esc_html__('View', 'event-genius') . '</a>';
			$actions['trash'] = '<a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=evge-all-events&action=trash&id=' . $item->ID), 'trash_post_' . $item->ID)) . '" class="submitdelete evge-trash-recurrence">' . esc_html__('Trash', 'event-genius') . '</a>';
			$actions['manage'] = '<a href="' . esc_url(admin_url('admin.php?page=evge-all-events&view=list&tab=registrations&back_page=evge-all-events&subtab=single&id=' . $item->ID)) . '">' . esc_html__('Manage Registrations', 'event-genius') . '</a>';
		} else {
			$actions['edit'] = '<a href="' . esc_url($edit_link) . '">' . esc_html__('Edit', 'event-genius') . '</a>';
			$actions['view'] = '<a href="' . esc_url(get_permalink($item->ID)) . '">' . esc_html__('View', 'event-genius') . '</a>';
			$actions['trash'] = '<a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=evge-all-events&action=trash&id=' . $item->ID), 'trash_post_' . $item->ID)) . '" class="submitdelete' . ($is_template ? ' evge-trash-template' : '') . '">' . esc_html__('Trash', 'event-genius') . '</a>';
			$actions['manage'] = '<a href="' . esc_url(admin_url('admin.php?page=evge-all-events&view=list&tab=registrations&back_page=evge-all-events&subtab=single&id=' . $item->ID)) . '">' . esc_html__('Manage Registrations', 'event-genius') . '</a>';
		}

		return $title . $this->row_actions($actions);
	}

	public function column_cb($item) {
		return sprintf(
			'<input type="checkbox" name="event[]" value="%s" />', esc_attr($item->ID)
		);
	}


	public function prepare_items() {
		// Process bulk actions
		$this->process_bulk_action();

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);

		$filter_params_obj = new FilterParams();

		$filter_params = $filter_params_obj->get_sanitized_params();
		$per_page = 20;

		$filter_params['posts_per_page'] = $per_page;
		$filter_params['post_status'] = ! empty( $filter_params['post_status'] ) ? $filter_params['post_status'] : 'publish';

		$this->event_query = new EventQuery( $filter_params );
		$this->event_query->add_wp_query();
		$this->event_query->hydrate();
		$items = $this->event_query->get_events();
		$total_items = $this->event_query->get_total_count();

		// If no items found with filters, check if any items exist without filters
		if (empty($items)) {
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

		$current_page = $this->get_pagenum();

		$this->set_pagination_args([
			'total_items' => $total_items,
			'per_page'    => $per_page,
		]);

		$this->items = $items;
	}

	public function has_any_items() {
		return $this->has_any_items;
	}

	private function get_event_count() {
		return 10;
	}

	public function get_bulk_actions() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if (isset($_REQUEST['post_status']) && sanitize_key($_REQUEST['post_status']) === 'trash') {
			$actions = array(
				'untrash' => __('Restore', 'event-genius'),
				'delete' => __('Delete Permanently', 'event-genius'),
				'empty_trash' => __('Empty Trash', 'event-genius'),
			);
		} else {
			$actions = array(
				'trash' => __('Move to Trash', 'event-genius'),
			);

			// Add publish action when viewing drafts
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if (isset($_REQUEST['post_status']) && sanitize_key($_REQUEST['post_status']) === 'draft') {
				$actions['publish'] = __('Publish', 'event-genius');
			}
		}
		return $actions;
	}

	/**
	 * Display the extra controls in the tablenav
	 * 
	 * @param string $which The location of the extra table nav markup: 'top' or 'bottom'.
	 */
	public function extra_tablenav($which) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ($which === 'bottom' && isset($_REQUEST['post_status']) && sanitize_key($_REQUEST['post_status']) === 'trash') {
			$num_posts = wp_count_posts(EVGE_EVENT_POST_TYPE);
			if ($num_posts->trash > 0) {
				$nonce = wp_create_nonce('bulk-' . $this->_args['plural']);
				?>
				<div class="alignleft actions">
					<button type="submit" name="action" value="empty_trash" class="button apply" onclick="return confirm('<?php esc_attr_e('Are you sure you want to permanently delete all items from the trash?', 'event-genius'); ?>');">
						<?php esc_html_e('Empty Trash', 'event-genius'); ?>
					</button>
					<input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>" />
				</div>
				<?php
			}
		}
	}

	public function process_bulk_action() {
		if (!isset($_REQUEST['_wpnonce'])) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$nonce = sanitize_text_field(wp_unslash($_REQUEST['_wpnonce']));
		if (!wp_verify_nonce($nonce, 'bulk-' . $this->_args['plural'])) {
			return;
		}

		if ('publish' === $this->current_action()) {
			$events = isset($_POST['event']) ? array_map('absint', $_POST['event']) : array();
			if (is_array($events)) {
				foreach ($events as $event_id) {
					if (current_user_can('publish_post', absint($event_id))) {
						wp_publish_post(absint($event_id));
					}
				}
			}
		} elseif ('empty_trash' === $this->current_action()) {

			$args = array(
				'post_type' => EVGE_EVENT_POST_TYPE,
				'post_status' => 'trash',
				'posts_per_page' => -1,
				'fields' => 'ids'
			);

			$trashed_events = get_posts($args);

			foreach ($trashed_events as $event_id) {
				if (current_user_can('delete_post', $event_id)) {
					wp_delete_post($event_id, true);
				}
			}

			// Instead of redirecting, just return to avoid "headers already sent" warning
			// The page will refresh and show the success message via the URL parameter
			return;
		} elseif ('delete' === $this->current_action()) {

			$events = isset($_POST['event']) ? array_map('absint', $_POST['event']) : array();
			if (is_array($events)) {
				foreach ($events as $event_id) {
					if ( current_user_can('delete_post', absint($event_id)) ) {
						wp_delete_post(absint($event_id), true);
					}
				}
			}
		} elseif ('trash' === $this->current_action()) {
			$events = isset($_POST['event']) ? array_map('absint', $_POST['event']) : array();
			if (is_array($events)) {
				foreach ($events as $event_id) {
					if ( current_user_can('edit_post', absint($event_id)) ) {
						wp_trash_post(absint($event_id));
					}
				}
			}
		} elseif ('untrash' === $this->current_action()) {
			$events = isset($_POST['event']) ? array_map('absint', $_POST['event']) : array();
			if (is_array($events)) {
				foreach ($events as $event_id) {
					if ( current_user_can('edit_post', absint($event_id)) ) {
						wp_untrash_post(absint($event_id));
					}
				}
			}
		}
	}

	public function bulk_actions($which = '') {
		if (is_null($this->_actions)) {
			$this->_actions = $this->get_bulk_actions();
		}

		if (!$this->has_items()) {
			return;
		}

		echo '<label for="bulk-action-selector-' . esc_attr($which) . '" class="screen-reader-text">' . esc_html__('Select bulk action', 'event-genius') . '</label>';
		echo '<select name="action" id="bulk-action-selector-' . esc_attr($which) . "\">\n";
		echo '<option value="-1">' . esc_html__('Bulk Actions', 'event-genius') . "</option>\n";

		foreach ($this->_actions as $name => $title) {
			$class = 'edit' === $name ? ' class="hide-if-no-js"' : '';

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo "\t" . '<option value="' . esc_attr($name) . '"' . $class . '>' . esc_html($title) . "</option>\n";
		}

		echo "</select>\n";
		$which = esc_attr( $which );

		submit_button(__('Apply', 'event-genius'), 'action', '', false, array('id' => "doaction$which"));
		echo "\n";
	}

	public function get_views() {
		$num_posts = wp_count_posts(EVGE_EVENT_POST_TYPE);
		$counts = array(
			'publish' => $num_posts->publish,
			'draft' => $num_posts->draft,
			'pending' => $num_posts->pending,
			'future' => $num_posts->future,
			'trash' => $num_posts->trash,
			'any' => 0,
		);
		$counts['any'] = array_sum($counts);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_status = isset($_GET['post_status']) ? sanitize_key($_GET['post_status']) : 'publish';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
		$base_url = admin_url('admin.php?page=' . $page);

		$status_links = array();
		$statuses = array(
			'publish' => __('Published', 'event-genius'),
			'future' => __('Scheduled', 'event-genius'),
			'draft' => __('Draft', 'event-genius'),
			'pending' => __('Pending', 'event-genius'),
			'any' => __('All', 'event-genius'),
			'trash' => __('Trash', 'event-genius'),
		);

		foreach ($statuses as $status => $label) {
			if ($counts[$status] > 0) {
				$status_links[$status] = sprintf(
					'<a href="%s"%s>%s <span class="count">(%s)</span></a>',
					esc_url(add_query_arg('post_status', $status === 'publish' ? false : $status, $base_url)),
					$current_status === $status ? ' class="current"' : '',
					$label,
					number_format_i18n($counts[$status])
				);
			}
		}

		return $status_links;
	}
}
