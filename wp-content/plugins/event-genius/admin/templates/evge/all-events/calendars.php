<?php
// Get current page and search query
use WPEventGenius\Common\Event\EventQuery;
use WPEventGenius\Common\Displays\CalendarDisplay;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$current_page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$search_query = isset($_GET['calendar_search']) ? sanitize_text_field(wp_unslash($_GET['calendar_search'])) : '';
$per_page     = 6;

// Base admin URL
$base_url = admin_url('admin.php?page=evge-all-events&tab=calendars');

$offset = ($current_page - 1) * $per_page;
if ( $current_page === 1 ) {
	$args_per_page = $per_page - 1;
} else {
	$offset = $offset - 1;
	$args_per_page = $per_page;
}

// Prepare arguments for get_terms
$args = [
	'taxonomy' => 'evge_calendar',
	'hide_empty' => false,
	'number' => $args_per_page,
	'offset' => $offset,
	'orderby' => 'term_id',
	'order' => 'ASC',
];

// Add search if present
if (!empty($search_query)) {
	$args['name__like'] = $search_query;
}

// Get calendars and total count
$calendars = get_terms($args);

// Add default calendar to the beginning of the array if we're on the first page
if ($current_page === 1) {
    $default_calendar = (object) [
        'term_id' => 'default',
        'name' => __('Default (Archive)', 'event-genius'),
        'is_default' => true
    ];
    array_unshift($calendars, $default_calendar);
}

$total_calendars = 1 + wp_count_terms([ // 1 for default calendar
	'taxonomy' => 'evge_calendar',
	'hide_empty' => false,
	'name__like' => !empty($search_query) ? $search_query : ''
]);

$total_pages = ceil($total_calendars / $per_page);
?>

