<?php
namespace WPEventGenius\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class Notice {
	protected $id;
	protected $is_two_step_notice = false;
	protected $image_url;

	// Step 1 properties
	protected $heading;
	protected $content;
	protected $primary_cta;
	protected $other_ctas = array();

	// Step 2 properties
	protected $heading_step_2;
	protected $content_step_2;
	protected $primary_cta_step_2;
	protected $other_ctas_step_2 = array();

	public function __construct( $args ) {
		$this->id = isset( $args['id'] ) ? $args['id'] : '';
		// Basic properties
		$this->is_two_step_notice = isset( $args['is_two_step_notice'] ) ? $args['is_two_step_notice'] : false;
		$this->image_url = isset( $args['image_url'] ) ? $args['image_url'] : '';

		// Step 1 properties
		$this->heading = isset( $args['heading'] ) ? $args['heading'] : '';
		$this->content = isset( $args['content'] ) ? $args['content'] : '';
		$this->primary_cta = isset( $args['primary_cta'] ) ? $args['primary_cta'] : null;
		$this->other_ctas = isset( $args['other_ctas'] ) ? $args['other_ctas'] : array();

		// Step 2 properties
		$this->heading_step_2 = isset( $args['heading_step_2'] ) ? $args['heading_step_2'] : '';
		$this->content_step_2 = isset( $args['content_step_2'] ) ? $args['content_step_2'] : '';
		$this->primary_cta_step_2 = isset( $args['primary_cta_step_2'] ) ? $args['primary_cta_step_2'] : null;
		$this->other_ctas_step_2 = isset( $args['other_ctas_step_2'] ) ? $args['other_ctas_step_2'] : array();
	}

	public function id() {
		return $this->id;
	}

	// Basic getters
	public function is_two_step_notice() {
		return $this->is_two_step_notice;
	}

	public function image_url() {
		return $this->image_url;
	}

	// Step 1 getters
	public function heading() {
		return $this->heading;
	}

	public function content() {
		return $this->content;
	}

	public function primary_cta() {
		return $this->primary_cta;
	}

	public function other_ctas() {
		return $this->other_ctas;
	}

	// Step 2 getters
	public function heading_step_2() {
		return $this->heading_step_2;
	}

	public function content_step_2() {
		return $this->content_step_2;
	}

	public function primary_cta_step_2() {
		return $this->primary_cta_step_2;
	}

	public function other_ctas_step_2() {
		return $this->other_ctas_step_2;
	}
}