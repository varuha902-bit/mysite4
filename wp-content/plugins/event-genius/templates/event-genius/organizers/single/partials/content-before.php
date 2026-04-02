<?php
/**
 * Organizer Content Before Template
 * 
 * This template displays the initial content sections of an organizer including:
 * - Featured image
 * - Organizer title
 * - Contact information
 * - Social media links
 * 
 * @var int    $organizer_id The ID of the current organizer
 * @package WPEventGenius
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\Common\Event\OrganizerPost;
use WPEventGenius\Common\Utils\Icon;
use WPEventGenius\Common\Utils\Templater;

$organizer_post = new OrganizerPost( $organizer_id );
$templater = new Templater();
?>
			
<div class="evge-profile-content">
	<div class="evge-left-column">
		<?php if ($organizer_post->should_show_section('featured_image')): ?>
			<div class="evge-featured-image">
				<?php 
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $organizer_post->get_the_featured_image('medium'); ?>
			</div>
		<?php endif; ?>
		
		<div class="evge-info">
			<?php 
			$has_contact_info = $organizer_post->get_the_email() || $organizer_post->get_the_phone() || $organizer_post->get_the_website();
			if ($has_contact_info) : ?>
				<h3><?php esc_html_e('Contact Info', 'event-genius'); ?></h3>
			<?php endif; ?>

			<?php if ($organizer_post->get_the_email()) : ?>
				<p class="evge-contact-email">
					<span class="evge-icon">
						<?php Icon::output( 'email' ); ?>
					</span>
					<a href="mailto:<?php echo esc_attr($organizer_post->get_the_email()); ?>">
						<?php echo esc_html($organizer_post->get_the_email()); ?>
					</a>
				</p>
			<?php endif; ?>

			<?php if ($organizer_post->get_the_phone()) : ?>
				<p class="evge-contact-phone">
					<span class="evge-icon">
						<?php Icon::output( 'phonefield' ); ?>
					</span>
					<a href="tel:<?php echo esc_attr($organizer_post->get_the_phone()); ?>">
						<?php echo esc_html($organizer_post->get_the_phone()); ?>
					</a>
				</p>
			<?php endif; ?>

			<?php if ($organizer_post->get_the_website()) : ?>
				<p class="evge-contact-website">
					<span class="evge-icon">
						<?php Icon::output( 'link' ); ?>
					</span>
					<a href="<?php echo esc_url($organizer_post->get_the_website()); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html($organizer_post->get_the_website()); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>
		
		<div class="evge-social-links">
			<?php
			$links = $organizer_post->get_the_links();
			if ( ! empty( $links ) ) : ?>
				<div class="evge-follow-row">
					<?php foreach ( $links as $link_type => $link_url ) : ?>
						<a href="<?php echo esc_url( $link_url ); ?>" target="_blank" rel="noopener noreferrer">
							<?php Icon::output( $link_type ); ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<div class="evge-right-column">
		<?php if ($organizer_post->should_show_section('title')): ?>
			<h1 class="evge-title"><?php echo esc_html( get_the_title() ); ?></h1>
		<?php endif; ?>
		
		<div class="evge-content">

