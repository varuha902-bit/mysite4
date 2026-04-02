<?php
use WPEventGenius\Common\Utils\Icon;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Modal settings
$modal_settings = array(
	'width' => 'small',
);
$modal_settings_json = wp_json_encode( $modal_settings );
?>

<div class="evge-dynamic evge-modal-settings" data-evge-modal-settings="<?php echo esc_attr( $modal_settings_json ); ?>">
	<div class="evge-modal-heading evge-upsell-modal-heading">
		<img src="<?php echo esc_url( EVGE_PLUGIN_URL . 'assets/images/admin/genius.png' ); ?>" alt="Event Genius" class="evge-upsell-logo" />
		<h2 class="evge-upsell-heading-title"><?php echo esc_html( $upsell_data['title'] ); ?></h2>
	</div>
	<div class="evge-upsell-modal-content evge-modal-body">
		<div class="evge-upsell-modal-inner">
			<div class="evge-upsell-layout">
				<?php if ( ! empty( $upsell_data['image'] ) ) : ?>
					<div class="evge-upsell-image-column">
						<picture>
							<source srcset="<?php echo esc_url( $upsell_data['image_2x'] ); ?>" media="(-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi)">
							<img src="<?php echo esc_url( $upsell_data['image'] ); ?>" alt="<?php echo esc_attr( $upsell_data['title'] ); ?>" class="evge-upsell-feature-image" />
						</picture>
					</div>
				<?php endif; ?>
				
				<div class="evge-upsell-content-column">
					<div class="evge-upsell-description">
						<p><?php echo esc_html( $upsell_data['description'] ); ?></p>
					</div>

					<?php if ( ! empty( $upsell_data['features'] ) && is_array( $upsell_data['features'] ) ) : ?>
						<div class="evge-upsell-features">
							<?php foreach ( $upsell_data['features'] as $feature ) : ?>
								<div class="evge-upsell-feature-item">
									<span class="evge-upsell-feature-icon">
										<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M13.3333 4L6 11.3333L2.66667 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
										</svg>
									</span>
									<span class="evge-upsell-feature-text"><?php echo esc_html( $feature ); ?></span>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					
					<a href="<?php echo esc_url( $upsell_link ); ?>" class="button button-primary evge-dialog-primary evge-upsell-button evge-upsell-cta-button" target="_blank" rel="noopener noreferrer">
						<span class="evge-icon-text">
							<?php esc_html_e( 'Upgrade to Pro', 'event-genius' ); ?>
							<?php 
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo Icon::get( 'right-chevron' ); ?>
						</span>
					</a>
				</div>
			</div>
			
			<div class="evge-upsell-footer">
				<h3 class="evge-upsell-footer-title-medium-screen"><?php esc_html_e( 'And much more!', 'event-genius' ); ?></h3>
				<div class="evge-upsell-footer-columns">
					<div class="evge-upsell-footer-column evge-upsell-footer-title-column">
						<h3 class="evge-upsell-footer-title"><?php esc_html_e( 'And much more!', 'event-genius' ); ?></h3>
					</div>
					
					<?php if ( ! empty( $upsell_data['additional_features'] ) && is_array( $upsell_data['additional_features'] ) ) : 
						$features = $upsell_data['additional_features'];
						$features_count = count( $features );
						$split_point_1 = ceil( $features_count / 3 );
						$split_point_2 = ceil( $features_count * 2 / 3 );
						$first_column = array_slice( $features, 0, $split_point_1 );
						$second_column = array_slice( $features, $split_point_1, $split_point_2 - $split_point_1 );
						$third_column = array_slice( $features, $split_point_2 );
					?>
						<div class="evge-upsell-footer-column evge-upsell-footer-features-column">
							<ul class="evge-upsell-footer-features-list">
								<?php foreach ( $first_column as $feature ) : ?>
									<li class="evge-upsell-footer-feature-item">
										<span class="evge-upsell-footer-feature-icon">
											<svg width="12" height="12" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
												<path d="M13.3333 4L6 11.3333L2.66667 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
											</svg>
										</span>
										<span class="evge-upsell-footer-feature-text"><?php echo esc_html( $feature ); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
						
						<div class="evge-upsell-footer-column evge-upsell-footer-features-column">
							<ul class="evge-upsell-footer-features-list">
								<?php foreach ( $second_column as $feature ) : ?>
									<li class="evge-upsell-footer-feature-item">
										<span class="evge-upsell-footer-feature-icon">
											<svg width="12" height="12" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
												<path d="M13.3333 4L6 11.3333L2.66667 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
											</svg>
										</span>
										<span class="evge-upsell-footer-feature-text"><?php echo esc_html( $feature ); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
						
						<div class="evge-upsell-footer-column evge-upsell-footer-features-column evge-upsell-footer-features-column-last">
							<ul class="evge-upsell-footer-features-list">
								<?php foreach ( $third_column as $feature ) : ?>
									<li class="evge-upsell-footer-feature-item">
										<span class="evge-upsell-footer-feature-icon">
											<svg width="12" height="12" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
												<path d="M13.3333 4L6 11.3333L2.66667 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
											</svg>
										</span>
										<span class="evge-upsell-footer-feature-text"><?php echo esc_html( $feature ); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>
				</div>
			
			</div>
		</div>
	</div>
</div>

