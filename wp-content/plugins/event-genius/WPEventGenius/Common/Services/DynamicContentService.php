<?php
namespace WPEventGenius\Common\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\Common\Event\EventPost;
use WPEventGenius\Common\Utils\DynamicContentHelper;

/**
 * Dynamic Content Service
 * 
 * Handles AJAX requests for dynamic content refresh
 * Returns all dynamic content types for a single event in one response
 */
class DynamicContentService {
	
	/**
	 * Initialize hooks
	 */
	public function init_hooks() {
		add_action( 'wp_ajax_evge_get_dynamic_content', array( $this, 'get_all_dynamic_content' ) );
		add_action( 'wp_ajax_nopriv_evge_get_dynamic_content', array( $this, 'get_all_dynamic_content' ) );
	}
	
	/**
	 * Get all dynamic content for an event (AJAX handler)
	 * 
	 * @return void Sends JSON response
	 */
	public function get_all_dynamic_content() {
		// Get event_id from POST or GET
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$event_id = isset( $_REQUEST['event_id'] ) ? absint( $_REQUEST['event_id'] ) : 0;
		
		if ( ! $event_id ) {
			wp_send_json_error( array(
				'message' => __( 'Event ID is required.', 'event-genius' ),
			) );
			return;
		}
		
		// Verify event exists
		$event_post = new EventPost( $event_id );
		
		if ( ! $event_post->get_the_id() ) {
			wp_send_json_error( array(
				'message' => __( 'Event not found.', 'event-genius' ),
			) );
			return;
		}
		
		// Set up registration counter if needed
		$factory = new \WPEventGenius\Common\Services\RegistrationObjectFactory();
		$event_post->set_registration_counter( $factory->create_registration_counter( $event_id, new \WPEventGenius\Common\Database() ) );
		
		// Build response with all dynamic content types
		$response_data = array();
		
		// 1. Registration Status (about items)
		$response_data['registration-status'] = $this->get_registration_status( $event_post );
		
		// 2. Attendee Count (about items)
		$response_data['attendee-count'] = $this->get_attendee_count( $event_post );
		
		// 3. Registration Form Status Message
		$response_data['registration-status-message'] = $this->get_registration_status_message( $event_post );
		
		// 4. Registration Form Button
		$response_data['registration-button'] = $this->get_registration_button( $event_post );
		
		// 5. Event CTA Container
		$response_data['event-cta'] = $this->get_event_cta( $event_post );
		
		wp_send_json_success( array(
			'data' => $response_data,
		) );
	}
	
	/**
	 * Get registration status text for about items
	 */
	protected function get_registration_status( $event_post ) {
		$about_items = $event_post->get_the_about_items( 'single' );
		
		foreach ( $about_items as $item ) {
			if ( isset( $item['slug'] ) && $item['slug'] === 'registration' ) {
				return $item['text'];
			}
		}
		
		return '';
	}
	
	/**
	 * Get attendee count text for about items
	 */
	protected function get_attendee_count( $event_post ) {
		$about_items = $event_post->get_the_about_items( 'single' );
		
		foreach ( $about_items as $item ) {
			if ( isset( $item['slug'] ) && $item['slug'] === 'attendees' ) {
				return $item['text'];
			}
		}
		
		return '';
	}
	
	/**
	 * Get registration form status message
	 */
	protected function get_registration_status_message( $event_post ) {
		// Check if registration is closed
		if ( $event_post->registration_has_closed() ) {
			return '<div class="evge-registration-status-message evge-registration-closed">' . 
				wp_kses_post( $event_post->closed_message() ) . 
				'</div>';
		}
		
		// Check if registration is filled
		if ( $event_post->registration_has_filled() ) {
			return '<div class="evge-registration-status-message evge-registration-filled">' . 
				wp_kses_post( $event_post->filled_message() ) . 
				'</div>';
		}
		
		// Check if registration is not open yet
		if ( ! $event_post->registration_is_open() ) {
			$not_open_message = $event_post->not_open_message();
			return '<div class="evge-registration-status-message evge-registration-not-open">' . 
				wp_kses_post( $not_open_message ) . 
				'</div>';
		}
		
		return '';
	}
	
