<?php
/**
 * Full Attendee Item Template
 * 
 * This template displays a single attendee's information in a CSS grid format.
 * It iterates through all form fields and displays the corresponding attendee data
 * in individual grid cells.
 * 
 * @package WPEventGenius
 * @since 1.0.0
 * 
 * @var \WPEventGenius\Registration\Form $form The registration form object
 * @var array $attendee The attendee data array containing placeholders
 * @var array $fields Array of form field objects
 * @var bool $is_hidden Optional. Whether this attendee should be hidden initially (default: false)
 * @var int $attendee_index Optional. The index of this attendee (for sorting)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Default is_hidden to false if not provided
$is_hidden = isset( $is_hidden ) ? (bool) $is_hidden : false;
$hidden_class = $is_hidden ? ' evge-attendee-hidden' : '';
$hidden_attr = $is_hidden ? ' style="display: none;"' : '';
$attendee_index = isset( $attendee_index ) ? absint( $attendee_index ) : 0;
?>

<div class="evge-attendee evge-attendee-row<?php echo esc_attr( $hidden_class ); ?>"<?php echo $hidden_attr; ?> data-attendee-index="<?php echo esc_attr( $attendee_index ); ?>">
	<?php foreach ( $fields as $index => $field ) : ?>
		<div class="evge-grid-cell" 
			data-field-slug="<?php echo esc_attr( $field->get_slug() ); ?>" 
			data-field-index="<?php echo esc_attr( $index ); ?>"
			data-field-label="<?php echo esc_attr( $field->get_label() ); ?>"
			data-field-value="<?php echo esc_attr( $attendee['placeholders']->replace( '{' . $field->get_slug() . '}' ) ); ?>">
			<?php 
			echo esc_html(
				$attendee['placeholders']->replace( '{' . $field->get_slug() . '}' )
			); 
			?>
		</div>
	<?php endforeach; ?>
</div> 