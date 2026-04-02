<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use WPEventGenius\Common\Utils\Icon;
?>

<div class="evge-dashboard-empty-state">
    <div class="evge-empty-state-container">
        <div class="evge-empty-state-column evge-empty-state-image">
            <div class="evge-empty-state-image-placeholder">
                <img src="<?php echo esc_url( EVGE_PLUGIN_URL . 'assets/images/admin/example-event-listing.png' ); ?>" alt="Event Genius Example Event Listing">
            </div>
        </div>
        
        <div class="evge-empty-state-column evge-empty-state-content">
            <h1 class="evge-empty-state-title"><?php esc_html_e( 'Welcome to Event Genius', 'event-genius' ); ?></h1>
            
            <div class="evge-empty-state-description">
                <p>
                    <?php esc_html_e( 'Event Genius is a powerful event management plugin for WordPress.', 'event-genius' ); ?>
                </p>
                <p>
                    <?php esc_html_e( 'To get started, use the button below to create your first event.', 'event-genius' ); ?>
                </p>
            </div>
            
            <div class="evge-empty-state-actions">
                <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . EVGE_EVENT_POST_TYPE ) ); ?>" class="button button-primary button-hero evge-create-first-event-btn">
                    <?php esc_html_e( 'Create My First Event', 'event-genius' ); ?>
                </a>
            </div>
            
            <div class="evge-empty-state-features">
                <h3><?php esc_html_e( 'You can also:', 'event-genius' ); ?></h3>
                <ul class="evge-feature-list">
                    <li class="evge-feature-item">
                        <span class="evge-feature-checkbox">✓</span>
                        <span class="evge-feature-text"><?php esc_html_e( 'Configure your Registration Form', 'event-genius' ); ?></span>
                    </li>
                    <li class="evge-feature-item">
                        <span class="evge-feature-checkbox">✓</span>
                        <span class="evge-feature-text"><?php esc_html_e( 'Customize and Embed Event Calendars', 'event-genius' ); ?></span>
                    </li>
                    <li class="evge-feature-item">
                        <span class="evge-feature-checkbox">✓</span>
                        <span class="evge-feature-text"><?php esc_html_e( 'Configure Emails and Settings', 'event-genius' ); ?></span>
                    </li>
                    <li class="evge-feature-item">
                        <span class="evge-feature-text"><?php esc_html_e( '...and much more!', 'event-genius' ); ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div> 