<?php

use WPEventGenius\Common\Utils\Icon;
use WPEventGenius\Common\Utils\Utils;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $registration_record->get_registration_data() ) ) : ?>
    <p class="evge-no-user">
		<?php esc_html_e('No record found.', 'event-genius'); ?>
    </p>
<?php return;
endif;
$registration_data = $registration_record->get_registration_data();
$payment_data = $registration_record->get_payment_data();
$status            = \WPEventGenius\Common\Utils\Formatter::filter_raw_status( array(
	'value' => $registration_data['status'],
	'before'   => '',
	'after'   => '',
) );
$event_post        = new \WPEventGenius\Common\Event\EventPost( $registration_data['event_id'] );
$data_to_format = Utils::maybe_add_group_data( $registration_data, $event_post );
$related_registrations = $registration_record->get_related_registrations();
$additional_guest_registrations = $registration_record->get_additional_guest_registrations();
$has_additional_guest_registrations = !empty($additional_guest_registrations) && count($additional_guest_registrations) > 1;

if ( empty( $page ) ) {
    $page = new \WPEventGenius\Admin\Page\BasePage();
}

$user = false;
if ( ! empty( $registration_data['user_id'] ) ) {
    $user = get_userdata( $registration_data['user_id'] );
}

?>
<div class="evge-single-registration-manager">
    <div class="evge-single-header evge-modal-heading">
        <h2><?php echo esc_html( \WPEventGenius\Common\Utils\Formatter::identity( $registration_data ) ); ?></h3>
        <?php echo wp_kses_post( $status['before'] . $status['value']. $status['after'] ); ?>
        <div class="evge-quantity-cost"><span class="evge-icon-text"><?php 
        $formatted_quantity_cost = \WPEventGenius\Common\Utils\Formatter::get_quantity_cost_display( $data_to_format );
        if ( $formatted_quantity_cost !== '-' ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo Icon::get( 'ticket' ) . esc_html( $formatted_quantity_cost );
        }
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        // Add payment status icon if available and event requires payments
        if ( ! empty( $payment_data['payment_status'] ) && $event_post && $event_post->get_accept_payments() ) {
            echo \WPEventGenius\Common\Utils\Formatter::get_payment_status_display( $payment_data['payment_status'], '', false );
        }
        ?></span></div>
    </div>
    <div class="evge-single-event-meta">
        <?php $post_status = get_post_status( $event_post->get_the_id() ) !== 'publish' ? '(' . get_post_status( $event_post->get_the_id() ) . ')' : '' ?>

        <div class="evge-single-event-meta-title"><span class="evge-icon-text"> <?php 
        // Use filter to get the appropriate title
        $title = apply_filters( 'evge_registration_display_title', get_the_title( $event_post->get_the_id() ), $event_post->get_the_id(), false );
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo Icon::get('list') . esc_html( $title );
        ?></span><span><?php echo esc_html( $post_status ); ?></span></div>
        <div class="evge-single-event-meta-date-summary">
            <span class="evge-icon-text"> <?php 
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo Icon::get('duration') . $event_post->recurrence_display() . esc_html( $event_post->get_the_date_summary() ); ?></span>
        </div>
        <?php
        $details_array = array();
        if ( ! empty( $event_post->get_the_venue_title() ) ) {
            $details_array[] = Icon::get( 'location' ) . $event_post->get_the_venue_title();
        }
        if ( ! empty( $event_post->get_the_cost_amount() ) ) {
            $details_array[] = Icon::get( 'ticket' ) . $event_post->get_the_cost_display();
        }
        if ( ! empty( $event_post->get_the_organizer_id() ) ) {
            $details_array[] = Icon::get( 'user' ) . get_the_title( $event_post->get_the_organizer_id() );
        }
        ?>
        <div class="evge-single-event-meta-misc">
            <div class="evge-event-detail-item">
                <span class="evge-icon-text evge-small-gap-icon-text">
                    <?php 
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo implode( '</span></div><div class="evge-event-detail-item"><span class="evge-icon-text evge-small-gap-icon-text">', $details_array ); ?>
                </span>
            </div>

        </div>
    </div>
    <div class="evge-modal-body">
    <?php do_action('evge_single_registration_content_before', $registration_record); ?>
    <div class="evge-single-registration-content evge-single-registration-submission evge-single-registration-tab" data-evge-tab="submissions">
        <div class="evge-2-column-wrapper">
            <div class="evge-column-1">
                <div class="evge-dashboard-item">
                    <div class="evge-dashboard-item-header">
                        <h3><?php esc_html_e( 'Registration Details', 'event-genius' ); ?></h3>
                    </div>
                    <div class="evge-dashboard-item-content">
                        <div>
                            <?php include EVGE_ADMIN_TEMPLATE_PATH . 'registration/partials/edit-registration-form.php'; ?>
                        </div>
                    </div>
                </div>
                <?php do_action( 'evge_single_registration_submissions_tab_content', $registration_record ); ?>
            </div>
            <div class="evge-column-2">
                <div class="evge-dashboard-item">
                    <div class="evge-dashboard-item-header">
                        <h3><?php esc_html_e('User Information', 'event-genius'); ?></h3>
                    </div>
                    <div class="evge-dashboard-item-content evge-user-info">
                        <?php if ($user) : ?>
                            <div class="evge-user-header">
                                <div class="evge-user-avatar">
                                    <?php echo get_avatar($user->ID, 96); ?>
                                </div>
                                <div class="evge-user-meta">
                                    <h4><?php echo esc_html($user->display_name); ?></h4>
                                    <?php if ($user->user_login !== $user->display_name) : ?>
                                        <p class="evge-username">@<?php echo esc_html($user->user_login); ?></p>
                                    <?php endif; ?>
                                    <p class="evge-user-email">
                                        <span class="evge-icon-text">
                                            <?php 
                                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                            echo Icon::get('mail') . esc_html($user->user_email); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <div class="evge-user-details">
                                <p>
                                    <span class="evge-icon-text">
                                        <?php 
                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                        /* translators: %s: formatted date when user registered */ echo Icon::get('list') . sprintf(
                                            esc_html__('Member since %s', 'event-genius'),
                                            esc_html(date_i18n(get_option('date_format'), strtotime($user->user_registered)))
                                        ); 
                                        ?>
                                    </span>
                                </p>
                                <?php if (!empty($user->user_url)) : ?>
                                    <p>
                                        <span class="evge-icon-text">
                                            <?php 
                                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                            echo Icon::get('link') . '<a href="' . esc_url($user->user_url) . '" target="_blank">' . esc_html($user->user_url) . '</a>'; ?>
                                        </span>
                                    </p>
                                <?php endif; ?>
                                <p>
                                    <span class="evge-icon-text">
                                        <?php 
                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                        /* translators: %d: total number of registrations */ echo Icon::get('list') . sprintf(
                                            esc_html__('Total Registrations: %d', 'event-genius'),
                                            count($related_registrations) + 1
                                        ); 
                                        ?>
                                    </span>
                                </p>
                                <p class="evge-user-actions">
                                    <?php if (current_user_can('edit_users')) : ?>
                                        <a href="<?php echo esc_url(get_edit_user_link($user->ID)); ?>" class="button">
                                            <?php esc_html_e('Edit User Profile', 'event-genius'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php do_action('evge_user_information_actions', $user); ?>
                                </p>
                            </div>
                        <?php else : ?>
                            <p class="evge-no-user">
                                <?php 
                                if (!empty($registration_data['email'])) {
                                    /* translators: %s: registration email address */ echo sprintf(
                                        esc_html__('No WordPress user account found. Registration email: %s', 'event-genius'),
                                        '<strong>' . esc_html($registration_data['email']) . '</strong>'
                                    );
                                } else {
                                    esc_html_e('No WordPress user account or email associated with this registration.', 'event-genius');
                                }
                                ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ( !empty($additional_guest_registrations) && $has_additional_guest_registrations ) : ?>
                <div class="evge-dashboard-item">
                    <div class="evge-dashboard-item-header">
                        <h3><?php esc_html_e('Registration Group', 'event-genius'); ?></h3>
                    </div>
                    <div class="evge-dashboard-item-content">
                        <table class="wp-list-table widefat striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('ID', 'event-genius'); ?></th>
                                    <th><?php esc_html_e('Name', 'event-genius'); ?></th>
                                    <th><?php esc_html_e('Email', 'event-genius'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($additional_guest_registrations as $guest_reg) :
                                    $guest_link = $page->nav_link('evge-registrations', array(
                                        'tab' => 'registrations',
                                        'back_page' => 'evge-all-events',
                                        'registration_id' => $guest_reg['id']
                                    ));
                                    // Use the proper Formatter::identity method for consistent display
                                    $guest_name = \WPEventGenius\Common\Utils\Formatter::identity($guest_reg);
                                    // Add (main) indicator for the main registration
                                    if (!empty($guest_reg['is_main'])) {
                                        $guest_name .= ' ' . esc_html__('(main)', 'event-genius');
                                    }
                                    $guest_email = !empty($guest_reg['email']) ? esc_html($guest_reg['email']) : esc_html__('No email', 'event-genius');
                                ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo esc_url($guest_link); ?>" target="_blank">
                                                <?php echo esc_html($guest_reg['id']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo esc_html($guest_name); ?></td>
                                        <td><?php echo $guest_email; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                <div class="evge-dashboard-item">
                    <div class="evge-dashboard-item-header">
                        <h3><?php esc_html_e('Related Registrations', 'event-genius'); ?></h3>
                    </div>
                    <div class="evge-dashboard-item-content">
                        <?php if (empty($related_registrations)) : ?>
                            <p><?php esc_html_e('No related registrations found.', 'event-genius'); ?></p>
                        <?php else : ?>
                            <table class="wp-list-table widefat striped">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('ID', 'event-genius'); ?></th>
                                        <th><?php esc_html_e('Event', 'event-genius'); ?></th>
                                        <th><?php esc_html_e('Date', 'event-genius'); ?></th>
                                        <th><?php esc_html_e('Quantity & Cost', 'event-genius'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach ($related_registrations as $reg) :
                                        $reg_link = $page->nav_link('evge-registrations', array(
                                            'tab' => 'registrations',
                                            'back_page' => 'evge-all-events',
                                            'registration_id' => $reg['id']
                                        ));
                                        $event_title = get_the_title($reg['event_id']);
                                        $reg_date = \WPEventGenius\Common\Utils\DateFormatter::date_string('registration_admin', $reg['registration_date'], '', get_option('timezone_string'));
                                        $quantity_cost = \WPEventGenius\Common\Utils\Formatter::get_quantity_cost_display($reg);
                                        // Get event post for payment status check
                                        $reg_event_post = new \WPEventGenius\Common\Event\EventPost($reg['event_id']);
                                    ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo esc_url($reg_link); ?>" target="_blank">
                                                    <?php echo esc_html($reg['id']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo esc_html($event_title); ?></td>
                                            <td><?php echo esc_html($reg_date); ?></td>
                                            <td>
                                                <span class="evge-icon-text">
                                                    <?php 
                                                    if ( $quantity_cost === '-' ) {
                                                        echo \WPEventGenius\Common\Utils\Formatter::tooltip( __( 'This guest is part of a registration group with a single payment.', 'event-genius' ) );
                                                    } else {
                                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        echo Icon::get('ticket') . esc_html($quantity_cost);
                                                    }
                                                    // Add payment status icon if available and event requires payments
                                                    if ( ! empty( $reg['payment_status'] ) && $reg_event_post && $reg_event_post->get_accept_payments() ) {
                                                        echo \WPEventGenius\Common\Utils\Formatter::get_payment_status_display( $reg['payment_status'] );
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>

    <?php do_action('evge_single_registration_content_after', $registration_record); ?>
</div>
