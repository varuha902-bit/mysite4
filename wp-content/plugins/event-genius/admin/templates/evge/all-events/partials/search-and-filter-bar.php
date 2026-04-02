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
$date_filter = ! empty( $params['start'] ) ? $params['start'] : wp_date( 'Y-m-d' );
$event_registration_status_filter = ! empty( $params['with'] ) ? $params['with'] : 'either';
$search_term = ! empty( $params['s'] ) ? $params['s'] : '';
$search_term_type = ! empty( $params['stype'] ) ? $params['stype'] : 'events';
$category = ! empty( $params['cat'] ) ? $params['cat'] : '';
$tag = ! empty( $params['tag'] ) ? $params['tag'] : '';
?>
<div class="evge-toolbar wp-filter">
    <div class="evge-toolbar-inner">
        <div class="evge-toolbar-secondary evge-flex-center">
            <div class="evge-flex-center">
                <input type="hidden" name="page" value="<?php echo esc_attr( $page_param ); ?>">
                <input type="hidden" name="view" value="<?php echo esc_attr( $view ); ?>" />
                <div class="view-switch evge-grid-view-switch">
                    <a href="<?php echo esc_url( $page->nav_link( $page_param, array( 'view' => 'list' ) ) ); ?>" class="<?php if( $view === 'list' ) echo ' current'; ?>" title="<?php esc_html_e( 'View a single column of events in a condensed list', 'event-genius' ); ?>">
                        <span class="screen-reader-text"><?php esc_html_e( 'List View', 'event-genius' ); ?></span>
                        <svg width="18" height="17" viewBox="0 0 18 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g>
                                <rect x="4" width="14" height="2" fill="white"/>
                                <rect x="4" width="14" height="2" fill="white"/>
                                <rect x="4" width="14" height="2" fill="white"/>
                                <rect x="4" y="5" width="14" height="2" fill="white"/>
                                <rect x="4" y="5" width="14" height="2" fill="white"/>
                                <rect x="4" y="5" width="14" height="2" fill="white"/>
                                <rect x="4" y="10" width="14" height="2" fill="white"/>
                                <rect x="4" y="10" width="14" height="2" fill="white"/>
                                <rect x="4" y="10" width="14" height="2" fill="white"/>
                                <rect x="4" y="15" width="14" height="2" fill="white"/>
                                <rect x="4" y="15" width="14" height="2" fill="white"/>
                                <rect x="4" y="15" width="14" height="2" fill="white"/>
                                <rect width="2" height="2" fill="white"/>
                                <rect width="2" height="2" fill="white"/>
                                <rect width="2" height="2" fill="white"/>
                                <rect y="5" width="2" height="2" fill="white"/>
                                <rect y="5" width="2" height="2" fill="white"/>
                                <rect y="5" width="2" height="2" fill="white"/>
                                <rect y="10" width="2" height="2" fill="white"/>
                                <rect y="10" width="2" height="2" fill="white"/>
                                <rect y="10" width="2" height="2" fill="white"/>
                                <rect y="15" width="2" height="2" fill="white"/>
                                <rect y="15" width="2" height="2" fill="white"/>
                                <rect y="15" width="2" height="2" fill="white"/>
                            </g>
                        </svg>

                    </a>
                    <a href="<?php echo esc_url( $page->nav_link( $page_param, array( 'view' => 'grid' ) ) ); ?>" class="<?php if( $view === 'grid' ) echo ' current'; ?>" title="<?php esc_html_e( 'View two columns of event cards in a grid', 'event-genius' ); ?>">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="11" width="9" height="9" rx="2" fill="white"/>
                            <rect width="9" height="9" rx="2" fill="white"/>
                            <rect y="11" width="9" height="9" rx="2" fill="white"/>
                            <rect x="11" y="11" width="9" height="9" rx="2" fill="white"/>
                        </svg>
                        <span class="screen-reader-text"><?php esc_html_e( 'Grid View', 'event-genius' ); ?></span>
                    </a>
                </div>
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
                <label for="evge-registrations-date" class="screen-reader-text"><?php esc_html_e( 'Sort by time', 'event-genius' ); ?></label>
                <select id="evge-registrations-date" name="qtype" class="registrations-filters">
                    <option value="upcoming" <?php if ( $range_filter === 'upcoming' ) echo 'selected'; ?>><?php esc_html_e( 'View Upcoming', 'event-genius' ); ?></option>
                    <option value="cur" <?php if ( $range_filter === 'cur' ) echo 'selected'; ?>><?php esc_html_e( 'View Current', 'event-genius' ); ?></option>
                    <option value="past" <?php if ( $range_filter === 'past' ) echo 'selected'; ?>><?php esc_html_e( 'View Past', 'event-genius' ); ?></option>
                    <option value="custom" <?php if ( $range_filter === 'custom' ) echo 'selected'; ?>><?php esc_html_e( 'After Custom Date', 'event-genius' ); ?></option>
                    <option value="all" <?php if ( $range_filter === 'all' ) echo 'selected'; ?>><?php esc_html_e( 'View All', 'event-genius' ); ?></option>
                </select>
                <label for="evge-date-filter" class="screen-reader-text"><?php esc_html_e( 'Sort by event start date', 'event-genius' ); ?></label>
	            <?php
	            $date_filter_obj = new EvgeDateTime( new \DateTime( $date_filter, DateFormatter::timezone_object( wp_timezone_string() ) ) );
	            ?>
                <input type="date" id="evge-date-filter" name="start" value="<?php echo esc_attr( $date_filter_obj->format('Y-m-d' ) ); ?>" data-format="" style="<?php if ( $range_filter !== 'custom' ) echo 'display: none;'; ?>"/>
                <input type="hidden" name="with" value="either" />

                <label for="evge-registrations-with" class="screen-reader-text"><?php esc_html_e( 'Sort by registration enabled', 'event-genius' ); ?></label>
                <select id="evge-registrations-with" name="with" class="registrations-filters">
                    <option value="either" <?php if ( $event_registration_status_filter === 'either' ) echo 'selected'; ?>><?php esc_html_e( 'All', 'event-genius' ); ?></option>
                    <option value="with" <?php if ( $event_registration_status_filter === 'with' ) echo 'selected'; ?>><?php esc_html_e( 'With Registration', 'event-genius' ); ?></option>
                </select>

	            <?php
	            $cats = get_terms(EVGE_EVENT_CATEGORY_TYPE);

	            if ( ! empty( $cats ) ) : ?>
                    <label for="evge-registrations-cat" class="screen-reader-text"><?php esc_html_e( 'Filter by category', 'event-genius' ); ?></label>
                    <select id="evge-registrations-cat" name="cat" class="registrations-filters">
                        <option value="" <?php if ( $category === '' ) echo 'selected'; ?>><?php esc_html_e( 'any category', 'event-genius' ); ?></option>
			            <?php foreach ( $cats as $term ) : ?>
                            <option value="<?php echo esc_attr( $term->term_id ); ?>" <?php if ( (int) $category === (int)$term->term_id ) echo 'selected'; ?>><?php echo esc_html( $term->name ); ?></option>
			            <?php endforeach; ?>
                    </select>
	            <?php endif; ?>

	            <?php
	            $tags = get_terms(EVGE_EVENT_TAG_TYPE);
	            if ( ! empty( $tags ) ) : ?>
                    <label for="evge-registrations-tag" class="screen-reader-text"><?php esc_html_e( 'Filter by tag', 'event-genius' ); ?></label>
                    <select id="evge-registrations-tag" name="tag" class="registrations-filters">
                        <option value="" <?php if ( $tag === '' ) echo 'selected'; ?>><?php esc_html_e( 'any tag', 'event-genius' ); ?></option>
			            <?php foreach ( $tags as $term ) : ?>
                            <option value="<?php echo esc_attr( $term->term_id ); ?>" <?php if ( (int)$tag === (int)$term->term_id ) echo 'selected'; ?>><?php echo esc_html( $term->name ); ?></option>
			            <?php endforeach; ?>
                    </select>
	            <?php endif; ?>

                <button id="evge-filter-go" type="submit" class="button evge-toolbar-button"><?php esc_html_e( 'Go', 'event-genius' ); ?></button>
            </div>
        </div>
        <div class="evge-toolbar-primary search-form evge-flex-center evge-toolbar-section">
            <div class="evge-toolbar-icon">
                <svg width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g opacity="0.7">
                        <path d="M12.432 7.24711C12.432 10.404 9.87283 12.9631 6.71598 12.9631C3.55913 12.9631 1 10.404 1 7.24711C1 4.09026 3.55913 1.53113 6.71598 1.53113C9.87283 1.53113 12.432 4.09026 12.432 7.24711Z" stroke="black" stroke-width="2"/>
                        <line x1="10.2374" y1="11.7251" x2="14.7069" y2="16.1947" stroke="black" stroke-width="2"/>
                    </g>
                </svg>

            </div>
            <label for="evge-search-input" class="screen-reader-text"><?php esc_html_e( 'Search Events or Registrants', 'event-genius' ); ?></label>
            <input type="search" name="s" placeholder="<?php esc_html_e( 'Search', 'event-genius' ); ?>" id="evge-search-input" class="search" value="<?php echo esc_attr( $search_term ); ?>">
            <input name="stype" value="events" type="hidden">
        </div>
    </div>

</div>