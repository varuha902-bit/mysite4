<?php
/**
 * Registration Input Field Template
 *
 * Renders the appropriate input element for a registration form field based on its type.
 * Handles text, email, phone, textarea, select, radio, checkbox, single-checkbox, and honeypot fields.
 *
 * @package WPEventGenius
 * @since 1.0.0
 *
 * @var \WPEventGenius\Registration\Field $field The field object
 * @var array $registration_data The current registration data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$flags = ! empty( $flags ) ? $flags : [];
$suffix = ! empty( $suffix ) ? $suffix : '';

// Determine if this is a guest field using the flags system
$is_guest_field = ! empty( $flags['is_guest'] );

// Use guest_is_required() for guest fields, main_is_required() for main fields
$is_required = $is_guest_field ? $field->guest_is_required() : $field->main_is_required();

switch ( $field->get_type() ) {
	case 'text':
	case 'phone':
	case 'email':
		?>
		<input type="<?php echo esc_attr( $field->get_type_attribute() ); ?>"
			name="evge_<?php echo esc_attr( $field->get_slug() . $suffix ); ?>"
			id="evge_<?php echo esc_attr( $field->get_slug() . $suffix ); ?>"
			placeholder="<?php echo esc_attr( $field->get_placeholder() ); ?>"
			value="<?php echo esc_attr( $field->get_value( $registration_data ) ); ?>"
			aria-required="<?php echo $is_required ? 'true' : 'false'; ?>"
			<?php $field->additional_input_attributes(); ?> />
		<?php
		break;

	case 'textarea':
		?>
		<textarea name="evge_<?php echo esc_attr( $field->get_slug() . $suffix ); ?>"
			id="evge_<?php echo esc_attr( $field->get_slug() . $suffix ); ?>"
			aria-required="<?php echo $is_required ? 'true' : 'false'; ?>"
			<?php $field->additional_input_attributes(); ?>><?php echo esc_textarea( $field->get_value( $registration_data ) ); ?></textarea>
		<?php
		break;

	case 'select':
		?>
		<select name="evge_<?php echo esc_attr( $field->get_slug() . $suffix ); ?>"
			id="evge_<?php echo esc_attr( $field->get_slug() . $suffix ); ?>"
			aria-required="<?php echo $is_required ? 'true' : 'false'; ?>"
			<?php $field->additional_input_attributes(); ?>>
			<?php foreach ( $field->get_options() as $option ) { ?>
				<option value="<?php echo esc_attr( wp_unslash( $option['value'] ) ); ?>" <?php echo $field->get_value( wp_unslash( $registration_data ) ) === wp_unslash( $option['value'] ) ? 'selected' : ''; ?>><?php echo esc_html( wp_unslash( $option['label'] ) ); ?></option>
			<?php } ?>
		</select>
		<?php
		break;

	case 'radio':
		?>
		<div class="evge-radio-checkbox-inputs-wrap" role="radiogroup" aria-label="<?php echo esc_attr( $field->get_label() ); ?>">
			<?php foreach ( $field->get_options() as $index => $option ) : ?>
				<div class="evge-radio-checkbox-input">
					<input type="radio"
						name="evge_<?php echo esc_attr( $field->get_slug() . $suffix ); ?>"
						id="evge_<?php echo esc_attr( $field->get_slug() . $suffix . '_' . $index ); ?>"
						value="<?php echo esc_attr( wp_unslash( $option['value'] ) ); ?>"
						<?php echo wp_unslash( $field->get_value( wp_unslash( $registration_data ) ) ) === wp_unslash( $option['value'] ) ? 'checked' : ''; ?>
						aria-required="<?php echo $is_required ? 'true' : 'false'; ?>"
						<?php $field->additional_input_attributes(); ?> />
					<label for="evge_<?php echo esc_attr( $field->get_slug() . $suffix . '_' . $index ); ?>">
						<?php echo esc_html( wp_unslash( $option['label'] ) ); ?>
					</label>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		break;

	case 'checkbox':
		?>
		<div class="evge-radio-checkbox-inputs-wrap" role="group" aria-label="<?php echo esc_attr( $field->get_label() ); ?>">
			<?php foreach ( $field->get_options() as $index => $option ) : ?>
				<div class="evge-radio-checkbox-input">
					<input type="checkbox"
						name="evge_<?php echo esc_attr( $field->get_slug() . $suffix ); ?>[]"
						id="evge_<?php echo esc_attr( $field->get_slug() . $suffix . '_' . $index ); ?>"
						value="<?php echo esc_attr( wp_unslash( $option['value'] ) ); ?>"
						<?php echo in_array( wp_unslash( $option['value'] ), wp_unslash( $field->get_value( $registration_data ) ) ) ? 'checked' : ''; ?>
						aria-required="<?php echo $is_required ? 'true' : 'false'; ?>"
						<?php $field->additional_input_attributes(); ?> />
					<label for="evge_<?php echo esc_attr( $field->get_slug() . $suffix . '_' . $index ); ?>">
						<?php echo esc_html( wp_unslash( $option['label'] ) ); ?>
					</label>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		break;

	case 'single-checkbox':
		$before = '';
		$after = '';
		if ( ! empty( $field->get_link() ) ) {
			$before = '<a href="' . esc_url( $field->get_link() ) . '" target="_blank" rel="noopener noreferrer">';
			$after = '</a>';
		}
		$asterisk = '';
		if ( $is_required ) {
			$asterisk = '*';
		}
		$misc = $field->get_misc();
		$scrollable_text = isset( $misc['scrollable_text'] ) ? $misc['scrollable_text'] : '';
		?>
		<div class="evge-single-checkbox-wrapper">
			<input type="checkbox"
				name="evge_<?php echo esc_attr( $field->get_slug() . $suffix ); ?>"
				id="evge_<?php echo esc_attr( $field->get_slug() . $suffix ); ?>"
				value="<?php echo esc_attr( wp_unslash( $field->get_label() ) ); ?>"
				data-single-checkbox="true"
				<?php if ( ! empty( $field->get_value( $registration_data ) ) ) { echo 'checked'; } ?>
				aria-required="<?php echo $is_required ? 'true' : 'false'; ?>"
				<?php $field->additional_input_attributes(); ?> />
			<label for="evge_<?php echo esc_attr( $field->get_slug() . $suffix ); ?>">
				<?php echo wp_kses_post( $before . wp_unslash( $field->get_label() ) . $asterisk . $after ); ?>
			</label>
			<?php if ( ! empty( $scrollable_text ) ) : ?>
				<div class="evge-terms-and-conditions-box">
					<div class="evge-terms-and-conditions-content">
						<?php echo wp_kses_post( wpautop( wp_unslash( $scrollable_text ) ) ); ?>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<?php
		break;

	case 'honeypot':
		?>
		<input type="text"
			name="evge_<?php echo esc_attr( $field->get_slug() . $suffix ); ?>"
			id="evge_<?php echo esc_attr( $field->get_slug() . $suffix ); ?>"
			class="evge-user-comments"
			value="" />
		<?php
		break;

	default:
		/**
		 * Action hook for custom field types
		 * 
		 * @param \WPEventGenius\Registration\Field $field The field object
		 * @param array $registration_data The current registration data
		 */
		do_action( 'evge_registration_field_input', $field, $registration_data, $flags );
		break;
}

