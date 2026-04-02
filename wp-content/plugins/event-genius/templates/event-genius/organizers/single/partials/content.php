<?php
/**
 * Organizer Content Template
 * 
 * This template displays the main content of an organizer including:
 * - Organizer description
 * - Action hooks for content modification
 * 
 * @var int    $organizer_id The ID of the current organizer
 * @package WPEventGenius
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\Common\Event\OrganizerPost;

$organizer_post = new OrganizerPost( $organizer_id );

do_action( 'evge_before_single_organizer_content', $organizer_id );

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo $organizer_post->get_the_content();

do_action( 'evge_after_single_organizer_content', $organizer_id );

