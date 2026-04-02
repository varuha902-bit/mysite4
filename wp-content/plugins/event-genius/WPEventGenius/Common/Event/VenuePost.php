<?php
namespace WPEventGenius\Common\Event;

use WPEventGenius\Common\Utils\DateFormatter;
use WPEventGenius\Common\Utils\Templater;
use WPEventGenius\Common\Utils\Text;
use WPEventGenius\Common\Utils\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class VenuePost {

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
	 * Check if this venue post exists and is published
	 * 
	 * @return bool Whether the venue exists and is published
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

	public function get_the_featured_image() {
		$thumbnail = get_the_post_thumbnail( $this->post_id );

		if ( ! empty( $thumbnail ) ) {
			return $thumbnail;
		}
		
		// Return empty string if no featured image is set
		return '';
	}

	/**
	 * Check if the venue has a featured image
	 * 
	 * @return bool True if the venue has a featured image, false otherwise
	 */
	public function has_featured_image() {
		return !empty(get_post_thumbnail_id($this->post_id));
	}

	public function get_the_content() {
		$content = get_post_field('post_content', $this->post_id);
		// Apply WordPress content filters to ensure proper block rendering
		return apply_filters('the_content', $content);
	}

	public function get_the_address_1() {
        if ( ! isset( $this->post_meta['evge_address_1'] ) ) {
            return '';
        }
		return $this->post_meta['evge_address_1'][0];
	}

	public function get_the_address_2() {
        if ( ! isset( $this->post_meta['evge_address_2'] ) ) {
            return '';
        }
		return $this->post_meta['evge_address_2'][0];
	}

	public function get_the_city() {
        if ( ! isset( $this->post_meta['evge_city'] ) ) {
            return '';
        }
		return $this->post_meta['evge_city'][0];
	}

	public function get_the_state() {
        if ( ! isset( $this->post_meta['evge_state'] ) ) {
            return '';
        }
		return $this->post_meta['evge_state'][0];
	}

	public function get_the_postal_code() {
        if ( ! isset( $this->post_meta['evge_postal_code'] ) ) {
            return '';
        }
		return $this->post_meta['evge_postal_code'][0];
	}

	public function get_the_country() {
        if ( ! isset( $this->post_meta['evge_country'] ) ) {
            return '';
        }
		return $this->post_meta['evge_country'][0];
	}

	public function get_the_phone() {
        if ( ! isset( $this->post_meta['evge_phone'] ) ) {
            return '';
        }
		return $this->post_meta['evge_phone'][0];
	}

	public function get_the_website() {
        if ( ! isset( $this->post_meta['evge_website'] ) ) {
            return '';
        }
		return $this->post_meta['evge_website'][0];
	}

	public function get_the_map_url() {
        if ( ! isset( $this->post_meta['evge_map_url'] ) ) {
            return '';
        }
		return $this->post_meta['evge_map_url'][0];
	}

	public function get_the_full_map_url() {
		$map_url = $this->get_the_map_url();
		if (empty($map_url)) {
			return '';
		}

		// If it's already a regular Google Maps URL, return it as is
		if (strpos($map_url, 'maps.google.com/maps') !== false) {
			return $map_url;
		}

		// If it's an embed URL, convert it
		if (strpos($map_url, 'maps/embed') !== false) {
			// Extract the location data from the embed URL
			if (preg_match('/!1m3!1d([^!]+)!2d([^!]+)!3d([^!]+)/', $map_url, $matches)) {
				$lat = $matches[3];
				$lng = $matches[2];
				return "https://www.google.com/maps?q={$lat},{$lng}";
			}
		}

		return $map_url;
	}

	public function get_the_street_address() {
		$address_1 = $this->get_the_address_1();
		$address_2 = $this->get_the_address_2();
		return $address_1 . ' ' . $address_2;
	}

	public function get_the_full_address() {
		$address_1 = $this->get_the_address_1();
		$address_2 = $this->get_the_address_2();
		$city = $this->get_the_city();
		$state = $this->get_the_state();
		$postal_code = $this->get_the_postal_code();
		$country = $this->get_the_country();

		$address_parts = array();

		// Add street address parts
		if (!empty($address_1)) {
			$address_parts[] = $address_1;
			if (!empty($address_2)) {
				$address_parts[] = $address_2;
			}
		} elseif (!empty($address_2)) {
			$address_parts[] = $address_2;
		}

		// Add city
		if (!empty($city)) {
			$address_parts[] = $city;
		}

		// Add state and postal code
		$location_parts = array();
		if (!empty($state)) {
			$location_parts[] = $state;
		}
		if (!empty($postal_code)) {
			$location_parts[] = $postal_code;
		}
		if (!empty($location_parts)) {
			$address_parts[] = implode(' ', $location_parts);
		}

		// Add country
		if (!empty($country)) {
			$address_parts[] = $country;
		}

		return implode(', ', $address_parts);
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

	public function maps_iframe_html() {
		$map_url = $this->get_the_map_url();
		if ( empty( $map_url ) ) {
			return;
		}
		if ( strpos( $map_url, 'openstreetmap.org'  ) !== false ) {
			?>
			<iframe id="evge-venue-iframe-<?php echo absint( $this->post_id ); ?>" src="<?php echo esc_url( $map_url ); ?>" width="100%" height="100%" style="border:0; visibility: hidden" allowfullscreen></iframe>
			<?php
		} else {
			?>
			<div class="evge-map-placeholder" data-src="<?php echo esc_url( $this->get_the_map_url() ); ?>" data-id="<?php echo absint( $this->post_id ); ?>">
				<div class="evge-spinner-container evge-is-processing"><div class="evge-spinner-circle"></div></div>
			</div>
<?php
		}
	}

	/**
	 * Check if a specific section should be displayed on the single venue page
	 * 
	 * @param string $section_key The section identifier (e.g., 'featured_image', 'title', 'contact_info', 'social_links')
	 * @return bool Whether the section should be displayed
	 */
	public function should_show_section($section_key) {
		return true;
	}
}