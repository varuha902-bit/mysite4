<?php
namespace WPEventGenius\Common\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class EvgeDateTime {

	protected $date_time_obj;


	public function __construct( \DateTime $date_time_obj ) {
		$this->date_time_obj = $date_time_obj;
	}

	public function set_time( $hour, $minute, $second = 0 ) {
		$this->date_time_obj->setTime( $hour, $minute, $second );
	}

	public function get_date_time_obj() {
		return $this->date_time_obj;
	}

	public function utc_timestamp() {
		$scoped_date_time_obj = clone $this->date_time_obj;
		$scoped_date_time_obj->setTimezone( new \DateTimeZone( 'UTC' ) );
		$timestamp = $scoped_date_time_obj->getTimestamp();
		unset( $scoped_date_time_obj );
		return $timestamp;
	}

	public function timestamp() {
		return $this->date_time_obj->getTimestamp();
	}
	public function format( $format ) {
		return $this->date_time_obj->format( $format );
	}


	public function to_standard_format( $datetime_local ) {
		return gmdate( 'Y-m-d H:i:s', strtotime( $datetime_local ) );
	}

	public function to_datetime_local_format() {
		return str_replace( ' ', 'T', $this->date_time_obj->format( 'Y-m-d H:i' ) );
	}
}