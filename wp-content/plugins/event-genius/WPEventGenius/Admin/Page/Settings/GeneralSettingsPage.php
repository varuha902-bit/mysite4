<?php
namespace WPEventGenius\Admin\Page\Settings;

use WPEventGenius\Admin\Page\BasePage;
use WPEventGenius\Admin\Page\SettingsBasePage;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class GeneralSettingsPage extends SettingsBasePage {
	protected $active_tab = 'general';

	public function __construct() {
	}

	public function build() {
	}

	public function content() {
		include_once( EVGE_ADMIN_TEMPLATE_PATH . 'evge/settings/general.php' );
    }

}
