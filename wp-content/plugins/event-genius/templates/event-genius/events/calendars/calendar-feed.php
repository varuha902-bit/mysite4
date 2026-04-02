<?php
/**
 * Calendar Feed Template
 * 
 * This template displays the main calendar feed for events. It handles:
 * - Rendering the calendar layout (grid or list view)
 * - Displaying pagination controls
 * - Loading and displaying individual event items
 * - Handling empty states
 * 
 * @package WPEventGenius
 * @since 1.0.0
 */

use WPEventGenius\Common\Utils\Templater;
use WPEventGenius\Common\Event\EventPost;
use WPEventGenius\Common\Event\RegistrationCounter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = $calendar_display->get_settings();
$view = $settings['view'];
$events = $calendar_display->get_calendar_events();
$pagination = $settings['pagination'];
$templater = new Templater( $view );

$calendar_data = $calendar_display->get_calendar_data();

// Check if bulk registration is enabled for this calendar
$bulk_registration_enabled = $calendar_display->is_bulk_registration_enabled();
?>

<div class="evge<?php echo esc_attr( $templater->evge_classes() ); ?>" data-evge-type="shortcode">
	<div class="evge-content">
		<div class="evge-events-archive-main<?php echo esc_attr( $templater->classes() ); ?>">
			<?php if ( ! empty( $events ) ) : ?>
				<?php if ( isset( $pagination ) ) : ?>
					<div class="evge-pagination-top">
						<?php 
						EVGE()->template_manager()->get_template(
							'events/common/pagination.php',
							[
								'calendar_display' => $calendar_display
							]
						); 
						?>
					</div>
				<?php endif; ?>

				<div class="evge-event-calendar evge-<?php echo esc_attr( $view ); ?>-layout<?php echo esc_attr( $templater->calendar_classes() ); ?>">
					<?php
					// Start the Loop.
					foreach ( $events as $event ) :
						$event_post = new EventPost( $event->get_the_id() );
						$factory = new \WPEventGenius\Common\Services\RegistrationObjectFactory();
						$event_post->set_registration_counter( 
							$factory->create_registration_counter( 
								$event->get_the_id(), 
								new \WPEventGenius\Common\Database() 
							) 
						);

						if ( $view === 'grid' ) {
							EVGE()->template_manager()->get_template(
								'events/calendars/partials/grid-item.php',
								[
									'event_post' => $event_post,
									'bulk_registration_enabled' => $bulk_registration_enabled
								]
							);
						} else {
							EVGE()->template_manager()->get_template(
								'events/calendars/partials/list-item.php',
								[
									'event_post' => $event_post,
									'bulk_registration_enabled' => $bulk_registration_enabled
								]
							);
						}
					endforeach;
					?>
				</div>

				<?php if ( isset( $pagination ) ) : ?>
					<div class="evge-pagination-bottom">
						<?php 
						EVGE()->template_manager()->get_template(
							'events/common/pagination.php',
							[
								'calendar_display' => $calendar_display
							]
						); 
						?>
					</div>
				<?php endif; ?>
			<?php else : ?>
				<?php
				// If no content, include the "No posts found" template.
				EVGE()->template_manager()->get_template(
					'events/calendars/partials/none-found.php',
					[
						'has_search' => false
					]
				);
				?>
			<?php endif; ?>
		</div><!-- .evge-events-archive-main -->
	</div><!-- .evge-content -->
</div><!-- .evge -->