<?php
/**
 * No Attendees Template
 * 
 * This template displays a message when no attendees are found for an event.
 * It provides a user-friendly notification with contextual messaging and
 * action buttons based on registration status.
 * 
 * @package WPEventGenius
 * @since 1.0.0
 * 
 * @var \WPEventGenius\Common\Event\Event $event The event object
 * @var \WPEventGenius\Common\Event\EventPost $event_post The event post object
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\Common\Utils\Icon;
use WPEventGenius\Common\Utils\DateFormatter;
use WPEventGenius\Common\Utils\EvgeDateTime;
use WPEventGenius\Common\Utils\Settings;

// Check registration status
$registration_is_open = $event_post->registration_is_open();
$registration_has_closed = $event_post->registration_has_closed();
$allow_registration = $event_post->get_allow_registration() === 'enabled';
?>

<div class="evge-no-attendees">
	<div class="evge-no-attendees-message">
		<div class="evge-no-attendees-icon">
			<?php Icon::output( 'person' ); ?>
		</div>

		<h3><?php esc_html_e( 'No Attendees Yet', 'event-genius' ); ?></h3>

		<?php if ( $allow_registration && $registration_is_open && ! $registration_has_closed ) : ?>
			<div class="evge-no-attendees-actions">
				<?php
				// Check if current user can register for this event
				$event_goer = EVGE()->event_goer();
				$event_goer->set_event( $event_post );
				if ( ! $event_goer || ! $event_goer->can_register_for_event() ) {
					// Show login to register button
					$login_button_text = __( 'Log In to Register', 'event-genius' );
					$login_url = wp_login_url( get_permalink( $event_post->get_the_id() ) );
					?>
					<a href="<?php echo esc_url( $login_url ); ?>" class="evge-button evge-button-primary">
						<?php echo esc_html( $login_button_text ); ?>
					</a>
					<?php
				} else {
					// User can register, show registration button
					$form = $event_post->get_form();
					$register_button_text = apply_filters( 'evge_form_register_button_text', $form->get_register_button_text(), $event_post );
					$edit_json_settings = array( 'width' => 'full' );
					
					// Enqueue registration form script
					EVGE()->script_service()->enqueue_script('evge_common');
					EVGE()->script_service()->enqueue_script('evge_registration_form');
					?>
					<button class="evge-button evge-button-primary evge-modal-trigger evge-checkout-cache-<?php echo esc_attr( $event_post->get_the_id() ); ?>" 
						data-evge-modal-settings="<?php echo esc_attr( wp_json_encode( $edit_json_settings ) ); ?>" 
						data-evge-modal-content="ajax" 
						data-evge-ajax="<?php echo esc_attr( $event_post->get_event_json( 'evge_get_registration_content' ) ); ?>">
						<?php echo esc_html( $register_button_text ); ?>
					</button>
					<?php
				}
				?>
			</div>
		<?php elseif ( $allow_registration && ! $registration_is_open && ! $registration_has_closed ) : ?>
			<?php
			// Get the message from settings
			$message = Settings::get( 'registration_not_open_text' );
			
			// Get the registration open date
			$open_date = $event_post->get_registration_open_date();
			
			if ( ! empty( $open_date ) && $open_date !== '100' ) {
				// Create EvgeDateTime object with proper timezone
				$open_date_time = new EvgeDateTime( new \DateTime( $open_date, DateFormatter::timezone_object( $event_post->get_the_timezone() ) ) );
				$current_time = new EvgeDateTime( new \DateTime( 'now', DateFormatter::timezone_object( $event_post->get_the_timezone() ) ) );
				
				// Format the open date using DateFormatter
				$formatted_date = DateFormatter::date_format( $open_date_time->format( 'Y-m-d H:i:s' ), 'registration_timeline' );
				
				// Calculate time difference for countdown
				$time_diff = $open_date_time->timestamp() - $current_time->timestamp();
				
				// Calculate days, hours, and minutes
				$days = floor( $time_diff / (60 * 60 * 24) );
				$hours = floor( ( $time_diff % (60 * 60 * 24) ) / (60 * 60) );
				$minutes = floor( ( $time_diff % (60 * 60) ) / 60 );
				
				// Build the countdown string
				$countdown_parts = array();
				if ( $days > 0 ) {
					/* translators: %d: Number of days */
					$countdown_parts[] = sprintf( _n( '%d day', '%d days', $days, 'event-genius' ), $days );
				}
				if ( $hours > 0 ) {
					/* translators: %d: Number of hours */
					$countdown_parts[] = sprintf( _n( '%d hour', '%d hours', $hours, 'event-genius' ), $hours );
				}
				if ( $minutes > 0 ) {
					/* translators: %d: Number of minutes */
					$countdown_parts[] = sprintf( _n( '%d minute', '%d minutes', $minutes, 'event-genius' ), $minutes );
				}
				
				$countdown = implode( ', ', $countdown_parts );
				
				// Replace placeholders
				$message = str_replace( '{open-date}', $formatted_date, $message );
				$message = str_replace( '{open-countdown}', $countdown, $message );
			}
			?>
			<p class="evge-no-attendees-description">
				<?php echo esc_html( $message ); ?>
			</p>
		<?php endif; ?>
	</div>
</div> 