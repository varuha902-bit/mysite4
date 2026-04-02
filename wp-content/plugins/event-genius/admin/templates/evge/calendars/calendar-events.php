<?php

use WPEventGenius\Common\Event\EventQuery;
use WPEventGenius\Common\Displays\CalendarDisplay;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$calendar_id      = isset($_GET['calendar_id']) ? sanitize_key($_GET['calendar_id']) : 0;

$calendar_name    = '';
// If editing, get existing values
if ($calendar_id) {
    if ($calendar_id === 'default') {
        $calendar_name = __('Default (Archive)', 'event-genius');
    } else {
        $calendar = get_term($calendar_id, 'evge_calendar');
        $calendar_name = $calendar ? $calendar->name : '';
    }
} 
$calendar_display = new CalendarDisplay($calendar_id);


// Get current page and search parameters
// phpcs:ignore WordPress.Security.NonceVerification.Recommended    
$current_page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended    
$search_query = isset($_GET['event_search']) ? sanitize_text_field(wp_unslash($_GET['event_search'])) : '';
$per_page = 20;

$calendar_display->apply_overrides([
    'per_page' => $per_page,
    'page' => $current_page,
    'search' => $search_query
]);
$calendar_display->build(new EventQuery($calendar_display->get_settings()));
$events = $calendar_display->get_events();

$total_events = $calendar_display->get_event_query()->get_total_count();
$total_pages = ceil($total_events / $per_page);
if ( $calendar_display->get_settings()['view'] === 'month' ) {
    $total_pages = 1;
}
?>

<div class="wrap">
    <?php $this->builder_top($settings, $calendar_name); ?>

    <div class="evge-builder-settings-wrap">

        <!-- Search Box -->
        <div class="tablenav top">

                <?php
                if ($total_pages > 1) { ?>
                    <div class="tablenav-pages">
                        <?php
                        echo wp_kses_post(paginate_links([
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'current' => $current_page,
                            'total' => $total_pages,
                        ])); ?>
                    </div>
                <?php } ?>
            
            <div class="actions">
                <form method="get" class="evge-event-search">
                    <span class="evge-toolbar-icon">
						<svg width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg">
							<g opacity="0.7">
								<path d="M12.432 7.24711C12.432 10.404 9.87283 12.9631 6.71598 12.9631C3.55913 12.9631 1 10.404 1 7.24711C1 4.09026 3.55913 1.53113 6.71598 1.53113C9.87283 1.53113 12.432 4.09026 12.432 7.24711Z" stroke="black" stroke-width="2"/>
								<line x1="10.2374" y1="11.7251" x2="14.7069" y2="16.1947" stroke="black" stroke-width="2"/>
							</g>
						</svg>
					</span>
                    <input type="hidden" name="page" value="evge-all-events">
                    <input type="hidden" name="tab" value="calendars">
                    <input type="hidden" name="calendar_id" value="<?php echo esc_attr($calendar_id); ?>">
                    <input type="hidden" name="subtab" value="events">
                    <input type="search" 
                        name="event_search" 
                        value="<?php echo esc_attr($search_query); ?>"
                        placeholder="<?php esc_attr_e('Search events...', 'event-genius'); ?>">
                </form>
            </div>
        </div>

        <!-- Events Table -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="column-title"><?php esc_html_e('Event', 'event-genius'); ?></th>
                    <th scope="col" class="column-date"><?php esc_html_e('Date', 'event-genius'); ?></th>
                    <th scope="col" class="column-categories"><?php esc_html_e('Categories', 'event-genius'); ?></th>
                    <th scope="col" class="column-tags"><?php esc_html_e('Tags', 'event-genius'); ?></th>
                    <th scope="col" class="column-venue"><?php esc_html_e('Venue', 'event-genius'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($events)) : ?>
                    <?php foreach ($events as $event) : 
                        // Get recurrence info
                        $is_recurrence = get_post_meta($event->get_the_id(), 'evge_is_recurrence', true);
                        
                        // Build edit link
                        $edit_link = get_edit_post_link($is_recurrence ? $is_recurrence : $event->get_the_id());
                        if ($is_recurrence) {
                            $edit_link = add_query_arg('recurrence_id', $event->get_the_id(), $edit_link);
                        }
                        ?>
                        <tr>
                            <td class="column-title">
                                <a href="<?php echo esc_url($edit_link); ?>"
                                class="row-title">
                                    <?php echo esc_html($event->get_the_title()); ?>
                                </a>
                            </td>
                            <td class="column-date">
                                <?php 
                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                echo $event->recurrence_display();
                                echo esc_html($event->get_the_date_summary()); 
                                ?>
                            </td>
                            <td class="column-categories">
                                <?php 
                                $categories = $event->get_categories();
                                $category_names = array_map(function($cat) {
                                    return esc_html($cat['label']);
                                }, $categories);
                                echo esc_html(implode(', ', $category_names));
                                ?>
                            </td>
                            <td class="column-tags">
                                <?php 
                                $tags = $event->get_tags();
                                $tag_names = array_map(function($tag) {
                                    return esc_html($tag['label']);
                                }, $tags);
                                echo esc_html(implode(', ', $tag_names));
                                ?>
                            </td>
                            <td class="column-venue">
                                <?php 
                                $venue_id = $event->get_the_venue_id();
                                echo $venue_id ? esc_html(get_the_title($venue_id)) : '';
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="no-items">
                            <?php esc_html_e('No events found.', 'event-genius'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Bottom Navigation -->
        <?php
        if ($total_pages > 1) { ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    echo wp_kses_post(paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'current' => $current_page,
                        'total' => $total_pages,
                    ])); ?>
                </div>    
            </div>
        <?php } ?>
        
    </div>
</div>