<?php
namespace WPEventGenius\Admin\CustomPostTypes;

use WP_List_Table;
use WP_Error;
use WPEventGenius\Common\Event\OrganizerPost;
use WPEventGenius\Common\Queries\OrganizerQuery;
use WPEventGenius\Common\Utils\FilterParams;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OrganizersWPListTable extends WP_List_Table {

	protected $organizer_query;

	public function __construct() {
		parent::__construct([
			'singular' => 'organizer',
			'plural'   => 'organizers',
			'ajax'     => false
		]);
	}

	public function get_columns() {
		$columns = array(
			'cb'        => '<input type="checkbox" />',
			'title'     => __('Name', 'event-genius'),
			'email'     => __('Email', 'event-genius'),
			'phone'    => __('Phone', 'event-genius'),
		);
		return $columns;
	}

	public function prepare_items() {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);

		$filter_params = new FilterParams();

		// Process bulk actions
		$this->process_bulk_action();

		$per_page = 20;
		$current_page = $this->get_pagenum();
		$total_items = $this->get_organizer_count();

		$this->set_pagination_args([
			'total_items' => $total_items,
			'per_page'    => $per_page,
		]);

		$args = array();
		if ( ! empty( $_GET['post_status'] ) ) {	
			$args['post_status'] = sanitize_key($_GET['post_status']);
		}
		$this->items = $this->get_organizers($args, $current_page);
	}

	public function column_default($item, $column_name) {
		$organizer_obj = new OrganizerPost( $item->ID );
		switch ($column_name) {
			case 'email':
				return esc_html($organizer_obj->get_the_email());
			case 'phone':
				return esc_html($organizer_obj->get_the_phone());
			default:
				return '';
		}
	}

	public function column_title($item) {
		$actions = array();
		$edit_link = get_edit_post_link($item->ID);
		
		$title = '<strong>';

		if ('trash' !== $item->post_status) {
			$title .= '<a class="row-title" href="' . esc_url($edit_link) . '">';
		}
		$title .= esc_html($item->post_title);
		if ('trash' !== $item->post_status) {
			$title .= '</a>';
		}

		if ($item->post_status !== 'publish') {
			$title .= ' — <span class="post-state">' . esc_html(get_post_status_object($item->post_status)->label) . '</span>';
		}
		
		$title .= '</strong>';

		if ('trash' === $item->post_status) {
			$actions['untrash'] = '<a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=evge-all-events&tab=organizers&action=untrash&id=' . $item->ID), 'untrash_post_' . $item->ID)) . '">' . esc_html__('Restore', 'event-genius') . '</a>';
			$actions['delete'] = '<a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=evge-all-events&tab=organizers&action=delete&id=' . $item->ID), 'delete_post_' . $item->ID)) . '" class="submitdelete">' . esc_html__('Delete Permanently', 'event-genius') . '</a>';
		} else {
			$actions['edit'] = '<a href="' . esc_url($edit_link) . '">' . esc_html__('Edit', 'event-genius') . '</a>';
			$actions['view'] = '<a href="' . esc_url(get_permalink($item->ID)) . '">' . esc_html__('View', 'event-genius') . '</a>';
			$actions['trash'] = '<a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=evge-all-events&tab=organizers&action=trash&id=' . $item->ID), 'trash_post_' . $item->ID)) . '" class="submitdelete">' . esc_html__('Trash', 'event-genius') . '</a>';
		}

		return $title . $this->row_actions($actions);
	}

	public function column_cb($item) {
		return sprintf(
			'<input type="checkbox" name="organizer[]" value="%s" />', esc_attr($item->ID)
		);
	}

	public function get_bulk_actions() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if (isset($_REQUEST['post_status']) && sanitize_key($_REQUEST['post_status']) === 'trash') {
			$actions = array(
				'restore' => __('Restore', 'event-genius'),
				'delete' => __('Delete Permanently', 'event-genius'),
			);
		} else {
			$actions = array(
				'trash' => __('Move to Trash', 'event-genius'),
			);
		}
		return $actions;
	}

	public function process_bulk_action() {
		if ( ! isset( $_REQUEST['_wpnonce'] ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$nonce = sanitize_text_field(wp_unslash($_REQUEST['_wpnonce']));
		if ( ! wp_verify_nonce( $nonce, 'bulk-' . $this->_args['plural'] ) ) {
			return;
		}
		$organizers = isset($_POST['organizer']) ? array_map('absint', $_POST['organizer']) : array();
		if ('delete' === $this->current_action()) {
			if (is_array($organizers)) {
				foreach ($organizers as $organizer_id) {
					if ( current_user_can('delete_post', absint($organizer_id)) ) {
						wp_delete_post(absint($organizer_id), true);
					}
				}
			}
		} elseif ('trash' === $this->current_action()) {
			if (is_array($organizers)) {
				foreach ($organizers as $organizer_id) {
					if ( current_user_can('edit_post', absint($organizer_id)) ) {
						wp_trash_post(absint($organizer_id));
					}
				}
			}
		} elseif ('untrash' === $this->current_action()) {
			if (is_array($organizers)) {
				foreach ($organizers as $organizer_id) {
					if ( current_user_can('edit_post', absint($organizer_id)) ) {
						wp_untrash_post(absint($organizer_id));
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
		echo '<select name="action' . ($which === 'bottom' ? '2' : '') . '" id="bulk-action-selector-' . esc_attr($which) . "\">\n";
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

	private function get_organizers($filter_params, $current_page) {
		$filter_params['per_page'] = isset($filter_params['per_page']) ? $filter_params['per_page'] : 20;
		$filter_params['paged'] = $current_page;

		$this->organizer_query = new OrganizerQuery($filter_params);
		$this->organizer_query->add_wp_query();
		$this->organizer_query->hydrate();
		return $this->organizer_query->get_organizers();
	}

	private function get_organizer_count() {
		$args = array(
			'post_type' => EVGE_ORGANIZER_POST_TYPE,
			'post_status' => 'publish',
		);

		$query = new \WP_Query($args);
		return $query->found_posts;
	}

public function get_views() {
		$num_posts = wp_count_posts(EVGE_ORGANIZER_POST_TYPE);
		$counts = array(
			'any' => 0,
            'publish' => $num_posts->publish,
			'draft' => $num_posts->draft,
			'pending' => $num_posts->pending,
			'future' => $num_posts->future,
			'trash' => $num_posts->trash,
		);
		$counts['any'] = array_sum($counts);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_status = isset($_GET['post_status']) ? sanitize_key($_GET['post_status']) : 'any';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
		$base_url = admin_url('admin.php?page=' . $page);

		$status_links = array();
		$statuses = array(
			'any' => __('All', 'event-genius'),
            'publish' => __('Published', 'event-genius'),
			'draft' => __('Draft', 'event-genius'),
			'pending' => __('Pending', 'event-genius'),
			'trash' => __('Trash', 'event-genius'),
		);

		foreach ($statuses as $status => $label) {
			if ($counts[$status] > 0) {
                $post_status = $status === 'any' ? false : $status;
				$status_links[$status] = sprintf(
					'<a href="%s"%s>%s <span class="count">(%s)</span></a>',
					esc_url(add_query_arg(array('post_status' => $post_status, 'tab' => 'organizers'), $base_url)),
					$current_status === $status ? ' class="current"' : '',
					$label,
					number_format_i18n($counts[$status])
				);
			}
		}

		return $status_links;
	}
}
