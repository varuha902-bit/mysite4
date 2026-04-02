<?php
/**
 * Events Archive Template
 * 
 * This template displays the events archive page including:
 * - Archive header
 * - Event calendar display
 * - Archive footer
 * 
 * @package WPEventGenius
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
include trailingslashit( EVGE_PLUGIN_PATH ) . 'templates/event-genius/events/archive/archive-content.php';
get_footer();