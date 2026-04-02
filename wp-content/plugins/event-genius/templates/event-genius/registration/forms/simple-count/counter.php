<?php
/**
 * Simple count counter template
 *
 * This template renders the +/- counter for simple guest count registration.
 *
 * Available variables:
 * @var int    $starting_count  The initial count to display
 * @var int    $min_count       Minimum allowed count (optional, defaults to 1)
 * @var int    $max_count       Maximum allowed count (optional, defaults to 1000)
 * @var string $label           The label text (optional)
 * @var string $wrapper_class   Additional CSS classes for the wrapper (optional)
 *
 * @package EventGenius
 */

use WPEventGenius\Common\Utils\Icon;
use WPEventGenius\Common\Utils\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Set defaults
$starting_count = isset( $starting_count ) ? (int) $starting_count : 1;
$min_count      = isset( $min_count ) ? (int) $min_count : 1;
$max_count      = isset( $max_count ) ? (int) $max_count : 1000;
$label          = isset( $label ) ? $label : Settings::get( 'how_many_registrations' );
$wrapper_class  = isset( $wrapper_class ) ? ' ' . $wrapper_class : '';

// Ensure starting count is within bounds
$starting_count = max( $min_count, min( $starting_count, $max_count ) );

// Determine button states
$subtract_disabled = $starting_count <= max( $min_count, 1 );
$add_disabled      = $starting_count >= $max_count;
?>
<div class="evge-num-registrations evge-modal-section<?php echo esc_attr( $wrapper_class ); ?>">
	<?php if ( $label ) : ?>
		<strong><?php echo esc_html( $label ); ?></strong>
	<?php endif; ?>
	<div class="evge-num-registrations-counter-wrap" aria-label="<?php esc_attr_e( 'Number of guests', 'event-genius' ); ?>">
		<div class="evge-num-registrations-counter" role="group" 
			data-min="<?php echo esc_attr( $min_count ); ?>" 
			data-max="<?php echo esc_attr( $max_count ); ?>">
			<a href="#" class="evge-counter-button evge-subtract <?php echo $subtract_disabled ? 'evge-button-disabled' : ''; ?>" aria-label="<?php esc_attr_e( 'Decrease guests', 'event-genius' ); ?>">
				<?php Icon::output( 'minus' ); ?>
			</a>
			<div class="evge-count-wrap">
				<div class="evge-count" aria-live="polite"><span><?php echo esc_html( $starting_count ); ?></span></div>
			</div>
			<a href="#" class="evge-counter-button evge-add <?php echo $add_disabled ? 'evge-button-disabled' : ''; ?>" aria-label="<?php esc_attr_e( 'Increase guests', 'event-genius' ); ?>">
				<?php Icon::output( 'plus' ); ?>
			</a>
		</div>
	</div>
	<input type="hidden" name="quantity" value="<?php echo esc_attr( $starting_count ); ?>" class="evge-quantity-input">
</div>

