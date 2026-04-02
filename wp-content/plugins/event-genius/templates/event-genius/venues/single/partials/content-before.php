<?php
/**
 * Venue Content Before Template
 * 
 * This template displays the initial content sections of a venue including:
 * - Featured image (wide or standard)
 * - Venue title
 * - Contact information
 * - Social media links
 * - Map integration
 * 
 * @var int    $venue_id The ID of the current venue
 * @package WPEventGenius
 * @since 1.0.0
 */

use WPEventGenius\Common\Utils\Icon;
use WPEventGenius\Common\Utils\Templater;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$venue_post = new \WPEventGenius\Common\Event\VenuePost( get_the_ID() );
$templater = new Templater();

// Get featured image details
$image_data = wp_get_attachment_image_src( get_post_thumbnail_id( $venue_post->get_the_id() ), 'full' );
$is_wide_image = false;

if ( $image_data ) {
	$image_width = $image_data[1];
	$image_height = $image_data[2];
	$is_wide_image = ( $image_width / $image_height ) > 1.25;
}

?>

<div class="evge-single-venue evge<?php echo esc_attr( $templater->evge_classes() ); ?>">
	<?php if ( $is_wide_image && $venue_post->has_featured_image() && $venue_post->should_show_section('featured_image') ) : ?>
		<div class="evge-hero">
			<?php 
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $venue_post->get_the_featured_image(); 
			?>
			<?php if ( $venue_post->should_show_section('title') ) : ?>
				<h1 class="evge-title"><?php echo esc_html( $venue_post->get_the_title() ); ?></h1>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="evge-profile-content">
		<div class="evge-left-column">
			<?php if ( ! $is_wide_image && $venue_post->has_featured_image() && $venue_post->should_show_section('featured_image') ) : ?>
				<div class="evge-featured-image">
					<?php 
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $venue_post->get_the_featured_image(); ?>
				</div>
			<?php endif; ?>

			<div class="evge-venue-details">
				<?php if ( ! $is_wide_image && $venue_post->should_show_section('title') ) : ?>
					<h1 class="evge-title"><?php echo esc_html( $venue_post->get_the_title() ); ?></h1>
				<?php endif; ?>

				<div class="evge-info">
					<?php if ( $venue_post->get_the_full_address() ) : ?>
						<p class="evge-address">
							<span class="evge-icon">
								<?php Icon::output( 'location-outline' ); ?>
							</span>
							<?php echo esc_html( $venue_post->get_the_full_address() ); ?>
						</p>
					<?php endif; ?>

					<?php if ( $venue_post->get_the_phone() ) : ?>
						<p class="evge-phone">
							<span class="evge-icon">
								<?php Icon::output( 'phonefield' ); ?>
							</span>
							<a href="tel:<?php echo esc_attr( $venue_post->get_the_phone() ); ?>">
								<?php echo esc_html( $venue_post->get_the_phone() ); ?>
							</a>
						</p>
					<?php endif; ?>

					<?php if ( $venue_post->get_the_website() ) : ?>
						<p class="evge-website">
							<span class="evge-icon">
								<?php Icon::output( 'link' ); ?>
							</span>
							<a href="<?php echo esc_url( $venue_post->get_the_website() ); ?>" target="_blank" rel="noopener noreferrer">
								<?php echo esc_html( $venue_post->get_the_website() ); ?>
							</a>
						</p>
					<?php endif; ?>
				</div>

				<?php 
				$links = $venue_post->get_the_links();
				if ( ! empty( $links ) ) : ?>
					<div class="evge-social-links">
						<div class="evge-follow-row">
							<?php foreach ( $links as $link_type => $link_url ) : ?>
								<a href="<?php echo esc_url( $link_url ); ?>" target="_blank" rel="noopener noreferrer">
									<?php Icon::output( $link_type ); ?>
								</a>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $venue_post->get_the_map_url() ) ) : ?>
					<div class="evge-map-wrap">
						<iframe 
							id="evge-venue-iframe" 
							src="<?php echo esc_url( $venue_post->get_the_map_url() ); ?>" 
							width="100%" 
							height="100%" 
							style="border:0;" 
							allowfullscreen
						></iframe>
					</div>
					<a class="evge-icon-link" href="<?php echo esc_url( $venue_post->get_the_full_map_url() ); ?>">
						<span><?php esc_html_e( 'See Full Map', 'event-genius' ); ?></span>
						<?php Icon::output( 'right-chevron' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<div class="evge-right-column">
			<div class="evge-content">