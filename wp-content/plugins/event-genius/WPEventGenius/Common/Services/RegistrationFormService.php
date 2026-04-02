<?php

namespace WPEventGenius\Common\Services;

use WPEventGenius\Common\Database;
use WPEventGenius\Common\Event\Event;
use WPEventGenius\Common\Event\EventPost;
use WPEventGenius\Common\Registration\Communicator\CommunicatorAfterConfirmed;
use WPEventGenius\Common\Registration\Communicator\CommunicatorAfterPending;
use WPEventGenius\Common\Registration\Form\Form;
use WPEventGenius\Common\Registration\Payment\Cart\Cart;
use WPEventGenius\Common\Registration\Payment\Gateways\Offline;
use WPEventGenius\Common\Registration\Payment\PaymentHandler;
use WPEventGenius\Common\Registration\Registrar\RegistrarAfterRegisters;
use WPEventGenius\Common\Registration\Registration\RegistrationGroup;
use WPEventGenius\Common\Registration\Submission\SubmissionGroup;
use WPEventGenius\Common\Registration\Submission\Validator;
use WPEventGenius\Common\Event\RegistrationCounter;
use WPEventGenius\Common\Utils\Settings;
use WPEventGenius\Common\Utils\Templater;
use WPEventGenius\Common\Utils\Utils;
use WPEventGenius\Common\Series\EventSeriesRepository;
use WPEventGenius\Common\Utils\Logger\DebugLogger;


