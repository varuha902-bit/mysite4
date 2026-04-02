<?php
/**
 * Single Organizer Template
 * 
 * This template displays a single organizer page including:
 * - Organizer profile information
 * - Contact details (email, phone, website)
 * - Social media links
 * - Featured image
 * - Upcoming and past events
 * 
 * @var int    $organizer_post The organizer post object
 * @package WPEventGenius
 * @since 1.0.0
 */

use WPEventGenius\Common\Utils\Templater;
use WPEventGenius\Common\Utils\Icon;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$templater = new Templater();
$organizer_post = new \WPEventGenius\Common\Event\OrganizerPost( get_the_ID() );
get_header();
?>

<div class="evge<?php echo esc_attr( $templater->evge_classes() ); ?>" data-evge-type="single-organizer">
	<div class="evge-content">
		<main id="evge-single-main" class="evge-organizers-single-main<?php echo esc_attr( $templater->classes() ); ?>">
			<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
				
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
						<?php
						$links = $organizer_post->get_the_links();
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
					</div>

					<div class="evge-right-column">
						<h1 class="evge-title"><?php echo esc_html( get_the_title() ); ?></h1>
						
						<div class="evge-content">
							<?php 
							$content = get_the_content();
							if (!empty($content)) {
								the_content();
							} else {
								$summary = $organizer_post->get_the_summary();
								if (!empty($summary)) {
									echo wp_kses_post($summary);
								}
							}
							?>
						</div>

						<div class="evge-organizer-events">
							<?php
							$upcoming_events = $organizer_post->events();
							$past_events = $organizer_post->events( array( 'past' => true ) );
							if (!empty($upcoming_events)) :
								?>
								<h3><?php esc_html_e('Upcoming Events', 'event-genius'); ?></h3>
							<?php
								EVGE()->template_manager()->get_template('events/common/event-list-item-summary.php', [
									'events' => $upcoming_events
								]);
							endif;

							if (!empty($past_events)) : ?>
								<h3><?php esc_html_e('Past Events', 'event-genius'); ?></h3>
							<?php
								EVGE()->template_manager()->get_template('events/common/event-list-item-summary.php', [
									'events' => $past_events
								]);
							endif;

							if (empty($upcoming_events) && empty( $past_events )) : ?>
								<p><?php esc_html_e('No events for this organizer. Check back soon!', 'event-genius'); ?></p>
							<?php endif; ?>

						</div>
					</div>
				</div>

			<?php endwhile; endif; ?>
		</main>
	</div>
</div>

<?php get_footer(); ?>
