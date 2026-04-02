<?php
/**
 * Interface for all types of submissions
 *
 * @since 2.21
 */
namespace WPEventGenius\Common\Registration\Submission;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

interface Submission {

	/**
	 * @param array $raw_data
	 *
	 * @return mixed
	 *
	 * @since 2.21
	 */
	public function set_raw_data( $raw_data );

	/**
	 * @return mixed
	 *
	 * @since 2.21
	 */
	public function normalize();

	/**
	 * @return mixed
	 *
	 * @since 2.21
	 */
	public function validate();

	/**
	 * @return mixed
	 *
	 * @since 2.21
	 */
	public function sanitize();

	/**
	 * @return mixed
	 *
	 * @since 2.21
	 */
	public function get_data();

	/**
	 * @return mixed
	 *
	 * @since 2.21
	 */
	public function is_valid();

	/**
	 * @param string $field
	 * @param bool $type
	 *
	 * @return mixed
	 *
	 * @since 2.21
	 */
	public function add_error( $field, $type = false );

	/**
	 * @return mixed
	 *
	 * @since 2.21
	 */
	public function get_errors();

}
