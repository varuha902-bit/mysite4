<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
settings_errors();
?>
<div class="evge-settings-page evge-bump-down">
    <form method="post" action="options.php">
        <input type="hidden" name="tab" value="events" />
		<?php settings_fields( 'evge_settings' ); ?>

        <div class="evge-settings-section-wrap">
		    <?php
		    // Show block theme notice for Event Display section
		    if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
			    $event_settings = new \WPEventGenius\Admin\Settings\EventSettings();
			    $event_settings->block_theme_notice();
		    }
		    ?>
		    <?php do_settings_sections( 'evge_event_settings_display' ); ?>
        </div>
        <div class="evge-settings-section-wrap">
		    <?php do_settings_sections( 'evge_event_settings_cost' ); ?>
        </div>
        <div class="evge-settings-section-wrap">
		    <?php do_settings_sections( 'evge_event_settings_dynamic_content' ); ?>
        </div>
        <div class="evge-settings-section-wrap">
		    <?php do_settings_sections( 'evge_event_settings_blog_loop' ); ?>
        </div>
        <input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes', 'event-genius' ); ?>" />

    </form>
</div>

