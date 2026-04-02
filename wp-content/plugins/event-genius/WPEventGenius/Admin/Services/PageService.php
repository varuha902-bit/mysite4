<?php

namespace WPEventGenius\Admin\Services;

use WPEventGenius\Admin\Actions\Export\CSVExport;
use WPEventGenius\Admin\Actions\Export\Exportable;
use WPEventGenius\Admin\Actions\PaymentRecord;
use WPEventGenius\Admin\Actions\RegistrationRecord;
use WPEventGenius\Admin\Page\AllEvents\RegistrationOverviewPage;
use WPEventGenius\Admin\Page\AllEvents\EventSinglePage;
use WPEventGenius\Admin\Page\AllEventsBasePage;
use WPEventGenius\Admin\Page\BasePage;
use WPEventGenius\Common\Database;
use WPEventGenius\Common\Event\Event;
use WPEventGenius\Common\Event\EventPost;
use WPEventGenius\Common\Registration\Communicator\CommunicatorAfterConfirmed;
use WPEventGenius\Common\Registration\Field\FieldHandler;
use WPEventGenius\Common\Registration\Form\Form;
use WPEventGenius\Common\Utils\Utils;
use WPEventGenius\Common\Series\EventSeriesRepository;
use WPEventGenius\Common\Registration\Payment\Cart\Cart;
use WPEventGenius\Common\Registration\Payment\Gateways\Offline;
use WPEventGenius\Common\Registration\Payment\PaymentHandler;
use WPEventGenius\Common\Registration\Registration\RegistrationGroup;
use WPEventGenius\Common\Services\RegistrationObjectFactory;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class PageService {
	public function __construct() {
	}

	public function init_hooks() {
		add_action( 'wp_ajax_evge_identifier_tools_modal_content', array( $this, 'identifier_tools_modal_content' ) );
		add_action( 'wp_ajax_evge_event_actions_modal_content', array( $this, 'event_actions_modal_content' ) );
		add_action( 'wp_ajax_evge_send_confirmation_email', array( $this, 'ajax_send_confirmation_email' ) );

		add_action( 'admin_init', array( $this, 'csv_listener' ) );
		add_action( 'admin_init', array( $this, 'post_actions_listener' ) );
		add_action( 'admin_init', array( $this, 'recurrence_debug_listener' ) );
		add_action( 'evge_admin_action_listener', array( $this, 'action_listener' ) );
		add_action( 'admin_footer', array( $this, 'maybe_check_series_queue' ) );

		add_action(EVGE_EVENT_CATEGORY_TYPE . '_pre_add_form', array( $this, 'custom_content' ) );
		add_action(EVGE_EVENT_TAG_TYPE . '_pre_add_form', array( $this, 'custom_content' ) );

		add_action( 'evge_admin_page_content', array( $this, 'content' ) );
		add_action( 'evge_admin_modal', array( $this, 'modal' ) );
	}

	public function custom_content() {
		$page = new AllEventsBasePage();
		?>
<div class="evge-header-nav-move">
<?php
		include_once EVGE_ADMIN_TEMPLATE_PATH . 'evge/partials/header-bar.php';
		$page->navigation_html( $page->navigation_args() )
			?>
</div>
<?php
	}

	public function content() {
		// phpcs:ignore WordPress.Security.NonceVerification
		$page_name = isset( $_GET['page'] ) ? str_replace( 'evge-', '', sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : $page_name;
		$active_tab = 'registrations';

		if ( $active_tab === 'form-builder' ) {
			if ( $tab === 'single' ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$event_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

				$page = new EventSinglePage( $event_id );

			} else {
				$page = new RegistrationOverviewPage();

			}
		} elseif ( $active_tab === 'registrations' ) {
			if ( $tab === 'single' ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$event_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

				$page = new EventSinglePage( $event_id );

			} else {
				$page = new RegistrationOverviewPage();

			}
		} elseif ( false ) {
			$page = new RegistrationOverviewPage();
		}
		$page->build();
		$page->render( $active_tab );
	}

	public function modal() {
		include_once trailingslashit( EVGE_PLUGIN_PATH ) . 'templates/event-genius/common/modal.php';
	}

	public function identifier_tools_modal_content() {
		if ( ! current_user_can( 'view_evge_registrations' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field(wp_unslash($_REQUEST['nonce'])), 'evge-admin' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		$registration_id = ! empty( $_REQUEST['registration_id'] ) ? absint( $_REQUEST['registration_id'] ) : 0;
		$transaction_id = ! empty( $_REQUEST['transaction_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['transaction_id'] ) ) : '';
		$subtab = ! empty( $_REQUEST['subtab'] ) ? sanitize_key( $_REQUEST['subtab'] ) : 'submissions';
		$selected = ! empty( $_REQUEST['selected'] ) ? sanitize_key( $_REQUEST['selected'] ) : '';
		$selected_registrations = ! empty( $_REQUEST['selected_registrations'] ) ? array_map( 'absint', $_REQUEST['selected_registrations'] ) : array();

		$return = array(
			'submission_status' => 'success',
			'html' => 'Unknown error'
		);
		$registration_record = new RegistrationRecord( new Database(), $registration_id );
		$payment_record = new PaymentRecord( new Database(), $transaction_id );
		if ( $selected === 'delete' ) {
			if ( 'payments' === $subtab ) {
				$delete_html = $this->get_delete_html( $payment_record );
			} else {
				$delete_html = $this->get_delete_html( $registration_record );
			}
			$return['html'] = $delete_html;
		} elseif ( $selected === 'edit' ) {
            $add_html = $this->get_edit_html( $registration_record );
			$return['html'] = $add_html;
		}


		wp_send_json_success( $return );

	}

	public function event_actions_modal_content() {
		if ( ! current_user_can( 'manage_evge_registrations' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field(wp_unslash($_REQUEST['nonce'])), 'evge-admin' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}
		$event_id = ! empty( $_REQUEST['event_id'] ) ? absint( $_REQUEST['event_id'] ) : 0;

		$registration_id = ! empty( $_REQUEST['registration_id'] ) ? absint( $_REQUEST['registration_id'] ) : 0;
		$transaction_id = ! empty( $_REQUEST['transaction_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['transaction_id'] ) ) : '';
		$subtab = ! empty( $_REQUEST['subtab'] ) ? sanitize_key( wp_unslash( $_REQUEST['subtab'] ) ) : 'submissions';
		$selected = ! empty( $_REQUEST['selected'] ) ? sanitize_key( $_REQUEST['selected'] ) : '';
		$selected_registrations = ! empty( $_REQUEST['selected_registrations'] ) ? array_map( 'absint', $_REQUEST['selected_registrations'] ) : array();

		$return = array(
			'submission_status' => 'success',
			'html' => 'Unknown error'
		);
		$registration_record = new RegistrationRecord( new Database(), $registration_id );
		if ( $selected === 'bulk_delete' ) {
			$delete_html = $this->get_bulk_delete_html( $selected_registrations );
			$return['html'] = $delete_html;
		} elseif ( $selected === 'add' ) {
			if ( $subtab === 'payments' ) {
				$add_html = $this->get_edit_payment_html( $registration_record, $event_id );
			} else {
				$add_html = $this->get_add_html( $registration_record, $event_id );
			}
			$return['html'] = $add_html;
		}

		wp_send_json_success( $return );

	}

    public function get_add_html( RegistrationRecord $registration_record, $event_id = 0 ) {
        ob_start();
        include_once( EVGE_ADMIN_TEMPLATE_PATH . 'registration/partials/edit-registration-form.php' );
        $return = ob_get_contents();
        ob_end_clean();
        return $return;

    }

	public function get_delete_html( $registration_or_payment_record ) {
		ob_start();
		include_once( EVGE_ADMIN_TEMPLATE_PATH . 'registration/partials/modal-content/delete-confirmation.php' );

		$return = ob_get_contents();
		ob_end_clean();

		return $return;
	}

	public function get_bulk_delete_html( $registration_or_payment_record ) {
		ob_start();
		include_once( EVGE_ADMIN_TEMPLATE_PATH . 'registration/partials/modal-content/delete-confirmation.php' );

		$return = ob_get_contents();
		ob_end_clean();

		return $return;
	}

	public function get_edit_html( RegistrationRecord $registration_record, $event_id = 0 ) {
		ob_start();
		include_once( EVGE_ADMIN_TEMPLATE_PATH . 'evge/registrations/single-registration.php' );
		$return = ob_get_contents();
		ob_end_clean();

		return $return;
	}

	/**
	 * Get HTML for the add/edit payment modal (Pro only).
	 * When not Pro, returns an upsell message so the modal does not break.
	 *
	 * @param RegistrationRecord $registration_record Registration record.
	 * @param int                $event_id            Event post ID.
	 * @return string HTML for the modal content.
	 */
	public function get_edit_payment_html( RegistrationRecord $registration_record, $event_id = 0 ) {
		if ( ! function_exists( 'evge_is_pro_tier' ) || ! evge_is_pro_tier() ) {
			return '<div class="evge-narrow-modal-content"><p>' . esc_html__( 'Add and edit payment records is a Pro feature. Upgrade to manage payments from the registrations screen.', 'event-genius' ) . '</p></div>';
		}

		$registration_data = $registration_record->get_registration_data();
		if ( empty( $event_id ) && ! empty( $registration_data['event_id'] ) ) {
			$event_id = (int) $registration_data['event_id'];
		}
		$payment_data = $registration_record->get_payment_data();
		$transaction_id = isset( $payment_data['transaction_id'] ) ? $payment_data['transaction_id'] : '';

		if ( empty( $payment_data ) && ! empty( $registration_data ) ) {
			$event_post = new EventPost( $event_id );
			$event_cost = is_numeric( $event_post->get_the_cost_amount() ) ? floatval( $event_post->get_the_cost_amount() ) : 0;
			$quantity = isset( $registration_data['quantity'] ) ? (int) $registration_data['quantity'] : 1;
			$payment_data = array(
				'payment_gross'   => ! empty( $registration_data['payment_gross'] ) ? $registration_data['payment_gross'] : number_format( $event_cost * $quantity, 2, '.', '' ),
				'currency_code'   => ! empty( $registration_data['currency_code'] ) ? $registration_data['currency_code'] : 'USD',
				'gateway'         => 'offline',
				'payment_date'    => gmdate( 'Y-m-d H:i:s' ),
				'invoice_id'      => Utils::generate_invoice_number( $registration_data['id'] ),
				'payment_id'      => 'offline-' . $registration_data['id'] . '-' . time(),
				'transaction_id'  => '',
				'business'        => 'Offline',
			);
		}

		ob_start();
		include EVGE_PLUGIN_PATH . 'admin/templates/pro/registration/partials/modal-content/edit-payment.php';
		return ob_get_clean();
	}

	/**
	 * Handle AJAX request to send confirmation email (from registration management modal).
	 * Available in all tiers so the "Send Confirmation Email" button works in free and Pro.
	 */
	public function ajax_send_confirmation_email() {
		if ( ! current_user_can( 'manage_evge_registrations' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'event-genius' ) ) );
		}
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'evge-admin' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'event-genius' ) ) );
		}
		$registration_id = isset( $_POST['registration_id'] ) ? absint( $_POST['registration_id'] ) : 0;
		$event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
		if ( empty( $registration_id ) || empty( $event_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request data.', 'event-genius' ) ) );
		}
		$database = new Database();
		$registration_group = new RegistrationGroup( $database );
		$registration_group->set_from_existing( $registration_id );
		if ( $registration_group->not_found() ) {
			wp_send_json_error( array( 'message' => __( 'Registration not found.', 'event-genius' ) ) );
		}
		$registration_group->build_payment_handler( new PaymentHandler( new Cart() ) );
		$event = new Event( $event_id );
		$communicator = new CommunicatorAfterConfirmed( $registration_group, $event );
		if ( $communicator->send_registration_confirmation_email() ) {
			wp_send_json_success( array( 'message' => __( 'Confirmation email sent.', 'event-genius' ) ) );
		}
		wp_send_json_error( array( 'message' => __( 'Failed to send confirmation email.', 'event-genius' ) ) );
	}

	public function action_listener() {
		if ( ! current_user_can( 'manage_evge_registrations' ) ) {
			return;
		}
		if ( empty( $_POST['evge_action'] ) ) {
			return;
		}

		$action = sanitize_key( $_POST['evge_action'] );

		if ( $action === 'cancel' ) {
			return;
		}

		$type = 'submissions';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! empty( $_POST['evge_transaction_id'] ) ) {
			$transaction_id = str_replace( ' ', '', sanitize_text_field( wp_unslash( $_POST['evge_transaction_id'] ) ) );
			$type = 'payments';
		} elseif ( ! empty( $_POST['evge_payment_status'] ) ) {
			$transaction_id = 0;
			$type = 'payments';
		}
		$database = new Database();

		if ( $action === 'delete_confirm' ) {
			$nonce = isset($_POST['evge-delete-registration-records-nonce']) ? sanitize_text_field( wp_unslash($_POST['evge-delete-registration-records-nonce']) ) : '';

			if ( ! wp_verify_nonce( $nonce, 'evge-delete-registration-records' ) ) {
				return;
			}

			if (!isset($_POST['ids']) || !isset($_POST['type']) || !isset($_POST['evge-delete-registration-records-nonce'])) {
				return;
			}
			
			$ids = explode( ',', sanitize_text_field( wp_unslash($_POST['ids']) ) );
			$type = sanitize_key( wp_unslash($_POST['type']) );

			if ( 'submissions' === $type ) {
				if ( count( $ids ) === 1 ) {
					$registration_record = new RegistrationRecord( $database, $ids[0] );
					$registration_record->delete();
				} else {
					foreach ( $ids as $registration_id ) {
						$registration_record = new RegistrationRecord( $database, $registration_id );
						$registration_record->delete();
					}

				}
			} else {
				foreach ( $ids as $transaction_id ) {
					$payment_record = new PaymentRecord( $database, $transaction_id );
					$payment_record->delete();
				}

			}

		} elseif ( $action === 'submit_create' || $action === 'submit_edit' ) {
			$nonce = isset($_POST['evge-edit-registration-nonce']) ? sanitize_text_field(wp_unslash($_POST['evge-edit-registration-nonce'])) : '';

			if (!wp_verify_nonce($nonce, 'evge-edit-registration')) {
				return;
			}
			$event_id = isset($_POST['evge_event_id']) ? absint($_POST['evge_event_id']) : 0;
			$registration_id = isset($_POST['evge_registration_id']) ? absint($_POST['evge_registration_id']) : 0;
			$transaction_id = isset($_POST['evge_transaction_id']) ? sanitize_text_field(wp_unslash($_POST['evge_transaction_id'])) : '';


			if ('payments' === $type) {
				$payment_record = new PaymentRecord(new Database(), $transaction_id);
				$payment_columns = Utils::get_payment_columns();
				$to_update = array();

				foreach ($payment_columns as $key => $value) {
					if ( ! empty( $_POST[ 'evge_' . $key ] ) ) {
						$to_update[ $key ] = sanitize_text_field( wp_unslash( $_POST[ 'evge_' . $key ] ) );
					} elseif ( ! empty( $_POST[ $key ] ) ) {
						$to_update[ $key ] = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
					}
				}

				if ( empty( $transaction_id ) ) {
					$payment_record->create($registration_id, $to_update);
				} else {
					$payment_record->update($to_update);
				}

			} else {

				$registration_record = new RegistrationRecord(new Database(), $registration_id);
				
				$standard_data = array(
					'event_id' => $event_id,
					'status' => isset($_POST['evge_status']) ? sanitize_key( wp_unslash( $_POST['evge_status'] ) ) : 'pending',
					'quantity' => isset($_POST['evge_quantity']) ? sanitize_key( wp_unslash( $_POST['evge_quantity'] ) ) : 1
				);

				// Get series ID if event is part of a series
				$series_repo = new EventSeriesRepository(new Database());
				$series_id = $series_repo->get_series_id_for_event($event_id);
				$standard_data['series_id'] = $series_id ? $series_id : 0;


				// Get the form through the event context like we do elsewhere
				$event_post = new EventPost($event_id);
				$form = $event_post->get_form();
				$fields = $form->get_fields();

				$meta_data = array();

				// If event is part of a series, store event details as meta
				if ($series_id) {
					$event_post = new EventPost($event_id);
					$meta_data['_event_info'] = $event_post->get_the_title() . ' ||| ' . $event_post->get_the_date_summary();
				}
				foreach ($fields as $field) {
					
					$field_slug = $field->get_slug();
					$field_key = 'evge_' . $field->get_slug();
					if ($field->get_type() === 'single-checkbox') {
						$meta_data[$field_slug] = '';
					}

					if ($field->get_type() === 'checkbox') {
						$meta_data[$field_slug] = [];
					}
					// Skip if the field isn't in submitted data
					if (!isset($_POST[$field_key])) {
						continue;
					}

					// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
					$input_value = isset($_POST[$field_key]) ? $_POST[$field_key] : '';
					$type = $field->get_type();
					
					switch ($type) {
						case 'textarea':
							// Allow newlines and basic HTML
							$new_val = wp_kses(wp_unslash($input_value), [
								'br' => [],
								'p' => [],
								'strong' => [],
								'em' => [],
							]);
							break;

						case 'checkbox':
							// Handle array of checkbox values
							if (is_array($input_value)) {
								$new_val = array_map(function($value) {
									return sanitize_text_field(wp_unslash($value));
								}, $input_value);
							} else {
								$new_val = sanitize_text_field(wp_unslash($input_value));
							}
							break;

						case 'single-checkbox':
							// Ensure boolean value
							$new_val = !empty($input_value);
							break;

						case 'email':
							$new_val = sanitize_email(wp_unslash($input_value));
							break;

						case 'phone':
							// Keep only digits, plus, parentheses, and dashes
							$new_val = preg_replace('/[^\d\+\-\(\)]/', '', wp_unslash($input_value));
							break;

						case 'select':
						case 'radio':
							// Ensure value matches one of the allowed options
							$new_val = sanitize_text_field(wp_unslash($input_value));
							$valid_options = array_column($field->get_options(), 'value');
							if (!in_array($new_val, $valid_options, true)) {
								$new_val = ''; // Invalid option submitted
							}
							break;

						case 'text':
						default:
							$new_val = sanitize_text_field(wp_unslash($input_value));
							break;
					}

					$meta_data[$field_slug] = $new_val;
				}

				$meta_data = apply_filters( 'evge_handle_admin_edit_value', $meta_data, $fields, $registration_record );

				if ($action === 'submit_edit' && $registration_id !== 0) {
					$registration_record->update($standard_data, $meta_data);
					
					// Handle payment logic for admin edits as well
					$registration_group = new RegistrationGroup( $database );
					$registration_group->set_from_existing( $registration_record->get_registration_id() );
					
					// Only build payment handler if registration was found
					if ( ! $registration_group->not_found() ) {
						$payment_handler = new PaymentHandler( new Cart() );
						$registration_group->build_payment_handler( $payment_handler );
						
						// Only set payment handler status if payment handler was successfully built
						if ( $registration_group->payment_handler() !== null ) {
							$registration_group->payment_handler()->set_status( $registration_group->get_payment_status() );
						}
						
						// Check if payment is required and not already completed
						$event_post = new \WPEventGenius\Common\Event\EventPost( $registration_record->get_event_id() );
						$should_trigger_payment = $registration_group->has_cost() && $event_post->get_accept_payments();
						
						if ( $should_trigger_payment ) {
							$payment_status = $registration_group->get_payment_status();
							if ( ! in_array( $payment_status, array( 'complete', 'processing' ) ) ) {
								$registration_group->get_main()->set_registration_meta( 'payment_required', '1' );
							} else {
								$abandoned_payment_service = new \WPEventGenius\Pro\Services\AbandonedPaymentService( new Database() );
								$abandoned_payment_service->delete_payment_required_meta( $registration_group->get_main()->get_registration_data( 'id' ) );
							}
						}
					}
				} else {
					$registration_record->create(array_merge($standard_data, $meta_data), $fields);

					$registration_group = new RegistrationGroup( $database );

					$registration_group->set_from_existing( $registration_record->get_registration_id() );

					// Only build payment handler if registration was found
					if ( ! $registration_group->not_found() ) {
						$payment_handler = new PaymentHandler( new Cart() );
		
						$registration_group->build_payment_handler( $payment_handler );
						
						// Only set payment handler status if payment handler was successfully built
						if ( $registration_group->payment_handler() !== null ) {
							$registration_group->payment_handler()->set_status( $registration_group->get_payment_status() );
						}

						$gateway = new Offline();

						// Pass skip_confirmation=true to prevent emails when creating manually in admin
						$payment_record = $gateway->create_payment( $registration_group, true );
					}
				}
			}

		} elseif ( $action === 'export_csv' ) {
			// something went wrong this should have been handled sooner
		}

	}

	public function csv_listener() {
		if ( ! current_user_can( 'manage_evge_registrations' ) ) {
			return;
		}

		if ( empty( $_POST['evge_action'] ) ) {
			return;
		}

		$action = sanitize_key( wp_unslash( $_POST['evge_action'] ) );

		if ( $action !== 'export_csv' ) {
			return;
		}


		$event_id = isset( $_POST['evge_event_id'] ) ? absint( $_POST['evge_event_id'] ) : 0;
		$nonce = isset( $_POST['evge_csv_export_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['evge_csv_export_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'evge_csv_export' ) ) {
			return;
		}

		$page = new EventSinglePage( $event_id );

		$page->build();
		$event = $page->get_event();
		$table_columns = $page->table_columns( $event->details['form'], array( 'include' => array( 'status' ), 'no_identity' => true ) );

		// Allow Standard tier to add additional columns (payment, attendance, etc.)
		$table_columns = apply_filters( 'evge_csv_export_table_columns', $table_columns, $event->registrations, $event_id );

		$event_post = new EventPost( $event_id );
		$header = array(
			array( $event_post->get_the_title() ),
			array( $event_post->get_the_venue_title() ),
			array( $event_post->get_the_full_date() ),
		);

		$rows = array();

		foreach ( $event->registrations as $index => $registration ) {
			$row_data = array();
			
			// Build initial row data from registration
			foreach ( $table_columns as $key => $label ) {
				if ( isset( $registration[ $key ] ) ) {
					$row_data[ $key ] = $registration[ $key ];
				}
			}
			
			// Allow Standard tier to add additional row data (payment, attendance, etc.)
			$row_data = apply_filters( 'evge_csv_export_row_data', $row_data, $registration, $table_columns, $event_id );
			
			// Format and add to rows
			foreach ( $table_columns as $key => $label ) {
				$value = isset( $row_data[ $key ] ) ? $row_data[ $key ] : '';
				
				// If the value is an array, implode it early as we can only display strings
				if ( is_array( $value ) ) {
					$value = implode( ', ', $value );
				}
				
				// If the value is a boolean, convert it to a string
				if ( is_bool( $value ) && $value === true ) {
					$value = '✅ true';
				}
				
				$formatted = apply_filters( 'evge_export_registration_output', $value, $key, $event_id );
				$rows[ $index ][ $key ] = $formatted;
			}
		}


		$exportable = new Exportable();
		$exportable->set_header( $header );
		$exportable->set_columns( $table_columns );
		$exportable->set_rows( $rows );
		$export = new CSVExport( $exportable );
		$export->generate( array( 'event_id' => $event_id ) );
	}

	public function post_actions_listener() {
		if ( ! current_user_can( 'edit_evge_events' ) ) {
			return;
		}

		if ( empty( $_GET['action'] ) ) {
			return;
		}

		if ( empty( $_GET['page'] ) ) {
			return;
		}

		if ( empty( $_GET['id'] ) ) {
			return;
		}

		if ( empty( $_GET['_wpnonce'] ) ) {
			return;
		}

		$page = sanitize_text_field( wp_unslash( $_GET['page'] ) );

		if ( strpos( $page, 'evge-' ) !== 0 ) {
			return;
		}

		$redirect_url = admin_url( 'admin.php?page=' . $page );

		if ( ! empty( $_GET['tab'] ) ) {
			$redirect_url = add_query_arg( array( 'tab' => sanitize_key( wp_unslash( $_GET['tab'] ) ) ), $redirect_url );
		}
		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
		$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		// CAPABILITY INCONSISTENCY: Using 'edit_evge_event' (singular) but plugin uses 'edit_evge_events' (plural)
		// Should use: current_user_can( 'edit_evge_events' ) or current_user_can( 'edit_post', $id ) for post-specific check
		if ( ! current_user_can( 'edit_post', $id ) ) {
			return;	
		}

		$query_action = 'trashed';
		if ($action === 'trash' && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'trash_post_' . $id)) {
			wp_trash_post($id);
			$query_action = 'trashed';

		} elseif ($action === 'untrash' && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'untrash_post_' . $id)) {
			wp_untrash_post($id);
			$query_action = 'untrashed';

		} elseif ($action === 'delete' && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'delete_post_' . $id)) {
			wp_delete_post($id, true);
			$query_action = 'deleted';
		}
		$redirect_url = add_query_arg( array( $query_action => 1 ), $redirect_url );

		wp_redirect($redirect_url);
		exit;
	}

	/**
	 * Handle recurrence debugging actions
	 * 
	 * @return void
	 */
	public function recurrence_debug_listener() {
		// CAPABILITY REVIEW: Using 'manage_options' for debug actions - this may be intentional for admin-only debug features
		// Consider if this should use 'edit_evge_events' or remain as 'manage_options' for security
		// Early return if not a debug action or user lacks capability
		if (!isset($_GET['evge_debug_action']) || !current_user_can('manage_options')) {
			return;
		}

		// Early return if nonce verification fails
		$nonce = isset($_GET['_wpnonce']) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if (!wp_verify_nonce($nonce, 'evge_toggle_debug_mode')) {
			return;
		}

		$action = isset( $_GET['evge_debug_action'] ) ? sanitize_key( wp_unslash( $_GET['evge_debug_action'] ) ) : '';
		
		switch ($action) {
			case 'enable':
				set_transient('evge_recurrence_debug_mode', time() + HOUR_IN_SECONDS, HOUR_IN_SECONDS);
				break;

			case 'disable':
				delete_transient('evge_recurrence_debug_mode');
				\WPEventGenius\Common\Utils\Logger\RecurrenceLogger::clear_log();
				break;

			case 'process':
				if (get_transient('evge_recurrence_debug_mode')) {
					try {
						$queue = new \WPEventGenius\Common\Series\Queue\SeriesQueue(new \WPEventGenius\Common\Database());
						$queue->process_batch();
					} catch (\Exception $e) {
						// Error will be shown in the UI
					}
				}
				break;

			case 'delete':
				if (get_transient('evge_recurrence_debug_mode')) {
					try {
						$queue = new \WPEventGenius\Common\Series\Queue\SeriesQueue(new \WPEventGenius\Common\Database());
						$queue->clear_queue();
					} catch (\Exception $e) {
						// Error will be shown in the UI
					}
				}
				break;

			case 'clear_log':
				if (get_transient('evge_recurrence_debug_mode')) {
					try {
						\WPEventGenius\Common\Utils\Logger\RecurrenceLogger::clear_log();
					} catch (\Exception $e) {
						// Error will be shown in the UI
					}
				}
				break;

			case 'delete_orphaned':
				if (get_transient('evge_recurrence_debug_mode')) {
					$cron_service = new \WPEventGenius\Common\Services\CronService(new \WPEventGenius\Common\Database());
					$cron_service->force_cleanup();
				}
				break;
		}

		// Get the current URL and remove debug-related query args
		$redirect_url = remove_query_arg(array('evge_debug_action', '_wpnonce'));
		
		// Add a success message based on the action
		$message = '';
		switch ($action) {
			case 'enable':
				$message = 'debug_mode_enabled';
				break;
			case 'disable':
				$message = 'debug_mode_disabled';
				break;
			case 'process':
				$message = 'queue_processed';
				break;
			case 'delete':
				$message = 'queue_deleted';
				break;
			case 'clear_log':
				$message = 'log_cleared';
				break;
			case 'delete_orphaned':
				$message = 'orphaned_events_deleted';
				break;
		}
		
		if ($message) {
			$redirect_url = add_query_arg('message', $message, $redirect_url);
		}

		// Safe redirect
		wp_safe_redirect($redirect_url);
		exit;
	}

	/**
	 * Check if there are items in the series queue and process them directly
	 */
	public function maybe_check_series_queue() {
		// Only run on Events Genius admin pages
		$screen = get_current_screen();
		// phpcs:ignore
		if (!$screen || (strpos($screen->id, 'evge') === false && strpos($_GET['page'] ?? '', 'evge') === false)) {
			return;
		}

		try {
			// Check if there are items in the queue
			$db = new \WPEventGenius\Common\Database();
			$queue = new \WPEventGenius\Common\Series\Queue\SeriesQueue($db);
			
			// Don't process if debug mode is enabled
			if ($queue->is_debug_mode_enabled()) {
				return;
			}
			
			$queue_items = $queue->get_queue();
			
			if (!empty($queue_items)) {
				// Process a batch of the queue directly
				$queue->process_batch();
			}
		} catch (\Exception $e) {
			
		}
	}


}