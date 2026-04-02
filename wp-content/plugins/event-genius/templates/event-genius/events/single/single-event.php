<?php
/**
 * Single Event Template
 * 
 * This template displays a single event page including:
 * - Event header
 * - Main content area
 * - Event details
 * - Footer
 * 
 * @var int    $event_id The ID of the current event
 * @package WPEventGenius
 * @since 1.0.0
 */

use WPEventGenius\Common\Utils\Templater;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$templater = new Templater();
get_header();
?>

<div class="evge<?php echo esc_attr( $templater->evge_classes() ); ?>" data-evge-type="single-event">
	<div class="evge-content">
		<main id="evge-single-main" class="evge-events-single-main<?php echo esc_attr( $templater->classes() ); ?>">
			<?php if ( have_posts() ) : ?>
				<?php
				// Start the Loop.
				while ( have_posts() ) :
					the_post();
					$event_id = get_the_ID();
					include $templater->get_event_template_part( 'single-content' );
				endwhile;
				// If no content, include the "No posts found" template.
			else :
				// No posts found.
			endif;
			?>
		</main>
	</div>
</div>

<?php get_footer(); ?>
