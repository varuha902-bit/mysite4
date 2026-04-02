<?php

namespace WPEventGenius\Admin;

use WPEventGenius\Common\Database;
use WPEventGenius\Common\Event\Event;
use WPEventGenius\Common\Registration\Registration\BaseRegistration;
use WPEventGenius\Common\Utils\Defaults;
use WPEventGenius\Common\Utils\Icon;

use WPEventGenius\Common\Utils\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BaseAdminPage {
	protected $text_area_fields = array();

	protected $rich_editor_fields = array();

	protected $checkbox_fields = array();

	protected $array_fields = array();

	protected $tab = '';

	public function __construct() {
		$this->text_area_fields = self::text_area_fields();
		$this->rich_editor_fields = self::rich_editor_fields();
		$this->checkbox_fields = self::checkbox_fields();
		$this->array_fields = self::array_fields();
	}

	public static function text_area_fields() {
		return array();
	}

	public static function rich_editor_fields() {
		return array(
			'cancel_request_instructions',
			'cancel_request_email_content',
            'cancel_success_message',
            'cancel_request_success_message',
			'cancel_notification_email_content',
			'confirmed_modal_message',
			'pending_approval_modal_message',
			'confirmation_email_content',
			'notification_email_content',
			'cancel_request_email_content',
			'cancel_success_message',
			'cancel_request_success_message',
			'cancel_notification_email_content',
			'confirmed_modal_message',
			'additional_guest_summary_template',
			'edit_success_message',
			'bulk_registration_notification_content',
			'bulk_registration_confirmation_content',
			'bulk_order_receipt_content',
			'bulk_order_payment_notification_content',
		);
	}

	public static function checkbox_fields() {
		return array();
	}

	public static function array_fields() {
		return array();
	}

	public static function date_format_fields() {
		return array(
			'time_format_custom',
			'full_date_format_custom',
			'date_summary_format_custom',
			'registration_timeline_format_custom',
		);
	}

	public function get_tab() {
		return $this->tab;
	}
	public function register_setting() {
		// phpcs:ignore PluginCheck.CodeAnalysis.SettingSanitization.register_settingDynamic	
		register_setting( 'evge_settings', 'evge_settings', array( $this, 'sanitize_settings' ) );
	}

	public function settings() {
		$this->register_setting();
	}

	public function add_setting_field( $args ) {
		$tab = ! empty( $_REQUEST['tab'] ) ? sanitize_key( wp_unslash( $_REQUEST['tab'] ) ) : 'general';
		$args['option'] = self::get_option_slug_for_tab( $tab );

		if ( $args['callback'] === 'textarea') {
			$this->text_area_fields[] = $args['id'];
		}
		if ( ! empty( $args['textarea'] ) ) {
			$this->text_area_fields = array_merge( $this->text_area_fields, $args['textarea'] );
		}

		if ( $args['callback'] === 'rich_editor') {
			$this->rich_editor_fields[] = $args['id'];
		}
		if ( ! empty( $args['rich_editor'] ) ) {
			$this->rich_editor_fields = array_merge( $this->rich_editor_fields, $args['rich_editor'] );
		}

		$overwrite = isset( $args['overwrite'] ) ? $args['overwrite'] : '';

		$after = '';
		if ( $overwrite ) {
			$after = $this->asterisk( __( 'This is a default setting. It will only apply to new events.', 'event-genius' ) );
		}

		$label_content = $args['label'] . $after;
		if ( ! empty( $args['tooltip'] ) && ! empty( $args['tooltip_next_to_label'] ) ) {
			$label_content .= ' ';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$label_content .= $this->tooltip( $args['tooltip'] );
			unset( $args['tooltip'] );
		}

		$callback = array( $this, $args['callback'] );
		if ( is_array( $args['callback'] ) ) {
			$callback = $args['callback'];
		}
		add_settings_field(
			$args['id'],
			'<label class="evge-setting-label" for="evge_' . $args['id'] . '">' . $label_content . '</label>',
			$callback,
			$args['page'],
			$args['section'],
			$args
		);
	}

	public function section_callback() {
		echo '';
	}

	public function notice( $args ) {
		if ( isset( $args['notice'] ) ) {
			echo wp_kses_post( $args['notice'] );
		}
	}


	public function text_field( $args ) {
		$value = $this->get_setting_value( $args['id'] );
		if ( ! empty( $args['value'] ) ) {
			$value = $args['value'];
		}
		$class = isset( $args['class'] ) ? $args['class'] : '';
		?>
		<div class="evge-flex<?php echo ! empty( $class ) ? ' ' . esc_attr( $class ) : ''; ?>">
			<input id="evge_<?php echo esc_attr( $args['id'] ); ?>"<?php if ( ! empty( $args['class'] ) ) { echo ' class="' . esc_attr( $args['class'] ) . '"'; } ?> type="text" name="<?php echo esc_attr( $args['option'] ); ?>[<?php echo esc_attr( $args['id'] ); ?>]" value="<?php echo esc_attr( $value ); ?>">
			<?php if ( ! empty( $args['tooltip'] ) ) : ?>
				<?php 
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $this->tooltip( $args['tooltip'] ); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Password field for sensitive data
	 * 
	 * @param array $args
	 * @return void
	 */
	public function password_field( $args ) {
		$value = $this->get_setting_value( $args['id'] );
		$class = ! empty( $args['class'] ) ? $args['class'] : 'regular-text';
		?>
		<input id="evge_<?php echo esc_attr( $args['id'] ); ?>" class="<?php echo esc_attr( $class ); ?>" type="password" name="<?php echo esc_attr( $args['option'] ); ?>[<?php echo esc_attr( $args['id'] ); ?>]" value="<?php echo esc_attr( $value ); ?>">
		<?php
	}

	public function multi_text_field( $args ) {
		$options = get_option( $args['option'], array() );
		
		// Sort text fields by priority if provided
		$text_fields = $args['text_fields'];
		if ( ! empty( $text_fields ) ) {
			uasort( $text_fields, function( $a, $b ) {
				$priority_a = isset( $a['priority'] ) ? (int) $a['priority'] : 10;
				$priority_b = isset( $b['priority'] ) ? (int) $b['priority'] : 10;
				return $priority_a <=> $priority_b;
			} );
		}
		?>
		<div class="evge-multi-text-fields-wrap">
			<?php
			foreach ( $text_fields as $key => $text_field ) :
				$default = isset( $text_field['default'] ) ? $text_field['default'] : Defaults::get( $text_field['id'] );
				$value = ! empty( $text_field['value'] ) ? $text_field['value'] : $this->get_setting_value( $text_field['id'] );
				$type = isset( $text_field['type'] ) ? $text_field['type'] : 'text';
				$tooltip = isset( $text_field['tooltip'] ) ? $text_field['tooltip'] : '';
				?>
            <?php if ( $type === 'rich_editor' ) :
				$rich_editor_args = $args;
				$rich_editor_args['id'] = $text_field['id'];
				$rich_editor_args['default'] = $default;
				$rich_editor_args['settings'] = isset( $text_field['settings'] ) ? $text_field['settings'] : array();
				$rich_editor_args['top_description'] = isset( $text_field['top_description'] ) ? $text_field['top_description'] : '';
				$rich_editor_args['include_reference'] = isset( $text_field['include_reference'] ) ? $text_field['include_reference'] : false;
				$rich_editor_args['reference_type'] = isset( $text_field['reference_type'] ) ? $text_field['reference_type'] : 'generic';
				$rich_editor_args['tooltip'] = isset( $text_field['tooltip'] ) ? $text_field['tooltip'] : '';
				$rich_editor_args['option'] = $args['option'];

				?>
				<div class="evge-multi-text-rich-editor-field">
					<label for="evge_<?php echo esc_attr( $text_field['id'] ); ?>"><?php echo esc_html( $text_field['label'] ); ?></label>
					<?php $this->rich_editor( $rich_editor_args ); ?>
				</div>
			<?php elseif ( $type === 'toggle_field') :
				$toggle_args = $args;
				$toggle_args['id'] = $text_field['id'];
				$toggle_args['default'] = $default;
				$toggle_args['after'] = isset( $text_field['label'] ) ? $text_field['label'] : '';
				$toggle_args['option'] = $args['option'];
                ?>

                <div class="evge-multi-text-toggle-field">
                    <?php $this->toggle_field( $toggle_args ); ?>
                </div>

			<?php else :
				$class = ! empty( $text_field['class'] ) ? $text_field['class'] : 'regular-text';
				?>
				<?php if ( ! empty( $text_field['show_label'] ) ) : ?>
                <label for="evge_<?php echo esc_attr( $text_field['id'] ); ?>"><?php echo esc_html( $text_field['label'] ); ?></label>
			<?php endif; ?>
				<div class="evge-flex-center evge-multi-text-text-field evge-tooltip-for-<?php echo esc_attr( $class ); ?>">

                    <input id="evge_<?php echo esc_attr( $text_field['id'] ); ?>" class="<?php echo esc_attr( $class ); ?>" type="text" name="<?php echo esc_attr( $args['option'] ); ?>[<?php echo esc_attr( $text_field['id'] ); ?>]" value="<?php echo esc_attr( $value ); ?>">
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $this->tooltip( $tooltip ); ?>
				</div>
			<?php
			endif;
			endforeach; ?>
		</div>
		<?php
	}

	public function textarea_field( $args ) {
		$option_string = $this->get_setting_value( $args['id'] );
		$rows          = isset( $args['rows'] ) ? $args['rows'] : '10';
		$columns       = isset( $args['columns'] ) ? $args['columns'] : '70';
		?>
		<textarea id="evge_<?php echo esc_attr( $args['id'] ); ?>" name="<?php echo esc_attr( $args['option'] ); ?>[<?php echo esc_attr( $args['id'] ); ?>]" cols="<?php echo absint( $columns ); ?>" rows="<?php echo absint( $rows ); ?>"><?php echo esc_textarea( $option_string ); ?></textarea>
		<?php
	}

	public function integer_field( $args ) {
		$value = $this->get_setting_value( $args['id'] );
		$min = isset( $args['min'] ) ? $args['min'] : 1;
		?>
		<div class="evge-setting-flex-column">
			<div class="evge-flex-center">
				<input id="evge_<?php echo esc_attr( $args['id'] ); ?>" class="evge-short-input" type="number" min="<?php echo (int)$min; ?>" step="1" name="<?php echo esc_attr( $args['option'] ); ?>[<?php echo esc_attr( $args['id'] ); ?>]" value="<?php echo esc_attr( $value ); ?>">
				<?php
				if ( ! empty( $args['after'] ) ) {
					echo wp_kses_post( $args['after'] );
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Number field callback for decimal values
	 */
	public function number_field( $args ) {
		$value = isset( $args['value'] ) ? $args['value'] : $this->get_setting_value( $args['id'] );
		$min = isset( $args['min'] ) ? $args['min'] : 0;
		$step = isset( $args['step'] ) ? $args['step'] : 1;
		$class = isset( $args['class'] ) ? $args['class'] : 'small-text';
		?>
		<div class="evge-setting-flex-column">
			<input 
				id="evge_<?php echo esc_attr( $args['id'] ); ?>" 
				class="<?php echo esc_attr( $class ); ?>" 
				type="number" 
				min="<?php echo esc_attr( $min ); ?>" 
				step="<?php echo esc_attr( $step ); ?>" 
				name="<?php echo esc_attr( $args['option'] ); ?>[<?php echo esc_attr( $args['id'] ); ?>]" 
				value="<?php echo esc_attr( $value ); ?>"
			>
		</div>
		<?php
	}

	public function radio_field( $args ) {
		$value = $this->get_setting_value( $args['id'] );

		$class = count( $args['options'] ) > 2 ? 'evge-setting-flex-column' : 'evge-flex-center evge-flex-large-gap';
		?>
		<div class="<?php echo esc_attr( $class ); ?>">
			<?php foreach ( $args['options'] as $key => $label ) : ?>
				<label>
					<input type="radio" name="<?php echo esc_attr( $args['option'] ); ?>[<?php echo esc_attr( $args['id'] ); ?>]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $key, $value ); ?>>
					<?php echo esc_html( $label ); ?>
				</label>
			<?php endforeach; ?>
		</div>
		<?php
	}

	public function select_field( $args ) {
		$value = isset( $args['value'] ) ? $args['value'] : $this->get_setting_value( $args['id'] );

		$options = isset( $args['options'] ) ? $args['options'] : array();
		$class = isset( $args['class'] ) ? $args['class'] : '';
		$disabled = isset( $args['disabled'] ) && $args['disabled'] ? 'disabled' : '';
		?>
		<div class="evge-flex<?php echo ! empty( $class ) ? ' ' . esc_attr( $class ) : ''; ?><?php echo $disabled ? ' evge-setting-disabled' : ''; ?>">
			<select id="evge_<?php echo esc_attr( $args['id'] ); ?>" name="<?php echo esc_attr( $args['option'] ); ?>[<?php echo esc_attr( $args['id'] ); ?>]" <?php echo $disabled ? 'disabled' : ''; ?>>
				<?php foreach ( $options as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $key, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php if ( ! empty( $args['tooltip'] ) ) : ?>
				<?php 
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $this->tooltip( $args['tooltip'] ); ?>
			<?php endif; ?>
		</div>
		<?php 
			// Handle additional content via callback
			if ( ! empty( $args['after_callback'] ) ) {
				if ( is_callable( $args['after_callback'] ) ) {
					call_user_func( $args['after_callback'], $args, $value );
				} elseif ( method_exists( $this, $args['after_callback'] ) ) {
					$this->{$args['after_callback']}( $args, $value );
				}
			}
			?>
		<?php
	}

	public function buttons_with_preview( $args ) {
		$value = ! empty( $args['value'] ) ? $args['value'] : $this->get_setting_value( $args['id'] . '_text' );
		$button_background = $this->get_setting_value( $args['id'] . '_background_color' );
		$button_text_color = $this->get_setting_value( $args['id'] . '_text_color' );
		$button_border_color = $this->get_setting_value( $args['id'] . '_border_color' );
		?>
        <div class="evge-button-preview-wrap">
            <div class="evge-button-preview-settings">
                <div class="evge-button-preview-single">
                    <label for="evge_<?php echo esc_attr( $args['id'] ); ?>_text" class="evge-button-preview-label"><?php esc_html_e( 'Text', 'event-genius' ); ?></label>
                    <input id="evge_<?php echo esc_attr( $args['id'] ); ?>_text" class="evge-button-preview-text" type="text" name="<?php echo esc_attr( $args['option'] ); ?>[<?php echo esc_attr( $args['id'] ); ?>_text]" value="<?php echo esc_attr( $value ); ?>">
                </div>
                <div class="evge-button-preview-single">
                    <label for="evge_<?php echo esc_attr( $args['id'] ); ?>_background" class="evge-button-preview-label"><?php esc_html_e( 'Background', 'event-genius' ); ?></label>
                    <input id="evge_<?php echo esc_attr( $args['id'] ); ?>_background" class="evge-colorpicker evge-button-preview-bg" type="text" name="<?php echo esc_attr( $args['option'] ); ?>[<?php echo esc_attr( $args['id'] ); ?>_background_color]" value="<?php echo esc_attr( $button_background ); ?>">
                </div>
                <div class="evge-button-preview-single">
                    <label for="evge_<?php echo esc_attr( $args['id'] ); ?>_text_color" class="evge-button-preview-label"><?php esc_html_e( 'Text Color', 'event-genius' ); ?></label>
                    <input id="evge_<?php echo esc_attr( $args['id'] ); ?>_text_color" class="evge-colorpicker evge-button-preview-tc" type="text" name="<?php echo esc_attr( $args['option'] ); ?>[<?php echo esc_attr( $args['id'] ); ?>_text_color]" value="<?php echo esc_attr( $button_text_color ); ?>">
                </div>
                <div class="evge-button-preview-single">
                    <label for="evge_<?php echo esc_attr( $args['id'] ); ?>_border_color" class="evge-button-preview-label"><?php esc_html_e( 'Border Color', 'event-genius' ); ?></label>
                    <input id="evge_<?php echo esc_attr( $args['id'] ); ?>_border_color" class="evge-colorpicker evge-button-preview-bordercolor" type="text" name="<?php echo esc_attr( $args['option'] ); ?>[<?php echo esc_attr( $args['id'] ); ?>_border_color]" value="<?php echo esc_attr( $button_border_color ); ?>">
                </div>

            </div>
            <div class="evge-button-preview-preview">
				<label for="evge_<?php echo esc_attr( $args['id'] ); ?>_text" class="evge-button-preview-label"><?php esc_html_e( 'Preview', 'event-genius' ); ?></label>
                <button class="evge-button-preview" style="background-color: <?php echo esc_attr( $button_background ); ?>; color: <?php echo esc_attr( $button_text_color );?>; border-color: <?php echo esc_attr( $button_border_color ); ?>"><?php echo esc_html( $value ); ?></button>
            </div>
        </div>
		<?php
	}

	public function rich_editor( $args ) {
		// get option 'text_string' value from the database
		$options = get_option( $args['option'], array() );
		$default       = isset( $args['default'] ) ? $args['default'] : false;
		$option_string = ! empty( $args['value'] ) ? $args['value'] : $this->get_setting_value( $args['id'] );

		$settings                  = $args['settings'];
		$settings['textarea_name'] = $args['option'] . '[' . $args['id'] . ']';
		$class = isset( $args['class'] ) ? $args['class'] : '';
		echo '<div class="evge-placeholderable-field' . ( ! empty( $class ) ? ' ' . esc_attr( $class ) : '' ) . '">';
		if ( ! empty( $args['top_description'] ) ) :
			?>
			<div class="evge-top-desc"><?php echo wp_kses_post( $args['top_description'] ); ?></div>
		<?php endif;

		wp_editor( $option_string, $args['id'], $settings );

		if ( ! empty( $args['include_reference'] ) ) :
			$type = ! empty( $args['reference_type'] ) ? $args['reference_type'] : 'generic';
			
			// Apply filter to allow custom reference handling based on type
			$filtered_reference = apply_filters( 'evge_rich_editor_reference', null, $type, $args );
			
			// If filter returns a value, use it instead of default behavior
			if ( $filtered_reference !== null ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $filtered_reference;
			} elseif ( $type === 'additional_guest_summary' ) {
				// Special handling for additional guest summary template
				$this->additional_guest_summary_placeholder_reference( $args );
			} else {
				$factory = new \WPEventGenius\Common\Services\RegistrationObjectFactory();
				$placeholders = $factory->create_placeholders( new BaseRegistration( new Database() ), new Event( 0 ), $type );
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $placeholders->placeholder_reference_table( $placeholders->data(), strpos( $args['id'], 'confirmation' ) === false && strpos( $args['id'], 'confirmed' ) === false );
			}
		endif;
		echo '</div>';
	}

	/**
	 * Generate placeholder reference table for additional guest summary template
	 * 
	 * @param array $args Field arguments
	 */
	private function additional_guest_summary_placeholder_reference( $args ) {
		// Get form fields for the current form
		$form_id = isset( $_GET['form_id'] ) ? intval( $_GET['form_id'] ) : 1;
		$factory = new \WPEventGenius\Common\Services\RegistrationObjectFactory();
		$form = $factory->create_form( $form_id );
		
		// Manually load fields since get_fields() doesn't auto-load them
		$form->set_fields();
		$form_fields = $form->get_fields();
		
		// Create custom placeholder data for additional guest summary
		$custom_placeholders = array();
		
		// Add guest-identity placeholder
		$guest_identity_template = $form->get_guest_identity_label();
		$guest_identity_example = str_replace( '{number}', '1', $guest_identity_template );
		$custom_placeholders['guest_identity'] = array(
			'value' => __( 'Main Registration', 'event-genius' ) . ' / ' . $guest_identity_example,
			'description' => __( 'The identity of the guest (Main Registration, or custom guest label).', 'event-genius' ),
			'category' => 'guest',
			'placeholder' => '{guest-identity}',
		);
		
		// Add all-fields placeholder
		$custom_placeholders['all_fields'] = array(
			'value' => __( 'All registration fields in table format', 'event-genius' ),
			'description' => __( 'All fields submitted in the registration form displayed in a table.', 'event-genius' ),
			'category' => 'guest',
			'placeholder' => '{all-fields}',
		);
		
		// Add individual form field placeholders
		foreach ( $form_fields as $field ) {
			$field_slug = $field->get_slug();
			$custom_placeholders[ $field_slug ] = array(
				'value' => sprintf( __( 'Value of %s field', 'event-genius' ), $field->get_label() ),
				'description' => sprintf( __( 'The value of the %s field for this guest.', 'event-genius' ), $field->get_label() ),
				'category' => 'guest',
				'placeholder' => '{' . $field_slug . '}',
			);
		}
		
		// Create a temporary placeholders object to use the reference table method
		$factory = new \WPEventGenius\Common\Services\RegistrationObjectFactory();
		$placeholders = $factory->create_placeholders( new BaseRegistration( new Database() ), new Event( 0 ), 'generic' );

		// Generate the reference table with our custom placeholders
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $placeholders->placeholder_reference_table( $custom_placeholders, false, array( 			array(
				'name' => 'guest',
				'label' => __( 'Additional Guest Summary', 'event-genius' ),
			), ) );
	}

	public function multi_checkbox( $args ) {
		$options = get_option( $args['option'], array() );
		$value = $this->get_setting_value( $args['id'] );

		// Ensure $value is always an array for multi-checkbox fields
		if ( ! is_array( $value ) ) {
			$value = array();
		}

		$options = isset( $args['options'] ) ? $args['options'] : array();
		$disabled = isset( $args['disabled'] ) && $args['disabled'] ? 'disabled' : '';
		?>
		<div class="evge-flex evge-setting-flex-column evge-multi-checkbox-<?php echo esc_attr( $args['id'] ); ?><?php echo $disabled ? ' evge-setting-disabled' : ''; ?>">
			<?php foreach ( $options as $key => $label ) : ?>
				<div class="evge-flex-center">
					<input id="evge_checkbox_<?php echo esc_attr( $args['id'] ) . '_' . esc_attr( $key ); ?>" type="checkbox" name="<?php echo esc_attr( $args['option'] ); ?>[<?php echo esc_attr( $args['id'] ); ?>][]" value="<?php echo esc_attr( $key ); ?>"<?php if ( in_array( $key, $value, true ) ) { echo ' checked'; } ?><?php echo $disabled ? ' disabled' : ''; ?>><label for="evge_checkbox_<?php echo esc_attr( $args['id'] ) . '_' . esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
				</div>
			<?php endforeach; ?>
			<?php if ( ! empty( $args['tooltip'] ) ) : ?>
				<?php 
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $this->tooltip( $args['tooltip'] ); ?>
			<?php endif; ?>
		</div>
		<?php
	}



	public function toggle_field( $args ) {			
		$value = $this->get_setting_value( $args['id'] );
		$disabled = isset( $args['disabled'] ) && $args['disabled'] ? true : false;
		?>
		<div class="evge-toggle-setting<?php echo $disabled ? ' evge-setting-disabled' : ''; ?>">
			<input class="evge-toggle-setting-enabled" type="hidden" name="<?php echo esc_attr( $args['option'] ); ?>[<?php echo esc_attr( $args['id'] ); ?>]" value="<?php echo esc_attr( $value ); ?>">
			<a class="evge-settings-toggle-wrap<?php echo $disabled ? ' evge-disabled' : ''; ?>" href=""<?php echo $disabled ? ' onclick="return false;"' : ''; ?>>
				<?php if ( $value === 'enabled' ) : ?>
					<span class="evge-settings-toggle evge-input-toggle--enabled" aria-label="<?php echo sprintf ( esc_html__( 'The setting is currently enabled', 'event-genius' ), '' ); ?>"><?php echo esc_html_e( 'Yes', 'event-genius' ); ?></span>
				<?php else : ?>
					<span class="evge-settings-toggle evge-input-toggle--disabled" aria-label="<?php echo sprintf ( esc_html__( 'The setting is currently disabled', 'event-genius' ), '' ); ?>"><?php echo esc_html_e( 'No', 'event-genius' ); ?></span>
				<?php endif; ?>
			</a>
            <?php
            if ( ! empty ( $args['after'] ) ) {
                echo wp_kses_post( $args['after'] );
            }
            ?>
		</div>
		<?php
	}

	public function message( $args ) {
		?>
		<div class="evge-message">
			<?php echo wp_kses_post( $args['message'] ); ?>
		</div>
		<?php
	}


	public function asterisk( $text = '' ) {
		$tooltip = '<span class="evge-asterisk">*</span>';
		return $tooltip;
	}

	public function tooltip( $text = '', $position = 'left' ) {
		$tooltip_class = 'evge-tooltip evge-shadow';
		if ( $position === 'right' ) {
			$tooltip_class .= ' evge-tooltip-right';
		}
		
		$tooltip = '<div class="evge-tooltip-wrap">';
		$tooltip .= '<a href="javascript:void(0);" class="evge-tooltip-link">';
		$tooltip .= Icon::get( 'tooltip' );
		$tooltip .= '</a>';
		$tooltip .= '<div class="' . $tooltip_class . '">';
		$tooltip .= '<p>' . esc_html( $text ). '</p>';
		$tooltip .= '</div>';
		$tooltip .= '</div>';

		return $tooltip;
	}

	public static function sanitize_settings( $input ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = ! empty( $_REQUEST['tab'] ) ? sanitize_key( wp_unslash( $_REQUEST['tab'] ) ) : 'general';

		$option_slug = self::get_option_slug_for_tab( $tab );

		$options = get_option( $option_slug, array() );
		foreach( self::checkbox_fields() as $checkbox_field ) {
			$options[ $checkbox_field ] = false;
		}
		foreach( self::array_fields() as $array_field ) {
			$options[ $array_field ] = array();
		}
		foreach( $input as $key => $value ) {
			// Special handling for payment_gateways field
			if ( in_array( $key, self::text_area_fields(), true ) ) {
				$options[ $key ] = sanitize_textarea_field( wp_unslash( $value ) );
			} elseif ( in_array( $key, self::rich_editor_fields(), true ) ) {
				$options[ $key ] = wp_kses_post( wp_unslash( $value ) );
			} elseif ( in_array( $key, self::checkbox_fields(), true ) ) {
				$options[ $key ] = (bool)$value;
			} elseif ( in_array( $key, self::date_format_fields(), true ) ) {
				// Validate date format by testing if it works with PHP's date() function
				$options[ $key ] = self::validate_date_format( $value );
			} elseif ( $key === 'cpt_slugs' && is_array( $value ) ) {
				$options[ $key ] = array();
				foreach ( $value as $sub_key => $sub_value ) {
					if ( is_string( $sub_key ) ) {
						$options[ $key ][ $sub_key ] = sanitize_title( wp_unslash( $sub_value ) );
					}
				}
			} elseif ( is_array( $value ) ) {
				$options[ $key ] = array();
				foreach( $value as $sub_key => $sub_value ) {
					if ( is_string( $sub_key) ) {
						$options[ $key ][ $sub_key ] = sanitize_text_field( wp_unslash( $sub_value ) );
					} else {
						$options[ $key ][] = $sub_value;
					}
				}
			} else {
				$options[ $key ] = sanitize_text_field( wp_unslash( $value ) );
			}
		}


		return $options;
	}

	

	public static function get_option_slug_for_tab( $tab ) {
		$return = 'evge_settings';
		switch( $tab ) {
			default:
				$return = 'evge_settings';
		}
		return apply_filters( 'evge_option_slug_for_tab', $return, $tab );
	}

	public static function get_option_slug() {
		return 'evge_settings';
	}

	/**
	 * Validate that a date format string works with PHP's date() function
	 * 
	 * Simply tests if the format can produce a valid date/time string.
	 * If valid, returns the format as-is (preserving backslashes).
	 * If invalid, returns empty string.
	 * 
	 * @param string $format The date format string to validate
	 * @return string Valid format string, or empty string if invalid
	 */
	public static function validate_date_format( $format ) {
		if ( ! is_string( $format ) ) {
			return '';
		}
		
		$format = trim( $format );
		if ( empty( $format ) ) {
			return '';
		}
		
		// Test if the format works with a known timestamp
		$test_timestamp = strtotime( '2024-01-15 14:30:00' );
		$test_result = @date( $format, $test_timestamp );
		
		// If date() returns false, the format is invalid
		if ( $test_result === false ) {
			return '';
		}
		
		// Format is valid - return as-is (backslashes preserved)
		return $format;
	}

	/**
	 * Detects if any popular SMTP plugins are installed and active
	 * 
	 * @return array Array of active SMTP plugins with keys 'slug' and 'name'
	 */
	public static function detect_smtp_plugins() {
		$smtp_plugins = array();

		// Make sure we have access to WordPress plugin functions
		if (!function_exists('is_plugin_active')) {
			require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		}

		// List of popular SMTP plugins to check
		$plugins_to_check = array(
			'wp-mail-smtp/wp_mail_smtp.php' => 'WP Mail SMTP',
			'easy-wp-smtp/easy-wp-smtp.php' => 'Easy WP SMTP',
			'post-smtp/postman-smtp.php' => 'Post SMTP Mailer',
			'gmail-smtp/main.php' => 'Gmail SMTP',
			'smtp-mailer/main.php' => 'SMTP Mailer',
			'wp-smtp/wp-smtp.php' => 'WP SMTP',
			'sendgrid-email-delivery-simplified/wpsendgrid.php' => 'SendGrid',
			'mailgun/mailgun.php' => 'Mailgun for WordPress'
		);

		// Check each plugin
		foreach ($plugins_to_check as $plugin_file => $plugin_name) {
			if (is_plugin_active($plugin_file)) {
				$smtp_plugins[] = array(
					'slug' => dirname($plugin_file),
					'name' => $plugin_name
				);
			}
		}

		return $smtp_plugins;
	}

	public function toggle_setting_display( $args ) {
		$value = isset( $args['value'] ) ? $args['value'] : false;
		$name = isset( $args['name'] ) ? $args['name'] : '';
		$enabled_text = isset( $args['enabled_text'] ) ? $args['enabled_text'] : __( 'Yes', 'event-genius' );
		$disabled_text = isset( $args['disabled_text'] ) ? $args['disabled_text'] : __( 'No', 'event-genius' );
		$enabled_aria = isset( $args['enabled_aria'] ) ? $args['enabled_aria'] : __( 'Setting is enabled', 'event-genius' );
		$disabled_aria = isset( $args['disabled_aria'] ) ? $args['disabled_aria'] : __( 'Setting is disabled', 'event-genius' );
		$wrapper_class = isset( $args['wrapper_class'] ) ? ' ' . $args['wrapper_class'] : '';
		$show_status_label = isset( $args['show_status_label'] ) ? $args['show_status_label'] : false;
		$enabled_label = isset( $args['enabled_label'] ) ? $args['enabled_label'] : __( 'Enabled', 'event-genius' );
		$disabled_label = isset( $args['disabled_label'] ) ? $args['disabled_label'] : __( 'Disabled', 'event-genius' );
		?>
		<div class="evge-toggle-setting<?php echo esc_attr( $wrapper_class ); ?>">
			<input class="evge-toggle-setting-enabled" 
				   type="hidden" 
				   name="<?php echo esc_attr( $name ); ?>" 
				   value="<?php echo $value ? 'enabled' : 'disabled'; ?>">
			<a class="evge-settings-toggle-wrap" href="">
				<?php if ( $value ) : ?>
					<span class="evge-settings-toggle evge-input-toggle--enabled" 
						  aria-label="<?php echo esc_attr( $enabled_aria ); ?>">
						<?php echo esc_html( $enabled_text ); ?>
					</span>
				<?php else : ?>
					<span class="evge-settings-toggle evge-input-toggle--disabled" 
						  aria-label="<?php echo esc_attr( $disabled_aria ); ?>">
						<?php echo esc_html( $disabled_text ); ?>
					</span>
				<?php endif; ?>
			</a>
			<?php if ( !empty( $args['label'] ) ) : ?>
				<label><?php echo esc_html( $args['label'] ); ?></label>
			<?php endif; ?>
			<?php if ( $show_status_label ) : ?>
				<span class="evge-toggle-status-label" 
					  data-enabled-text="<?php echo esc_attr( $enabled_label ); ?>" 
					  data-disabled-text="<?php echo esc_attr( $disabled_label ); ?>">
					<?php echo $value ? esc_html( $enabled_label ) : esc_html( $disabled_label ); ?>
				</span>
			<?php endif; ?>
		</div>
		<?php
	}

	protected function get_setting_value( $id ) {
		return Settings::get( $id );
	}
}