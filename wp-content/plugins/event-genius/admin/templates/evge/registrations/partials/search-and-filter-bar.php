<?php
use WPEventGenius\Common\Utils\DateFormatter;
use WPEventGenius\Common\Utils\EvgeDateTime;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$params     = $page->get_sanitized_params();
$page_param = ! empty( $params['page'] ) ? $params['page'] : 'evge-all-events';

$view = ! empty( $params['view'] ) ? $params['view'] : get_user_meta( get_current_user_id(), 'evge_all_events_view', true );
$range_filter = ! empty( $params['qtype'] ) ? $params['qtype'] : 'upcoming';
$post_status = ! empty( $params['post_status'] ) ? $params['post_status'] : 'publish';
$registration_status = ! empty( $params['registration_status'] ) ? $params['registration_status'] : 'active';
$date_filter = ! empty( $params['start'] ) ? $params['start'] : wp_date( 'Y-m-d' );
$event_registration_status_filter = ! empty( $params['with'] ) ? $params['with'] : 'either';
$search_term = ! empty( $params['s'] ) ? $params['s'] : '';
$search_term_type = ! empty( $params['stype'] ) ? $params['stype'] : 'events';
$category = '';
?>
<div class="evge-toolbar wp-filter evge-registration-toolbar">
    <div class="evge-toolbar-inner evge-no-view-select">
        <div class="evge-toolbar-secondary evge-flex-center evge-toolbar-section">
            <input type="hidden" name="page" value="<?php echo esc_attr( $page_param ); ?>">
            <input type="hidden" name="view" value="<?php echo esc_attr( $view ); ?>" />
            <div class="evge-toolbar-icon">
                <svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <line opacity="0.8" y1="1.92857" x2="16.1905" y2="1.92857" stroke="black"/>
                    <line opacity="0.8" y1="14.881" x2="16.1905" y2="14.881" stroke="black"/>
                    <line opacity="0.8" y1="8.40476" x2="16.1905" y2="8.40476" stroke="black"/>
                    <g opacity="0.8">
                        <circle cx="3.64295" cy="2.02381" r="2.02381" fill="black"/>
                        <circle cx="3.64295" cy="2.02381" r="2.02381" fill="black"/>
                        <circle cx="3.64295" cy="2.02381" r="2.02381" fill="black"/>
                        <circle cx="3.64295" cy="2.02381" r="2.02381" fill="black"/>
                    </g>
                    <g opacity="0.8">
                        <circle cx="8.49988" cy="8.5" r="2.02381" fill="black"/>
                        <circle cx="8.49988" cy="8.5" r="2.02381" fill="black"/>
                        <circle cx="8.49988" cy="8.5" r="2.02381" fill="black"/>
                        <circle cx="8.49988" cy="8.5" r="2.02381" fill="black"/>
                    </g>
                    <g opacity="0.8">
                        <circle cx="12.5477" cy="14.9762" r="2.02381" fill="black"/>
                        <circle cx="12.5477" cy="14.9762" r="2.02381" fill="black"/>
                        <circle cx="12.5477" cy="14.9762" r="2.02381" fill="black"/>
                        <circle cx="12.5477" cy="14.9762" r="2.02381" fill="black"/>
                    </g>
                </svg>
            </div>


            <input type="hidden" name="page" value="<?php echo esc_attr( $page_param ); ?>">
            <input type="hidden" name="view" value="<?php echo esc_attr( $view ); ?>" />
            <label for="evge-registrations-date" class="screen-reader-text"><?php esc_html_e( 'Sort by time', 'event-genius' ); ?></label>
            <select id="evge-registrations-date" name="qtype" class="registrations-filters">
                <option value="latest" <?php if ( $range_filter === 'latest' ) echo 'selected'; ?>><?php esc_html_e( 'View Latest', 'event-genius' ); ?></option>
                <option value="custom" <?php if ( $range_filter === 'custom' ) echo 'selected'; ?>><?php esc_html_e( 'After Custom Date', 'event-genius' ); ?></option>
            </select>

            <label for="evge-date-filter" class="screen-reader-text"><?php esc_html_e( 'Sort by event start date', 'event-genius' ); ?></label>
			<?php
			$date_filter_obj = new EvgeDateTime( new \DateTime( $date_filter, DateFormatter::timezone_object( wp_timezone_string() ) ) );
			?>
            <input type="date" id="evge-date-filter" name="start" value="<?php echo esc_attr( $date_filter_obj->format('Y-m-d' ) ); ?>" data-format="" style="<?php if ( $range_filter !== 'custom' ) echo 'display: none;'; ?>"/>

            <input type="hidden" name="with" value="either" />
			<?php if ( ! empty( $terms ) ) : ?>
                <label for="evge-registrations-cat" class="screen-reader-text"><?php esc_html_e( 'Filter by category', 'event-genius' ); ?></label>
                <select id="evge-registrations-cat" name="cat" class="registrations-filters">
                    <option value="" <?php if ( $category=== '' ) echo 'selected'; ?>><?php esc_html_e( 'any category', 'event-genius' ); ?></option>
					<?php foreach ( $terms as $term ) : ?>
                        <option value="<?php echo esc_attr( $term->term_id ); ?>" <?php if ( (int)$category=== (int)$term->term_id ) echo 'selected'; ?>><?php echo esc_html( $term->name ); ?></option>
					<?php endforeach; ?>
                </select>
			<?php endif; ?>
            <button id="evge-filter-go" type="submit" class="button evge-toolbar-button"><?php esc_html_e( 'Go', 'event-genius' ); ?></button>
        </div>
        <div class="evge-toolbar-primary search-form evge-toolbar-section">
            <div class="evge-toolbar-icon">
                <svg width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g opacity="0.7">
                        <path d="M12.432 7.24711C12.432 10.404 9.87283 12.9631 6.71598 12.9631C3.55913 12.9631 1 10.404 1 7.24711C1 4.09026 3.55913 1.53113 6.71598 1.53113C9.87283 1.53113 12.432 4.09026 12.432 7.24711Z" stroke="black" stroke-width="2"/>
                        <line x1="10.2374" y1="11.7251" x2="14.7069" y2="16.1947" stroke="black" stroke-width="2"/>
                    </g>
                </svg>

            </div>
            <label for="evge-search-input" class="screen-reader-text"><?php esc_html_e( 'Search Registrations', 'event-genius' ); ?></label>
            <input type="search" name="s" placeholder="<?php esc_html_e( 'Search', 'event-genius' ); ?>" id="evge-search-input" class="search" value="<?php echo esc_attr( $search_term ); ?>">
            <input name="stype" value="registrants" type="hidden">
            <input name="registration_status" value="<?php echo esc_attr( $registration_status ); ?>" type="hidden">
        </div>
    </div>

</div>