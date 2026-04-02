<?php
/**
 * Payment Table Template
 * 
 * This template renders just the payment breakdown table without event meta information.
 * It can be reused in different contexts to avoid duplication.
 * 
 * @package WPEventGenius
 * @since 1.0.0
 * 
 * @var \WPEventGenius\Event\Post $event_post The event post object
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="evge-modal-order-detail">
    <div class="evge-modal-line-item evge-cost-item" id="evge-line-item-event">
        <div class="evge-line-item-left">
            <div class="evge-line-item-quantity">1</div>x<div class="evge-line-item-name"><?php echo esc_html( $event_post->get_the_title() ); ?></div>
        </div>
        <div class="evge-line-item-right">
            <div class="evge-line-item-total"><?php echo esc_html( $event_post->currency_symbol_before() ); ?><span><?php echo esc_html( number_format( $event_post->get_the_cost_amount(), 2 ) ); ?></span><?php echo esc_html( $event_post->currency_symbol_after() ); ?></div>
        </div>
    </div>

    <?php if ( ! empty( $additional_line_items ) ) : ?>
        <hr class="evge-line-item-break" id="evge-subtotal-line-break">
        <div class="evge-modal-line-item" id="evge-line-item-subtotal">
            <div class="evge-line-item-left">
                <?php esc_html_e( 'Subtotal', 'event-genius' ); ?>
            </div>
            <div class="evge-line-item-right">
                <div class="evge-line-item-total"><?php echo esc_html( $event_post->currency_symbol_before() ); ?><span><?php echo esc_html( $event_post->get_the_cost_amount() ); ?></span><?php echo esc_html( $event_post->currency_symbol_after() ); ?></div>
            </div>
        </div>
        <div class="evge-modal-line-item" id="evge-line-item-fees">
            <div class="evge-line-item-left">
                <?php esc_html_e( 'Fees', 'event-genius' ); ?>
            </div>
            <div class="evge-line-item-right">
                <div class="evge-line-item-total"><?php echo esc_html( $event_post->currency_symbol_before() ); ?><span></span><?php echo esc_html( $event_post->currency_symbol_after() ); ?></div>
            </div>
        </div>

        <hr class="evge-line-item-break" id="evge-subtotal-line-total">
        <div class="evge-modal-line-item evge-modal-line-large" id="evge-line-item-total">
            <div class="evge-line-item-left">
                <?php esc_html_e( 'Total', 'event-genius' ); ?>
            </div>
            <div class="evge-line-item-right">
                <div class="evge-line-item-total"><?php echo esc_html( $event_post->currency_symbol_before() ); ?><span><?php echo esc_html( $event_post->get_the_cost_amount() ); ?></span><?php echo esc_html( $event_post->currency_symbol_after() ); ?></div>
            </div>
        </div>
    <?php endif; ?>
</div>
