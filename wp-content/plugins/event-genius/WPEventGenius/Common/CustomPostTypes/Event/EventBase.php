<?php
namespace WPEventGenius\Common\CustomPostTypes\Event;

use WPEventGenius\Common\Event\EventPost;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class EventBase {

	public function __construct(){

	}

	public function init_custom_hooks() {
		add_action( 'wp_ajax_nopriv_evge_get_registration_content', array( $this, 'get_registration_content' ), 10, 1 );
		add_action( 'wp_ajax_evge_get_registration_content', array( $this, 'get_registration_content' ), 10, 1 );

		add_action( 'wp_ajax_nopriv_evge_get_already_registered_content', array( $this, 'get_already_registered_content' ), 10, 1 );
		add_action( 'wp_ajax_evge_get_already_registered_content', array( $this, 'get_already_registered_content' ), 10, 1 );
	}

	public function is_cpt_page() {
		return is_post_type_archive( EVGE_EVENT_POST_TYPE )  || get_post_type() === EVGE_EVENT_POST_TYPE;
	}


	public function maybe_alter_template( $template ) {
		
	}

	public function maybe_alter_query( $query ) {
		return $query;
	}

	public function enqueue( $screen ) {
		if ( ! $this->is_cpt_page() ) {
			return;
		}
		EVGE()->style_service()->enqueue_style('evge_common');
		EVGE()->style_service()->enqueue_style('evge_single_post');
		EVGE()->style_service()->enqueue_style('evge_registration_form');
        EVGE()->style_service()->enqueue_style('evge_attendee_list');

		EVGE()->script_service()->enqueue_script('evge_common');
		EVGE()->script_service()->enqueue_script('evge_event');
		EVGE()->script_service()->enqueue_script('evge_registration_form');
		EVGE()->script_service()->enqueue_script('evge_single_post');
		EVGE()->script_service()->enqueue_script('evge_attendee_list');

	}

	public function get_registration_content() {
		//phpcs:ignore WordPress.Security.NonceVerification.Missing
		// Support both single event_id (backward compatible) and event_ids array (for bulk registration)
		$event_ids = array();
		
		if ( ! empty( $_POST['event_ids'] ) && is_array( $_POST['event_ids'] ) ) {
			// Bulk registration: multiple event IDs
		//phpcs:ignore WordPress.Security.NonceVerification.Missing
			$event_ids = array_map( 'absint', $_POST['event_ids'] );
			$event_ids = array_filter( $event_ids ); // Remove any invalid IDs
		} elseif ( ! empty( $_POST['event_id'] ) ) {
			// Single event (backward compatible)
			//phpcs:ignore WordPress.Security.NonceVerification.Missing
			$event_ids = array( absint( $_POST['event_id'] ) );
		}
		
		if ( empty( $event_ids ) ) {
			wp_send_json_error( array(
				'error_message' => __( 'No event ID(s) provided.', 'event-genius' )
			) );
			return;
		}

		// Use factory to create appropriate EventPost instance
		// Factory will return BulkEventPost for multiple events (Premium tier) or EventPost for single
		$factory = new \WPEventGenius\Common\Services\RegistrationObjectFactory();
		$event_post = $factory->create_event_post( $event_ids );

		// Check if current user can register for the primary event
		$event_goer = EVGE()->event_goer();
		EVGE()->event_goer()->set_event( $event_post );

		if ( ! $event_goer || ! $event_goer->can_register_for_event() ) {
			$error_message = __( 'You do not have permission to register for this event. Please log in if required.', 'event-genius' );
			wp_send_json_error( array(
				'error_message' => $error_message
			) );
			return;
		}

		// Validate all events if multiple are provided
		if ( count( $event_ids ) > 1 ) {
			foreach ( $event_ids as $event_id ) {
				if ( ! \WPEventGenius\Common\Utils\Utils::is_event_visible_to_user( $event_id ) ) {
					wp_send_json_error( array(
						'error_message' => sprintf( __( 'Event ID %d is not visible or accessible.', 'event-genius' ), $event_id )
					) );
					return;
				}
			}
		}

		do_action( 'evge_before_registration_content', $event_post, $event_ids );

		$return = array(
			'html' => ''
		);

		// Initialize registration_data for the template
		// This will be an empty array for new registrations, or populated for edit mode
		$registration_data = apply_filters( 'evge_registration_data', array(), $event_post );

		// Pass event_ids to template via a variable
		// For backward compatibility, single event_id still works
		$is_bulk_registration = count( $event_ids ) > 1;

		$template_path = EVGE()->template_manager()->locate_template('registration/forms/registration/registration-modal-content.php');
		if ($template_path) {
			ob_start();
			include $template_path;
			$return['html'] = ob_get_contents();
			ob_end_clean();
		}

		wp_send_json_success( $return );
	}

	public function get_already_registered_content() {
		//phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['event_id'] ) ) {
			wp_send_json_error();
		}
		//phpcs:ignore WordPress.Security.NonceVerification.Missing
		$event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;

		// Should check: Utils::is_event_visible_to_user( $event_id ) before proceeding
		if ( ! \WPEventGenius\Common\Utils\Utils::is_event_visible_to_user( $event_id ) ) {
			wp_send_json_error( array(
				'error_message' => __( 'You do not have permission to access this event.', 'event-genius' )
			) );
			return;
		}

		$event_post = new EventPost( $event_id );

		$return = array(
			'html' => '',
		);

		$template_path = EVGE()->template_manager()->locate_template('registration/forms/cancel/already-registered-modal-content.php');
		if ($template_path) {
			ob_start();
			include $template_path;
			$return['html'] = ob_get_contents();
			ob_end_clean();
		}

		wp_send_json_success( $return );
	}

}