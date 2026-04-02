<?php
/**
 * Already Registered Modal Content Template
 * 
 * This template displays a modal dialog when a user attempts to register for an event
 * they are already registered for. It shows event details and provides options to
 * cancel their existing registration.
 * 
 * The template handles:
 * - Modal dialog structure
 * - Event details display
 * - Cancel registration form
 * 
 * @package WPEventGenius
 * @since 1.0.0
 * 
 * @var \WPEventGenius\Common\Utils\Templater $templater The templater instance
 * @var \WPEventGenius\Events\Event $event_post The event post object
 */

use WPEventGenius\Common\Utils\Settings;
use WPEventGenius\Common\Utils\Templater;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$templater = new Templater();
?>

<div class="evge-modal-reveal evge-already-registered-modal" 
	id="evge-registration-modal" 
	style="display: none" 
	role="dialog" 
	aria-modal="true" 
	aria-labelledby="evge-modal-title" 
	aria-describedby="evge-modal-description">
	
	<div class="evge-single-col-modal-content">
		<div class="evge-modal-event-details" 
			role="region" 
			aria-label="<?php echo esc_attr__( 'Event Details', 'event-genius' ); ?>">
			
			<div class="evge-modal-title" id="evge-modal-title">
				<strong><?php echo esc_html( $event_post->get_the_title() ); ?></strong>
			</div>
			
			<div class="evge-modal-date-venue">
				<?php
				EVGE()->template_manager()->get_template(
					'events/common/event-meta.php',
					[
						'event_post' => $event_post,
						'show_map'   => false
					]
				);
				?>
			</div>
		</div>
		
		<div class="evge-left-dynamic evge-cancel-form-wrap" 
			role="region" 
			aria-label="<?php echo esc_attr( Settings::get( 'cancel_registration_button_text' ) ); ?>">
			
			<strong class="evge-modal-section-heading">
				<?php echo wp_kses_post( Settings::get( 'already_registered' ) ); ?>
			</strong>
			
			<p id="evge-modal-description">
				<?php echo wp_kses_post( Settings::get( 'cancel_request_instructions' ) ); ?>
			</p>

			<?php include $templater->get_registration_template_part( 'cancel_input' ); ?>
		</div>
	</div>
</div>

