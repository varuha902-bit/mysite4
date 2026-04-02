<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use WPEventGenius\Common\Utils\Icon;
?>


<div class="evge-dashboard-wrap">
    <?php do_action( 'evge_dashboard_top' ); ?>
    <div class="evge-dashboard-row evge-1-column">
            <?php include_once 'partials/dashboard/quick-actions.php'; ?>
    </div>

    <div class="evge-2-column-wrapper">
        <div class="evge-column-1">
            <div class="evge-dashboard-item">
                <div class="evge-dashboard-item-header">
                    <h3><?php 
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo Icon::get( 'two-people-circle' ); ?><?php esc_html_e( 'Latest Registrations', 'event-genius' ); ?></h3>
                </div>
                <div class="evge-dashboard-item-content">
			        <?php include_once 'partials/dashboard/latest.php'; ?>
                </div>
                <div class="evge-dashboard-item-footer">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=evge-registrations' ) ); ?>" class="evge-icon-link"><span><?php esc_html_e( 'Manage All Registrations', 'event-genius' ); ?></span>
                    <?php 
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo Icon::get_svg( 'right-chevron' ); ?></a>
                </div>
            </div>
        </div>
        <div class="evge-column-2">
            <div class="evge-dashboard-item">
                <div class="evge-dashboard-item-header">
                    <h3>
                        <?php 
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo Icon::get( 'events-circle' ); ?><?php esc_html_e( 'Upcoming Events', 'event-genius' ); ?></h3>
                </div>
                <div class="evge-dashboard-item-content">
			        <?php include_once 'partials/dashboard/upcoming.php'; ?>
                </div>
                <div class="evge-dashboard-item-footer">
                    <div class="evge-flex-center evge-bottom-action-bar">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=evge-all-events' ) ); ?>" class="evge-icon-link"><span><?php esc_html_e( 'Manage All Events', 'event-genius' ); ?></span>
                        <?php
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo Icon::get_svg( 'right-chevron' ); ?></a>
                        <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . EVGE_EVENT_POST_TYPE ) ); ?>" class="evge-icon-link"><span><?php esc_html_e( 'Add New Event', 'event-genius' ); ?></span>
                        <?php 
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo Icon::get_svg( 'right-chevron' ); ?></a>
                        <a href="<?php echo esc_url( get_post_type_archive_link( EVGE_EVENT_POST_TYPE ) ); ?>" class="evge-icon-link"><span><?php esc_html_e( 'View Event Archive', 'event-genius' ); ?></span>
                        <?php 
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo Icon::get_svg( 'right-chevron' ); ?></a>
                    </div>
                </div>
            </div>
            <?php if ( ! evge_is_free_version() ) : ?>
            <div class="evge-dashboard-item evge-dashboard-item-no-footer">
                <div class="evge-dashboard-item-content">
	                <?php
	                if ( function_exists( 'evge_is_pro_tier' ) && evge_is_pro_tier() ) {
		                include_once EVGE_ADMIN_TEMPLATE_PATH . 'pro/partials/dashboard/analytics-pro.php';
	                } else {
		                include_once 'partials/dashboard/analytics.php';
	                }
	                ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>
