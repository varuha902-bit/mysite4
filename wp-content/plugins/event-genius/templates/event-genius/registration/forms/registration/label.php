<?php
/**
 * Registration Field Label Template
 *
 * Renders the label for a registration form field, including a required asterisk if needed.
 * Skips output if the field is set to hide its label.
 *
 * @package WPEventGenius
 * @since 1.0.0
 *
 * @var \WPEventGenius\Registration\Field $field The field object
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$suffix = ! empty( $suffix ) ? $suffix : '';
$flags = ! empty( $flags ) ? $flags : [];

// Determine if this is a guest field using the flags system
$is_guest_field = ! empty( $flags['is_guest'] );

// Use guest_is_required() for guest fields, main_is_required() for main fields
$is_required = $is_guest_field ? $field->guest_is_required() : $field->main_is_required();

if ( $field->hidden_label() ) {
	return;
}
?>
<label for="evge_<?php echo esc_attr( $field->get_slug() . $suffix ); ?>">
	<?php echo esc_html( wp_unslash( $field->get_label() ) ); ?><?php if ( $is_required ) { echo '*'; } ?>
</label>