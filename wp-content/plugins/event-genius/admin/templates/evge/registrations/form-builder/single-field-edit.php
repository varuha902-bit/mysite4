<?php
use WPEventGenius\Common\Utils\Icon;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="evge-field-edit-wrap" data-id="<?php echo esc_attr( $field->get_id() ); ?>">
    <div class="evge-save-needed-alert evge-icon-circle"><?php 
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo \WPEventGenius\Common\Utils\Icon::get( 'exclamation'); ?></div>
	<div class="evge-field-edit-group">
		<button class="evge-field-edit-summary">
			<div class="evge-field-label">
                <div class="evge-icon-text"><span class="evge-icon-buffer"><?php 
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo Icon::get( $field->get_type() . 'field' ); ?></span><?php echo esc_html( wp_unslash( $field->get_label() ) ) ?></div>
            </div>
			<div class="evge-edit-wrap"><div class="evge-toggle-edit-icon"><span class="screen-reader-text"><?php esc_html_e( 'Edit', 'event-genius' ); ?></span><span class="evge-edit-icon-wrap"><?php 
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo Icon::get( 'edit' ); ?></span></div></div>
        </button>
		<div class="evge-field-edit-settings evge-hidden-initially">
			<input type="hidden" class="evge-field-id" name="id" value="<?php echo esc_attr( $field->get_id() ); ?>">

            <h2 class="evge-field-edit-settings-tabs nav-tab-wrapper">
                <a class="evge-field-edit-settings-tab nav-tab nav-tab-active" data-tab="basic"><?php esc_html_e('Basic', 'event-genius'); ?></a>
                <a class="evge-field-edit-settings-tab nav-tab" data-tab="advanced"><?php esc_html_e('Advanced', 'event-genius'); ?></a>
            </h2>
            <div class="evge-field-edit-settings-subsection" data-tab="basic">
                <div class="evge-settings-section evge-type-edit">
                    <label for="field-label-<?php echo esc_attr( $field->get_id() ); ?>"><?php esc_html_e('Type', 'event-genius'); ?></label>
                    <div class="evge-type-select-wrapper">
	                <?php if ( ! in_array( $field->get_slug(), array( 'first', 'email', 'last' ) ) ) : ?>
                        <select id="field-type-<?php echo esc_attr( $field->get_id() ); ?>" name="type">
			                <?php foreach ( $field_handler->available_types() as $key => $value) { ?>
                                <option value="<?php echo esc_attr( $key ); ?>" <?php echo $field->get_type() === $key ? 'selected' : ''; ?>><?php echo esc_html( $value ); ?></option>
			                <?php } ?>
                        </select>
	                <?php else: ?>
                        <select id="evge-disabled-field" name="type" disabled>
			                <option value="<?php echo esc_html( $field->get_type() ); ?>" selected><?php echo esc_html( ucfirst( $field->get_type() ) ); ?></option>
                        </select>
                    <?php endif; ?>
                    <?php
                    /**
                     * Action hook to add content next to the Type select field (e.g., upsell links)
                     * 
                     * @param object $field The field object
                     */
                    do_action( 'evge_form_field_type_select_after', $field );
                    ?>
                    </div>
                </div>
                <div class="evge-settings-section evge-label-edit">
                    <label for="field-label-<?php echo esc_attr( $field->get_id() ); ?>"><?php esc_html_e('Label', 'event-genius'); ?></label>
                    <input type="text" id="field-label-<?php echo esc_attr( $field->get_id() ); ?>" name="label" value="<?php echo esc_attr( wp_unslash( $field->get_label() ) ) ?>">
                </div>
                <div class="evge-settings-section evge-options-edit" id="field-options-1" data-for="select,checkbox,radio">
                    <label for="field-options-list-<?php echo esc_attr( $field->get_id() ); ?>"><?php esc_html_e('Options (one per line)', 'event-genius' ); ?></label>

                    <textarea id="field-options-list-<?php echo esc_attr( $field->get_id() ); ?>" name="options" rows="4"><?php echo esc_textarea( wp_unslash( $field_handler->options_textarea( $field->get_options() ) ) ); ?></textarea>
                </div>
                <div class="evge-settings-section evge-link-edit" id="field-options-1" data-for="single-checkbox">
                    <label for="field-link-<?php echo esc_attr( $field->get_id() ); ?>"><?php esc_html_e('Link (terms, privacy, etc.)', 'event-genius' ); ?></label>

                    <input type="text" id="field-link-<?php echo esc_attr( $field->get_id() ); ?>" name="link" placeholder="https://example.com" value="<?php echo esc_attr( wp_unslash( $field->get_link() ) ) ?>">
                </div>
                <?php do_action( 'evge_pro_field_edit_settings_basic', $field ); ?>
            </div>

            <div class="evge-field-edit-settings-subsection" data-tab="advanced" style="display: none">
                <div class="evge-settings-section evge-placeholder-edit">
                    <label for="field-placeholder-<?php echo esc_attr( $field->get_id() ); ?>"><?php esc_html_e( 'Placeholder', 'event-genius' ); ?></label>
                    <input type="text" id="field-placeholder-<?php echo esc_attr( $field->get_id() ); ?>" name="placeholder" value="<?php echo esc_attr( wp_unslash( $field->get_placeholder() ) ) ?>">
                </div>
                <div class="evge-settings-section evge-error-message-edit">
                    <label for="field-error-message-<?php echo esc_attr( $field->get_id() ); ?>"><?php esc_html_e( 'Error Message', 'event-genius' ); ?></label>
                    <input type="text" id="field-error-message-<?php echo esc_attr( $field->get_id() ); ?>" name="error_message" value="<?php echo esc_attr( wp_unslash( $field->get_error_message() ) ) ?>">
                </div>
                <div class="evge-settings-section evge-default-edit">
                    <label for="field-default-<?php echo esc_attr( $field->get_id() ); ?>"><?php esc_html_e( 'Default Value', 'event-genius' ); ?></label>
                    <input type="text" id="field-default-<?php echo esc_attr( $field->get_id() ); ?>" name="default" value="<?php echo esc_attr( wp_unslash( $field->get_default() ) ) ?>">
                </div>
                <div class="evge-settings-section evge-scrollable-text-edit" id="field-scrollable-text-<?php echo esc_attr( $field->get_id() ); ?>" data-for="single-checkbox">
                    <label for="field-scrollable-text-<?php echo esc_attr( $field->get_id() ); ?>"><?php esc_html_e('Scrollable Text', 'event-genius' ); ?></label>
                    <?php
                    $misc = $field->get_misc();
                    $scrollable_text = isset( $misc['scrollable_text'] ) ? $misc['scrollable_text'] : '';
                    ?>
                    <textarea id="field-scrollable-text-<?php echo esc_attr( $field->get_id() ); ?>" name="misc[scrollable_text]" rows="6" placeholder="<?php esc_attr_e( 'Enter text, such as terms and conditions, here...', 'event-genius' ); ?>"><?php echo esc_textarea( wp_unslash( $scrollable_text ) ); ?></textarea>
                </div>
                <?php do_action( 'evge_pro_field_edit_settings_advanced', $field ); ?>
            </div>

            <div class="evge-field-save-button-wrap">
                <button type="button" class="button evge-field-save-button"><?php esc_html_e('Save', 'event-genius' ); ?></button>
                <?php if ( ! in_array( $field->get_slug(), array( 'first', 'email', 'last' ) ) ) : ?>
                <a class="evge-field-delete-button evge-icon-text" href="">
                    <?php 
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo Icon::get( 'trash' ); ?>
                </a>
                <?php else: ?>
                <a class="evge-no-action evge-icon-text" href="" title="<?php esc_html_e( 'Cannot delete these fields because they are used for confirmation emails and registrant identity.', 'event-genius' ); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M367.2 412.5L99.5 144.8C77.1 176.1 64 214.5 64 256c0 106 86 192 192 192c41.5 0 79.9-13.1 111.2-35.5zm45.3-45.3C434.9 335.9 448 297.5 448 256c0-106-86-192-192-192c-41.5 0-79.9 13.1-111.2 35.5L412.5 367.2zM0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256z"/></svg>
                </a>
                <?php endif; ?>
            </div>
		</div>
	</div>
    <div class="evge-add-section"><div class="evge-form-field-add"><button type="button" class="evge-in-form-field-add-button"><span><?php 
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo Icon::get( 'right-chevron' ); ?></span></button></div></div>


</div>
