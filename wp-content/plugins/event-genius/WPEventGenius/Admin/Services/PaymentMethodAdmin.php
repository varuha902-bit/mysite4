<?php

namespace WPEventGenius\Admin\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PaymentMethodAdmin {
	public function __construct() {

	}

	public function init_hooks() {
        add_action( 'wp_ajax_evge_toggle_gateway_enabled', array( $this, 'toggle_gateway_enabled' ) );
	}


	public static function payment_method_setting_callback( $args ) {
		?>
		<table class="evge_gateways_table widefat evge-collapse-label-column">
			<thead>
			<tr>
				<th class="sort"></th>
				<th class="name"><?php esc_html_e( 'Method', 'event-genius' ); ?></th>
				<th class="status"><?php esc_html_e( 'Enabled', 'event-genius' ); ?></th>
				<th class="method-description"><?php esc_html_e( 'Description', 'event-genius' ); ?></th>
				<th class="action"></th>
			</tr>
			</thead>
			<tbody class="ui-sortable">
			<?php foreach ( EVGE()->admin_gateways() as $gateway ) :
				$details = $gateway->details();
				?>
				<tr data-gateway-id="<?php echo esc_attr( $details['identity_key'] ); ?>">
					<td class="sort ui-sortable-handle" width="1%">
						<div class="evge-item-reorder-nav">
							<button type="button" class="evge-move-up evge-move-disabled" tabindex="-1" aria-hidden="true" aria-label="
							<?php 
							// translators: %s is the name of the payment method
							echo esc_html( sprintf (__( 'Move the %s payment method up', 'event-genius' ), $details['name'] ) ); ?>">
							<?php esc_html_e( 'Move up', 'event-genius' ); ?></button>
							<button type="button" class="evge-move-down" tabindex="0" aria-hidden="false" aria-label="
							<?php 
							// translators: %s is the name of the payment method
							echo esc_html( sprintf (__( 'Move the %s payment method down', 'event-genius' ), $details['name'] ) ); ?>">
							<?php esc_html_e( 'Move down', 'event-genius' ); ?></button>
							<input type="hidden" name="evge_registration_settings[gateway_order][]" value="<?php echo esc_attr( $details['identity_key'] ); ?>">
						</div>
					</td>
					<td class="name">
						<div class="evge-payment-gateway-method__name">
							<?php echo esc_html( $details['name'] ); ?>
						</div>
					</td>
					<?php
					$configure_url = ! empty( $details['configure_url'] ) ? $details['configure_url'] : add_query_arg( array( 'page' => 'evge-settings', 'tab' => 'payments', 'subtab' => $details['identity_key'] ), admin_url( 'admin.php' ) );
					?>
					<td class="status">
                        <a class="evge-payment-gateway-method-toggle-enabled" href="<?php echo esc_url( $configure_url ); ?>">
                            <?php if ( $details['enabled'] && $details['is_configured'] ) : ?>
                            <span class="evge-input-toggle evge-input-toggle--enabled" aria-label="
							<?php 
							/* translators: %s: name of the payment method */
							echo esc_html( sprintf (__( 'The %s payment method is currently enabled', 'event-genius' ), $details['name'] ) ); ?>"><?php esc_html_e( 'Yes', 'event-genius' ); ?></span>
                            <?php else : ?>
                            <span class="evge-input-toggle evge-input-toggle--disabled" aria-label="
							<?php 
							/* translators: %s: name of the payment method */
							echo esc_html( sprintf (__( 'The %s payment method is currently disabled', 'event-genius' ), $details['name'] ) ); ?>"><?php esc_html_e( 'No', 'event-genius' ); ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="evge-redirect-needed-alert" style="display: none;">
                            <div class="evge-spinner-container"><div class="evge-spinner-circle"></div></div><span><?php esc_html_e( 'Configuration needed. Redirecting.', 'event-genius' ); ?></span>
                        </div>
                    </td>
					<td class="method-description"><span><?php echo wp_kses_post( $details['description'] ); ?></span></td>
					<td class="action">

                        <a href="<?php echo esc_url( $configure_url ); ?>" class="button alignright is-secondary"><?php esc_html_e( 'Configure', 'event-genius' ); ?></a>
					</td>
				</tr>
		<?php endforeach; ?>
			</tbody>
		</table>

	<?php
	}

    public function toggle_gateway_enabled() {
        check_ajax_referer( 'evge_toggle_gateway_enabled', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $options = (array)get_option( 'evge_gateways', array() );
	    $gateway_id = isset($_POST['gateway_id']) ? sanitize_key( wp_unslash( $_POST['gateway_id'] ) ) : '';

        if ( empty( $options[ $gateway_id ] ) ) {
	        $options[ $gateway_id ] = array(
                'enabled' => false,
            );
        }

	    $options[ $gateway_id ]['enabled'] = ! $options[ $gateway_id ]['enabled'];

        update_option( 'evge_gateways', $options );

        $configured = false;
        $admin_gateway = EVGE()->get_admin_gateway( $gateway_id );

        if ( $admin_gateway ) {
            $details = $admin_gateway->details();
            $configured = $details['is_configured'];

            if ( ! $configured ) {
	            wp_send_json_success( 'needs_setup' );
            }
        }
        wp_send_json_success( $options[ $gateway_id ]['enabled'] );
    }
}