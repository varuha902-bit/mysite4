<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\Common\Event\EventPost;
use WPEventGenius\Common\Utils\Icon;
$event_post = new EventPost( $event->ID );

?>

<div class="evge-event-details">
	<div class="evge-event-details-top evge-detail-row">
		<?php $post_status = get_post_status( $event->ID ) !== 'publish' ? '(' . get_post_status( $event->ID ) . ')' : '' ?>
		<?php if ( $page->identity() !== 'single' ): ?>
            <div class="evge-event-details-title"><a href="<?php echo esc_url( $event->details['edit_link'] ); ?>"> <?php 
            // Use filter to get the appropriate title
            echo esc_html( $event->post_title );
            ?> <span><?php echo esc_html( $post_status ); ?></span></a></div>
		<?php else: ?>
            <div class="evge-event-details-title"><?php 
            echo esc_html( $event->post_title );
            ?> <span><?php echo esc_html( $post_status ); ?></span></div>
		<?php endif; ?>
		<?php $page->template_part( 'actions', $event ); ?>

    </div>
	<div class="evge-detail-row">
        <div class="evge-event-details-date-summary evge-event-detail">
        <?php 
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $event_post->recurrence_display() . esc_html( $event->details['full_date'] ); ?>
        </div>
		<?php
		$details_array = array();
		if ( $event->details['allow_registration'] ) {
			$details_array[] = Icon::get( 'register-clipboard' ) . esc_html( $event->details['registration_quantity'] ) . ' / ' . esc_html( $event->details['capacity_display'] );
		}
		if ( ! empty( $event->details['venue_id'] ) ) {
			$details_array[] = Icon::get( 'location' ) . get_the_title( $event->details['venue_id'] );
		}
		if ( ! empty( $event->details['cost_amount'] ) ) {
			$details_array[] = Icon::get( 'ticket' ) . $event->details['cost_display'];
		}
		if ( ! empty( $event->details['organizer_id'] ) ) {
			$details_array[] = Icon::get( 'user' ) . get_the_title( $event->details['organizer_id'] );
		}
		
		// Allow other parts of the system to add additional details (like series information)
		$details_array = apply_filters( 'evge_event_details_misc_items', $details_array, $event->ID, $event_post );
		?>
        <div class="evge-event-details-misc evge-event-detail">
            <div class="evge-event-detail-item">
                <span class="evge-icon-text evge-small-gap-icon-text">
                    <?php 
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo implode( '</span></div><div class="evge-event-detail-item"><span class="evge-icon-text evge-small-gap-icon-text">', $details_array ); ?>
                </span>
            </div>

        </div>
		<?php if ( $page->identity() === 'single' ): ?>
		<?php
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_subtab = isset( $_GET['subsubtab'] ) ? sanitize_key( wp_unslash( $_GET['subsubtab'] ) ) : 'submissions';
		$is_attendance_tab = $current_subtab === 'attendance';
		?>
        <div class="evge-event-details-misc evge-event-detail evge-reg-status-bar">
            <div class="evge-event-detail-item">
                <?php
                // Show attendance status filters only on attendance tab (Standard tier only)
                if ( $is_attendance_tab && function_exists( 'evge_is_standard_tier' ) && evge_is_standard_tier() ) {
                    // Allow filtering of the status bar content (for attendance status filters in Standard tier)
                    $status_bar_content = apply_filters( 'evge_registration_status_bar_content', '', $event, $page );
                    
                    if ( ! empty( $status_bar_content ) ) {
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo $status_bar_content;
                    } else {
                        // Fallback to default registration status filters if filter doesn't provide content
                        $all_count = (int)$event->registration_status_counts['confirmed'] + (int)$event->registration_status_counts['pending'] + (int)$event->registration_status_counts['canceled'];
                        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                        $current = isset( $_GET['registration_status'] ) ? sanitize_key( $_GET['registration_status'] ) : 'all';
                        ?>
                        <ul class="subsubsub">
                            <li class="all"><a href="<?php echo esc_url( $page->nav_link( 'evge-all-events', array( 'id' => $event->ID, 'tab' => 'registrations', 'subtab' => 'single', 'subsubtab' => 'attendance', 'registration_status' => 'all' ) ) ); ?>" <?php if ( $current === 'all' ) { echo 'class="current"'; } ?>><?php esc_html_e( 'All', 'event-genius'); ?> <span class="count">(<?php echo esc_html( $all_count ); ?>)</span></a></li>
                            <?php if ( ! empty( $event->registration_status_counts['confirmed'] ) ) : ?>
                                <li class="confirmed"><a href="<?php echo esc_url( $page->nav_link( 'evge-all-events', array( 'id' => $event->ID, 'tab' => 'registrations', 'subtab' => 'single', 'subsubtab' => 'attendance', 'registration_status' => 'confirmed' ) ) ); ?>" <?php if ( $current === 'confirmed' ) { echo 'class="current"'; } ?>><?php esc_html_e( 'Confirmed', 'event-genius'); ?> <span class="count">(<?php echo esc_html( $event->registration_status_counts['confirmed'] ); ?>)</span></a></li>
                            <?php endif; ?>
                            <?php if ( ! empty( $event->registration_status_counts['pending'] ) ) : ?>
                                <li class="pending"><a href="<?php echo esc_url( $page->nav_link( 'evge-all-events', array( 'id' => $event->ID, 'tab' => 'registrations', 'subtab' => 'single', 'subsubtab' => 'attendance', 'registration_status' => 'pending' ) ) ); ?>" <?php if ( $current === 'pending' ) { echo 'class="current"'; } ?>><?php esc_html_e( 'Pending', 'event-genius'); ?> <span class="count">(<?php echo esc_html( $event->registration_status_counts['pending'] ); ?>)</span></a></li>
                            <?php endif; ?>
                            <?php if ( ! empty( $event->registration_status_counts['canceled'] ) ) : ?>
                                <li class="canceled"><a href="<?php echo esc_url( $page->nav_link( 'evge-all-events', array( 'id' => $event->ID, 'tab' => 'registrations', 'subtab' => 'single', 'subsubtab' => 'attendance', 'registration_status' => 'canceled' ) ) ); ?>" <?php if ( $current === 'canceled' ) { echo 'class="current"'; } ?>><?php esc_html_e( 'Canceled', 'event-genius'); ?> <span class="count">(<?php echo esc_html( $event->registration_status_counts['canceled'] ); ?>)</span></a></li>
                            <?php endif; ?>
                        </ul>
                        <?php
                    }
                } else {
                    // Default registration status filters (for non-attendance tabs)
                    $all_count = (int)$event->registration_status_counts['confirmed'] + (int)$event->registration_status_counts['pending'] + (int)$event->registration_status_counts['canceled'];
                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    $current = isset( $_GET['registration_status'] ) ? sanitize_key( $_GET['registration_status'] ) : 'all';
                    ?>
                    <ul class="subsubsub">
                        <li class="all"><a href="<?php echo esc_url( $page->nav_link( 'evge-all-events', array( 'id' => $event->ID, 'tab' => 'registrations', 'subtab' => 'single', 'registration_status' => 'all' ) ) ); ?>" <?php if ( $current === 'all' ) { echo 'class="current"'; } ?>><?php esc_html_e( 'All', 'event-genius'); ?> <span class="count">(<?php echo esc_html( $all_count ); ?>)</span></a></li>
                        <?php if ( ! empty( $event->registration_status_counts['confirmed'] ) ) : ?>
                            <li class="confirmed"><a href="<?php echo esc_url( $page->nav_link( 'evge-all-events', array( 'id' => $event->ID, 'tab' => 'registrations', 'subtab' => 'single', 'registration_status' => 'confirmed' ) ) ); ?>" <?php if ( $current === 'confirmed' ) { echo 'class="current"'; } ?>><?php esc_html_e( 'Confirmed', 'event-genius'); ?> <span class="count">(<?php echo esc_html( $event->registration_status_counts['confirmed'] ); ?>)</span></a></li>
                        <?php endif; ?>
                        <?php if ( ! empty( $event->registration_status_counts['pending'] ) ) : ?>
                            <li class="pending"><a href="<?php echo esc_url( $page->nav_link( 'evge-all-events', array( 'id' => $event->ID, 'tab' => 'registrations', 'subtab' => 'single', 'registration_status' => 'pending' ) ) ); ?>" <?php if ( $current === 'pending' ) { echo 'class="current"'; } ?>><?php esc_html_e( 'Pending', 'event-genius'); ?> <span class="count">(<?php echo esc_html( $event->registration_status_counts['pending'] ); ?>)</span></a></li>
                        <?php endif; ?>
                        <?php if ( ! empty( $event->registration_status_counts['canceled'] ) ) : ?>
                            <li class="canceled"><a href="<?php echo esc_url( $page->nav_link( 'evge-all-events', array( 'id' => $event->ID, 'tab' => 'registrations', 'subtab' => 'single', 'registration_status' => 'canceled' ) ) ); ?>" <?php if ( $current === 'canceled' ) { echo 'class="current"'; } ?>><?php esc_html_e( 'Canceled', 'event-genius'); ?> <span class="count">(<?php echo esc_html( $event->registration_status_counts['canceled'] ); ?>)</span></a></li>
                        <?php endif; ?>
                    </ul>
                    <?php
                }
                ?>
            </div>

        </div>

        <?php endif; ?>
    </div>



</div>
