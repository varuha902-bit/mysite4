<?php
/**
 * Modal component for displaying and copying shortcodes
 * @param int $calendar_id The ID of the calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="evge-modal evge-shortcode-modal" id="shortcode-modal-<?php echo esc_attr($calendar_id); ?>" aria-hidden="true">
    <div class="evge-modal-overlay" tabindex="-1" data-close-modal>
        <div class="evge-modal-container" role="dialog" aria-modal="true">
            <header class="evge-modal-header">
                <h2><?php esc_html_e('Embed Calendar', 'event-genius'); ?></h2>
                <button class="evge-modal-close" aria-label="<?php esc_attr_e('Close modal', 'event-genius'); ?>" data-close-modal>×</button>
            </header>
            
            <div class="evge-modal-content">
                <p><?php esc_html_e('Copy this shortcode and paste it into any post or page:', 'event-genius'); ?></p>
                <div class="evge-shortcode-container">
                    <code class="evge-shortcode">[event_genius_calendar id="<?php echo esc_attr($calendar_id); ?>"]</code>
                    <button type="button" class="button evge-copy-shortcode" data-shortcode='[event_genius_calendar id="<?php echo esc_attr($calendar_id); ?>"]'>
                        <?php esc_html_e('Copy', 'event-genius'); ?>
                    </button>
                </div>
                <div class="evge-copy-success" style="display: none;">
                    <?php esc_html_e('Shortcode copied!', 'event-genius'); ?>
                </div>
            </div>
        </div>
    </div>
</div>