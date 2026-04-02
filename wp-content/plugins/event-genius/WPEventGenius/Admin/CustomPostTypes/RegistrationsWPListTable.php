<?php
namespace WPEventGenius\Admin\CustomPostTypes;

use WP_List_Table;
use WPEventGenius\Admin\Actions\RegistrationRecord;
use WPEventGenius\Common\Database;
use WPEventGenius\Common\Queries\RegistrationQuery;
use WPEventGenius\Common\Utils\FilterParams;
use WPEventGenius\Common\Utils\Formatter;
use WPEventGenius\Common\Utils\Icon;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RegistrationsWPListTable extends WP_List_Table {

    protected $registration_query;

    public function __construct() {
        parent::__construct([
            'singular' => 'registration',
            'plural'   => 'registrations',
            'ajax'     => false
        ]);
    }

    public function get_columns() {
        $columns = array(
            'cb'        => '<input type="checkbox" />',
            'name'      => __('Name', 'event-genius'),
            'registration_date'      => __('Registration Date', 'event-genius'),
            'event'     => __('Event', 'event-genius'),
            'status'    => __('Status', 'event-genius'),
            'quantity'     => __('Quantity & Cost', 'event-genius'),
        );
        return $columns;
    }

    public function column_name($item) {
	    $actions = Formatter::row_actions( $item, 'submissions', 'evge-registrations' );
		$identity = Formatter::identity( $item );
	    $name = '<strong><a href="' . esc_url( add_query_arg( array( 'page' => 'evge-registrations', 'tab' => 'registrations', 'registration_id' => absint( $item['id'] ) ), admin_url( 'admin.php' ) ) ) . '">#' . absint( $item['id'] ) . ' ' . esc_html( $identity ) . '</a></strong>';

        return sprintf('%1$s %2$s', $name, $this->row_actions($actions));
    }

	public function column_quantity($item) {
		$output = $item['quantity_cost'];

		if ( $output === '-' ) {
			return '<div class="evge-icon-text">' . Formatter::tooltip( __( 'This guest is part of a registration group with a single payment.', 'event-genius' ) ) . '</div>';
		}
		
		// Add payment status icon if available and event requires payments
		$event_post = $this->registration_query->get_associated_event( $item['event_id'] );
		if ( ! empty( $item['payment_status'] ) && $event_post && $event_post->get_accept_payments() ) {
			$output .= Formatter::get_payment_status_display( $item['payment_status'] );
		}
		
		$output = '<div class="evge-icon-text">' . $output . '</div>';
		
		return $output;
	}



    public function prepare_items() {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $filter_params_obj = new FilterParams();
		$filter_params = $filter_params_obj->get_sanitized_params();

		  // Add status filter
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_status = isset($_REQUEST['registration_status']) ? sanitize_key($_REQUEST['registration_status']) : 'active';
		$filter_params['status'] = [$current_status];


        $this->process_bulk_action();

        $per_page = 20;
        $current_page = $this->get_pagenum();
        $filter_params['per_page'] = $per_page;
        $filter_params['paged'] = $current_page;;

        $this->registration_query = new RegistrationQuery($filter_params);
		$this->registration_query->build();
		$this->registration_query->hydrate();

        $items = $this->registration_query->get_registrations();

        $total_items = $this->registration_query->get_total_count();

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
        ]);

        $this->items = $items;
    }

    public function column_default($item, $column_name) {
		if ( $column_name === 'event' ) {
			// Use filter to get the appropriate title
			$title = apply_filters( 'evge_registration_display_title', get_the_title( $item['event_id'] ), $item['event_id'], false );
			$link = apply_filters( 'evge_registration_display_link', get_edit_post_link( $item['event_id'] ), $item['event_id'] );
			return '<a href="' . esc_url( $link . '&back_page=evge-registrations' ) . '">' . esc_html( $title ) . '</a>';
		}
        return Formatter::escaped_output($item, $column_name, 'registration');
    }

    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="registrations[]" value="%s" />', $item['id']
        );
    }

	public function get_bulk_actions() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if (isset($_REQUEST['registration_status']) && sanitize_key($_REQUEST['registration_status']) === 'canceled') {
			$actions = array(
				'confirm' => __('Confirm', 'event-genius'),
				'pending' => __('Pending', 'event-genius'),
				'delete' => __('Delete Permanently', 'event-genius'),
			);
		} else {
			$actions = array(
				'confirm' => __('Confirm', 'event-genius'),
				'pending' => __('Pending', 'event-genius'),
				'cancel' => __('Cancel', 'event-genius'),
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
		if ('delete' === $this->current_action()) {


			$registrations = isset($_POST['registrations']) ? array_map( 'absint', $_POST['registrations'] ) : array();
			if (is_array($registrations)) {
				foreach ($registrations as $registration_id) {
					if ( current_user_can('manage_evge_registrations' ) ) {
						$database = new Database();

						$registration_record = new RegistrationRecord( $database, $registration_id );
						$registration_record->delete();
					}
				}
			}
		} elseif ('cancel' === $this->current_action()) {

			$registrations = isset($_POST['registrations']) ? array_map( 'absint', $_POST['registrations'] ) : array();
			if (is_array($registrations)) {
				foreach ($registrations as $registration_id) {
					if ( current_user_can('manage_evge_registrations' ) ) {
						$database = new Database();

						$registration_record = new RegistrationRecord( $database, $registration_id );
						$registration_record->update( array( 'status' => 'canceled' ), array() );
					}
				}
			}
		} elseif ('confirm' === $this->current_action()) {
			$registrations = isset($_POST['registrations']) ? array_map( 'absint', $_POST['registrations'] ) : array();
			if (is_array($registrations)) {
				foreach ($registrations as $registration_id) {
					if ( current_user_can('manage_evge_registrations' ) ) {
						$database = new Database();

						$registration_record = new RegistrationRecord( $database, $registration_id );
						$registration_record->update( array( 'status' => 'confirmed' ), array() );
					}
				}
			}
		} elseif ('pending' === $this->current_action()) {
			$registrations = isset($_POST['registrations']) ? array_map( 'absint', $_POST['registrations'] ) : array();
			if (is_array($registrations)) {
				foreach ($registrations as $registration_id) {
					if ( current_user_can('manage_evge_registrations' ) ) {
						$database = new Database();

						$registration_record = new RegistrationRecord( $database, $registration_id );
						$registration_record->update( array( 'status' => 'pending' ), array() );
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

        submit_button(__('Apply', 'event-genius'), 'action', '', false, array('id' => "doaction$which"));
        echo "\n";
    }

	protected function get_views() {
		$status_links = array();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_status = isset($_REQUEST['registration_status']) ? sanitize_key($_REQUEST['registration_status']) : 'active';
		

		// Get registration counts
		$registration_query = new RegistrationQuery([]);
		$counts = $registration_query->get_status_counts();
		
		// Define all possible statuses and their labels
		$statuses = array(
			'active' => array(
				'label' => __('Active', 'event-genius'),
				'count' => (isset($counts['confirmed']) ? $counts['confirmed'] : 0) + 
						  (isset($counts['pending']) ? $counts['pending'] : 0)
			),
			'confirmed' => array(
				'label' => __('Confirmed', 'event-genius'),
				'count' => isset($counts['confirmed']) ? $counts['confirmed'] : 0
			),
			'pending' => array(
				'label' => __('Pending', 'event-genius'),
				'count' => isset($counts['pending']) ? $counts['pending'] : 0
			),
			'canceled' => array(
				'label' => __('Canceled', 'event-genius'),
				'count' => isset($counts['canceled']) ? $counts['canceled'] : 0
			)
		);
		
		foreach ($statuses as $status => $data) {
			if ($data['count'] > 0) {
				$status_links[$status] = sprintf(
					'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
					esc_url(add_query_arg('registration_status', $status, admin_url('admin.php?page=evge-registrations'))),
					($current_status === $status) ? ' class="current"' : '',
					$data['label'],
					$data['count']
				);
			}
		}
		
		return $status_links;
	}
}

