<?php

namespace WPEventGenius\Common\Registration\Registrar;

use WPEventGenius\Common\Event\Event;
use WPEventGenius\Common\Registration\Registration\RegistrationGroup;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class Registrar {

	protected $registration_group;

	protected $event;

	public function __construct( RegistrationGroup $registration_group, Event $event ) {
		$this->registration_group = $registration_group;
		$this->event              = $event;
	}

	public function registration_is_open() {
		return true;
	}

	public function registration_deadline_has_passed() {
		return false;
	}

	public function capacity_available() {
		return true;
	}

	public function calculate_status() {
		return '';
	}
}
