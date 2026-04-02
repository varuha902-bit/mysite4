<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$misc = $field->get_misc();
?>
<div class="evge-field-edit-wrap evge-field-edit-wrap-submit-button" data-id="<?php use WPEventGenius\Common\Utils\Icon;echo esc_attr( $field->get_id() ); ?>">
    <div class="evge-save-needed-alert evge-icon-circle">
        <?php 
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo \WPEventGenius\Common\Utils\Icon::get( 'exclamation'); ?>
    </div>
	<div class="evge-field-edit-group">
		<button class="evge-field-edit-summary">
			<div class="evge-field-label">
                <div class="evge-icon-text"><span class="evge-icon-buffer">
                    <?php 
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo Icon::get( 'submitfield' ); ?>
                </span><?php echo esc_html( wp_unslash( $field->get_label() ) ) ?></div>
            </div>
			<div class="evge-edit-wrap"><div class="evge-toggle-edit-icon"><span class="screen-reader-text"><?php esc_html_e( 'Edit', 'event-genius' ); ?></span><span class="evge-edit-icon-wrap"><?php 
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo Icon::get( 'edit' ); ?></span></div></div>
        </button>
		<div class="evge-field-edit-settings evge-hidden-initially">
			<input type="hidden" class="evge-field-id" name="id" value="<?php echo esc_attr( $field->get_id() ); ?>">

            <h2 class="evge-field-edit-settings-tabs nav-tab-wrapper" style="display: none">
                <a class="evge-field-edit-settings-tab nav-tab nav-tab-active" data-tab="basic"><?php esc_html_e('Default', 'event-genius'); ?></a>
                <a class="evge-field-edit-settings-tab nav-tab" data-tab="advanced"><?php esc_html_e('Hover', 'event-genius'); ?></a>
            </h2>
            <div class="evge-field-edit-settings-subsection" data-tab="basic">
                <div class="evge-settings-section evge-color-edit">
                    <div class="evge-button-preview-settings" data-evge-preview-trigger="submitButton">
                        <div class="evge-button-preview-single">
                            <label for="evge_form_submit_button_text" class="evge-button-preview-label">Text</label>
                            <input id="evge_form_submit_button_text" class="evge-button-preview-text" type="text" name="form_submit_button_text" value="<?php echo esc_attr( $misc['form_submit_button_text'] ); ?>">
                        </div>
                        <div class="evge-button-preview-single">
                            <label for="evge_form_submit_button_background" class="evge-button-preview-label">Background</label>
                            <input id="evge_submit_button_background" class="evge-colorpicker evge-button-preview-bg" type="text" name="submit_button_background_color" value="<?php echo esc_attr( $misc['submit_button_background_color'] ); ?>">
                        </div>
                        <div class="evge-button-preview-single">
                            <label for="evge_form_submit_button_text_color" class="evge-button-preview-label">Text Color</label>
                            <input id="evge_submit_button_text_color" class="evge-colorpicker evge-button-preview-tc" type="text" name="submit_button_text_color" value="<?php echo esc_attr( $misc['submit_button_text_color'] ); ?>">
                        </div>
                        <div class="evge-button-preview-single">
                            <label for="evge_form_submit_button_border_color" class="evge-button-preview-label">Border Color</label>
                            <input id="evge_submit_button_border_color" class="evge-colorpicker evge-button-preview-bordercolor" type="text" name="submit_button_border_color" value="<?php echo esc_attr( $misc['submit_button_border_color'] ); ?>">
                        </div>

                    </div>
                </div>
            </div>

            <div class="evge-field-edit-settings-subsection" data-tab="advanced" style="display: none">
                <div class="evge-settings-section evge-color-edit">

                </div>
            </div>

            <div class="evge-field-save-button-wrap">
                <button type="button" class="button evge-field-save-button"><?php esc_html_e('Save', 'event-genius' ); ?></button>
                <a class="evge-no-action evge-icon-text" href="" title="<?php esc_html_e( 'Cannot delete this field as it is used to submit the registration form.', 'event-genius' ); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M367.2 412.5L99.5 144.8C77.1 176.1 64 214.5 64 256c0 106 86 192 192 192c41.5 0 79.9-13.1 111.2-35.5zm45.3-45.3C434.9 335.9 448 297.5 448 256c0-106-86-192-192-192c-41.5 0-79.9 13.1-111.2 35.5L412.5 367.2zM0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256z"/></svg>
                </a>
            </div>
		</div>
	</div>
    <div class="evge-add-section"><div class="evge-form-field-add evge-disabled evge-disabled-always"><button type="button" class="evge-in-form-field-add-button"><span class="evge-add-chevron"><?php 
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo Icon::get( 'right-chevron' ); ?></span></button></div></div>


</div>
