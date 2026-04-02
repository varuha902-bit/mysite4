<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use WPEventGenius\Common\Utils\Icon;
?>
<div class="evge-dashboard-quick-actions evge-dashboard-item">
    <div class="evge-dashboard-quick-actions-inner evge-flex-center">
        <svg width="11" height="20" viewBox="0 0 11 20" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M10.1752 8.25813H5.22677V0.318115L0.27832 11.9236H5.22677V19.8636L10.1752 8.25813Z" fill="#444444"/>
        </svg>

        <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . EVGE_EVENT_POST_TYPE ) ); ?>" class="evge-icon-link"><span><?php esc_html_e( 'Add an Event', 'event-genius' ); ?></span>
        <?php 
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo Icon::get_svg( 'right-chevron' ); ?></a>

        <a href="<?php echo esc_url( admin_url( 'admin.php?page=evge-settings' ) ); ?>" class="evge-icon-link"><span><?php esc_html_e( 'Edit Settings', 'event-genius' ); ?></span>
        <?php 
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo Icon::get_svg( 'right-chevron' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=evge-registrations&tab=forms' ) ); ?>" class="evge-icon-link"><span><?php esc_html_e( 'Edit Registration Forms', 'event-genius' ); ?></span>
        <?php 
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo Icon::get_svg( 'right-chevron' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?tab=calendars&page=evge-all-events' ) ); ?>" class="evge-icon-link"><span><?php esc_html_e( 'Create a Calendar', 'event-genius' ); ?></span>
        <?php 
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo Icon::get_svg( 'right-chevron' ); ?></a>
    </div>

</div>
