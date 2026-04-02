<?php
/**
 * Registration Modal Content Template
 *
 * Renders the modal dialog for event registration, including the registration form,
 * event details, and a summary of the order/cost. This template is used as the main
 * modal content for attendee registration.
 *
 * @package WPEventGenius
 * @since 1.0.0
 *
 * @var \WPEventGenius\Event\Post $event_post The event post object
 */

use WPEventGenius\Common\Utils\Settings;
use WPEventGenius\Common\Utils\Templater;
use WPEventGenius\Common\Utils\Icon;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$templater = new Templater();

// Check if we're in edit mode (registration has an ID)
$is_edit_mode = ! empty( $registration_data['id'] ) && ! empty( $registration_data );
?>
<div class="evge-modal-reveal"
	id="evge-registration-modal"
	role="dialog"
	aria-modal="true"
	aria-labelledby="evge-modal-title"
	aria-describedby="evge-modal-description"
	style="display: none">
	<div class="evge-cols">
		<div class="evge-modal-col evge-modal-col-left">
			<div class="evge-left-dynamic">
				<?php if ( $is_edit_mode ) : 
					// Get event ID for the back button
					$back_event_id = ! empty( $registration_data['event_id'] ) ? $registration_data['event_id'] : $event_post->get_the_id();
				?>
					<div class="evge-edit-back-button-wrap">
						<button type="button"
								class="evge-button evge-button-secondary evge-back-button evge-action-trigger"
								data-evge-ajax="<?php echo esc_attr( wp_json_encode( array(
									'action' => 'evge_get_already_registered_content',
									'event_id' => $back_event_id
								) ) ); ?>"
								data-evge-modal-content="ajax">
							<span class="evge-icon-text">
								<?php 
								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								echo Icon::get( 'left-chevron' ); 
								?>
								<?php esc_html_e( 'Back', 'event-genius' ); ?>
							</span>
						</button>
					</div>
				<?php endif; ?>
				
				<?php do_action( 'evge_registration_form_top', $event_post, $registration_data ); ?>
				<div class="evge-registration-form-wrap">
					<?php 
					$title_text = apply_filters( 'evge_registration_modal_title', esc_html__( 'Register Attendee', 'event-genius' ), $event_post );
					?>
					<h3 id="evge-modal-title"><?php echo esc_html( $title_text ); ?></h3>
					<div id="evge-modal-description" class="screen-reader-text">
						<?php esc_html_e( 'Registration form for the event. Please fill in your details to complete registration.', 'event-genius' ); ?>
					</div>
					<div id="evge-form-messages" class="evge-form-messages evge-screen-reader-text" role="alert" aria-live="polite"></div>
					<?php 
					// Set flag to indicate modal context
					$is_modal_context = true;
					include $templater->get_registration_template_part( 'form' ); 
					?>
				</div>
				<?php do_action( 'evge_registration_form_bottom', $event_post, $registration_data ); ?>
			</div>
		</div>
		<div class="evge-modal-col evge-modal-col-right">
			<?php EVGE()->template_manager()->get_template( 'common/payment-summary.php', [
            	'event_post' => $event_post
        	]); 
			?>
			
			<!-- Submit button for modal context - positioned below payment summary -->
			<div class="evge-modal-submit-wrapper">
				<button id="evge-registration-submit" 
					class="evge-form-button evge-button evge-green-button evge-modal-submit-button" 
					type="submit"
					form="evge-registration-form"
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
		</div>
	</div>
</div>

