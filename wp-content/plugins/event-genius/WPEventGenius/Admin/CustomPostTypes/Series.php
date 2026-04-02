<?php
namespace WPEventGenius\Admin\CustomPostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class Series implements CustomPostType {
    protected $db;

    public function __construct() {
        $this->db = new \WPEventGenius\Common\Database();
    }

    public function get_post_type() {
        return EVGE_SERIES_POST_TYPE;
    }

    public function register() {
        $labels = array(
            'name' => __('Series', 'event-genius'),
            'singular_name' => __('Series', 'event-genius'),
            'all_items' => __('All Series', 'event-genius'),
            'add_new_item' => __('Add New Series', 'event-genius'),
            'add_new' => __('New Series', 'event-genius'),
            'new_item' => __('New Series', 'event-genius'),
            'edit_item' => __('Edit Series', 'event-genius'),
            'view_item' => __('View Series', 'event-genius'),
            'search_items' => __('Search Series', 'event-genius'),
            'not_found' => __('No series found', 'event-genius'),
            'not_found_in_trash' => __('No series found in trash', 'event-genius')
        );

        $args = array(
            'labels' => $labels,
            'public' => false,  // Hidden from UI
            'publicly_queryable' => false,
            'show_ui' => false, // Hidden from admin
            'show_in_menu' => false,
            'show_in_nav_menus' => false,
            'show_in_admin_bar' => false,
            'show_in_rest' => false,
            'capability_type' => array('evge_series', 'evge_series'),
            'map_meta_cap' => true,
            'hierarchical' => false,
            'supports' => array('title'),
            'has_archive' => false,
            'rewrite' => false,
            'query_var' => false,
        );

        register_post_type(EVGE_SERIES_POST_TYPE, $args);
    }

    public function register_taxonomies() {
        // No taxonomies needed for now
    }

    public function filter_default_columns($defaults) {
        return $defaults; // No custom columns needed yet
    }

    public function custom_column_content($column_name, $post_id) {
        // No custom column content needed yet
    }

    public function add_meta_boxes() {
        // No meta boxes needed yet
    }

    public function save_post($post_id) {
        // Add any series-specific meta saving here
    }

    public function enqueue($screen) {
        // No assets needed yet
    }
} 