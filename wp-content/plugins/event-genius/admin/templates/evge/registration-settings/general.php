<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
settings_errors();
?>
<form method="post" action="options.php">
	<?php settings_fields( 'evge_registration_settings' ); ?>

	<?php do_settings_sections( 'evge_registration_general' ); ?>

    <hr />

	<?php do_settings_sections( 'evge_registration_restrictions' ); ?>
    <input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes', 'event-genius' ); ?>" />
    <hr />

	<?php do_settings_sections( 'evge_registration_attendee_list' ); ?>
    <input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes', 'event-genius' ); ?>" />
    <hr />

	<?php do_settings_sections( 'evge_registration_editing' ); ?>
    <input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes', 'event-genius' ); ?>" />
</form>
