<?php
/**
 * Registration Form Template
 * 
 * This template renders the main registration form container and structure.
 * It includes the form fields, honeypot field (if enabled), and submit button.
 * 
 * @package WPEventGenius
 * @since 1.0.0
 * 
 * @var \WPEventGenius\Event\Post $event_post The event post object
 */

use WPEventGenius\Common\Utils\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$registration_data = apply_filters( 'evge_registration_data', array(), $event_post );
$flags = apply_filters( 'evge_registration_form_flags', array(), $event_post );
?>

<div class="evge-registration-form-fields" 
	role="region" 
	aria-label="<?php esc_attr_e( 'Event Registration Form', 'event-genius' ); ?>">
	
	<div class="evge-registration-form-inner">
		<form id="evge-registration-form" 
			method="post" 
			action="" 
			class="evge-registration-form" 
			aria-label="<?php esc_attr_e( 'Event Registration', 'event-genius' ); ?>">
			
			<?php 
			// Use EventPost method to get the appropriate event ID input(s)
			// This handles both single and bulk registration automatically
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $event_post->get_event_id_input();
			?>
			<input type="hidden" 
				name="action" 
				value="evge_registration_form_submit">

			<?php do_action( 'evge_registration_form_before_fields', $event_post, $registration_data ); ?>

			<div class="evge-form-fields-information" 
				role="group" 
				aria-label="<?php esc_attr_e( 'Registration Information', 'event-genius' ); ?>">
				<div class="evge-main-registration evge-form-fields" data-guest-number="main">
					<?php 
					// Disable wrapper output since form.php already provides the wrapper
					$flags['output_wrapper'] = false;
					$event_post->display_form_fields( $registration_data, $flags ); 
					?>
					<?php $event_post->maybe_display_honeypot(); ?>
				</div>
				<?php do_action( 'evge_registration_form_after_fields', $event_post, $registration_data ); ?>
			</div>

			<?php 
			// Only show submit button in standalone forms, not in modal context
			// Check if we're in modal context (set by the including template)
			$is_modal_context = isset($is_modal_context) && $is_modal_context === true;
			if ( !$is_modal_context ) : ?>
			<div class="evge-form-button-wrapper">
				<button id="evge-registration-submit" 
					class="evge-form-button evge-button evge-green-button" 
					type="submit"
					aria-label="<?php esc_attr_e( 'Submit Registration', 'event-genius' ); ?>"
					<?php 
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $event_post->submit_button_style_att(); ?>>
					<?php 
					$submit_button_text = apply_filters( 'evge_registration_form_submit_button_text', esc_html( Settings::get( 'form_submit_button_text' ) ), $event_post, $registration_data );
					echo esc_html( $submit_button_text ); 
					?>
				</button>
			</div>
			<?php endif; ?>
		</form>
	</div>

</div>
