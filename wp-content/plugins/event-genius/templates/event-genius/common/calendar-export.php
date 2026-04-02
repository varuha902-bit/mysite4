<?php
/**
 * Calendar Export Template
 * 
 * This template handles the display of the "Add to Calendar" button and its dropdown options.
 * It provides functionality for users to export event details to various calendar formats.
 */

use WPEventGenius\Common\Utils\EventExport\Options;
use WPEventGenius\Common\Utils\Icon;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Prepare inline styles if provided
$styles_att = '';
if ( ! empty( $styles_array ) ) {
	$styles = implode( ';', $styles_array );
	$styles_att = ' style="' . $styles . '"';
}
?>

<div class="evge-export-list-wrap">
	<button 
		class="<?php echo esc_attr( $button_class ); ?> evge-export-list" 
		aria-expanded="false" 
		aria-controls="evge-export-options-dropdown"
		aria-label="<?php echo esc_attr__( 'Add to Calendar Options', 'event-genius' ); ?>"
		<?php 
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $styles_att; 
		?>
	>
		<span class="evge-icon-text">
			<?php Icon::output( 'calendar-add' ); ?>
			<?php echo esc_html( $event_post->add_to_calendar_text() ); ?>
		</span>
	</button>
	<div 
		id="evge-export-options-dropdown" 
		class="evge-export-options-dropdown" 
		role="menu" 
		aria-label="<?php echo esc_attr__( 'Calendar Export Options', 'event-genius' ); ?>"
		hidden
	>
		<?php
		$options = new Options( $event_post );
		$options = $options->get_options();

		foreach ( $options as $option ) {
			?>
			<a 
				href="<?php echo esc_url( $option['url'] ); ?>" 
				class="evge-export-option evge-beige"
				role="menuitem"
				<?php 
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $option['attr']; 
				?>
			>
				<?php echo esc_html( $option['name'] ); ?>
			</a>
			<?php
		}
		?>
	</div>
</div>

