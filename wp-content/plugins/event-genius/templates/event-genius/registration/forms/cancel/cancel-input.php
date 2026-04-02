<?php
/**
 * Cancel Registration Input Form Template
 * 
 * This template displays a form that allows users to cancel their event registration
 * by entering their email address. It includes form validation and error handling.
 * 
 * The template handles:
 * - Cancel registration form structure
 * - Email input field
 * - Submit button
 * - Error message display
 * 
 * @package WPEventGenius
 * @since 1.0.0
 * 
 * @var \WPEventGenius\Events\Event $event_post The event post object
 */

use WPEventGenius\Common\Utils\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="evge-registration-form-inner">
	<form id="evge-cancel-form" 
		method="post" 
		action="" 
		class="evge-cancel-form" 
		aria-label="<?php echo esc_attr__( 'Cancel Registration Form', 'event-genius' ); ?>">
		
		<input type="hidden" 
			name="event_id" 
			value="<?php echo esc_attr( $event_post->get_the_id() ); ?>">
		<input type="hidden" 
			name="action" 
			value="evge_registration_cancel_submit">

		<div class="evge-field-wrapper" 
			role="group" 
			aria-labelledby="evge-cancel-email-label">
			
			<div class="evge-field-inner">
				<div class="evge-label-wrapper">
					<label for="evge_cancel_email" 
						id="evge-cancel-email-label">
						<?php echo esc_html( Settings::get( 'cancel_request_field_label' ) ); ?>
					</label>
				</div>
				
				<div class="evge-input-wrapper">
					<input type="email" 
						name="evge_cancel_email" 
						id="evge_cancel_email" 
						placeholder="" 
						value="" 
						aria-required="true" />
						
					<button id="evge-cancel-submit" 
						class="evge-form-button evge-button evge-green-button" 
						type="submit">
						<?php echo esc_html( Settings::get( 'cancel_request_submit_button_text' ) ); ?>
					</button>
				</div>
				
				<div class="evge-field-error" 
					role="alert" 
					aria-live="polite">
					<span>
						<?php echo esc_html( Settings::get( 'cancel_no_results' ) ); ?>
					</span>
				</div>
			</div>
		</div>
	</form>
</div>
