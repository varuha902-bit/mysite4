<?php
/**
 * Calendar Template
 * 
 * This template serves as the main container for the calendar display. It includes:
 * - Calendar wrapper with dynamic content area
 * - Optional filter bar for event filtering
 * - Dynamic content area that switches between month and list views
 * - Event tooltip container for hover details
 * - Day events modal for expanded event views
 * 
 * @var \WPEventGenius\Common\Displays\CalendarDisplay $calendar_display The calendar display instance
 * @package WPEventGenius
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\Common\Utils\Templater;

$templater = new Templater();

// Get settings from calendar display
$settings = $calendar_display->get_settings();
$view = $settings['view'];
$current_month = $settings['month'];
$calendar_id = $calendar_display->get_calendar_id();
$events = $calendar_display->get_calendar_events();
?>

<?php
// Check if bulk registration is enabled for this calendar
$bulk_registration_enabled = $calendar_display->is_bulk_registration_enabled();
?>
<div class="evge-calendar-wrapper evge<?php echo esc_attr( $templater->evge_classes() ); ?>" 
     data-current-month="<?php echo esc_attr( $current_month ); ?>" 
     data-calendar-id="<?php echo esc_attr( $calendar_id ); ?>" 
     data-view="<?php echo esc_attr( $view ); ?>" 
     data-bulk-registration-enabled="<?php echo $bulk_registration_enabled ? '1' : '0'; ?>"
     data-rendered-at="<?php echo esc_attr( time() ); ?>"
     role="region" 
     aria-label="<?php esc_attr_e( 'Event Calendar', 'event-genius' ); ?>">

    <?php if ( $calendar_display->is_bulk_registration_enabled() ) : ?>
        <?php 
        // Request the bulk registration panel (will be added to footer once)
        add_filter( 'evge_bulk_registration_panel_needed', '__return_true' );
        add_filter( 'evge_bulk_registration_panel_data', function( $data ) use ( $calendar_display ) {
            if ( empty( $data ) ) {
                $data = array();
            }
            $data['calendar_display'] = $calendar_display;
            return $data;
        } );
        ?>
    <?php endif; ?>

    <?php if ( ! isset( $settings['show_toolbar'] ) || $settings['show_toolbar'] === 'enabled' ) : ?>
        <?php EVGE()->template_manager()->get_template( 'events/calendars/partials/filter-bar.php', [
            'calendar_display' => $calendar_display
        ] ); ?>
    <?php endif; ?>

    <div class="evge-calendar-dynamic-content" role="main">
        <?php 
        if ( $view === 'month' ) {
            EVGE()->template_manager()->get_template( 'events/calendars/calendar-month.php', [
                'calendar_display' => $calendar_display
            ] );
        } else {
            if ( empty( $events ) ) {
                EVGE()->template_manager()->get_template( 'events/calendars/partials/none-found.php', [
                    'has_search' => $calendar_display->has_search()
                ] );
            } else {
                EVGE()->template_manager()->get_template( 'events/calendars/calendar-feed.php', [
                    'calendar_display' => $calendar_display
                ] );
            }
        }
        ?>
    </div>

    <!-- Event Details Tooltip -->
    <div class="evge-tooltipster-container">
        <div class="evge-event-tooltip" role="tooltip" aria-hidden="true" style="display: none;"></div>
    </div>

    <div class="evge-day-events-calendar" role="dialog" aria-modal="true" aria-hidden="true" style="display: none;">
    </div>

</div>