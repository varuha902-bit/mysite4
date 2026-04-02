<?php
/**
 * Modal Template
 * 
 * This template provides a reusable modal dialog component with a close button
 * and content area. Used for displaying various modal content throughout the plugin.
 */

use WPEventGenius\Common\Utils\Icon;
use WPEventGenius\Common\Utils\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get color theme setting
$color_theme = Settings::get( 'color_theme' );
$dark_theme_class = ( $color_theme === 'dark' ) && ! is_admin() ? ' evge-modal-dark' : '';
?>

<div class="evge-modal-backdrop"></div>

<div id="evge-modal" class="evge-modal evge-medium-max-width-modal<?php echo esc_attr( $dark_theme_class ); ?>">
	<button type="button" class="evge-button-link evge-form-modal-close evge-action-modal-close">
		<?php Icon::output( 'modal-close' ); ?>
		<span class="evge-modal-icon">
			<span class="screen-reader-text">
				<?php esc_html_e( 'Close', 'event-genius' ); ?>
			</span>
		</span>
	</button>

	<div class="evge-modal-content">
		<div class="evge-modal-placeholder"></div>
	</div>
</div>