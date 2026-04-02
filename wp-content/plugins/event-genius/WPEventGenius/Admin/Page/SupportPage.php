<?php
namespace WPEventGenius\Admin\Page;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class SupportPage extends BasePage {

	protected $tab = 'support';

	public function __construct() {
	}

	public function build() {
	}

    public function page_title() {
		return __( 'Support', 'event-genius' );
	}

	public function navigation($activeTab) {
	}

	public function content() {
		include_once( EVGE_ADMIN_TEMPLATE_PATH . 'evge/support.php' );
    }


}
