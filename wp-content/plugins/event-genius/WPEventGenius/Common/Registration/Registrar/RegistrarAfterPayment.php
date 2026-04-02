<?php

namespace WPEventGenius\Common\Registration\Registrar;

use WPEventGenius\Common\Event\Event;
use WPEventGenius\Common\Registration\Registration\RegistrationGroup;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class RegistrarAfterPayment extends Registrar {
	public function get_own_context() {
		return 'payment';
	}
}