	/**
	 * Get registration button HTML
	 */
	protected function get_registration_button( $event_post ) {
		$event_id = $event_post->get_the_id();
		
		// Check if user is already registered (same logic as RegistrationForm block)
		$is_already_registered = false;
		if ( is_user_logged_in() 
			&& $event_post->get_allow_registration() === 'enabled' 
			&& ! $event_post->registration_has_closed()
			&& ! $event_post->cancellation_has_closed() ) {
			
			$event_goer = EVGE()->event_goer();
			$event_goer->set_event( $event_post );
			$event_goer->init( $event_post );
			
			if ( $event_goer->has_made_submission_for_event() ) {
				$is_already_registered = true;
			}
		}
		
		// Build button HTML based on registration status
		if ( $is_already_registered ) {
			// User is already registered - show "Manage Registration" button
			$modal_settings = array( 'width' => 'full' );
			return sprintf(
				'<button class="evge-button evge-primary evge-modal-trigger evge-manage-registration-%s" data-evge-modal-settings="%s" data-evge-modal-content="ajax" data-evge-ajax="%s">%s</button>',
				esc_attr( $event_id ),
				esc_attr( wp_json_encode( $modal_settings ) ),
				esc_attr( wp_json_encode( array(
					'action' => 'evge_get_already_registered_content',
					'event_id' => $event_id
				) ) ),
				esc_html__( 'Manage Registration', 'event-genius' )
			);
		}
		
		// Check registration status
		if ( $event_post->registration_has_closed() || 
			 $event_post->registration_has_filled() || 
			 ! $event_post->registration_is_open() ) {
			// Registration not available - return empty
			return '';
		}
		
		// User can register - show registration button
		$form = $event_post->get_form();
		$register_button_text = apply_filters( 'evge_form_register_button_text', $form->get_register_button_text(), $event_post );
		$modal_settings = array( 'width' => 'full' );
		
		// Use the public wrapper method to get event JSON (already JSON encoded)
		$event_json = $event_post->get_event_json( 'evge_get_registration_content' );
		
		return sprintf(
			'<button class="evge-button evge-primary evge-modal-trigger evge-checkout-cache-%s" data-evge-modal-settings="%s" data-evge-modal-content="ajax" data-evge-ajax="%s">%s</button>',
			esc_attr( $event_id ),
			esc_attr( wp_json_encode( $modal_settings ) ),
			esc_attr( $event_json ),
			esc_html( $register_button_text )
		);
	}
	
	/**
	 * Get event CTA container HTML (inner content only, wrapper is in template)
	 */
	protected function get_event_cta( $event_post ) {
		ob_start();
		?>
		<?php
		// Only show cost section if there's an actual amount
		$cost_amount = $event_post->get_the_cost_amount();
		if ( ! empty( $cost_amount ) ) : ?>
			<div class="evge-single-event-cost">
				<?php echo esc_html( $event_post->get_the_cost_display() ); ?>
			</div>
		<?php endif; ?>

		<?php if ( $event_post->get_allow_registration() === 'enabled' && $event_post->should_show_section( 'capacity' ) && ! $event_post->registration_has_filled() && ! $event_post->registration_has_closed() ) : ?>
			<div class="evge-single-event-capacity">
				<span><?php echo wp_kses_post( $event_post->get_registration_capacity_text() ); ?></span>
			</div>
		<?php endif; ?>

		<?php 
		do_action( 'evge_event_single_cta_before', $event_post );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $event_post->get_the_cta( 'single' );
		
		do_action( 'evge_event_single_cta_after', $event_post );
		?>
		<?php
		return ob_get_clean();
	}
}
