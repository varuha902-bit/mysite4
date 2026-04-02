<?php
/**
 * Registration Field Template
 * 
 * This template renders a single registration form field, including its label,
 * input element, error message, and type mismatch warning if applicable.
 * 
 * @package WPEventGenius
 * @since 1.0.0
 * 
 * @var \WPEventGenius\Common\Utils\Templater $templater The templater instance
 * @var \WPEventGenius\Registration\Field $field The field object
 * @var array $registration_data The current registration data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure expected variables exist (template may be included from contexts that omit them, e.g. honeypot)
if ( ! isset( $registration_data ) ) {
	$registration_data = array();
}
if ( ! isset( $flags ) ) {
	$flags = array();
}

// Determine if this is a guest field using the flags system
$is_guest_field = ! empty( $flags['is_guest'] );

// Use guest_is_required() for guest fields, main_is_required() for main fields
$is_required = $is_guest_field ? $field->guest_is_required() : $field->main_is_required();
?>

<div class="evge-field-wrapper evge-field-wrapper-<?php echo esc_attr( $field->get_id() ); ?> evge-field-wrapper-type-<?php echo esc_attr( $field->get_type() ); ?>" 
	data-id="<?php echo esc_attr( $field->get_id() ); ?>" 
	data-required="<?php echo esc_attr( $is_required ? '1' : '0' ); ?>"
	data-show-in-attendee-list="<?php echo esc_attr( $field->show_in_attendee_list() ? '1' : '0' ); ?>"
	<?php $field->additional_wrapper_data_attributes(); ?>>
	
	<div class="evge-field-inner">
		<div class="evge-label-wrapper">
			<?php include $templater->get_registration_template_part( 'label' ); ?>
		</div>
		
		<div class="evge-input-wrapper">
			<?php include $templater->get_registration_template_part( 'input' ); ?>
		</div>

		<?php do_action( 'evge_field_after_input', $field, $registration_data, $flags ); ?>
		
		<div class="evge-field-error">
			<span><?php echo esc_attr( $field->get_error_message() ); ?></span>
		</div>
	</div>
	
	<?php if ( $field->has_type_mismatch( $registration_data ) ) : ?>
		<div class="evge-field-type-mismatch-warning" 
			role="alert">
			<?php echo esc_html( $field->get_type_mismatch_warning() ); ?>
		</div>
	<?php endif; ?>
</div>