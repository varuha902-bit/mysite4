<?php
namespace WPEventGenius\Admin\Page\Registrations;

use WPEventGenius\Admin\Page\RegistrationsBasePage;
use WPEventGenius\Common\Registration\Field\FieldHandler;
use WPEventGenius\Common\Registration\Form\Form;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class RegistrationsPage extends RegistrationsBasePage {

	protected $active_tab = 'registrations';

	protected $sanitized_params;

	public function __construct() {
	}

	public function build() {
		do_action( 'evge_admin_action_listener' );

		$this->sanitized_params = $this->base_params();
	}

	public function page_title() {
		return __( 'Registrations', 'event-genius' );
	}

	public function get_sanitized_params() {
		return $this->sanitized_params;
	}


}
