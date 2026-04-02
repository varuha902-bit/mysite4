<?php
namespace WPEventGenius\Admin\CustomPostTypes\QuickCreate;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class VenueCreate extends BaseCreate {

	protected const POST_TYPE = 'evge_venue';

	protected const SLUG = 'venue';

	public function expected_input_names() {
		return array(
			'evge_new_venue_address_1',
			'evge_new_venue_address_2',
			'evge_new_venue_city',
			'evge_new_venue_state',
			'evge_new_venue_postal_code',
			'evge_new_venue_country',
			'evge_new_venue_phone',
			'evge_new_venue_website',
			'evge_new_venue_map_url',
			'evge_new_venue_facebook',
			'evge_new_venue_twitter',
			'evge_new_venue_instagram',
			'evge_new_venue_linkedin',
			'evge_new_venue_youtube',
			'evge_new_venue_image_id'
		);
	}

	public function insert_new_post($image_id = null) {
		$post_id = parent::insert_new_post();
		
		if ($post_id && !empty($image_id)) {
			set_post_thumbnail($post_id, absint($image_id));
		}
		
		return $post_id;
	}
}