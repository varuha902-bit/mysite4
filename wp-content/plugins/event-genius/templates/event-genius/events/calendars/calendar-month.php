<?php
/**
 * Month Calendar View Template
 * 
 * This template displays the month view of the calendar. It includes:
 * - Month navigation controls
 * - Month/year selector dropdown
 * - Calendar grid with weekday headers
 * - Day cells with event listings
 * - Event cards with time and title
 * - "Show more" functionality for days with many events
 * 
 * @var \WPEventGenius\Common\Displays\CalendarDisplay $calendar_display The calendar display instance
 * @package WPEventGenius
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\Common\Utils\Icon;
use WPEventGenius\Common\Utils\Settings;
use WPEventGenius\Common\Utils\DateFormatter;

// Get settings and data from calendar display
$settings = $calendar_display->get_settings();
$calendar_data = $calendar_display->get_calendar_data();
$current_month = $settings['month'];
$max_events = $settings['events_per_day'] ?? 3;
$start_of_week = Settings::get( 'start_of_the_week' );
$first_weekday = DateFormatter::get_weekday_number( $start_of_week );
?>

<div class="evge-month-calendar-wrap" role="application" aria-label="<?php esc_attr_e( 'Month Calendar View', 'event-genius' ); ?>">
	<div class="evge-month-calendar-top">
		<button class="evge-nav-prev evge-nav-button" data-action="prev" aria-label="<?php esc_attr_e( 'Previous month', 'event-genius' ); ?>">
			<?php Icon::output( 'left-chevron' ); ?>
		</button>

		<div class="evge-month-selector">
			<div class="evge-month-calendar-month-name" data-action="month-select" role="button" aria-expanded="false" aria-controls="evge-month-dropdown">
				<span class="evge-month-name"><?php echo esc_html( $calendar_display->get_formatted_date() ); ?></span>
				<span class="evge-month-dropdown-arrow" aria-hidden="true">
					<?php Icon::output( 'down-chevron' ); ?>
				</span>
			</div>

			<div class="evge-month-dropdown" id="evge-month-dropdown" role="menu" aria-hidden="true">
				<div class="evge-month-dropdown-header">
					<button class="evge-year-prev" aria-label="<?php esc_attr_e( 'Previous year', 'event-genius' ); ?>">
						<?php Icon::output( 'left-chevron' ); ?>
					</button>
					<span class="evge-year-display" role="status"></span>
					<button class="evge-year-next" aria-label="<?php esc_attr_e( 'Next year', 'event-genius' ); ?>">
						<?php Icon::output( 'right-chevron' ); ?>
					</button>
				</div>

				<div class="evge-month-grid" role="menu">
					<?php
					// Generate month names starting with January
					for ( $month = 1; $month <= 12; $month++ ) {
						printf(
							'<div class="evge-month-option%s" data-month="%d" role="menuitem">%s</div>',
							$month === (int) date( 'm', strtotime( $current_month ) ) ? ' evge-active' : '',
							esc_attr( $month ),
							esc_html( wp_date( 'M', strtotime( "2024-{$month}-03" ) ) )
						);
					}
					?>
				</div>
			</div>
		</div>

		<button class="evge-nav-next evge-nav-button" data-action="next" aria-label="<?php esc_attr_e( 'Next month', 'event-genius' ); ?>">
			<?php Icon::output( 'right-chevron' ); ?>
		</button>
	</div>

	<div class="evge-calendar-grid" role="grid">
		<div class="evge-calendar-headers" role="row">
			<?php
			// Get localized abbreviated weekday names using timezone-aware calculation
			$weekdays = DateFormatter::get_weekday_names( $first_weekday );

			foreach ( $weekdays as $day ) : ?>
				<div class="evge-calendar-header" role="columnheader">
					<?php echo esc_html( $day ); ?>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="evge-calendar-days" role="rowgroup">
			<?php foreach ( $calendar_data['weeks'] as $week ) : ?>
				<div class="evge-calendar-week" role="row">
					<?php foreach ( $week as $day ) :
						$day_classes = [ 'evge-calendar-day' ];
						if ( $day['is_other_month'] ) {
							$day_classes[] = 'evge-other-month';
						}
						if ( $day['is_today'] ) {
							$day_classes[] = 'evge-today';
						}
						if ( ! empty( $day['events'] ) ) {
							$day_classes[] = 'evge-has-events';
						}
						?>
						<div class="<?php echo esc_attr( implode( ' ', $day_classes ) ); ?>" 
							 data-date="<?php echo esc_attr( $day['date'] ); ?>" 
							 role="gridcell" 
							 aria-selected="false">
							<div class="evge-day-header">
								<span class="evge-day-date"><?php echo esc_html( $day['day_number'] ); ?></span>
							</div>

							<?php if ( ! empty( $day['events'] ) ) : ?>
								<div class="evge-day-events" role="list">
									<?php
									$total_events = count( $day['events'] );

									foreach ( $day['events'] as $index => $event ) :
										$event_start_date = new DateTime( $event->get_the_start_date_raw() );
										$event_end_date = new DateTime( $event->get_the_end_date_raw() );
										$current_date = new DateTime( $day['date'] );

										// Get the start of the current week
										$week_start = clone $current_date;
										$current_weekday = (int) $week_start->format( 'w' );
										$days_to_subtract = ( $current_weekday - $first_weekday + 7 ) % 7;
										if ( $days_to_subtract > 0 ) {
											$week_start->modify( '-' . $days_to_subtract . ' days' );
										}

										// Get the end of the current week
										$week_end = clone $current_date;
										$current_weekday = (int) $week_end->format( 'w' );
										$days_to_add = ( ( $first_weekday + 6 ) % 7 ) - $current_weekday;
										if ( $days_to_add < 0 ) {
											$days_to_add += 7;
										}
										if ( $days_to_add > 0 ) {
											$week_end->modify( '+' . $days_to_add . ' days' );
										}

										// Determine if this is the start of a week-segment for this event
										$is_week_segment_start = false;

										if ( $event_start_date->format( 'Y-m-d' ) === $current_date->format( 'Y-m-d' ) ) {
											// This is the actual start of the event
											$is_week_segment_start = true;
										} elseif ( $current_weekday === $first_weekday &&
												$event_start_date < $current_date &&
												$event_end_date >= $current_date ) {
											// This is the start of a new week for an ongoing event
											$is_week_segment_start = true;
										}

										// Calculate days remaining in current week
										$days_in_week = min(
											$event_end_date->diff( $current_date )->days + 1,
											$week_end->diff( $current_date )->days + 1
										);

										$event_classes = [ 'evge-event-card' ];
										if ( $is_week_segment_start ) {
											$event_classes[] = 'evge-event-start';
											$event_classes[] = 'evge-span-' . $days_in_week;
										} else {
											$event_classes[] = 'evge-event-continuation';
										}

										if ( $index >= $max_events ) {
											$event_classes[] = 'evge-event-hidden';
										}

										// Add tag classes
										foreach ( $event->get_tags() as $tag ) {
											$event_classes[] = 'evge-tag-' . sanitize_html_class( $tag['slug'] );
										}

										// Add category classes
										foreach ( $event->get_categories() as $category ) {
											$event_classes[] = 'evge-category-' . sanitize_html_class( $category['slug'] );
										}
										?>
										<a href="<?php echo esc_url( $event->get_the_permalink() ); ?>" 
										   class="<?php echo esc_attr( implode( ' ', $event_classes ) ); ?>"
										   data-event-id="<?php echo esc_attr( $event->get_the_id() ); ?>"
										   role="listitem"
										   aria-label="<?php echo esc_attr( sprintf(
											   /* translators: 1: Event title, 2: Event start time */
											   __( '%1$s at %2$s', 'event-genius' ),
											   $event->get_the_title(),
											   $event->get_the_start_time( true )
										   ) ); ?>"
										   style="--event-color: <?php echo esc_attr( $event->get_the_color() ); ?>">
											<span class="evge-event-card-container">
												<span class="evge-card-event-title"><?php echo esc_html( $event->get_the_title() ); ?></span>
												<?php if ( $event->get_the_start_time() ) : ?>
													<span class="evge-event-time"><?php echo esc_html( $event->get_the_start_time( true ) ); ?></span>
												<?php endif; ?>
											</span>
										</a>
									<?php endforeach;

									if ( $total_events > $max_events ) : ?>
										<button class="evge-show-more-events" 
												data-count="<?php echo esc_attr( $total_events - $max_events ); ?>"
												role="button"
												aria-expanded="false"
												aria-controls="evge-hidden-events-<?php echo esc_attr( $day['date'] ); ?>">
											<?php 
											/* translators: %s: number of additional events */
											echo esc_html( sprintf(
												_n( '+%s more', '+%s more', $total_events - $max_events, 'event-genius' ),
												number_format_i18n( $total_events - $max_events )
											) ); ?>
										</button>
									<?php endif; ?>

									<span class="evge-calendar-event-indicator" aria-hidden="true">
										<span class="evge-event-dot"></span>
									</span>
								</div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>