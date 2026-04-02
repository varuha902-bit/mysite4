<?php
namespace WPEventGenius\Admin\CustomPostTypes\QuickCreate;

use WPEventGenius\Common\Utils\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

abstract class BaseCreate {

	protected const POST_TYPE = 'evge_base';

	protected const SLUG = 'base';

	protected $args;

	protected $post_id;

	public function __construct( $args ) {
		$this->args = $args;
		$this->args['post_type'] = static::POST_TYPE;
		$this->args['post_status'] = 'publish';
		$this->args['post_content'] = '';
		if ( empty( $this->args['post_title'] ) ) {
			$this->args['post_title'] = 'New ' . static::SLUG;
		}
	}

	public function insert_new_post() {
		$post_id = wp_insert_post( $this->args );

		if ( is_wp_error( $post_id ) ) {
			// TODO: log error
			return false;
		}
		$this->post_id = $post_id;
		return $post_id;
	}

	public function sanitize_input_for_meta_key_pairs( $input ) {
		$meta_key_value_pairs = array();

		foreach ( $this->expected_input_names() as $meta_key ) {
			if ( ! empty( $input[ $meta_key ] ) ) {
				if ( strpos( $meta_key, 'summary' ) !== false ) {
					$meta_key_value_pairs[ str_replace( 'new_' . static::SLUG . '_', '', $meta_key ) ] = sanitize_textarea_field( $input[ $meta_key ] );
				} elseif ( strpos( $meta_key, 'url' ) !== false ) {
					$raw_url = Utils::parse_iframe_src( $input[ $meta_key ] );
					$meta_key_value_pairs[ str_replace( 'new_' . static::SLUG . '_', '', $meta_key ) ] = esc_url_raw( $raw_url );
				} else {
					$meta_key_value_pairs[ str_replace( 'new_' . static::SLUG . '_', '', $meta_key ) ] = sanitize_text_field( $input[ $meta_key ] );
				}
			}
		}

		return $meta_key_value_pairs;
	}

	public function insert_meta( $meta_key_value_pairs ) {
		foreach ( $meta_key_value_pairs as $meta_key => $value ) {
			update_post_meta( $this->post_id, $meta_key, $value );
		}
	}

	public function expected_input_names() {
		return array();
	}
}