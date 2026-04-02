<?php
namespace WPEventGenius\Common\Registration\Payment\Gateways;

use WPEventGenius\Common\Utils\Settings;
use WPEventGenius\Pro\Utils\ProSettings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class OfflineService extends GatewayService {

	public function listeners() {}

	public function handle_create_request( $registration_group, $data ) {
		$gateway = new Offline();
		$payment_record = $gateway->create_payment( $registration_group );
        $status = $payment_record ? 'success' : 'failed';
		return $gateway->request_response( $status, $registration_group );
	}

	public function allowed_data() {
		return array();
	}

	public function identity_key() {
		return 'offline';
	}

	public function name() {
		return __( 'Offline', 'event-genius' );
	}

	public function checkout_label() {
		return ProSettings::get( 'offline_payment_option_label', __( 'Offline', 'event-genius' ) );
	}
	public function description() {
        return ProSettings::get( 'offline_payment_option_description', __( 'Instructions via email', 'event-genius' ) );
	}

	public function form_inputs() {
		$additional_instructions = ProSettings::get( 'offline_payment_additional_instructions', '' );
		$button_text = ProSettings::get( 'offline_payment_button_text', __( 'Submit', 'event-genius' ) );
		?>
		<div class="evge-payment-additional-instructions">
			<?php echo wp_kses_post( $additional_instructions ); ?>
		</div>
		<button class="evge-ajax-payment evge-button evge-green-button evge-payment-button" data-submit-type="ajax" type="submit" value="offline"><?php echo esc_html( $button_text ); ?></button>
		<?php
	}
}