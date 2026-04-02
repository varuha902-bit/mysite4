<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
settings_errors();
?>
<form method="post" action="options.php">
	<?php settings_fields( 'evge_registration_settings' ); ?>
	<?php do_settings_sections( 'evge_payment_methods' ); ?>

    <input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes', 'event-genius' ); ?>" />
    <hr />
	<?php do_settings_sections( 'evge_payment_settings_general' ); ?>

    <input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes', 'event-genius' ); ?>" />
    <hr />

</form>
