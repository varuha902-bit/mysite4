<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
settings_errors();
?>
<form method="post" action="options.php">
	<?php settings_fields( 'evge_registration_settings' ); ?>
	<?php do_settings_sections( 'evge_registration_settings' ); ?>

	<?php do_settings_sections( 'evge_registration_settings_section' ); ?>
	<input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes', 'event-genius' ); ?>" />
	<hr />
</form>
