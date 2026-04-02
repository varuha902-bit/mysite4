<?php
/**
 * Series Content Block
 * 
 * Renders the series content using the existing template.
 * This block is hidden from the inserter and only used in block templates.
 *
 * @var array    $attributes Block attributes
 * @var string   $content   Block content
 * @var WP_Block $block     Block instance
 * @package WPEventGenius
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if Pro tier is available
if ( ! function_exists( 'evge_is_pro_tier' ) || ! evge_is_pro_tier() ) {
	return '';
}

// Get series ID from context or current post
$series_id = ! empty( $block->context['postId'] ) ? $block->context['postId'] : get_the_ID();

// Verify this is a series post type
if ( get_post_type( $series_id ) !== EVGE_SERIES_POST_TYPE ) {
	if ( current_user_can( 'edit_posts' ) ) {
		return '<div class="evge-block-notice">' . esc_html__( 'This block only works with series posts.', 'event-genius' ) . '</div>';
	}
	return '';
}

// Request modal since series pages use modal triggers
EVGE()->modal_service()->request_modal();

// Set up global post for templates
global $post;
$original_post = $post;
$post = get_post( $series_id );
setup_postdata( $post );

// Output the template
$templater = new \WPEventGenius\Common\Utils\Templater();
$template_path = $templater->get_series_template_part( 'single-content' );

if ( file_exists( $template_path ) ) {
	include $template_path;
}

wp_reset_postdata();
$post = $original_post;
