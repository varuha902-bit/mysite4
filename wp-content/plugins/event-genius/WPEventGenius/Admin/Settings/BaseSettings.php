<?php
namespace WPEventGenius\Admin\Settings;

use WPEventGenius\Admin\Page\BasePage;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class BaseSettings extends BasePage {

	protected $page = 'evge_base_settings';

	public function exclamation_notice( $message ) {
		?>
		<div class="evge-notice-base evge-exclamation-notice">
			<div class="evge-notice-icon">
				<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
					<circle cx="8" cy="8" r="8" fill="#AAAAAA"/>
					<circle cx="8" cy="4" r="1" fill="white"/>
					<rect x="7" y="7" width="2" height="6" fill="white"/>
				</svg>
			</div>
			<p><?php echo wp_kses_post( $message ); ?></p>
		</div>
		<?php
	}

	public function documentation_notice( $message, $link = '' ) {
		?>
		<div class="evge-notice-base evge-documentation-notice">
			<div class="evge-notice-icon">
				<?php 
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo \WPEventGenius\Common\Utils\Icon::get( 'doc' ); ?>
			</div>
			<?php if ( ! empty( $link ) ) : ?>
				<a href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener noreferrer" class="evge-icon-text">
					<p><?php echo wp_kses_post( $message ); ?></p>
					<?php 
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo \WPEventGenius\Common\Utils\Icon::get( 'right-chevron' ); ?>
				</a>
			<?php else : ?>
				<p><?php echo wp_kses_post( $message ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}
}
