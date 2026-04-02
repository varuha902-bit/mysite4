<?php

namespace WPEventGenius\Common\Taxonomies;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class CalendarTaxonomy {
    const TAXONOMY = 'evge_calendar';

    public function register() {
        register_taxonomy(self::TAXONOMY, null, [
            'labels' => [
                'name' => __('Calendars', 'event-genius'),
                'singular_name' => __('Calendar', 'event-genius'),
                'add_new_item' => __('Add New Calendar', 'event-genius'),
                'edit_item' => __('Edit Calendar', 'event-genius'),
                'new_item' => __('New Calendar', 'event-genius'),
                'view_item' => __('View Calendar', 'event-genius'),
                'search_items' => __('Search Calendars', 'event-genius'),
                'not_found' => __('No calendars found', 'event-genius'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
			'capability_type' => array('evge_event', 'evge_events'),
			'map_meta_cap' => true,
            'show_admin_column' => false,
            'hierarchical' => false,
            'show_in_rest' => true,
            'rewrite' => false,
        ]);

        // Register meta fields
        register_term_meta(self::TAXONOMY, 'evge_calendar_settings', [
            'type' => 'object',
            'single' => true,
            'show_in_rest' => true,
            'default' => [
                'view_type' => 'month',
                'events_per_page' => 12,
                'filters' => [],
                'color' => '#3498db',
                'show_toolbar' => 'enabled'
            ]
        ]);
    }
} 