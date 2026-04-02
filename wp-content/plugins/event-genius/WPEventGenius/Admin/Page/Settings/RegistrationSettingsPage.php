<?php
namespace WPEventGenius\Admin\Page\Settings;

use WPEventGenius\Admin\Page\BasePage;
use WPEventGenius\Admin\Page\SettingsBasePage;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class RegistrationSettingsPage extends SettingsBasePage {
	protected $active_tab = 'registration';

	protected $subtab;

	public function __construct() {

	}

	public function build() {

	}

	public function identity() {
		return 'registration-settings';
	}

	public function notices() {
		$this->defaults_explanation_notice();
	}

	public function content() {
		include_once( EVGE_ADMIN_TEMPLATE_PATH . 'evge/settings/registration.php' );

	}

}
