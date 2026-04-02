<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current form name
$current_form_name = __( 'Default', 'event-genius' );
if ( ! empty( $form_id ) && class_exists( '\WPEventGenius\Standard\Database\StandardDatabase' ) ) {
	$database = new \WPEventGenius\Standard\Database\StandardDatabase();
	$form = $database->get_form_by_id( $form_id );
	if ( $form && ! empty( $form['name'] ) ) {
		$current_form_name = $form['name'];
	}
}
?>
<div class="evge-settings-page evge-bump-down">
    <form id="evge-fb-settings" method="post" action="options.php">
        <?php settings_fields( 'evge_registration_settings' ); ?>
        
        <?php if ( function_exists( 'evge_is_standard_tier' ) && evge_is_standard_tier() ) : ?>
        <div class="evge-settings-section-wrap">
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="evge-form-name"><?php esc_html_e( 'Form Name', 'event-genius' ); ?></label>
                        </th>
                        <td>
                            <input type="text"
                                   id="evge-form-name"
                                   name="form_name"
                                   value="<?php echo esc_attr( $current_form_name ); ?>"
                                   class="regular-text"
                                   placeholder="<?php esc_attr_e( 'Enter form name', 'event-genius' ); ?>" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="evge-settings-section-wrap">
		    <?php do_settings_sections( 'evge_registration_confirmation_flow' ); ?>
        </div>

        <div class="evge-settings-section-wrap">
		    <?php do_settings_sections( 'evge_registration_messages' ); ?>
        </div>
        <div class="evge-settings-section-wrap">
            <?php do_settings_sections( 'evge_registration_buttons' ); ?>
        </div>

        <?php do_action( 'evge_form_builder_settings_sections', $this ); ?>
        

    </form>
</div>