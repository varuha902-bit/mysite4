<?php
/**
 * Event Content Template
 * 
 * This template displays the main content of an event including:
 * - Event description
 * - Custom content
 * - Action hooks for content modification
 * 
 * @var int    $event_id The ID of the current event
 * @package WPEventGenius
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\Common\Event\EventPost;

$event_post = new EventPost( $event_id );

do_action( 'evge_before_single_event_content', $event_id );

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo $event_post->get_the_content();

do_action( 'evge_after_single_event_content', $event_id );

