<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$type = 'submissions';
if ( $registration_or_payment_record instanceof \WPEventGenius\Admin\Actions\RegistrationRecord ) {
    $registration_record = $registration_or_payment_record;
    $payment_record = null;
	$count = 1;
	$ids = array( $registration_record->get_registration_data()['id'] );
	/* translators: %d: number of registration records being deleted */
    $text = sprintf( __( 'Number of records to delete: %d', 'event-genius' ), $count );

} elseif ( $registration_or_payment_record instanceof \WPEventGenius\Admin\Actions\PaymentRecord ) {
	$type = 'payments';
	$payment_record = $registration_or_payment_record;
    $registration_record = null;
	$count = 1;
	$ids = array( $payment_record->get_payment_data()['transaction_id'] );
	/* translators: %d: number of payment records being deleted */
	$text = sprintf( __( 'Number of payment records to delete: %d', 'event-genius' ), $count );

} elseif ( is_array( $registration_or_payment_record ) ) {
	$count = count( $registration_or_payment_record );
	$ids = $registration_or_payment_record;
	/* translators: %d: number of registration records being deleted */
    $text = sprintf( __( 'Number of records to delete: %d', 'event-genius' ), $count );
} elseif ( is_string( $registration_or_payment_record ) ) {
	$ids = array( $registration_or_payment_record );
}

$modal_settings = array( 
    'width' => 'narrow',
    'noHeader' => true 
);
$modal_settings_json = wp_json_encode( $modal_settings );
$icon_html = '<div class="evge-alert-icon evge-unknown evge-shadow"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M64 64c0-17.7-14.3-32-32-32S0 46.3 0 64V320c0 17.7 14.3 32 32 32s32-14.3 32-32V64zM32 480a40 40 0 1 0 0-80 40 40 0 1 0 0 80z"/></svg></div>';
?>

<div class="evge-dynamic evge-modal-settings" data-evge-modal-settings="<?php echo esc_attr( $modal_settings_json ); ?>">
    <div class="evge-narrow-modal-content">
        <div class="evge-narrow-modal-inner">
            <div class="evge-modal-section">
                <div class="evge-status-message">
	                <?php if ( ! empty( $icon_html )) : ?>
                        <div class="evge-cancel-request-icon">
			                <?php 
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo $icon_html; ?>
                        </div>
	                <?php endif; ?>
                    <div class="evge-delete-confirm evge-standard-dialog">
                        <strong><?php echo esc_html( $text ) ;?></strong>
                        <p>
			                <?php esc_html_e( 'This cannot be undone. Are you sure you want to delete these records?', 'event-genius' ); ?>

                        </p>

                    </div>
                </div>

                <div class="evge-delete-confirm-buttons evge-standard-dialog-buttons">
                    <form action="" method="post">
                        <input type="hidden" name="ids" value="<?php echo esc_attr( implode( ',' , $ids ) ); ?>">
                        <input type="hidden" name="type" value="<?php echo esc_attr( $type ); ?>">
                        <?php wp_nonce_field( 'evge-delete-registration-records', 'evge-delete-registration-records-nonce' ); ?>
                        <button type="submit" name="evge_action" value="delete_confirm" class="evge-dialog-primary evge-destructive"><?php esc_html_e( 'Confirm', 'event-genius' ); ?></button>
                        <button type="submit" name="evge_action" value="cancel" class="evge-dialog-secondary"><?php esc_html_e( 'Cancel', 'event-genius' ); ?></button>
                    </form>
                </div>
            </div>

        </div>
    </div>

</div>