if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class RegistrationFormService {

	/**
	 * @var RegistrationObjectFactory
	 */
	protected $object_factory;

	/**
	 * @param RegistrationObjectFactory|null $object_factory
	 */
	public function __construct( $object_factory = null ) {
		$this->object_factory = $object_factory ?: new RegistrationObjectFactory();
	}

	public function init_hooks() {
		add_action( 'wp_ajax_evge_registration_form_submit', array( $this, 'process_form_submission' ) );
		add_action( 'wp_ajax_nopriv_evge_registration_form_submit', array( $this, 'process_form_submission' ) );
		add_action( 'wp_ajax_evge_validate_email', array( $this, 'validate_email' ) );
		add_action( 'wp_ajax_nopriv_evge_validate_email', array( $this, 'validate_email' ) );
		
		// Register shortcode for displaying registration form
		add_shortcode( 'event_genius_registration_form', array( $this, 'render_registration_form_shortcode' ) );
	}

	public function get_form_for_event( $event_id ) {
		return $this->object_factory->create_form( 1 );
	}

	/**
	 * Render the registration form shortcode
	 * 
	 * @param array $atts Shortcode attributes
	 * @return string
	 */
	public function render_registration_form_shortcode( $atts ) {
		// Enqueue necessary scripts and styles
		EVGE()->style_service()->enqueue_style('evge_common');
		EVGE()->style_service()->enqueue_style('evge_registration_form');

		EVGE()->script_service()->enqueue_script('evge_common');
		EVGE()->script_service()->enqueue_script('evge_registration_form');
		EVGE()->script_service()->enqueue_script('evge_single_post');
		
		// Request modal since registration forms can use modal triggers
		EVGE()->modal_service()->request_modal();

		
		// Parse shortcode attributes
		$atts = shortcode_atts( array(
			'event' => 0,
			'header' => '', // yes or no
		), $atts, 'event_genius_registration_form' );
		
		// If no event ID is provided, try to get it from the current post
		if ( empty( $atts['event'] ) ) {
			$atts['event'] = get_the_ID();
		}
		
		// Convert to boolean
		$show_header = ( $atts['header'] === 'true' );
		
		// Validate event ID and visibility
		$event_id = absint( $atts['event'] );
		if ( ! Utils::is_event_visible_to_user( $event_id ) ) {
			return Utils::get_event_visibility_error_message( 'form_display' );
		}
		
		// Create event post object
		$event_post = $this->object_factory->create_event_post( $event_id );
		EVGE()->event_goer()->set_event( $event_post );
		
	
		// Get payment JSON data for standalone forms (needed for quantity restrictions)
		$payment_json = method_exists( $event_post, 'get_payment_json' ) ? $event_post->get_payment_json() : '';
		
		// Start output buffering to capture the HTML
		ob_start();
		
		// Display the form
		?>
		<div class="evge-registration-form-container evge evge-standalone-registration-form" data-event-id="<?php echo esc_attr( $event_id ); ?>"<?php echo ! empty( $payment_json ) ? ' data-payment-json="' . esc_attr( $payment_json ) . '"' : ''; ?>>
			<?php if ( $show_header ) : ?>
				<div class="evge-registration-header">
					<div class="evge-single-event-title">
                        <h3><?php echo esc_html( $event_post->get_the_title() ); ?></h3>
                    </div>
                    <div class="evge-single-event-meta evge-single-event-section">
                        <?php
                        EVGE()->template_manager()->get_template('events/common/event-meta.php', [
                            'event_post' => $event_post,
                            'show_map' => true
                        ]);
                        ?>
                    </div>
				</div>
			<?php endif; ?>
			
			<div class="evge-form-wrapper">
				<?php 
				// Check if registration is open
				if ( ! EVGE()->event_goer()->can_register_for_event() ) : 
					echo '<div class="evge-registration-login-required-inline">' . 
					'<p>' . esc_html__( 'Login required to register for this event.', 'event-genius' ) . '</p>' .
					'<a href="' . esc_url( wp_login_url( get_permalink() ) ) . '" class="evge-button evge-green-button">' . 
					esc_html__( 'Log In', 'event-genius' ) . '</a>' .
					'</div>';
				elseif ( $event_post->registration_is_open() ) : 
					// Get registration data for hooks (same as form template)
					$registration_data = apply_filters( 'evge_registration_data', array(), $event_post );
					echo '<div class="evge-standalone-registration-form-container-inner">';
					// Call evge_registration_form_top hook to display simple count and additional guests tabs
					do_action( 'evge_registration_form_top', $event_post, $registration_data );
					
					$templater = new Templater();
					include $templater->get_registration_template_part( 'form' );
					
					// Call evge_registration_form_bottom hook to display additional guests button
					do_action( 'evge_registration_form_bottom', $event_post, $registration_data );
				
					echo '</div>';
					else : 
					if ( $event_post->registration_has_closed() ) : 
						echo '<div class="evge-registration-closed">' . wp_kses_post( $event_post->closed_message() ) . '</div>';
					else : 
						echo '<div class="evge-registration-not-open">' . wp_kses_post( $event_post->not_open_until_message() ) . '</div>';
					endif;
				endif; 
				?>
			</div>
		</div>
		<?php
		
		// Return the buffered content
		return ob_get_clean();
	}

	public function process_form_submission() {
		// no checks as it's used in the frontend
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['lang'] ) && ! empty( $GLOBALS['sitepress'] ) && $GLOBALS['sitepress'] instanceof \SitePress ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$lang = isset($_POST['lang']) ? sanitize_text_field(wp_unslash($_POST['lang'])) : '';
			global $sitepress;
			$sitepress->switch_lang( $lang, true );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$event_id = isset($_POST['event_id']) ? (int) $_POST['event_id'] : 0;

		// Check if the event is visible to the current user
		if ( ! Utils::is_event_visible_to_user( $event_id ) ) {
			$error_message = Utils::get_event_visibility_error_message( 'form_submission' );
			wp_send_json_error( array(
				'submission_status' => 'error',
				'error_fields' => array(),
				'response_html' => wp_kses_post( $error_message )
			) );
			return;
		}

		// Check if event is at or above capacity - this must be done first before any other processing
		$event_post = new EventPost( $event_id );
		$event_capacity = $event_post->get_the_capacity();
		
		// Only check capacity if event has a capacity limit (not unlimited)
		if ( $event_capacity > 0 ) {
			// Create registration counter to check current registrations
			$database = new Database();
			$registration_counter = new RegistrationCounter( $event_id, $database );
			$active_registrations = $registration_counter->get_active_count();
			
			// Check if event is at or above capacity
			if ( $active_registrations >= $event_capacity ) {
				$error_message = __( 'Sorry, this event has reached capacity and registration is no longer available. Your registration was not submitted.', 'event-genius' );

				wp_send_json_error( array(
					'submission_status' => 'capacity',
					'error_fields' => array(),
					'response_html' => '<div class="evge-filled-message evge-status-message">' . esc_html( $error_message ) . '</div>'
				) );
				return;
			}
		}

		// Check registration status early - before processing any form data
		if ( $event_post->registration_has_closed() ) {
			$error_message = wp_kses_post( $event_post->closed_message() );
			wp_send_json_error( array(
				'submission_status' => 'closed',
				'error_fields' => array(),
				'response_html' => $error_message
			) );
			return;
		}

		if ( ! $event_post->registration_is_open() ) {
			$error_message = wp_kses_post( $event_post->not_open_until_message() );
			wp_send_json_error( array(
				'submission_status' => 'not_open',
				'error_fields' => array(),
				'response_html' => $error_message
			) );
			return;
		}

		do_action( 'evge_before_process_form_submission', $event_id );

		$event = $this->object_factory->create_event( $event_id );
		$event_form = $event->get_form();
		$main_submission = $this->object_factory->create_main_submission( new Validator(), $event_form );

		$expected_fields = $main_submission->get_expected_fields();
		$expected_data = array();
		foreach ( $expected_fields as $field ) {
			// phpcs:ignore 
			$expected_data[$field] = isset($_POST[$field]) ? $_POST[$field] : '';
		}
		$main_submission->set_raw_data($expected_data);
		$main_submission->set_single_raw_datum( 'user_id', get_current_user_id() );
		$main_submission->set_single_raw_datum( 'quantity', 1 );

		$submission_group = new SubmissionGroup( $main_submission );
		$submission_group->add_inherited_responses();

		$submission_group->normalize_all();
		$submission_group->validate_all();

		$database = new Database();
		$registration_group = $this->object_factory->create_registration_group( $database );
		$submission_status = 'form';

		// Log any validation errors that occurred
		$errors = $submission_group->get_all_errors();
		if (!empty($errors) && !empty($errors['main'])) {
			// Sanitize submission data before logging
			$sanitized_submission_data = array();
			foreach ($expected_data as $key => $value) {
				// Skip sensitive fields
				if (in_array($key, ['first', 'last', 'phone', 'email', 'user_comments']) && !empty($value)) {
					$sanitized_submission_data[$key] = '[REDACTED]';
					continue;
				}

				// Sanitize based on field type
				if (is_array($value)) {
					$sanitized_submission_data[$key] = array_map('sanitize_text_field', $value);
				} else {
					$sanitized_submission_data[$key] = sanitize_text_field($value);
				}
			}

			$error_context = array(
				'event' => $event_id . ' | ' . get_the_title($event_id) . ' | ' . get_the_permalink($event_id),
				'user_id' => get_current_user_id(),
				'submission_data' => $sanitized_submission_data,
				'validation_errors' => $errors,
			);

			DebugLogger::log(
				'Server side validation failed',
				$error_context
			);
		}

		if ( $submission_group->all_valid() ) {
			$submission_group->sanitize_all();
			$registration_group->set_from_submission_group( $submission_group );

			// Add series ID to submission data if event is part of a series
			$main_submission = $submission_group->get_main_submission();
			$submission_data = $main_submission->get_data();
			if (!empty($submission_data['event_id'])) {
				$series_repo = new EventSeriesRepository($database);
				$series_id = $series_repo->get_series_id_for_event($submission_data['event_id']);
				$registration_group->set_datum('series_id', $series_id ? $series_id : 0);

								// If event is part of a series, store event details as meta
				if ($series_id) {
					$event_post = new EventPost($event_id);
				
					$registration_group->set_datum('_event_info', $event_post->get_the_title() . ' ||| ' . $event_post->get_the_date_summary());
				}
			}

			$registrar = $this->object_factory->create_registrar( $registration_group, $event );

			if ( $registrar->too_many_guests_for_capacity() ) {
				$submission_status = 'capacity';
				$communicator      = $this->object_factory->create_communicator( 'error', $registration_group, $event );
			} elseif ( $registrar->registration_deadline_has_passed() || ! $registrar->registration_is_open() ) {
				$submission_status = 'closed';
				$communicator      = $this->object_factory->create_communicator( 'error', $registration_group, $event );
			} else {
				$result = $this->process_registration_status( $registrar, $registration_group, $event, $event_form );
				$submission_status = $result['submission_status'];
				$communicator = $result['communicator'];
			}
		} else {
			$communicator = $this->object_factory->create_communicator( 'error', $this->object_factory->create_registration_group( $database ), $event );
		}
		$communicator->execute();

		if ( ! empty( $registration_group ) ) {
			$placeholders = $this->object_factory->create_placeholders( $registration_group->get_main(), $event, 'frontend' );
			$response_html = $placeholders->replace( $communicator->get_response_html( $submission_group->get_all_errors()) );
		} else {
			$response_html = $communicator->get_response_html( $submission_group->get_all_errors());
		}

		// Apply escaping only if the communicator allows it
		if ( $communicator->should_escape() ) {
			$response_html = wp_kses_post( nl2br( $response_html ) );
		}

		// Fire action hook after successful registration submission
		if ( 'success' === $submission_status && ! empty( $registration_group ) ) {
			/**
			 * Fires after a registration has been successfully submitted via AJAX
			 * 
			 * @param object $registration_group The registration group object
			 */
			do_action( 'evge_after_registration_submitted', $registration_group );
		}

		$return = array(
			'submission_status' => $submission_status,
			'error_fields' => $submission_group->get_all_errors(),
			'response_html' => $response_html
		);

		wp_send_json_success( $return );
	}

	/**
	 * Process registration status and handle payment logic
	 * 
	 * @param RegistrarAfterRegisters $registrar
	 * @param RegistrationGroup $registration_group
	 * @param Event $event
	 * @param Form $event_form
	 * @return array Array containing submission_status and communicator
	 */
	protected function process_registration_status( $registrar, $registration_group, $event, $event_form, $args = array() ) {
		$registrant_status = $registrar->calculate_status();
		if ( 'error' !== $registrant_status ) {
			$submission_status = 'success';
			$registration_group->set_status( $registrant_status );
			$registration_group->build_payment_handler( $this->object_factory->create_payment_handler( $this->object_factory->create_cart() ));

			$should_trigger_payment = $registration_group->has_cost() && $event->get_accept_payments();
			
			// In free version, if event has a cost, we should create offline payment record
			// even if get_accept_payments() returns false (no gateways configured)
			$should_create_offline_payment = false;
			if ( function_exists( 'evge_is_free_tier' ) && evge_is_free_tier() ) {
				$should_create_offline_payment = $registration_group->has_cost();
			}

			$registration_group->insert_or_update_all( $event_form );

			// Set payment required flag if payment should be triggered
			if ( $should_trigger_payment ) {
				$registration_group->get_main()->set_registration_meta( 'payment_required', '1' );
			}

			if ( 'confirmed' === $registrant_status && ! $should_trigger_payment && ! $should_create_offline_payment ) {
				$communicator = $this->object_factory->create_communicator( 'confirmed', $registration_group, $event, $args );
			} elseif ( 'pending' === $registrant_status ) {
				$communicator_type = ( $event->get_confirmation_condition() === 'manual_only' ) ? 'pending_approval' : 'pending';
				if ( 'pending' === $communicator_type ) {
					$args = array_merge( $args, array(
						'should_trigger_payment' => $should_trigger_payment,
					) );
				}
				$communicator = $this->object_factory->create_communicator( $communicator_type, $registration_group, $event, $args );
			} elseif ( 'confirmed' === $registrant_status && ( $should_trigger_payment || $should_create_offline_payment ) ){
				$payment_handler = $this->object_factory->create_payment_handler( $this->object_factory->create_cart() );
				/*
				* TODO: Make dynamic based on payment type
				*/
				$registration_group->build_payment_handler( $payment_handler );
				$registration_group->payment_handler()->set_status( $registration_group->get_payment_status() );

				$gateway = new Offline();

				$payment_record = $gateway->create_payment( $registration_group, true );

				$communicator = $this->object_factory->create_communicator( 'confirmed', $registration_group, $event, $args );
			} elseif ( 'confirmed' === $registrant_status ) {
				// Fallback: should not reach here, but handle just in case
				$communicator = $this->object_factory->create_communicator( 'confirmed', $registration_group, $event, $args );
			}

		} else {
			$submission_status = 'form';
			$communicator      = $this->object_factory->create_communicator( 'error', $registration_group, $event );
		}

		return array(
			'submission_status' => $submission_status,
			'communicator' => $communicator
		);
	}

