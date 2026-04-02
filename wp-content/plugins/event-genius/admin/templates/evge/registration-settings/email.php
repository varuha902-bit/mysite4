<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
settings_errors();
?>
<div class="evge-settings-page evge-bump-down">
<form method="post" action="options.php">
	<?php settings_fields( 'evge_registration_settings' ); ?>

    <div class="evge-settings-section-wrap">
        <?php do_settings_sections( 'evge_confirmation_email' ); ?>
    </div>

    <div class="evge-settings-section-wrap">
        <input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes', 'event-genius' ); ?>" />
    </div>

    <div class="evge-settings-section-wrap">
        <?php do_settings_sections( 'evge_notification_email' ); ?>
    </div>
    <input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes', 'event-genius' ); ?>" />

    <div class="evge-settings-section-wrap evge-bump-down">
        <?php do_settings_sections( 'evge_registration_email_cancel_request' ); ?>
    </div>

    <div class="evge-settings-section-wrap">
        <?php do_settings_sections( 'evge_registration_email_cancel_notification' ); ?>
    </div>

    <div class="evge-settings-section-wrap">
        <?php do_settings_sections( 'evge_registration_email_cancel_confirmation' ); ?>
    </div>
    <input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes', 'event-genius' ); ?>" />
</form>
</div>
