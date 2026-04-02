<?php
namespace WPEventGenius\Admin\Page\Registrations;

use WPEventGenius\Admin\Actions\RegistrationRecord;
use WPEventGenius\Admin\Page\RegistrationsBasePage;
use WPEventGenius\Common\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class SingleRegistrationPage extends RegistrationsBasePage {

	protected $active_tab = 'registrations';

	protected $sanitized_params;

	protected $registration_record;

	public function __construct() {
	}

	public function build() {
		do_action( 'evge_admin_action_listener' );

		$this->sanitized_params = $this->base_params();

		$this->registration_record = new RegistrationRecord( new Database(), $this->sanitized_params['registration_id'] );

	}

	public function get_registration_record() {
		return $this->registration_record;
	}

	public function page_title() {
		return __( 'Single Registration', 'event-genius' );
	}

	public function get_sanitized_params() {
		return $this->sanitized_params;
	}


}
