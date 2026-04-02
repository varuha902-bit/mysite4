<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
settings_errors();
?>
<div class="evge-settings-page">
	<form method="post" action="options.php">
        <input type="hidden" name="tab" value="text" />
		<?php settings_fields( 'evge_settings' ); ?>
        <div class="evge-settings-section-wrap">
			<?php do_settings_sections( 'evge_registration_text' ); ?>
        </div>

		<input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes', 'event-genius' ); ?>" />
	</form>
</div>

