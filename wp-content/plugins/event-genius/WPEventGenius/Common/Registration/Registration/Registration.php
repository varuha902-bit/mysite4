<?php

namespace WPEventGenius\Common\Registration\Registration;

use WPEventGenius\Common\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

interface Registration {
	public function __construct( Database $database );

	public function get_registration_data( $key = false );

	public function get_entry_id();
}
