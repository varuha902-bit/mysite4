<?php
namespace WPEventGenius\Common\Services;

use WPEventGenius\Common\Database;
use WPEventGenius\Common\Event\Event;
use WPEventGenius\Common\Event\EventPost;
use WPEventGenius\Common\Registration\Communicator\CommunicatorAfterCancelRequest;
use WPEventGenius\Common\Registration\Communicator\CommunicatorAfterCancel;
use WPEventGenius\Common\Registration\Field\FieldHandler;
use WPEventGenius\Common\Registration\Form\Form;
use WPEventGenius\Common\Registration\Registration\RegistrationGroup;
use WPEventGenius\Common\Utils\EventExport\iCal;

use WPEventGenius\Common\Utils\Settings;
use WPEventGenius\Common\Utils\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class ActionService {

	/**
	 * @var RegistrationObjectFactory
	 */
	private $object_factory;

	/**
	 * @param RegistrationObjectFactory|null $object_factory
	 */
	public function __construct( $object_factory = null ) {
		$this->object_factory = $object_factory ?: new RegistrationObjectFactory();
	}

	public function init_hooks() {
		add_action( 'template_redirect', array( $this, 'maybe_ical_export' ) );

		add_action( 'wp_footer', array( $this, 'action_listeners' ), 99 );

		add_action( 'wp_ajax_evge_registration_cancel_submit', array( $this, 'process_cancel_submission' ) );
		add_action( 'wp_ajax_nopriv_evge_registration_cancel_submit', array( $this, 'process_cancel_submission' ) );

		add_action( 'wp_ajax_evge_cancel_request_confirm', array( $this, 'process_cancel_request_confirm' ) );
		add_action( 'wp_ajax_nopriv_evge_cancel_request_confirm', array( $this, 'process_cancel_request_confirm' ) );

		add_action( 'wp_ajax_evge_get_shortcode_content', array( $this, 'get_attendee_list' ) );
		add_action( 'wp_ajax_nopriv_evge_get_shortcode_content', array( $this, 'get_attendee_list' ) );
	}

	public function maybe_ical_export() {
		//phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['ical'] ) ) {
			return;
		}

		// Handle single event iCal export
		if ( ! is_singular( EVGE_EVENT_POST_TYPE ) ) {
			return;
		}

		global $post;

		try {
			$ical = new iCal( 'single' );

			$ical->set_events( array( $post ) );
			$ical->export();
			die();
		} catch ( \Exception $e ) {
			// Log the error
			$error_message = sprintf(
				/* translators: 1: Error message, 2: Event ID */
				__( 'iCal export error: %1$s (Event ID: %2$d)', 'event-genius' ),
				$e->getMessage(),
				isset( $post->ID ) ? $post->ID : 0
			);
			
			error_log( 'Event Genius iCal Export Error: ' . $error_message );
			error_log( 'Stack trace: ' . $e->getTraceAsString() );
			
			// Log to DebugLogger if available
			if ( class_exists( '\WPEventGenius\Common\Utils\Logger\DebugLogger' ) ) {
				\WPEventGenius\Common\Utils\Logger\DebugLogger::log(
					$error_message,
					array(
						'event_id' => isset( $post->ID ) ? $post->ID : 0,
						'exception' => $e->getMessage(),
						'trace' => $e->getTraceAsString(),
					)
				);
			}
			
			// Send a proper error response
			status_header( 500 );
			wp_die(
				esc_html__( 'An error occurred while generating the calendar file. Please try again later.', 'event-genius' ),
				esc_html__( 'Calendar Export Error', 'event-genius' ),
				array( 'response' => 500 )
			);
		}
	}

	public function action_listeners() {
		//phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['evge_action'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = sanitize_key( wp_unslash( $_GET['evge_action'] ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$key = ! empty( $_GET['evge_key'] ) ? sanitize_text_field( wp_unslash( $_GET['evge_key'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$event_id = ! empty( $_GET['evge_post'] ) ? absint( $_GET['evge_post'] ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$confirm_needed = ! empty( $_GET['evge_confirm'] ) ? absint( $_GET['evge_confirm'] ) : false;

		if ( $action === 'cancel' ) {
			$this->process_cancel_request( $key, $event_id, $confirm_needed );
		}

		if ( empty( $key ) ) {
			return;
		}

		do_action( 'evge_action_listeners', $action, $key, $event_id, $confirm_needed );
	}

	public function process_cancel_submission() {
		//phpcs:ignore WordPress.Security.NonceVerification
		if ( isset( $_POST['lang'] ) && ! empty( $GLOBALS['sitepress'] ) && $GLOBALS['sitepress'] instanceof \SitePress ) {
			// phpcs:ignore WordPress.Security.NonceVerification
			$lang = isset($_POST['lang']) ? sanitize_text_field(wp_unslash($_POST['lang'])) : '';
			global $sitepress;
			$sitepress->switch_lang( $lang, true );
		}

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! isset( $_POST['event_id'] ) ) {
			wp_send_json_error();
		}

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! isset( $_POST['evge_cancel_email'] ) ) {
			wp_send_json_error();
		}

		// phpcs:ignore WordPress.Security.NonceVerification
		$event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification
		$cancel_email = isset($_POST['evge_cancel_email']) ? sanitize_email(wp_unslash($_POST['evge_cancel_email'])) : '';

		do_action( 'evge_before_process_cancel_submission', $event_id, $cancel_email );

		$event = new Event( $event_id );
		$db = new Database();

		$results = $db->search_event_registrations( $event_id, $cancel_email );

		if ( empty( $results ) ) {
			$return = array(
				'submission_status' => 'error',
				'error_fields' => array( 'cancel_email' ),
				'response_html' => ''
			);
			wp_send_json_success( $return );
		}

		if ( ! empty( $results[0]['id'] ) ) {
			$registration_group = new RegistrationGroup($db);
			$registration_group->set_from_existing( $results[0]['id'] );
			$registration = $registration_group->get_main();

			$communicator = new CommunicatorAfterCancelRequest( $registration_group, $event, $registration );
			$communicator->execute();

			$placeholder = $this->object_factory->create_placeholders( $registration, $event, 'action' );
			$modal_settings = array();
			$modal_settings_json = wp_json_encode( $modal_settings );
			$classes = '';
			$html = $placeholder->replace( Settings::get( 'cancel_request_success_message' ) );

			$icon_html = '<div class="evge-alert-icon evge-email-icon evge-success"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M64 112c-8.8 0-16 7.2-16 16v22.1L220.5 291.7c20.7 17 50.4 17 71.1 0L464 150.1V128c0-8.8-7.2-16-16-16H64zM48 212.2V384c0 8.8 7.2 16 16 16H448c8.8 0 16-7.2 16-16V212.2L322 328.8c-38.4 31.5-93.7 31.5-132 0L48 212.2zM0 128C0 92.7 28.7 64 64 64H448c35.3 0 64 28.7 64 64V384c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V128z"/></svg></div>';
			$template_path = EVGE()->template_manager()->locate_template('registration/forms/cancel/cancel-request-modal-content.php');
			if ($template_path) {
				ob_start();
				include $template_path;
				$response_html = ob_get_contents();
				ob_end_clean();
			}
		}

		$return = array(
			'submission_status' => 'success',
			'error_fields' => '',
			'response_html' => $response_html
		);

		wp_send_json_success( $return );
	}

	public function process_cancel_request_confirm() {
		//phpcs:ignore WordPress.Security.NonceVerification.Missing
		$key = ! empty( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';

		if ( empty( $key ) ) {
			return;
		}

		$db = new Database();

		$where = array(
			array(
				'column' => 'action_key',
				'value' => $key,
				'compare' => '=',
				'type' => 'string'
			)
		);

		$maybe_registration = $db->registration_query( $where );
		$modal_settings = array( 
			'width' => 'narrow',
			'noHeader' => true 
		);
		$modal_settings_json = wp_json_encode( $modal_settings );
		$classes = ' evge-modal-reveal';
		if ( $maybe_registration ) {
			// Check if cancellation is allowed
			$event_post = new EventPost( $maybe_registration[0]['event_id'] );
			if ( $event_post->cancellation_has_closed() ) {
				$icon_html = '<div class="evge-alert-icon evge-unknown evge-shadow"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M80 160c0-35.3 28.7-64 64-64h32c35.3 0 64 28.7 64 64v3.6c0 21.8-11.1 42.1-29.4 53.8l-42.2 27.1c-25.2 16.2-40.4 44.1-40.4 74V320c0 17.7 14.3 32 32 32s32-14.3 32-32v-1.4c0-8.2 4.2-15.8 11-20.2l42.2-27.1c36.6-23.6 58.8-64.1 58.8-107.7V160c0-70.7-57.3-128-128-128H144C73.3 32 16 89.3 16 160c0 17.7 14.3 32 32 32s32-14.3 32-32zm80 320a40 40 0 1 0 0-80 40 40 0 1 0 0 80z"/></svg></div>';

				$html = __( 'Cancellation is not allowed for this event.', 'event-genius' );
				$template_path = EVGE()->template_manager()->locate_template('registration/forms/cancel/cancel-request-modal-content.php');
				if ($template_path) {
					ob_start();
					include $template_path;
					$response_html = ob_get_contents();
					ob_end_clean();
				} else {
					$response_html = $icon_html . $html;
				}
				wp_send_json_success( array( 'html' => $response_html ) );
				return;
			}

			$registration_group = new RegistrationGroup( $db );
			$registration_group->set_from_existing( $maybe_registration[0]['id'] );

			$registration_group->set_status( 'canceled' );
			$db->set_status( $maybe_registration[0]['id'], 'canceled' );

			// Send cancellation emails using existing communicator
			$event = new Event( $maybe_registration[0]['event_id'] );
			$communicator = new CommunicatorAfterCancel( $registration_group, $event );
			$communicator->execute();

			do_action( 'evge_after_successful_cancel', $maybe_registration[0] );

			$placeholder = $this->object_factory->create_placeholders( $registration_group->get_main(), new Event( $maybe_registration[0]['event_id'] ), 'action' );

			$html = $placeholder->replace( Settings::get( 'cancel_success_message' ) );

			$icon_html = '<div class="evge-alert-icon evge-success evge-shadow"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"/></svg></div>';
			$template_path = EVGE()->template_manager()->locate_template('registration/forms/cancel/cancel-request-modal-content.php');
			if ($template_path) {
				ob_start();
				include $template_path;
				$response_html = ob_get_contents();
				ob_end_clean();
			}

			$return = array(
				'action_status' => 'success',
				'error_fields' => array(),
				'html' => $response_html
			);

			wp_send_json_success( $return );
		}

		$html = Settings::get( 'cancel_none_found_message' );;

		$icon_html = '<div class="evge-alert-icon evge-unknown evge-shadow"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M80 160c0-35.3 28.7-64 64-64h32c35.3 0 64 28.7 64 64v3.6c0 21.8-11.1 42.1-29.4 53.8l-42.2 27.1c-25.2 16.2-40.4 44.1-40.4 74V320c0 17.7 14.3 32 32 32s32-14.3 32-32v-1.4c0-8.2 4.2-15.8 11-20.2l42.2-27.1c36.6-23.6 58.8-64.1 58.8-107.7V160c0-70.7-57.3-128-128-128H144C73.3 32 16 89.3 16 160c0 17.7 14.3 32 32 32s32-14.3 32-32zm80 320a40 40 0 1 0 0-80 40 40 0 1 0 0 80z"/></svg></div>';
		$template_path = EVGE()->template_manager()->locate_template('registration/forms/cancel/cancel-request-modal-content.php');
		if ($template_path) {
			ob_start();
			include $template_path;
			$response_html = ob_get_contents();
			ob_end_clean();
		}

		$return = array(
			'action_status' => 'none',
			'error_fields' => array(),
			'html' => $response_html
		);

		wp_send_json_success( $return );

	}


	public function process_cancel_request( $key, $event_id, $confirm_needed = false ) {

		$db = new Database();
		$where = array(
			array(
				'column' => 'action_key',
				'value' => $key,
				'compare' => '=',
				'type' => 'string'
			),
			array(
				'column' => 'event_id',
				'value' => $event_id,
				'compare' => '=',
				'type' => 'int'
			)
		);
		$maybe_registration = $db->registration_query( $where );

		$action_status = 'none';
		$context_registration = array();

		$event_post = new EventPost( $event_id );

		// Check if cancellation is allowed
		if ( $event_post->cancellation_has_closed() ) {
			$icon_html = '<div class="evge-alert-icon evge-unknown evge-shadow"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M80 160c0-35.3 28.7-64 64-64h32c35.3 0 64 28.7 64 64v3.6c0 21.8-11.1 42.1-29.4 53.8l-42.2 27.1c-25.2 16.2-40.4 44.1-40.4 74V320c0 17.7 14.3 32 32 32s32-14.3 32-32v-1.4c0-8.2 4.2-15.8 11-20.2l42.2-27.1c36.6-23.6 58.8-64.1 58.8-107.7V160c0-70.7-57.3-128-128-128H144C73.3 32 16 89.3 16 160c0 17.7 14.3 32 32 32s32-14.3 32-32zm80 320a40 40 0 1 0 0-80 40 40 0 1 0 0 80z"/></svg></div>';

			$html = __( 'Cancellation is not allowed for this event.', 'event-genius' );
			$template_path = EVGE()->template_manager()->locate_template('registration/forms/cancel/cancel-request-modal-content.php');
			if ( $template_path ) {
				ob_start();
				include $template_path;
				$return_html = ob_get_contents();
				ob_end_clean();
			} else {
				$return_html = $icon_html . $html;
			}
			wp_send_json_success( array( 'html' => $return_html ) );
			return;
		}

		if ( ! empty( $maybe_registration ) ) {
			$context_registration = $maybe_registration[0];
			$event_form = $this->object_factory->create_form( $event_post->get_the_form_id() );

			$registration_group = new RegistrationGroup( $db );
			$registration_group->set_from_existing( $maybe_registration[0]['id'] );
			$registration_group->set_status( 'canceled' );
			if ( ! $confirm_needed ) {
				$action_status = 'success';
			} else {
				$action_status = 'confirm';
			}

		}

		$action_status = apply_filters( 'evge_cancel_request_status', $action_status, $context_registration );
		$modal_settings = array( 
			'width' => 'narrow',
			'noHeader' => true 
		);
		$classes = '';
		if ( $event_post->cancellation_has_closed() ) {
			$icon_html = '<div class="evge-alert-icon evge-unknown evge-shadow"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M80 160c0-35.3 28.7-64 64-64h32c35.3 0 64 28.7 64 64v3.6c0 21.8-11.1 42.1-29.4 53.8l-42.2 27.1c-25.2 16.2-40.4 44.1-40.4 74V320c0 17.7 14.3 32 32 32s32-14.3 32-32v-1.4c0-8.2 4.2-15.8 11-20.2l42.2-27.1c36.6-23.6 58.8-64.1 58.8-107.7V160c0-70.7-57.3-128-128-128H144C73.3 32 16 89.3 16 160c0 17.7 14.3 32 32 32s32-14.3 32-32zm80 320a40 40 0 1 0 0-80 40 40 0 1 0 0 80z"/></svg></div>';

			$html = __( 'Cancellation for this event has closed.', 'event-genius' );
		} elseif ( $action_status === 'none' ) {
			$icon_html = '<div class="evge-alert-icon evge-unknown evge-shadow"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M80 160c0-35.3 28.7-64 64-64h32c35.3 0 64 28.7 64 64v3.6c0 21.8-11.1 42.1-29.4 53.8l-42.2 27.1c-25.2 16.2-40.4 44.1-40.4 74V320c0 17.7 14.3 32 32 32s32-14.3 32-32v-1.4c0-8.2 4.2-15.8 11-20.2l42.2-27.1c36.6-23.6 58.8-64.1 58.8-107.7V160c0-70.7-57.3-128-128-128H144C73.3 32 16 89.3 16 160c0 17.7 14.3 32 32 32s32-14.3 32-32zm80 320a40 40 0 1 0 0-80 40 40 0 1 0 0 80z"/></svg></div>';

			$html = Settings::get( 'cancel_none_found_message' );
		} elseif ( $action_status === 'confirm' ) {
			$placeholder = $this->object_factory->create_placeholders( $registration_group->get_main(), new Event( $event_id ), 'action' );

			$html = $placeholder->replace( Settings::get( 'cancel_confirm_needed_message' ) );

			$json = array(
				'event_id' => $event_id,
				'key' => $key,
				'action' => 'evge_cancel_request_confirm'
			);
			$button_html = '<button class="evge-cancel-confirm evge-green-button evge-action-trigger" data-evge-ajax="' . esc_attr( wp_json_encode( $json ) ) . '">' . esc_html( Settings::get( 'cancel_confirm_button_text' ) ) . '</button>';
		} elseif ( $action_status === 'success' ) {
			$db->set_status( $context_registration['id'], 'canceled' );
			do_action( 'evge_after_successful_cancel', $context_registration );

			$event = new Event( $event_id );
			$communicator = new CommunicatorAfterCancel( $registration_group, $event );
			$communicator->execute();

			$placeholder = $this->object_factory->create_placeholders( $registration_group->get_main(), new Event( $event_id ), 'action' );

			$html = $placeholder->replace( Settings::get( 'cancel_success_message' ) );

			$icon_html = '<div class="evge-alert-icon evge-success evge-shadow"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"/></svg></div>';
		} else {
			$html = '';
			do_action( 'evge_custom_cancel_request_action', $action_status, $context_registration );

			return;
		}

		$modal_settings_json = wp_json_encode( $modal_settings );

		$template_path = EVGE()->template_manager()->locate_template('registration/forms/cancel/cancel-request-modal-content.php');
		if ($template_path) {
			include $template_path;
		}


	}

	/**
	 * Get attendee list for a specific event
	 * 
	 * @return void
	 */
	public function get_attendee_list() {
		//phpcs:ignore WordPress.Security.NonceVerification.Missing
		$event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
		
		if (!$event_id) {
			wp_send_json_error(array(
				'message' => __('Invalid event ID', 'event-genius')
			));
		}
		
		// Check if the event is visible to the current user
		if ( ! Utils::is_event_visible_to_user( $event_id ) ) {
			$error_message = Utils::get_event_visibility_error_message( 'attendee_list' );
			wp_send_json_error(array(
				'html' => wp_kses_post( $error_message )
			));
			return;
		}

		$event_post = new EventPost( $event_id );

		if ( ! is_user_logged_in() && $event_post->get_who_can_see_attendee_list() === 'logged_in' ) {
			wp_send_json_error(array(
				'message' => __('Attende list is for logged-in users only', 'event-genius')
			));
		}

		$return = array(
			'html' => '<div class="evge-modal-reveal">' . do_shortcode('[event_genius_attendees event="' . $event_id . '" per_load="1000" template="auto"]') . '</div>'
		);

		wp_send_json_success( $return );
		
	}
}