<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$modal_settings = array('width' => 'max');
$modal_settings_json = wp_json_encode( $modal_settings );
?>

<div class="evge-dynamic evge-modal-settings" data-evge-modal-settings="<?php echo esc_attr( $modal_settings_json ); ?>">

	<div class="evge-registration-form-wrap evge-modal-pad">
		<?php include EVGE_ADMIN_TEMPLATE_PATH . 'registration/partials/edit-registration-form.php'; ?>
	</div>



</div>