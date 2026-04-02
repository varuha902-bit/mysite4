<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>

<div class="evge-modal-event-details">
    <div class="evge-modal-title">
        <strong><?php 
        $event_title = apply_filters( 'evge_payment_summary_event_title', $event_post->get_the_title(), $event_post );
        echo esc_html( $event_title ); 
        ?></strong>
    </div>
    <div class="evge-modal-date-venue">
        <?php
        // Check if we should use series meta instead of event meta
        $meta_html = '';
        $meta_html = apply_filters( 'evge_payment_summary_event_meta', $meta_html, $event_post );
        
        if ( ! empty( $meta_html ) ) {
            echo $meta_html;
        } else {
            // Fall back to regular event meta
            EVGE()->template_manager()->get_template( 'events/common/event-meta.php', [
                'event_post' => $event_post,
                'show_map'   => false,
                'venue_unlink'  => true
            ]);
        }
        ?>
    </div>
</div>
<h3><?php esc_html_e( 'Summary', 'event-genius' ); ?></h3>

<?php
$payment_json = $event_post->get_payment_json();
$cost_amount = $event_post->get_the_cost_amount();

$additional_line_items = apply_filters( 'evge_additional_line_items', array(), $event_post );
?>
<?php if ( ! empty( $cost_amount ) ) : ?>
    <div class="evge-modal-summary evge-modal-cost-details"
        data-payment-json="<?php echo esc_attr( $payment_json ); ?>"
        role="region"
        aria-label="<?php esc_attr_e( 'Order Summary', 'event-genius' ); ?>">
        
        <?php 
        // Use the new payment table template for consistency
        EVGE()->template_manager()->get_template( 'common/payment-table.php', [
            'event_post' => $event_post
        ]);
        ?>
        
    </div>
<?php else : ?>
    <div class="evge-modal-summary evge-modal-quantity-summary"
        data-payment-json="<?php echo esc_attr( $payment_json ); ?>"
        role="region"
        aria-label="<?php esc_attr_e( 'Order Summary', 'event-genius' ); ?>">
        <div class="evge-modal-order-detail">
            <div class="evge-modal-line-item evge-cost-item" id="evge-line-item-event">
                <div class="evge-line-item-quantity">1</div>x<div class="evge-line-item-name"><?php echo esc_html( $event_post->get_the_title() ); ?></div>
            </div>
        </div>
    </div>
<?php endif; ?>