/**
	 * Validate if an email is already registered for an event
	 * 
	 * @return void
	 */
	public function validate_email() {
		// Get and sanitize input
		$event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

		// Validate input
		if ( empty( $event_id ) || empty( $email ) ) {
			wp_send_json_error( array(
				'message' => __( 'Invalid input provided.', 'event-genius' )
			) );
			return;
		}

		// Check if email is valid
		if ( ! is_email( $email ) ) {
			wp_send_json_error( array(
				'message' => __( 'Please enter a valid email address.', 'event-genius' )
			) );
			return;
		}

		// Check if email is already registered for this event
		$database = new Database();
		$registrations = $database->search_event_registrations( $event_id, $email );

		$is_duplicate = ! empty( $registrations );

		$prevent_registration = apply_filters( 'evge_prevent_registration', $is_duplicate, $event_id, $email );
		$return_message = apply_filters( 'evge_prevent_registration_message', __( 'This email address has already been used to register for this event.', 'event-genius' ), $prevent_registration, $event_id, $email );

		if ( $prevent_registration ) {
			wp_send_json_success( array(
				'prevent_registration' => true,
				'message' => $return_message
			) );
			return;
		}

		// Email is valid and not registered
		wp_send_json_success( array(
				'prevent_registration' => false,
				'message' => __( 'Email address is available.', 'event-genius' )
			)
		);
	}

}