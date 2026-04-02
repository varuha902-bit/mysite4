<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
settings_errors();
?>
<div class="evge-settings-page">
    <form method="post" action="options.php">
        <input type="hidden" name="tab" value="general" />
	    <?php settings_fields( 'evge_settings' ); ?>
        <?php
		/**
		 * Hook to add license key section
		 * Free version will show upsell, Pro version will show license management
		 */
		do_action( 'evge_general_settings_license_section' );
		?>

        <div class="evge-settings-section-wrap">
	        <?php do_settings_sections( 'evge_event_settings_date' ); ?>
        </div>

        <div class="evge-settings-section-wrap">
            <?php do_settings_sections( 'evge_event_settings_permalinks' ); ?>
        </div>

        <div class="evge-settings-section-wrap">
            <?php do_settings_sections( 'evge_event_settings_email' ); ?>
        </div>

        <div class="evge-settings-section-wrap">
            <?php do_settings_sections( 'evge_event_settings_uninstall' ); ?>
        </div>
        <input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes', 'event-genius' ); ?>" />
        <hr />
    </form>
</div>