<div class="wrap">

	<!-- Search Form -->
	<div class="evge-calendar-filter-bar">
		<?php if ($total_pages > 1) : ?>
			<div class="tablenav bottom">
				<div class="alignleft actions">
				</div>
				<div class="tablenav-pages">
                    <span class="pagination-links">
                        <?php
                        // First page button
                        if ($current_page > 1) {
	                        printf(
		                        '<a class="first-page button" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">&laquo;</span></a>',
		                        esc_url(remove_query_arg('paged', $base_url)),
		                        esc_html__('First page', 'event-genius')
	                        );
                        } else {
	                        echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
                        }

                        // Previous page button
                        if ($current_page > 1) {
	                        printf(
		                        '<a class="prev-page button" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">&lsaquo;</span></a>',
		                        esc_url(add_query_arg('paged', max(1, $current_page - 1), $base_url)),
		                        esc_html__('Previous page', 'event-genius')
	                        );
                        } else {
	                        echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
                        }
                        ?>

                        <span class="screen-reader-text"><?php esc_html_e('Current Page', 'event-genius'); ?></span>
                        <span id="table-paging" class="paging-input">
                            <span class="tablenav-paging-text">
                                <?php echo wp_kses_post(sprintf(
	                                '%1$s of <span class="total-pages">%2$s</span>',
	                                number_format_i18n($current_page),
	                                number_format_i18n($total_pages)
                                )); ?>
                            </span>
                        </span>

                        <?php
                        // Next page button
                        if ($current_page < $total_pages) {
	                        printf(
		                        '<a class="next-page button" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">&rsaquo;</span></a>',
		                        esc_url(add_query_arg('paged', min($total_pages, $current_page + 1), $base_url)),
		                        esc_html__('Next page', 'event-genius')
	                        );
                        } else {
	                        echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
                        }

                        // Last page button
                        if ($current_page < $total_pages) {
	                        printf(
		                        '<a class="last-page button" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">&raquo;</span></a>',
		                        esc_url(add_query_arg('paged', $total_pages, $base_url)),
		                        esc_html__('Last page', 'event-genius')
	                        );
                        } else {
	                        echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
                        }
                        ?>
                    </span>
					<span class="displaying-num">
                        <?php 
                        /* translators: %s: number of items */
                        echo wp_kses_post( sprintf(
                            _n( '%s item', '%s items', $total_calendars, 'event-genius' ),
                            number_format_i18n( $total_calendars )
                        ) ); ?>
                    </span>
				</div>
				<br class="clear">
			</div>
		<?php endif; ?>

		<div class="evge-flex-center evge-search-box">
			<form method="get" class="evge-search-form">
				<input type="hidden" name="page" value="evge-all-events">
				<input type="hidden" name="tab" value="calendars">
				<p class="search-box">
					<span class="evge-toolbar-icon">
						<svg width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg">
							<g opacity="0.7">
								<path d="M12.432 7.24711C12.432 10.404 9.87283 12.9631 6.71598 12.9631C3.55913 12.9631 1 10.404 1 7.24711C1 4.09026 3.55913 1.53113 6.71598 1.53113C9.87283 1.53113 12.432 4.09026 12.432 7.24711Z" stroke="black" stroke-width="2"/>
								<line x1="10.2374" y1="11.7251" x2="14.7069" y2="16.1947" stroke="black" stroke-width="2"/>
							</g>
						</svg>
					</span>
					<label class="screen-reader-text" for="calendar-search-input">
						<?php esc_html_e('Search Lists', 'event-genius'); ?>
					</label>
					<input type="search"
					       id="calendar-search-input"
					       name="calendar_search"
					       value="<?php echo esc_attr($search_query); ?>"
					       placeholder="<?php esc_attr_e('Search', 'event-genius'); ?>">
				</p>
			</form>
		</div>

	</div>

	<div class="evge-items-grid">
		<?php if (!empty($calendars) && !is_wp_error($calendars)) : ?>
			<?php foreach ($calendars as $calendar) :
				// Get calendar settings
				$settings = $calendar->is_default ? ['color' => '#000000'] : get_term_meta($calendar->term_id, 'evge_calendar_settings', true);
				$color = isset($settings['color']) ? $settings['color'] : 'transparent';

				$calendar_display = new CalendarDisplay($calendar->term_id);
				$calendar_display->apply_overrides([
					'num' => 4,
				]);
				$calendar_display->build(new EventQuery($calendar_display->get_settings()));
				$events = $calendar_display->get_events();
				$total_events = $calendar_display->get_event_query()->get_total_count();
				?>
				<div class="evge-item-card">
					<div class="evge-item-card-top evge-flex-center evge-item-card-section">
						<div class="evge-flex-center evge-item-card-header">
							<h2 class="evge-item-title">
								<span class="evge-calendar-color-dot" style="background-color: <?php echo esc_attr($color); ?>"></span>
								<?php echo esc_html($calendar->name); ?>
							</h2>

							<?php if (! $calendar->is_default) : ?>
							<div class="evge-calendar-id">
								<?php esc_html_e('ID:', 'event-genius'); ?> <?php echo esc_html($calendar->term_id); ?>
							</div>
							<?php endif; ?>
						</div>

						<div class="evge-item-actions">
							<div class="evge-tooltip-wrap evge-button-tooltip">
								<a href="<?php echo esc_url($base_url . '&calendar_id=' . $calendar->term_id); ?>"
								   class="evge-admin-secondary-button button evge-flex-center">
									<span class="evge-icon-text">
										<?php 
										// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										echo \WPEventGenius\Common\Utils\Icon::get('edit'); ?>
									</span>
								</a>
								<div class="evge-tooltip">
									<p><?php esc_html_e('Edit', 'event-genius'); ?></p>
								</div>
							</div>
							<?php 
							$embed_json_settings = array('width' => 'medium');
							$embed_json_array = [
								'action' => 'evge_get_calendar_embed_instructions',
								'calendar_id' => $calendar->term_id,
							];
							?>
							<div class="evge-tooltip-wrap evge-button-tooltip">
								<button type="button"
										class="evge-admin-secondary-button button evge-modal-trigger"
										data-calendar-id="<?php echo esc_attr($calendar->term_id); ?>"
										data-evge-modal-content="ajax" 
										data-evge-modal-settings="<?php echo esc_attr(wp_json_encode($embed_json_settings)); ?>" 
										data-evge-ajax="<?php echo esc_attr(wp_json_encode($embed_json_array)); ?>">
									<span class="evge-icon-text">
										<?php 
										// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										echo \WPEventGenius\Common\Utils\Icon::get('code'); ?>
									</span>
								</button>
								<div class="evge-tooltip">
									<p><?php esc_html_e('Embed', 'event-genius'); ?></p>
								</div>
							</div>
							<?php if (!$calendar->is_default) : // Don't allow deleting default calendar ?>
							<?php
							$delete_url = wp_nonce_url(
								add_query_arg([
									'action' => 'evge_delete_calendar',
									'calendar_id' => $calendar->term_id,
								], $base_url),
								'evge_delete_calendar_' . $calendar->term_id,
								'evge_delete_nonce'
							);
							?>
							<div class="evge-tooltip-wrap evge-button-tooltip evge-delete-button-wrap">
								<?php
								$edit_json_settings = array('width' => 'narrow');
								$edit_json_array = [
									'action' => 'evge_get_calendar_content',
									'security' => wp_create_nonce('evge_calendar_builder'),
									'calendar_id' => $calendar->term_id,
								];

								$delete_json_array = [
									'action' => 'evge_delete_calendar_modal',
									'security' => wp_create_nonce('evge_calendar_builder'),
									'calendar_id' => $calendar->term_id,
								];?>
								<a href="<?php echo esc_url($delete_url); ?>" 
								class="evge-admin-secondary-button button evge-flex-center evge-delete-button evge-modal-trigger"
								data-evge-modal-content="ajax"
								data-evge-modal-settings='<?php echo esc_attr(wp_json_encode($edit_json_settings)); ?>'
								data-evge-ajax='<?php echo esc_attr(wp_json_encode($delete_json_array)); ?>'>
									<span class="evge-icon-text">
										<?php 
										// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										echo \WPEventGenius\Common\Utils\Icon::get('trash'); ?>
									</span>
								</a>
								<div class="evge-tooltip">
									<p><?php esc_html_e('Delete', 'event-genius'); ?></p>
								</div>
							</div>
							<?php endif; ?>
							
						</div>

						<div class="evge-embed-code" style="display: none;">
							<code>[event_genius_calendar id="<?php echo esc_attr($calendar->term_id); ?>"]</code>
						</div>
					</div>

					<div class="evge-item-card-bottom evge-item-card-section">
						<?php if (!empty($events)) : ?>
							<ul class="evge-events-preview">
								<?php
								$count = 0;
								foreach ($events as $event) :
									if ($count >= 3) {
										break;
									}
									?>
									<li class="evge-preview-event">
										<a class="evge-preview-title" href="<?php echo esc_url(get_the_permalink($event->get_the_id())); ?>">
											<?php echo esc_html($event->get_the_title()); ?>
										</a>
										<div class="evge-preview-date">
											<?php 
											// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											echo $event->recurrence_display() . esc_html($event->get_the_date_summary()); ?>
											<?php if($event->get_the_venue_title()) : ?>
												<span class="evge-preview-venue"><?php echo esc_html($event->get_the_venue_title()); ?></span>
											<?php endif; ?>
										</div>
									</li>
									<?php
									$count++;
								endforeach;
								wp_reset_postdata();
								?>
							</ul>
							<?php if ($total_events > 3) : ?>
								<p class="evge-view-all">
									<a href="<?php echo esc_url($base_url . '&action=view&calendar_id=' . $calendar->term_id); ?>">
										+
										<?php
										echo intval($total_events - 3) . ' ';
										esc_html_e('More Events', 'event-genius');
										?>
									</a>
								</p>
							<?php endif; ?>
						<?php else : ?>
							<p class="evge-no-events">
								<?php esc_html_e('No upcoming events', 'event-genius'); ?>
							</p>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		<?php else : ?>
			<div class="evge-no-items">
				<p><?php esc_html_e('No calendars found. Create your first calendar!', 'event-genius'); ?></p>
			</div>
		<?php endif; ?>
	</div>

</div>

