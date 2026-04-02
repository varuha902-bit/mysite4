<?php
/**
 * Cancel Request Modal Content Template
 * 
 * This template displays a modal dialog that shows the status of a registration
 * cancellation request. It includes an icon, status message, and optional action button.
 * 
 * The template handles:
 * - Modal dialog structure
 * - Status message display
 * - Icon display
 * - Action button display
 * 
 * @package WPEventGenius
 * @since 1.0.0
 * 
 * @var string $classes Additional CSS classes for the modal
 * @var string $modal_settings_json JSON string containing modal settings
 * @var string $icon_html HTML markup for the status icon
 * @var string $html HTML markup for the status message
 * @var string $button_html HTML markup for the action button
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="evge-dynamic evge-autotrigger-modal<?php echo esc_attr( $classes ); ?>" 
	data-evge-modal-settings="<?php echo esc_attr( $modal_settings_json ); ?>" 
	<?php 
	// Add refresh-on-close attribute for successful cancellations
	if ( isset( $action_status ) && $action_status === 'success' ) {
		echo ' data-evge-refresh-on-close';
	}
	?>
	role="dialog" 
	aria-modal="true" 
	aria-labelledby="evge-status-message">
	
	<div class="evge-narrow-modal-content">
		<div class="evge-narrow-modal-inner">
			<div class="evge-modal-section" 
				role="region" 
				aria-label="<?php echo esc_attr__( 'Cancellation Status', 'event-genius' ); ?>">
				
				<div class="evge-status-message" 
					id="evge-status-message" 
					role="status" 
					aria-live="polite">
					
					<?php if ( ! empty( $icon_html ) ) : ?>
						<div class="evge-cancel-request-icon" 
							aria-hidden="true">
							<?php echo $icon_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
					<?php endif; ?>
					
					<div class="evge-cancel-request-message">
						<?php echo wp_kses_post( $html ); ?>
					</div>
				</div>
				
				<?php if ( ! empty( $button_html ) ) : ?>
					<div class="evge-cancel-request-button">
						<?php echo $button_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
