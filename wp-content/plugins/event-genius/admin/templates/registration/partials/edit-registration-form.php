<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\Common\Event\EventPost;

// Initialize registration_data with default values
$registration_data = !empty($registration_data) ? $registration_data : array(
    'id' => 0,
    'event_id' => 0,
    'quantity' => 1,
    'status' => 'confirmed'
);

if ( empty( $event_id ) ) {
	$event_id = $registration_data['event_id'];
}

$event_post = new EventPost( $event_id );

if ( ! empty( $registration_data ) ) {
	$registration_id = $registration_data['id'];
} else {
	$registration_id = 0;
}
$quantity = ! empty( $registration_data['quantity'] ) ? $registration_data['quantity'] : 1;

$status = ! empty( $registration_data['status'] ) ? $registration_data['status'] : 'confirmed';

// Check user capabilities
$can_manage_registrations = current_user_can( 'manage_evge_registrations' );
$can_view_registrations = current_user_can( 'view_evge_registrations' );

// If user can't view registrations, don't show anything
if ( ! $can_view_registrations ) {
    return;
}

// Check if this is the "Add New" modal (no existing registration ID)
$is_add_new_modal = empty( $registration_id );
?>

<?php if ( $is_add_new_modal ) : ?>
<div class="evge-dynamic evge-modal-settings" data-evge-modal-settings="<?php echo esc_attr( wp_json_encode( array( 'width' => 'medium' ) ) ); ?>">
    <div class="evge-modal-heading">
        <h2><?php esc_html_e( 'Add New Registration', 'event-genius' ); ?></h2>
    </div>
    <div class="evge-registration-form-wrap evge-modal-pad evge-modal-body">
        <div class="evge-registration-form-inner">
<?php else : ?>
<div class="evge-registration-form-inner">
<?php endif; ?>

    <?php if ( $can_manage_registrations ) : ?>
        <form id="evge-registration-form" method="post" action="" enctype="multipart/form-data" class="evge-registration-form">
    <?php else : ?>
        <div class="evge-registration-form evge-registration-form-readonly">
    <?php endif; ?>
    
        <input type="hidden" name="evge_registration_id" value="<?php echo esc_attr( $registration_id ); ?>">
        <input type="hidden" name="evge_event_id" value="<?php echo esc_attr( $event_id ); ?>">
        <?php if ( $can_manage_registrations ) : ?>
            <?php wp_nonce_field( 'evge-edit-registration', 'evge-edit-registration-nonce' ); ?>
        <?php endif; ?>
        
        <div class="evge-field-wrapper">
            <div class="evge-field-inner">
                <div class="evge-label-wrapper">
                    <label for="evge_first"><?php esc_html_e( 'Status', 'event-genius' ); ?></label>
                </div>
                <div class="evge-input-wrapper evge-select-input">
                    <?php if ( $can_manage_registrations ) : ?>
                        <select id="evge-status" type="select" name="evge_status">
                            <option value="confirmed" <?php selected( $status, 'confirmed' ); ?>><?php esc_html_e( 'Confirmed', 'event-genius' ); ?></option>
                            <option value="pending" <?php selected( $status, 'pending' ); ?>><?php esc_html_e( 'Pending', 'event-genius' ); ?></option>
                            <option value="canceled" <?php selected( $status, 'canceled' ); ?>><?php esc_html_e( 'Canceled', 'event-genius' ); ?></option>
                        </select>
                    <?php else : ?>
                        <div class="evge-readonly-value">
                            <?php 
                            $status_labels = array(
                                'confirmed' => __( 'Confirmed', 'event-genius' ),
                                'pending' => __( 'Pending', 'event-genius' ),
                                'canceled' => __( 'Canceled', 'event-genius' )
                            );
                            echo esc_html( $status_labels[$status] ?? $status );
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <input type="hidden" name="evge_quantity" value="<?php echo absint( $quantity ); ?>">

        <div class="evge-registration-form-fields">
            <?php $event_post->display_form_fields( $registration_data, array( 'is_admin' => true ) ); ?>
        </div>

        <?php if ( $can_manage_registrations ) : ?>
            <?php if ( ! empty( $registration_data['id'] ) ) : ?>
            <div class="evge-send-confirmation-feedback evge-email-results" aria-live="polite"></div>
            <?php endif; ?>
            <div class="evge-form-button-wrapper">
                <?php if ( empty( $registration_data['id'] ) ) : ?>
                    <button id="evge-registration-save" name="evge_action" value="submit_create" class="evge-admin-button evge-blue-action-button evge-admin-button-large" type="submit"><span><?php esc_html_e( 'Create', 'event-genius' ); ?></span></button>
                <?php else :
                    $base_json_array = array(
                        'registration_id' => $registration_data['id'],
                        'transaction_id' => ! empty( $item['transaction_id'] ) ? $item['transaction_id'] : 0,
                        'action' => 'evge_identifier_tools_modal_content',
                    );
                    $delete_json_array = array_merge( $base_json_array, array( 'selected' => 'delete' ) );
                    ?>
                    <button id="evge-registration-save" name="evge_action" value="submit_edit" class="evge-admin-button evge-blue-action-button evge-admin-button-large" type="submit"><span><?php esc_html_e( 'Save Changes', 'event-genius' ); ?></span></button>
                    <button type="button" class="evge-send-confirmation-email-btn evge-admin-button evge-admin-button-large" data-registration-id="<?php echo esc_attr( (string) $registration_data['id'] ); ?>" data-event-id="<?php echo esc_attr( (string) $event_id ); ?>"><span class="evge-icon-text">
                        <?php
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo \WPEventGenius\Common\Utils\Icon::get( 'email' ) . esc_html( __( 'Send Confirmation Email', 'event-genius' ) ); ?></span></button>
                    <a href="#" data-evge-modal-content="ajax" data-evge-ajax="<?php echo esc_attr( wp_json_encode( $delete_json_array ) ); ?>"  data-evge-modal-settings="<?php echo esc_attr( wp_json_encode( array( 'width' => 'narrow' ) ) ); ?>" class="evge-modal-trigger evge-in-modal-action evge-admin-button evge-danger-button evge-admin-button-large"><span class="evge-icon-text">
                        <?php
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo \WPEventGenius\Common\Utils\Icon::get( 'trash' ) . esc_html( __( 'Delete Registration', 'event-genius' ) ); ?></span></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    <?php if ( $can_manage_registrations ) : ?>
        </form>
    <?php else : ?>
        </div>
    <?php endif; ?>

<?php if ( $is_add_new_modal ) : ?>
        </div>

    </div>
</div>
<?php else : ?>
</div>
<?php endif; ?>
