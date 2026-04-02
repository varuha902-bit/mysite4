<?php
namespace WPEventGenius\Admin\Page\Settings\Text;

use WPEventGenius\Admin\Page\SettingsBasePage;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class RegistrationTextSettingsPage extends TextSettingsPage {
	protected $active_subtab = 'registration';

	public function __construct() {
	}

	public function build() {
	}

	public function content() {
		include_once( EVGE_ADMIN_TEMPLATE_PATH . 'evge/settings/registration-text.php' );
    }

}
