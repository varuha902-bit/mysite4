<?php
namespace WPEventGenius\Common\Event;

use WPEventGenius\Common\Utils\Text;
use WPEventGenius\Common\Utils\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class OrganizerPost {

	protected $post_id;

	protected $post_meta;


	public function __construct( $post_id ){
		$this->post_id = $post_id;

		$this->post_meta = get_post_meta( $post_id );
	}

	public function get_the_id() {
		return $this->post_id;
	}

	/**
	 * Check if this organizer post exists and is published
	 * 
	 * @return bool Whether the organizer exists and is published
	 */
	public function exists() {
		if ( empty( $this->post_id ) ) {
			return false;
		}
		
		$post_status = get_post_status( $this->post_id );
		return $post_status === 'publish';
	}

	public function get_the_title() {
		if ( empty( $this->post_id ) ) {
			return '';
		}
		return get_the_title( $this->post_id );
	}

	public function get_the_permalink() {
		return get_the_permalink( $this->post_id );
	}

	public function get_the_featured_image($size = 'medium') {
		$image_id = get_post_thumbnail_id($this->post_id);
		
		if (!$image_id) {
			// Return placeholder SVG if no featured image is set
			$placeholder_url = trailingslashit( EVGE_PLUGIN_URL ) . 'assets/images/front-end/svgs/organizer-placeholder.svg';
			$alt_text = get_the_title($this->post_id) ?: __('Organizer', 'event-genius');
			
			return sprintf(
				'<img src="%s" alt="%s" class="evge-organizer-image evge-placeholder-image" />',
				esc_url($placeholder_url),
				esc_attr($alt_text)
			);
		}
		
		$attr = array(
			'class' => 'evge-organizer-image',
			'alt'   => get_the_title($this->post_id)
		);
		
		return wp_get_attachment_image($image_id, $size, false, $attr);
	}

	/**
	 * Check if the organizer has a featured image
	 * 
	 * @return bool True if the organizer has a featured image, false otherwise
	 */
	public function has_featured_image() {
		return !empty(get_post_thumbnail_id($this->post_id));
	}

	/**
	 * Get the featured image URL, including placeholder fallback
	 * 
	 * @param string $size The image size
	 * @return string The image URL
	 */
	public function get_featured_image_url($size = 'full') {
		$thumbnail_id = get_post_thumbnail_id($this->post_id);
		
		if ($thumbnail_id) {
			return wp_get_attachment_image_url($thumbnail_id, $size);
		}
		
		// Return the organizer placeholder image URL
		return trailingslashit( EVGE_PLUGIN_URL ) . 'assets/images/front-end/svgs/organizer-placeholder.svg';
	}

	public function get_the_content() {
		$content = get_post_field('post_content', $this->post_id);
		// Apply WordPress content filters to ensure proper block rendering
		return apply_filters('the_content', $content);
	}

	public function get_the_summary( $max_length = 280, $show_more = true, $use_content = true ) {
		if ( ! empty( $this->post_meta['evge_summary'] ) ) {
			return Text::maybe_shorten_text( nl2br( $this->post_meta['evge_summary'][0] ), $max_length, $show_more );
		}

		if ( $use_content && ! empty( $this->post_id ) ) {
			return Text::maybe_shorten_text( nl2br( get_post_field('post_content', $this->post_id) ), $max_length, $show_more );
		}
		return '';
	}

	public function get_the_email() {
		if ( empty( $this->post_meta['evge_email'] ) ) {
			return '';
		}
		return $this->post_meta['evge_email'][0];
	}

	public function get_the_phone() {
		if ( empty( $this->post_meta['evge_phone'] ) ) {
			return '';
		}
		return $this->post_meta['evge_phone'][0];
	}

	public function get_the_website() {
		if ( empty( $this->post_meta['evge_website'] ) ) {
			return '';
		}
		return $this->post_meta['evge_website'][0];
	}

	public function get_the_links() {
		$links = array();
		if ( ! empty( $this->post_meta['evge_facebook'] )
			&& ! empty( $this->post_meta['evge_facebook'][0] ) ) {
			$links['facebook'] = Utils::build_social_media_url( 'facebook', $this->post_meta['evge_facebook'][0] );
		}
		if ( ! empty( $this->post_meta['evge_twitter'] )
			&& ! empty( $this->post_meta['evge_twitter'][0] ) ) {
			$links['twitter'] = Utils::build_social_media_url( 'twitter', $this->post_meta['evge_twitter'][0] );
		}
		if ( ! empty( $this->post_meta['evge_instagram'] )
		     && ! empty( $this->post_meta['evge_instagram'][0] ) ) {
			$links['instagram'] = Utils::build_social_media_url( 'instagram', $this->post_meta['evge_instagram'][0] );
		}
		if ( ! empty( $this->post_meta['evge_linkedin'] )
			&& ! empty( $this->post_meta['evge_linkedin'][0] ) ) {
			$links['linkedin'] = Utils::build_social_media_url( 'linkedin', $this->post_meta['evge_linkedin'][0] );
		}
		if ( ! empty( $this->post_meta['evge_youtube'] )
		&& ! empty( $this->post_meta['evge_youtube'][0] ) ) {
			$links['youtube'] = Utils::build_social_media_url( 'youtube', $this->post_meta['evge_youtube'][0] );
		}

		return $links;
	}

	public function events( $params = [] ) {

		if ( isset( $params['past'] ) ) {
			// Create a new query instance with the organizer ID
			$query = new EventQuery([
				'organizer_id' => $this->get_the_id(),
				'time_filter' => 'past',
				'num' => 3
			]);
			$query->apply_params();
		} else {
			// Create a new query instance with the organizer ID
			$query = new EventQuery([
				'organizer_id' => $this->get_the_id(),  // Replace with your actual organizer ID
				'num' => 3
			]);
			$query->apply_params();
		}


// Get the events as EventPost objects
		$events = $query->get_events();

		return $events;
	}

	/**
	 * Check if a specific section should be displayed on the single organizer page
	 * 
	 * @param string $section_key The section identifier (e.g., 'featured_image', 'title', 'contact_info', 'social_links')
	 * @return bool Whether the section should be displayed
	 */
	public function should_show_section($section_key) {
		return true;
	}
}