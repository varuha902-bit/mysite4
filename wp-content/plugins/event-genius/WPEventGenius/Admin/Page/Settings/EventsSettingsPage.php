<?php
namespace WPEventGenius\Admin\Page\Settings;

use WPEventGenius\Admin\Page\BasePage;
use WPEventGenius\Admin\Page\SettingsBasePage;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class EventsSettingsPage extends SettingsBasePage {
	protected $active_tab = 'events';

	public function __construct() {
	}

	public function build() {
	}

	public function content() {
		include_once( EVGE_ADMIN_TEMPLATE_PATH . 'evge/settings/events.php' );
    }
}
