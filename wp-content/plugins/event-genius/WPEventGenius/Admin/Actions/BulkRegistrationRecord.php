<?php

namespace WPEventGenius\Admin\Actions;

use WPEventGenius\Common\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class BulkRegistrationRecord {

	protected $registration_id;

	public function __construct( Database $db, $registration_id ) {
		$this->database = $db;
		$this->registration_id = $registration_id;
		$this->raw_registration_data = $this->database->query_registration_record( $this->registration_id );
	}

	public function get_registration_data() {
		return $this->raw_registration_data[0];
	}


}