<?php

namespace WPEventGenius\Admin\Services;

use WPEventGenius\Common\Event\ExampleEventPost;
use WPEventGenius\Common\Registration\Field\FieldHandler;
use WPEventGenius\Common\Services\RegistrationObjectFactory;
use WPEventGenius\Common\Utils\Icon;
use WPEventGenius\Common\Utils\Templater;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class FormBuilderService {
	private $factory;
	
	public function __construct() {
		$this->factory = new RegistrationObjectFactory();
	}

	public function init_hooks() {
		add_action( 'wp_ajax_evge_save_field', array( $this, 'save_field' ) );
		add_action( 'wp_ajax_evge_save_all_fields', array( $this, 'save_all_fields' ) );
		add_action( 'wp_ajax_evge_delete_field', array( $this, 'delete_field' ) );
		add_action( 'wp_ajax_evge_create_field', array( $this, 'create_field' ) );
		add_action( 'wp_ajax_evge_save_form', array( $this, 'save_form' ) );
		add_action( 'wp_ajax_evge_in_form_add_field', array( $this, 'in_form_add_field' ) );
		add_action( 'wp_ajax_evge_create_form_modal_content', array( $this, 'create_form_modal_content' ) );
		add_action( 'wp_ajax_evge_create_new_form', array( $this, 'create_new_form' ) );
		add_action( 'init', array( $this, 'handle_form_deletion' ) );
		add_action( 'init', array( $this, 'handle_form_duplication' ) );

		add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
	}

	public static function rich_editor_fields() {
		return array(
			'cancel_request_instructions',
			'cancel_request_email_content',
			'cancel_success_message',
			'cancel_request_success_message',
			'cancel_notification_email_content',
			'confirmed_modal_message',
			'pending_approval_modal_message',
			'confirmation_email_content',
			'notification_email_content',
			'cancel_request_email_content',
			'cancel_success_message',
			'cancel_request_success_message',
			'cancel_notification_email_content',
			'confirmed_modal_message',
			'additional_guest_summary_template',
		);
	}

	public function save_field() {
		check_ajax_referer( 'evge-admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'You do not have permission to perform this action.' ) );
		}
		$field_handler = $this->factory->create_field_handler();

		if ( ! isset( $_POST['fields'] ) ) {
			wp_send_json_error( array( 'message' => 'No fields to save.' ) );
		}

		//phpcs:ignore 
		$raw_fields = isset($_POST['fields']) ? $_POST['fields'] : array();
		$sanitized_fields = array();
		foreach ( $raw_fields as $key => $field_data ) {
			if ( $key === 'options' ) {
				$sanitized_fields[ sanitize_key( $key ) ] = $field_handler->parse_options( sanitize_textarea_field( wp_unslash( $field_data ) ) );
			} elseif ( $key === 'misc' && is_array( $field_data ) ) {
				// Handle misc array - scrollable_text should allow basic HTML
				$sanitized_misc = array();
				foreach ( $field_data as $misc_key => $misc_value ) {
					if ( $misc_key === 'scrollable_text' ) {
						// Allow basic HTML: headings, anchors, bold, etc.
						$sanitized_misc[ sanitize_key( $misc_key ) ] = wp_kses_post( wp_unslash( $misc_value ) );
					} else {
						$sanitized_misc[ sanitize_key( $misc_key ) ] = sanitize_text_field( wp_unslash( $misc_value ) );
					}
				}
				$sanitized_fields[ sanitize_key( $key ) ] = $sanitized_misc;
			} elseif ( is_array( $field_data ) ) {
				$sanitized_fields[ sanitize_key( $key ) ] = array_map( 'sanitize_text_field', wp_unslash( $field_data ) );
			} else {
				$sanitized_fields[ sanitize_key( $key ) ] = sanitize_text_field( wp_unslash( $field_data ) );
			}
		}

		// Apply Pro field data processing filter
		$sanitized_fields = apply_filters( 'evge_form_builder_sanitized_field_before_save_form', $sanitized_fields, $raw_fields );


		if ( $sanitized_fields['id'] === 'submit-button' ) {
			$this->update_submit_button( $sanitized_fields );
			$all_fields = new \WPEventGenius\Admin\FormBuilder\AllFields( $field_handler );
			$field = $all_fields->get_submit_button();
			$field_label_html = '<span class="evge-icon-buffer">' . Icon::get( 'submitfield' ) . '</span>' . esc_html( $field->get_label() );
			wp_send_json_success( array( 'success' => true, 'html' => '', 'fieldLabelHTML' => $field_label_html, 'badgeMessage' => __( 'Field saved', 'event-genius' ) ) );
		}

		$updated_field_db_data = $field_handler->update_field( $sanitized_fields['id'], $sanitized_fields );

		// Ensure field is properly initialized
		if (empty($updated_field_db_data)) {
			wp_send_json_error(array('message' => 'Failed to update field.'));
			return;
		}

		$field = $field_handler->convert_field( $updated_field_db_data );

		$field_label_html = '<span class="evge-icon-buffer">' . Icon::get( $field->get_type() . 'field' ) . '</span>' . esc_html( $field->get_label() );

		$templater = new Templater();

		$registration_data = array();
		$flags = array();
		ob_start();
		include $templater->get_registration_template_part( 'field' );
		$field_html = ob_get_clean();

		$badge_message = __( 'Field saved', 'event-genius' );

		wp_send_json_success( array( 'success' => true, 'html' => $field_html, 'fieldLabelHTML' => $field_label_html, 'badgeMessage' => $badge_message ) );
	}

	public function update_submit_button( $data ) {
		$settings = get_option( 'evge_default_form_settings', array() );
		if ( is_string( $settings ) ) {
			$settings = json_decode( $settings, true );
		}
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}	

		$settings['form_submit_button_text'] = $data['form_submit_button_text'];
		$settings['submit_button_background_color'] = $data['submit_button_background_color'];
		$settings['submit_button_text_color'] = $data['submit_button_text_color'];
		$settings['submit_button_border_color'] = $data['submit_button_border_color'];

		update_option( 'evge_default_form_settings', wp_json_encode( $settings ) );
	}

	public function save_all_fields() {
		check_ajax_referer( 'evge-admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'You do not have permission to perform this action.' ) );
		}

		$field_handler = $this->factory->create_field_handler();

		//phpcs:ignore 
		$raw_all_fields = isset( $_POST['all_fields'] ) ? $_POST['all_fields'] : array();
		$sanitized_all_fields = array();
		foreach ( $raw_all_fields as $key => $raw_fields ) {
			$sanitized_fields = array();
			foreach ( $raw_fields as $key => $field_data ) {
				if ( $key === 'options' ) {
					$sanitized_fields[ sanitize_key( $key ) ] = $field_handler->parse_options( sanitize_textarea_field( wp_unslash( $field_data ) ) );
				} elseif ( $key === 'misc' && is_array( $field_data ) ) {
					// Handle misc array - scrollable_text should allow basic HTML
					$sanitized_misc = array();
					foreach ( $field_data as $misc_key => $misc_value ) {
						if ( $misc_key === 'scrollable_text' ) {
							// Allow basic HTML: headings, anchors, bold, etc.
							$sanitized_misc[ sanitize_key( $misc_key ) ] = wp_kses_post( wp_unslash( $misc_value ) );
						} else {
							$sanitized_misc[ sanitize_key( $misc_key ) ] = sanitize_text_field( wp_unslash( $misc_value ) );
						}
					}
					$sanitized_fields[ sanitize_key( $key ) ] = $sanitized_misc;
				} elseif ( is_array( $field_data ) ) {
					$sanitized_fields[ sanitize_key( $key ) ] = array_map( 'sanitize_text_field', wp_unslash( $field_data ) );
				} else {
					$sanitized_fields[ sanitize_key( $key ) ] = sanitize_text_field( wp_unslash( $field_data ) );
				}
			}
			$sanitized_all_fields[] = $sanitized_fields;
		}

		$updated_field_db_data = array();
		foreach ( $sanitized_all_fields as $sanitized_fields ) {
			$updated_field_db_data[] = $field_handler->update_field( $sanitized_fields['id'], $sanitized_fields );
		}

		$all_fields_html = array();
		foreach ( $updated_field_db_data as $field_db_data ) {
			$field = $field_handler->convert_field( $field_db_data );
			$field_label_html = '<span class="evge-icon-buffer">' . Icon::get( $field->get_type() . 'field' ) . '</span>' . esc_html( $field->get_label() );
			$all_fields_html[] = array(
				'id' =>  $field->get_id(),
				'html' => $field_label_html
			);
		}

		wp_send_json_success( array( 'success' => true, 'allFieldsHTML' => $all_fields_html ) );
	}

	public function delete_field() {
		check_ajax_referer( 'evge-admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'You do not have permission to perform this action.' ) );
		}
		$field_handler = $this->factory->create_field_handler();

		if ( ! isset( $_POST['id'] ) ) {
			wp_send_json_error( array( 'message' => 'No field ID to delete.' ) );
		}

		$field_id = sanitize_key( wp_unslash( $_POST['id'] ) );

		$field_handler->delete_field( $field_id );

		$badge_message = __( 'Field deleted', 'event-genius' );

		wp_send_json_success( array( 'success' => true, 'badgeMessage' => $badge_message ) );
	}

	public function save_form() {
		check_ajax_referer( 'evge-admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'You do not have permission to perform this action.' ) );
		}

		if ( ! isset( $_POST['form_id'] ) ) {
			wp_send_json_error( array( 'message' => 'No form ID to save.' ) );
		}

		$form_id = sanitize_key( wp_unslash( $_POST['form_id'] ) );

		$sanitize_fields = array();
		//phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( isset( $_POST['fields'] ) ) {
			//phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			foreach ( $_POST['fields'] as $field ) {
				$sanitized = array(
					'id' => sanitize_key( wp_unslash( $field['id'] ) ),
					'required' => $field['required'] === 'enabled',
					'editable' => isset( $field['editable'] ) ? $field['editable'] === 'enabled' : true,
					'show_in_attendee_list' => isset( $field['show_in_attendee_list'] ) ? $field['show_in_attendee_list'] === 'enabled' : false,
				);
				$sanitized = apply_filters( 'evge_form_builder_sanitized_field_before_save_form', $sanitized, $field );
				$sanitize_fields[] = $sanitized;
			}
		}

		$field_handler = $this->factory->create_field_handler();

		$field_handler->update_form( $form_id, $sanitize_fields );
		
		// Handle form name update
		if ( isset( $_POST['form_name'] ) ) {
			$form_name = sanitize_text_field( wp_unslash( $_POST['form_name'] ) );
			if ( ! empty( $form_name ) && class_exists( '\WPEventGenius\Standard\Database\StandardDatabase' ) ) {
				$database = new \WPEventGenius\Standard\Database\StandardDatabase();
				$database->update_form_name( $form_id, $form_name );
			}
		}
		
		//phpcs:ignore
		$email_settings = ! empty( $_POST['email'] ) ? $_POST['email'] : array();
		$email_settings_sanitized = array();
		if ( ! empty( $email_settings ) ) {
			$email_settings_sanitized = self::sanitize_settings_for_form_builder( $email_settings );
		}

		if ( ! isset( $_POST['settings'] ) ) {
			wp_send_json_error( array( 'message' => 'No settings to save.' ) );
		}
		// add flag for admin so we can see admin only fields
		add_filter( 'evge_registration_form_flags', array( $this, 'add_flag_for_already_registered' ), 10, 2 );

		//phpcs:ignore
		$settings_settings = ! empty( $_POST['settings'] ) ? $_POST['settings'] : array();
		$settings_settings_sanitized = array();
		if ( ! empty( $settings_settings ) ) {	
			$settings_settings_sanitized = self::sanitize_settings_for_form_builder( $settings_settings );
		}

		// Handle form-specific settings storage
		$form_settings_service = $this->factory->create_form_settings_service();
		
		if ( $form_settings_service ) {
			// Use database storage for form-specific settings
			$all_settings = array_merge( $email_settings_sanitized, $settings_settings_sanitized );
			$success = $form_settings_service->update_all_settings( $form_id, $all_settings );
			
			if ( ! $success ) {
				wp_send_json_error( array( 'message' => 'Failed to save form settings to database.' ) );
			}
		} else {
			// Fallback to legacy option storage
			$settings_settings = array_merge( $email_settings_sanitized, $settings_settings_sanitized );
			// merge email and settings settings
			$settings = json_encode( array_merge( $email_settings_sanitized, $settings_settings_sanitized ) );

			update_option( 'evge_default_form_settings', $settings );
		}

		// Update template-form associations (only for Standard/Pro versions)
		if ( evge_is_standard_tier() && class_exists( '\WPEventGenius\Standard\Admin\Page\Registrations\FormBuilder\StandardEmailPage' ) ) {
			$email_page = new \WPEventGenius\Standard\Admin\Page\Registrations\FormBuilder\StandardEmailPage();
			if ( method_exists( $email_page, 'update_template_form_associations' ) ) {
				$email_page->update_template_form_associations( $form_id, $email_settings_sanitized );
			}
		}

		$sanitize_submit_button = array();
		//phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( isset( $_POST['submit_button'] ) ) {
			//phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			foreach ( $_POST['submit_button'] as $key => $value ) {
				$sanitize_submit_button[ $key ] = sanitize_text_field( wp_unslash( $value ) );
			}
		}

		$this->update_submit_button( $sanitize_submit_button );

		$templater  = new Templater();
		$event_post = new ExampleEventPost( 0, $form_id );
		ob_start();
		include $templater->get_registration_template_part( 'form' );

		$field_html = ob_get_clean();

		$badge_message = __( 'Form saved', 'event-genius' );
		wp_send_json_success( array( 'success' => true, 'html' => $field_html, 'badgeMessage' => $badge_message ) );
	}

	public function add_flag_for_already_registered( $flags, $event_post ) {
		$flags['is_admin'] = true;
		return $flags;
	}

	public function create_field() {
		check_ajax_referer( 'evge-admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'You do not have permission to perform this action.' ) );
		}

		$field_handler = $this->factory->create_field_handler();

		$field_db_data = $field_handler->create_generic_field();

		$field = $field_handler->convert_field( $field_db_data );
		$registration_data = array();

		ob_start();
		include( EVGE_ADMIN_TEMPLATE_PATH . 'evge/registrations/form-builder/single-field-edit.php' );
		$field_html = ob_get_clean();

		wp_send_json_success( array( 'success' => true, 'html' => $field_html ) );

	}

	public function in_form_add_field() {
		check_ajax_referer( 'evge-admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'You do not have permission to perform this action.' ) );
		}

		$field_handler = $this->factory->create_field_handler();

		if ( ! isset( $_POST['id'] ) ) {
			wp_send_json_error( array( 'message' => 'No field ID to add.' ) );
		}
		
		$field_id = sanitize_key( wp_unslash( $_POST['id'] ) );

		$field_db_data = $field_handler->query_fields( 'field_id', array( 'field_id' => $field_id ) );

		$field_html = '';

		$field = $field_handler->convert_field( $field_db_data );

		$templater = new Templater();
		$registration_data = array();
		$flags = array();
		ob_start();
		include $templater->get_registration_template_part( 'field' );
		$field_html = ob_get_clean();

		wp_send_json_success( array( 'success' => true, 'html' => $field_html ) );

	}

		/**
	 * Enqueue builder assets
	 */
	public function enqueue_assets($hook) {
		if (!$this->is_form_builder_screen()) {
			return;
		}

		EVGE()->style_service()->enqueue_style( 'evge_admin_common' );
		EVGE()->style_service()->enqueue_style( 'evge_form_builder' );
		EVGE()->style_service()->enqueue_style( 'evge_registration_form' );

		
		wp_enqueue_style('wp-color-picker');
		wp_enqueue_script('wp-color-picker');
		
		// Enqueue WordPress editor for TinyMCE support
		wp_enqueue_editor();
		wp_enqueue_media();
		
		EVGE()->script_service()->enqueue_script( 'evge_admin_form_builder' );
		EVGE()->script_service()->enqueue_script( 'evge_email_template_preview' );
		
	}

	public static function sanitize_settings_for_form_builder( $input ) {
		$options = array();
		foreach( $input as $key => $value ) {
			if ( in_array( $key, self::rich_editor_fields(), true ) ) {
				$options[ $key ] = wp_kses_post( wp_unslash( $value ) );
			} elseif ( is_array( $value ) ) {
				$options[ $key ] = array();
				foreach( $value as $sub_key => $sub_value ) {
					if ( is_string( $sub_key) ) {
						$options[ $key ][ $sub_key ] = sanitize_text_field( wp_unslash( $sub_value ) );
					} else {
						$options[ $key ][] = $sub_value;
					}
				}
			} else {
				$options[ $key ] = sanitize_text_field( wp_unslash( $value ) );
			}
		}


		return $options;
	}

	private function is_form_builder_screen() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset($_GET['page']) && $_GET['page'] === 'evge-registrations' && isset($_GET['tab']) && $_GET['tab'] === 'forms';
	}

	/**
	 * Handle AJAX request for create form modal content
	 */
	public function create_form_modal_content() {
		check_ajax_referer( 'evge-admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'You do not have permission to perform this action.' ) );
		}

		ob_start();
		?>
		<div class="evge-create-form-modal">
			<div class="evge-modal-header">
				<h2><?php esc_html_e( 'Create New Form', 'event-genius' ); ?></h2>
			</div>
			<div class="evge-modal-body">
				<form id="evge-create-form-form" class="evge-form" method="post" action="">
					<?php wp_nonce_field( 'evge-admin', 'nonce' ); ?>
					<input type="hidden" name="form_id" value="new">
					<input type="hidden" name="action" value="evge_create_new_form">
					<div class="evge-form-field">
						<label for="form_name" class="evge-form-label">
							<?php esc_html_e( 'Form Name', 'event-genius' ); ?>
							<span class="evge-required">*</span>
						</label>
						<input 
							type="text" 
							id="form_name" 
							name="form_name" 
							class="evge-form-input" 
							placeholder="<?php esc_attr_e( 'Enter form name...', 'event-genius' ); ?>"
							required
							maxlength="255"
						>
						<button type="submit" class="button button-primary evge-create-form-submit">
							<?php esc_html_e( 'Create Form', 'event-genius' ); ?>
						</button>
					</div>
				</form>
			</div>

		</div>
		<?php
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Handle form deletion via POST or GET submission
	 */
	public function handle_form_deletion() {
		// Check if this is a form deletion submission (POST or GET)
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : ( isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '' );
		
		if ( $action !== 'evge_delete_form' ) {
			return;
		}

		// Verify nonce (check both POST and GET)
		$nonce = isset( $_POST['evge_delete_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['evge_delete_nonce'] ) ) : ( isset( $_GET['evge_delete_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['evge_delete_nonce'] ) ) : '' );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$form_id = isset( $_POST['form_id'] ) ? intval( $_POST['form_id'] ) : ( isset( $_GET['form_id'] ) ? intval( $_GET['form_id'] ) : 0 );
		
		if ( ! $form_id ) {
			wp_die( __( 'Invalid form ID', 'event-genius' ) );
		}

		if ( ! wp_verify_nonce( $nonce, 'evge_delete_form_' . $form_id ) ) {
			wp_die( __( 'Security check failed. Please try again.', 'event-genius' ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have permission to perform this action.', 'event-genius' ) );
		}

		// Don't allow deleting the default form (ID 1)
		if ( $form_id === 1 ) {
			wp_die( __( 'Cannot delete the default form', 'event-genius' ) );
		}

		// Delete the form using StandardDatabase
		$database = new \WPEventGenius\Standard\Database\StandardDatabase();
		$result = $database->delete_form( $form_id );

		if ( ! $result ) {
			wp_die( __( 'Failed to delete form. Please try again.', 'event-genius' ) );
		}

		// Redirect to the forms homepage with success message
		$redirect_url = admin_url( 'admin.php?page=evge-registrations&tab=forms&message=deleted' );
		wp_redirect( $redirect_url );
		exit;
	}

	/**
	 * Handle form duplication via GET submission
	 */
	public function handle_form_duplication() {
		// Check if this is a form duplication submission
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
		
		if ( $action !== 'duplicate' ) {
			return;
		}

		// Only run on the registrations page with forms tab
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = ! empty( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = ! empty( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';

		if ( $page !== 'evge-registrations' || $tab !== 'forms' ) {
			return;
		}

		// Verify nonce
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$nonce = isset( $_GET['evge_duplicate_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['evge_duplicate_nonce'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$form_id = isset( $_GET['form_id'] ) ? intval( $_GET['form_id'] ) : 0;

		if ( ! $form_id ) {
			wp_die( __( 'Invalid form ID', 'event-genius' ) );
		}

		if ( ! wp_verify_nonce( $nonce, 'evge_duplicate_form_' . $form_id ) ) {
			wp_die( __( 'Security check failed. Please try again.', 'event-genius' ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have permission to perform this action.', 'event-genius' ) );
		}

		// Duplicate the form
		$database = new \WPEventGenius\Standard\Database\StandardDatabase();
		$duplicated_form_id = $this->duplicate_form( $form_id, $database );

		if ( ! $duplicated_form_id ) {
			wp_die( __( 'Failed to duplicate form. Please try again.', 'event-genius' ) );
		}

		// Redirect to the duplicated form edit page
		$redirect_url = admin_url( 'admin.php?page=evge-registrations&tab=forms&form_id=' . $duplicated_form_id . '&message=duplicated' );
		wp_redirect( $redirect_url );
		exit;
	}

	/**
	 * Duplicate a form and all its fields
	 * 
	 * @param int $form_id The form ID to duplicate
	 * @param \WPEventGenius\Standard\Database\StandardDatabase $database The database instance
	 * @return int|false The new form ID or false on failure
	 */
	private function duplicate_form( $form_id, $database ) {
		global $wpdb;

		// Get the original form
		$original_form = $database->get_form_by_id( $form_id );
		
		if ( ! $original_form ) {
			return false;
		}

		// Get the original form fields
		$original_fields = $database->query_form_fields( $form_id );

		// Start transaction
		$wpdb->query( 'START TRANSACTION' );

		try {
			// Create new form with copied name
			$forms_table = $database->get_table_name( \WPEventGenius\Standard\Database\StandardDatabase::REGISTRATION_FORMS_TABLE );
			$new_form_name = $original_form['name'] . ' (Copy)';
			
			$result = $wpdb->insert(
				$forms_table,
				array(
					'name' => $new_form_name,
					'visibility' => $original_form['visibility'],
					'user_id' => get_current_user_id(),
					'settings' => wp_json_encode( $original_form['settings'] ),
					'email' => wp_json_encode( $original_form['email'] ),
					'created_date' => current_time( 'mysql' ),
					'updated_date' => current_time( 'mysql' ),
				),
				array( '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
			);

			if ( ! $result ) {
				throw new \Exception( 'Failed to create duplicate form' );
			}

			$new_form_id = $wpdb->insert_id;

			// Copy form fields
			if ( ! empty( $original_fields ) ) {
				$form_fields_table = $database->get_table_name( \WPEventGenius\Standard\Database\StandardDatabase::FORM_FIELDS_TABLE );
				
				foreach ( $original_fields as $index => $field ) {
					$wpdb->insert(
						$form_fields_table,
						array(
							'form_id' => $new_form_id,
							'field_id' => $field['id'],
							'order_index' => $index + 1,
							'main_required' => $field['main_required'] ? 1 : 0,
							'guest_required' => isset( $field['guest_required'] ) && $field['guest_required'] ? 1 : 0,
							'main_include' => isset( $field['main_include'] ) && $field['main_include'] ? 1 : 0,
							'guest_include' => isset( $field['guest_include'] ) && $field['guest_include'] ? 1 : 0,
							'guest_inherit' => isset( $field['guest_inherit'] ) && $field['guest_inherit'] ? 1 : 0,
							'editable' => isset( $field['editable'] ) && $field['editable'] ? 1 : 0,
							'show_in_attendee_list' => isset( $field['show_in_attendee_list'] ) && $field['show_in_attendee_list'] ? 1 : 0,
						),
						array( '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d' )
					);
				}
			}

			// Commit transaction
			$wpdb->query( 'COMMIT' );
			
			return $new_form_id;

		} catch ( \Exception $e ) {
			// Rollback transaction
			$wpdb->query( 'ROLLBACK' );
			return false;
		}
	}
}