<?php
/**
 * Venue Content Template
 * 
 * This template displays the main content of a venue including:
 * - Venue description
 * - Action hooks for content modification
 * 
 * @var int    $venue_id The ID of the current venue
 * @package WPEventGenius
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\Common\Event\VenuePost;

$venue_post = new VenuePost( $venue_id );

do_action( 'evge_before_single_venue_content', $venue_id );

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo $venue_post->get_the_content();

do_action( 'evge_after_single_venue_content', $venue_id );

