<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
settings_errors();
?>
<div class="evge-settings-page">
    <?php $this->notices(); ?>
    <form method="post" action="options.php">
        <input type="hidden" name="tab" value="registration" />
	    <?php settings_fields( 'evge_settings' ); ?>
        <div class="evge-settings-section-wrap evge-bump-down">
		    <?php do_settings_sections( 'evge_registration_settings_general' ); ?>
        </div>

        <div class="evge-settings-section-wrap">
	        <?php do_settings_sections( 'evge_registration_restrictions' ); ?>
        </div>

        <div class="evge-settings-section-wrap">
		    <?php do_settings_sections( 'evge_registration_attendee_list' ); ?>
        </div>

        <div class="evge-settings-section-wrap">
		    <?php do_settings_sections( 'evge_registration_editing' ); ?>
        </div>

        <?php do_action( 'evge_registration_settings_sections_after', $this ); ?>

        <input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes', 'event-genius' ); ?>" />
    </form>
</div>

