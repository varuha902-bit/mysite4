<?php

namespace WPEventGenius\Common\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Upsell Service
 * 
 * Handles upsell functionality for the free version of Event Genius.
 * Provides methods to generate Pro upsell links with UTM tracking.
 * 
 * @package WPEventGenius
 * @since 1.0.0
 */
class UpsellService {

	/**
	 * Base URL for the pricing page
	 * 
	 * @var string
	 */
	private $pricing_url = 'https://wpeventgenius.com/pricing/';

	/**
	 * Constructor
	 */
	public function __construct() {}

	/**
	 * Initialize hooks
	 * 
	 * Sets up WordPress hooks for the upsell service.
	 * 
	 * @return void
	 */
	public function init_hooks() {
		if ( ! $this->is_free_tier() ) {
			return;
		}

		// Add "Add New" button to forms page that opens an upsell modal
		add_filter( 'evge_form_builder_action_button', array( $this, 'add_new_form_button' ), 10, 2 );

		// Add Series tab to All Events navigation (free version - opens upsell modal)
		add_filter( 'evge_all_events_navigation_args', array( $this, 'add_series_tab_to_all_events_navigation' ), 10, 1 );

		// Add upsell sections to single event settings page
		add_filter( 'evge_single_event_sections_registration', array( $this, 'add_event_settings_upsells' ), 100, 1 );

		// Add upsell tabs to single registration sub-navigation
		add_filter( 'evge_single_registration_sub_navigation_args', array( $this, 'add_registration_sub_navigation_upsells' ), 10, 2 );

		// Add Payments tab to Settings navigation (free version - opens upsell modal)
		add_filter( 'evge_settings_navigation_args', array( $this, 'add_payments_tab_to_settings_navigation' ), 10, 2 );

		// Add advanced form fields upsell link next to Type select field
		add_action( 'evge_form_field_type_select_after', array( $this, 'add_advanced_fields_upsell_link' ), 10, 1 );

		// Register AJAX handler for upsell modal
		add_action( 'wp_ajax_evge_get_upsell_modal', array( $this, 'ajax_get_upsell_modal' ) );
	}

	/**
	 * Get a Pro upsell link with UTM tracking parameters
	 * 
	 * @param array $args {
	 *     Optional. Configuration for the upsell link.
	 * 
	 *     @type string $source   The source location (e.g., 'settings-page', 'dashboard-page')
	 *     @type string $medium   The medium type (e.g., 'upsell-link', 'feature-notice')
	 *     @type string $content  Optional. Specific content identifier for tracking
	 *     @type string $url      Optional. Custom URL to use instead of default pricing page
	 * }
	 * @return string The complete URL with UTM parameters, or empty string if not free tier
	 */
	public function get_pro_upsell_link( $args = array() ) {


		$defaults = array(
			'source'  => 'plugin',
			'medium'  => 'upsell-link',
			'content' => '',
			'url'     => $this->pricing_url,
		);

		$args = wp_parse_args( $args, $defaults );

		// Get the current tier for UTM campaign
		$tier = evge_get_tier();
		$utm_campaign = 'evge-' . $tier;

		// Build UTM parameters
		$utm_params = array(
			'utm_campaign' => $utm_campaign,
			'utm_source'   => sanitize_key( $args['source'] ),
			'utm_medium'   => sanitize_key( $args['medium'] ),
		);

		// Add content parameter if provided
		if ( ! empty( $args['content'] ) ) {
			$utm_params['utm_content'] = sanitize_key( $args['content'] );
		}

		// Build the complete URL with UTM parameters
		$url = add_query_arg( $utm_params, $args['url'] );

		return esc_url( $url );
	}

