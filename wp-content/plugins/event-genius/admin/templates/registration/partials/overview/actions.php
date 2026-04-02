<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\Common\Utils\Icon;

$is_recurrence = isset($event->event_meta['evge_is_recurrence']) && $event->event_meta['evge_is_recurrence'] ? true : false;
$button_text = $is_recurrence ? esc_html__( 'Edit Recurrences', 'event-genius' ) : esc_html__( 'Edit', 'event-genius' );
?>

<div class="evge-event-details-actions">
	<div class="evge-tooltip-wrap evge-button-tooltip">
		<a class="evge-event-details-actions-button evge-admin-secondary-button button action evge-flex-center" href="<?php echo esc_url( $event->details['edit_link'] ); ?>">
			<span class="evge-icon-text">
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo Icon::get( 'edit' ); ?>
			</span>
		</a>
		<div class="evge-tooltip">
			<p><?php echo wp_kses_post( $button_text ); ?></p>
		</div>
	</div>
	<div class="evge-tooltip-wrap evge-button-tooltip">
		<a class="evge-event-details-actions-button evge-admin-secondary-button button action evge-flex-center" href="<?php echo esc_url( get_the_permalink( $event->ID ) ); ?>">
			<span class="evge-icon-text">
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo Icon::get( 'eye' ); ?>
			</span>
		</a>
		<div class="evge-tooltip">
			<p><?php esc_html_e( 'View', 'event-genius' ); ?></p>
		</div>
	</div>
</div>
