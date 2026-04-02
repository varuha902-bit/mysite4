<?php
/**
 * Simple Attendee Item Template
 * 
 * This template displays a single attendee's name in a list item format.
 * It provides a simplified view showing just the first and last name of the attendee
 * in a grid layout.
 * 
 * @package WPEventGenius
 * @since 1.0.0
 * 
 * @var array $attendee The attendee data array containing placeholders
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<li class="evge-attendee evge-attendee-grid-item">
	<?php 
	// Use custom format if provided in args, otherwise use filter/default
	$default_format = '{first} {last}';
	$custom_format = isset( $simple_format ) && ! empty( $simple_format ) ? $simple_format : null;
	$name_format = $custom_format ? $custom_format : apply_filters( 'evge_simple_attendee_name_format', $default_format, $attendee );
	echo esc_html(
		$attendee['placeholders']->replace( $name_format )
	); 
	?>
</li> 