	/**
	 * Filter callback to add "Add New" button to forms page (free version)
	 * 
	 * Returns a button that opens an upsell modal instead of creating a new form.
	 * This is used as an upsell to encourage users to upgrade to Pro.
	 * 
	 * @param string $button_html Existing button HTML
	 * @param object $page The page instance
	 * @return string Button HTML
	 */
	public function add_new_form_button( $button_html, $page ) {
		// Only add button on the forms tab
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = ! empty( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
		
		if ( $tab !== 'forms' ) {
			return $button_html;
		}

		// Request modal since we're adding an upsell link
		EVGE()->modal_service()->request_modal();

		// Generate button with upsell modal
		$add_new_button = $this->get_upsell_button( array(
			'type'     => 'add_new_form',
			'location' => 'forms-tab',
		) );

		return $button_html . $add_new_button;
	}

	/**
	 * Get an upsell button HTML with modal trigger
	 * 
	 * @param array $args {
	 *     Configuration for the upsell button.
	 * 
	 *     @type string $type     The type of upsell (e.g., 'add_new_form', 'multiple_forms', etc.)
	 *     @type string $location The location of the button (e.g., 'forms-tab', 'settings-page', etc.)
	 *     @type string $text     Optional. Button text. Defaults to '+ Add New'
	 *     @type string $class    Optional. Additional CSS classes
	 * }
	 * @return string Button HTML
	 */
	public function get_upsell_button( $args = array() ) {
		$defaults = array(
			'type'     => '',
			'location' => '',
			'text'     => __( 'Add New', 'event-genius' ),
			'class'    => 'button evge-admin-secondary-button',
		);

		$args = wp_parse_args( $args, $defaults );

		if ( empty( $args['type'] ) || empty( $args['location'] ) ) {
			return '';
		}

		// Build AJAX data for the modal
		$ajax_data = array(
			'action'   => 'evge_get_upsell_modal',
			'type'      => sanitize_key( $args['type'] ),
			'location'  => sanitize_key( $args['location'] ),
		);

		// Build modal settings
		$modal_settings = array(
			'width' => 'small',
		);

		$button_html = sprintf(
			'<button type="button" class="%s evge-modal-trigger" data-evge-modal-content="ajax" data-evge-ajax="%s" data-evge-modal-settings="%s">
				+ %s <span class="evge-upsell-pro-badge">%s</span>
			</button>',
			esc_attr( $args['class'] ),
			esc_attr( wp_json_encode( $ajax_data ) ),
			esc_attr( wp_json_encode( $modal_settings ) ),
			esc_html( $args['text'] ),
			esc_html__( 'Pro', 'event-genius' )
		);

		return $button_html;
	}

	/**
	 * AJAX handler for getting upsell modal content
	 * 
	 * @return void
	 */
	public function ajax_get_upsell_modal() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'evge-admin' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed', 'event-genius' ) ) );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_evge_registrations' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'event-genius' ) ) );
		}

		// Get parameters
		$upsell_type = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
		$location    = isset( $_POST['location'] ) ? sanitize_key( wp_unslash( $_POST['location'] ) ) : '';

		if ( empty( $upsell_type ) || empty( $location ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters', 'event-genius' ) ) );
		}

		// Get upsell link with UTM tracking
		// Include feature type in medium for better tracking of which features users are interested in
		$upsell_link = $this->get_pro_upsell_link( array(
			'source'  => $location,
			'medium'  => 'upsell-modal-' . $upsell_type,
			'content' => 'upgrade-to-pro',
		) );

		// Get upsell data (will be parameterized later)
		$upsell_data = $this->get_upsell_data( $upsell_type, $location );

		// Start output buffering to capture the modal HTML
		ob_start();
		$this->render_upsell_modal( $upsell_data, $upsell_link );
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Get upsell data based on type and location
	 * 
	 * @param string $type The type of upsell
	 * @param string $location The location of the button
	 * @return array Upsell data (title, description, image, etc.)
	 */
	private function get_upsell_data( $type, $location ) {
		// Default generic upsell data
		// This will be parameterized later to accept custom content
		$defaults = array(
			'title'              => __( 'Upgrade to Pro', 'event-genius' ),
			'description'        => __( 'This feature is available in Event Genius Pro. Upgrade now to unlock this and many other powerful features!', 'event-genius' ),
			'image'              => '',
			'image_2x'           => '',
			'features'           => array(),
			'additional_features' => array(),
		);

		// Type-specific data (can be expanded later)
		$type_data = array();

		switch ( $type ) {
			case 'add_new_form':
				$type_data = array(
					'title'       => __( 'Multiple Registration Forms', 'event-genius' ),
					'description' => __( 'Upgrade to Event Genius Pro to create multiple registration forms', 'event-genius' ),
					'image'       => EVGE_PLUGIN_URL . 'assets/images/admin/features/feature-forms.png',
					'image_2x'    => EVGE_PLUGIN_URL . 'assets/images/admin/features/feature-forms-2x.png',
					'features'    => array(
						__( 'Create unique custom registration forms for each event.', 'event-genius' ),
						__( 'Assign forms to specific events, customizing confirmations and notifications for different audiences.', 'event-genius' ),
						__( 'Reuse custom form fields across multiple forms for efficiency.', 'event-genius' ),
						__( 'Allow for additional guests and bulk event registration.', 'event-genius' ),
					),
				);
				break;
			case 'event_series_management':
				$type_data = array(
					'title'       => __( 'Custom Event Series', 'event-genius' ),
					'description' => __( 'Upgrade to Event Genius Pro to create and manage event series', 'event-genius' ),
					'image'       => EVGE_PLUGIN_URL . 'assets/images/admin/features/feature-series.png',
					'image_2x'    => EVGE_PLUGIN_URL . 'assets/images/admin/features/feature-series-2x.png',
					'features'    => array(
						__( 'Group related events into series for better organization.', 'event-genius' ),
						__( 'Allow attendees to register for the full series or just a single date.', 'event-genius' ),
						__( 'Edit each recurrence individually or make bulk changes.', 'event-genius' ),
						__( 'Dedicated pages for each series with registration options.', 'event-genius' ),
					),
				);
				break;
			case 'accept_payments':
				$type_data = array(
					'title'       => __( 'Accept Payments & Sell Tickets', 'event-genius' ),
					'description' => __( 'Upgrade to Event Genius Pro to accept payments and sell tickets', 'event-genius' ),
					'image'       => EVGE_PLUGIN_URL . 'assets/images/admin/features/feature-payments.png',
					'image_2x'    => EVGE_PLUGIN_URL . 'assets/images/admin/features/feature-payments-2x.png',
					'features'    => array(
						__( 'Stripe and PayPal payment gateways.', 'event-genius' ),
						__( 'Flexible offline or manual payment tracking and instructions.', 'event-genius' ),
						__( 'Allow attendees to pay for additional guests or multiple events at once.', 'event-genius' ),
						__( 'Complete control over payment records.', 'event-genius' ),
						__( 'Contact individual attendess as needed.', 'event-genius' ),
					),
				);
				break;
			case 'scheduled_event_reminders':
				$type_data = array(
					'title'       => __( 'Scheduled Event Messages', 'event-genius' ),
					'description' => __( 'Upgrade to Event Genius Pro to send scheduled messages and reminders to your attendees', 'event-genius' ),
					'image'       => EVGE_PLUGIN_URL . 'assets/images/admin/features/feature-scheduled.png',
					'image_2x'    => EVGE_PLUGIN_URL . 'assets/images/admin/features/feature-scheduled-2x.png',
					'features'    => array(
						__( 'Automatically send reminder emails before your event to improve attendance.', 'event-genius' ),
						__( 'Send follow-up and thank youmessages after events to keep attendees engaged.', 'event-genius' ),
						__( 'Schedule relative to the event start and end times or a specific date and time.', 'event-genius' ),
						__( 'Automate post event surveys to gather feedback and improve future events.', 'event-genius' ),
					),
				);
				break;
			case 'attendance_tracking':
				$type_data = array(
					'title'       => __( 'Check-in & Attendance Tracking', 'event-genius' ),
					'description' => __( 'Upgrade to Event Genius Pro to track attendance and check-in status for your events', 'event-genius' ),
					'image'       => EVGE_PLUGIN_URL . 'assets/images/admin/features/feature-attendance.png',
					'image_2x'    => EVGE_PLUGIN_URL . 'assets/images/admin/features/feature-attendance-2x.png',
					'features'    => array(
						__( 'Look up attendees by name, email, or confirmation code and record attendance with a single click.', 'event-genius' ),
						__( 'Track attendance status including attended, no-show, and excused.', 'event-genius' ),
						__( 'Allow event helpers to check in attendees with a simple interface.', 'event-genius' ),
						__( 'Schedule follow up emails based on attendance status.', 'event-genius' ),
					),
				);
				break;
			case 'advanced_form_fields':
				$type_data = array(
					'title'       => __( 'Advanced Form Fields', 'event-genius' ),
					'description' => __( 'Upgrade to Event Genius Pro to unlock advanced form field types', 'event-genius' ),
					'image'       => EVGE_PLUGIN_URL . 'assets/images/admin/features/feature-fields.png',
					'image_2x'    => EVGE_PLUGIN_URL . 'assets/images/admin/features/feature-fields-2x.png',
					'features'    => array(
						__( 'File upload fields for documents, images, and other attachments.', 'event-genius' ),
						__( 'Date picker fields with customizable date formats and restrictions.', 'event-genius' ),
						__( 'Attendee number fields to allow multiple guest registration.', 'event-genius' ),
						__( 'Hidden admin fields for storing custom data visible only to administrators.', 'event-genius' ),
					),
				);
				break;
		}

		// Get all additional features and exclude the current type
		$all_additional_features = $this->get_all_additional_features();
		$additional_features = array();
		
		foreach ( $all_additional_features as $feature_type => $feature_text ) {
			if ( $feature_type !== $type ) {
				$additional_features[] = $feature_text;
			}
		}

		// Merge additional features into type data
		$type_data['additional_features'] = $additional_features;

		return wp_parse_args( $type_data, $defaults );
	}

	/**
	 * Get all additional features keyed by upsell type
	 * 
	 * @return array Array of features where keys are upsell types and values are feature descriptions
	 */
	private function get_all_additional_features() {
		return array(
			'accept_payments' 			=> __( 'Accept payments & sell tickets', 'event-genius' ),
			'add_new_form'              => __( 'Multiple registration forms', 'event-genius' ),
			'attendance_tracking'    	=> __( 'Check-in & attendance tracking', 'event-genius' ),
			'event_series_management'   => __( 'Event series management', 'event-genius' ),
			'multiple_email_templates'    => __( 'Multiple email templates', 'event-genius' ),
			'scheduled_event_reminders' => __( 'Scheduled event reminders', 'event-genius' ),
			'multiple_guests'         => __( 'Register multiple guests', 'event-genius' ),
			'registered_events'    => __( 'List events registered for', 'event-genius' ),
			'manual_email'    			=> __( 'Manually email registrants', 'event-genius' ),
			'priority_support'     => __( 'Priority support', 'event-genius' ),
			'blocks_and_shortcodes' => __( 'Additional blocks and shortcodes', 'event-genius' ),
			'multiple_event_registration'     => __( 'Multiple event registration', 'event-genius' ),
			'offline_payment_tracking'      => __( 'Offline payment tracking', 'event-genius' ),
		);
	}

	/**
	 * Render the upsell modal template
	 * 
	 * @param array  $upsell_data Upsell data (title, description, etc.)
	 * @param string $upsell_link The Pro upgrade link with UTM tracking
	 * @return void
	 */
	private function render_upsell_modal( $upsell_data, $upsell_link ) {
		$template_path = trailingslashit( EVGE_ADMIN_TEMPLATE_PATH ) . 'evge/upsell/modal.php';

		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			// Fallback if template doesn't exist
			echo '<div class="evge-upsell-modal-content">';
			echo '<h2>' . esc_html( $upsell_data['title'] ) . '</h2>';
			echo '<p>' . esc_html( $upsell_data['description'] ) . '</p>';
			echo '<a href="' . esc_url( $upsell_link ) . '" class="button button-primary" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Upgrade to Pro', 'event-genius' ) . '</a>';
			echo '</div>';
		}
	}

	/**
	 * Add Series tab to All Events navigation (free version - opens upsell modal)
	 * 
	 * @param array $navigation_args The navigation arguments array
	 * @return array Modified navigation arguments with Series tab
	 */
	public function add_series_tab_to_all_events_navigation( $navigation_args ) {

		// Only add Series tab if user has appropriate capability
		if ( ! current_user_can( 'manage_options' ) ) {
			return $navigation_args;
		}

		// Check if Series tab already exists (added by Pro version)
		$series_exists = array_search( 'series', array_column( $navigation_args['nav_items'], 'id' ) ) !== false;
		if ( $series_exists ) {
			return $navigation_args;
		}

		// Add Series tab after Venues tab
		$venues_index = array_search( 'venues', array_column( $navigation_args['nav_items'], 'id' ) );
		if ( $venues_index !== false ) {
			// Build AJAX data for the modal
			$ajax_data = array(
				'action'   => 'evge_get_upsell_modal',
				'type'     => 'event_series_management',
				'location' => 'all-events-navigation',
			);

			// Build modal settings
			$modal_settings = array(
				'width' => 'small',
			);

			// Get the Series navigation item with modal trigger
			$series_nav_item = array(
				'id'                => 'series',
				'title'             => __( 'Series', 'event-genius' ),
				'url'               => '#', // Placeholder URL
				'capability'        => 'manage_options',
				'modal_trigger'     => true,
				'modal_ajax_data'   => $ajax_data,
				'modal_settings'    => $modal_settings,
			);
			
			// Insert Series tab after Venues tab
			$navigation_args['nav_items'] = array_merge(
				array_slice( $navigation_args['nav_items'], 0, $venues_index + 1 ),
				array( $series_nav_item ),
				array_slice( $navigation_args['nav_items'], $venues_index + 1 )
			);
					// Request modal since we're adding an upsell link
			EVGE()->modal_service()->request_modal();
		}

		return $navigation_args;
	}

	/**
	 * Add upsell sections to single event settings page
	 * 
	 * @param array $sections The event sections array
	 * @return array Modified sections array
	 */
	public function add_event_settings_upsells( $sections ) {
		// Request modal since we're adding upsell links
		EVGE()->modal_service()->request_modal();
		// Build AJAX data for the modal
		$ajax_data = array(
			'action'   => 'evge_get_upsell_modal',
			'type'     => 'accept_payments',
			'location' => 'event-settings',
		);

		// Build modal settings
		$modal_settings = array(
			'width' => 'small',
		);

		// Add Payments upsell section after blocks_shortcodes
		$sections['payments_upsell'] = array(
			'title' => __( 'Payments', 'event-genius' ),
			'pro_badge' => array(
				'ajax_data' => $ajax_data,
				'modal_settings' => $modal_settings,
			),
			'subsections' => array(
				'enable_payments_upsell' => array(
					'priority' => 10,
					'id' => 'enable_payments_upsell',
					'type' => 'payment_enabled_upsell',
					'callback' => array( $this, 'payment_enabled_upsell_display' ),
					'label' => __( 'Enable Payments', 'event-genius' ),
					'description' => '',
					'settings' => array()
				)
			)
		);

		// Build AJAX data for Scheduled Messages modal
		$scheduled_messages_ajax_data = array(
			'action'   => 'evge_get_upsell_modal',
			'type'     => 'scheduled_event_reminders',
			'location' => 'event-settings',
		);

		// Add Scheduled Messages upsell section after payments
		$sections['scheduled_messages_upsell'] = array(
			'title' => __( 'Scheduled Messages', 'event-genius' ),
			'pro_badge' => array(
				'ajax_data' => $scheduled_messages_ajax_data,
				'modal_settings' => $modal_settings,
			),
			'subsections' => array(
				'enable_scheduled_messages_upsell' => array(
					'priority' => 10,
					'id' => 'enable_scheduled_messages_upsell',
					'type' => 'scheduled_messages_enabled_upsell',
					'callback' => array( $this, 'scheduled_messages_enabled_upsell_display' ),
					'label' => __( 'Enable Scheduled Messages', 'event-genius' ),
					'description' => '',
					'settings' => array()
				)
			)
		);

		return $sections;
	}

	/**
	 * Display the payment enable/disable upsell toggle
	 * 
	 * @param array $section The section data
	 */
	public function payment_enabled_upsell_display( $section ) {
		// Build AJAX data for the modal
		$ajax_data = array(
			'action'   => 'evge_get_upsell_modal',
			'type'     => 'accept_payments',
			'location' => 'event-settings',
		);

		// Build modal settings
		$modal_settings = array(
			'width' => 'small',
		);

		?>
		<div id="evge-single-setting-<?php echo esc_attr( $section['id'] ); ?>" class="evge-payment-enabled-upsell-wrap evge-single-section evge-upsell-disabled">
			<div class="evge-flex evge-flex-center">
				<div class="evge-toggle-setting evge-upsell-toggle">
					<input class="evge-toggle-setting-enabled" 
						   type="hidden" 
						   name="evge_payment_status_upsell" 
						   value="disabled">
					<a class="evge-settings-toggle-wrap evge-modal-trigger" 
					   href="#"
					   data-evge-modal-content="ajax"
					   data-evge-ajax="<?php echo esc_attr( wp_json_encode( $ajax_data ) ); ?>"
					   data-evge-modal-settings="<?php echo esc_attr( wp_json_encode( $modal_settings ) ); ?>">
						<span class="evge-settings-toggle evge-input-toggle--disabled" 
							  aria-label="<?php esc_attr_e( 'Payments are disabled - Click to learn more', 'event-genius' ); ?>">
							<?php esc_html_e( 'No', 'event-genius' ); ?>
						</span>
					</a>
					<?php if ( ! empty( $section['label'] ) ) : ?>
						<label><?php echo esc_html( $section['label'] ); ?></label>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Display the scheduled messages enable/disable upsell toggle
	 * 
	 * @param array $section The section data
	 */
	public function scheduled_messages_enabled_upsell_display( $section ) {
		// Build AJAX data for the modal
		$ajax_data = array(
			'action'   => 'evge_get_upsell_modal',
			'type'     => 'scheduled_event_reminders',
			'location' => 'event-settings',
		);

		// Build modal settings
		$modal_settings = array(
			'width' => 'small',
		);

		?>
		<div id="evge-single-setting-<?php echo esc_attr( $section['id'] ); ?>" class="evge-scheduled-messages-enabled-upsell-wrap evge-single-section evge-upsell-disabled">
			<div class="evge-flex evge-flex-center">
				<div class="evge-toggle-setting evge-upsell-toggle">
					<input class="evge-toggle-setting-enabled" 
						   type="hidden" 
						   name="evge_scheduled_messages_status_upsell" 
						   value="disabled">
					<a class="evge-settings-toggle-wrap evge-modal-trigger" 
					   href="#"
					   data-evge-modal-content="ajax"
					   data-evge-ajax="<?php echo esc_attr( wp_json_encode( $ajax_data ) ); ?>"
					   data-evge-modal-settings="<?php echo esc_attr( wp_json_encode( $modal_settings ) ); ?>">
						<span class="evge-settings-toggle evge-input-toggle--disabled" 
							  aria-label="<?php esc_attr_e( 'Scheduled messages are disabled - Click to learn more', 'event-genius' ); ?>">
							<?php esc_html_e( 'No', 'event-genius' ); ?>
						</span>
					</a>
					<?php if ( ! empty( $section['label'] ) ) : ?>
						<label><?php echo esc_html( $section['label'] ); ?></label>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Add upsell tabs to single registration sub-navigation
	 * 
	 * @param array $sub_navigation_args The sub-navigation arguments array
	 * @param object $event The event object
	 * @return array Modified sub-navigation arguments with upsell tabs
	 */
	public function add_registration_sub_navigation_upsells( $sub_navigation_args, $event ) {
		// Request modal since we're adding upsell links
		EVGE()->modal_service()->request_modal();
		// Build AJAX data for Payments modal
		$payments_ajax_data = array(
			'action'   => 'evge_get_upsell_modal',
			'type'     => 'accept_payments',
			'location' => 'registration-subnav',
		);

		// Build AJAX data for Attendance modal
		$attendance_ajax_data = array(
			'action'   => 'evge_get_upsell_modal',
			'type'     => 'attendance_tracking',
			'location' => 'registration-subnav',
		);

		// Build modal settings
		$modal_settings = array(
			'width' => 'small',
		);

		// Add Payments tab
		$sub_navigation_args['nav_items'][] = array(
			'id'                => 'payments',
			'title'             => __( 'Payments', 'event-genius' ),
			'url'               => '#',
			'modal_trigger'     => true,
			'modal_ajax_data'   => $payments_ajax_data,
			'modal_settings'    => $modal_settings,
			'pro_badge'         => true,
		);

		// Add Attendance tab
		$sub_navigation_args['nav_items'][] = array(
			'id'                => 'attendance',
			'title'             => __( 'Attendance', 'event-genius' ),
			'url'               => '#',
			'modal_trigger'     => true,
			'modal_ajax_data'   => $attendance_ajax_data,
			'modal_settings'    => $modal_settings,
			'pro_badge'         => true,
		);

		return $sub_navigation_args;
	}

	/**
	 * Add Payments tab to Settings navigation (free version - opens upsell modal)
	 * 
	 * @param array $navigation_args The navigation arguments array
	 * @param string $active_tab The active tab
	 * @return array Modified navigation arguments with Payments tab
	 */
	public function add_payments_tab_to_settings_navigation( $navigation_args, $active_tab ) {
		// Check if Payments tab already exists (added by Pro version)
		$payments_exists = array_search( 'payments', array_column( $navigation_args['nav_items'], 'id' ) ) !== false;
		if ( $payments_exists ) {
			return $navigation_args;
		}

		// Add Payments tab after Registration tab
		$registration_index = array_search( 'registration', array_column( $navigation_args['nav_items'], 'id' ) );
		if ( $registration_index !== false ) {
			// Build AJAX data for the modal
			$ajax_data = array(
				'action'   => 'evge_get_upsell_modal',
				'type'     => 'accept_payments',
				'location' => 'settings-navigation',
			);

			// Build modal settings
			$modal_settings = array(
				'width' => 'small',
			);

			// Request modal since we're adding an upsell link
			EVGE()->modal_service()->request_modal();

			// Get the Payments navigation item with modal trigger
			$payments_nav_item = array(
				'id'                => 'payments',
				'title'             => __( 'Payments', 'event-genius' ),
				'url'               => '#', // Placeholder URL
				'modal_trigger'     => true,
				'modal_ajax_data'   => $ajax_data,
				'modal_settings'    => $modal_settings,
			);
			
			// Insert Payments tab after Registration tab
			$navigation_args['nav_items'] = array_merge(
				array_slice( $navigation_args['nav_items'], 0, $registration_index + 1 ),
				array( $payments_nav_item ),
				array_slice( $navigation_args['nav_items'], $registration_index + 1 )
			);
		}

		return $navigation_args;
	}

	/**
	 * Add advanced form fields upsell link next to Type label
	 * 
	 * @param object $field The field object
	 * @return void
	 */
	public function add_advanced_fields_upsell_link( $field ) {
		// Only show for free tier users
		if ( ! $this->is_free_tier() ) {
			return;
		}

		// Build AJAX data for the modal
		$ajax_data = array(
			'action'   => 'evge_get_upsell_modal',
			'type'     => 'advanced_form_fields',
			'location' => 'form-builder-type-setting',
		);

		// Build modal settings
		$modal_settings = array(
			'width' => 'small',
		);

		// Request modal since we're adding an upsell link
		EVGE()->modal_service()->request_modal();

		?>
		<a href="#" 
		   class="evge-advanced-fields-upsell-link evge-modal-trigger"
		   data-evge-modal-content="ajax"
		   data-evge-ajax="<?php echo esc_attr( wp_json_encode( $ajax_data ) ); ?>"
		   data-evge-modal-settings="<?php echo esc_attr( wp_json_encode( $modal_settings ) ); ?>">
			<?php esc_html_e( 'All field types', 'event-genius' ); ?>
		</a>
		<?php
	}

	/**
	 * Check if the current tier is free
	 * 
	 * @return bool True if free tier, false otherwise
	 */
	private function is_free_tier() {
		return function_exists( 'evge_is_free_tier' ) && evge_is_free_tier();
	}
}

