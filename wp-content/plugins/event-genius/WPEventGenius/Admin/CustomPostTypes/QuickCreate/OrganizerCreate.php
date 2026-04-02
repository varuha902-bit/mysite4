<?php
namespace WPEventGenius\Admin\CustomPostTypes\QuickCreate;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class OrganizerCreate extends BaseCreate {

	protected const POST_TYPE = 'evge_organizer';

	protected const SLUG = 'organizer';

	public function expected_input_names() {
		return array(
			'evge_new_organizer_summary',
			'evge_new_organizer_email',
			'evge_new_organizer_phone',
			'evge_new_organizer_website',
			'evge_new_organizer_facebook',
			'evge_new_organizer_twitter',
			'evge_new_organizer_instagram',
			'evge_new_organizer_linkedin',
			'evge_new_organizer_youtube',
			'evge_new_organizer_image_id'
		);
	}

	public function insert_new_post($image_id = null) {
		
		$post_id = parent::insert_new_post();
		
		if ($post_id && !empty($image_id)) {
			set_post_thumbnail($post_id, $image_id);
		}
		
		return $post_id;
	}
}