<?php
namespace WPEventGenius\Admin\Page\Settings\Text;

use WPEventGenius\Admin\Page\SettingsBasePage;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class TextSettingsPage extends SettingsBasePage {
	protected $active_tab = 'text';

	public function __construct() {
	}

	public function build() {
	}

	public function before_subnav() {
		// if loco translate is not installed, show a notice else show a link to the loco translate settings page
		if ( ! is_plugin_active('loco-translate/loco.php') ) {
			// translators: %s: Link to LocoTranslate documentation
			$message = sprintf( __( 'Looking to translate more text? Check out our guide on using %sLocoTranslate%s for easy translation management.', 'event-genius' ), '<a href="https://wpeventgenius.com/docs/how-to-translate-or-change-date-formats-and-front-end-text/#locotranslate?utm_campaign=evge-free&utm_source=translation-settings-page&utm_medium=loco-translate-notice&utm_content=LocoTranslate" target="_blank">', '</a>' );
			$this->exclamation_notice( $message );
		} else {
			// translators: %s: Link to LocoTranslate settings page
			$message = sprintf( __( 'Visit the %sLocoTranslate settings page%s to manage translations for text not available here.', 'event-genius' ), '<a href="' . esc_url( admin_url( 'admin.php?bundle=wp-event-genius%2Fevent-genius.php&page=loco-plugin&action=view' ) ) . '" target="_blank">', '</a>' );
			$this->exclamation_notice( $message );
		}
	}

	public function sub_navigation_args() {
		$nav_items = array(
			array(
				'id' => 'event',
				'title' => __( 'Event', 'event-genius' ),
				'url' => $this->nav_link( 'evge-settings', array( 'tab' => 'text', 'subtab' => 'event' ) )
			),
			array(
				'id' => 'registration',
				'title' => __( 'Registration', 'event-genius' ),
				'url' => $this->nav_link( 'evge-settings', array( 'tab' => 'text', 'subtab' => 'registration' ) )
			),
		);

		// Add Series tab only for Pro tier
		if ( function_exists( 'evge_is_pro_tier' ) && evge_is_pro_tier() ) {
			$nav_items[] = array(
				'id' => 'series',
				'title' => __( 'Series', 'event-genius' ),
				'url' => $this->nav_link( 'evge-settings', array( 'tab' => 'text', 'subtab' => 'series' ) )
			);
		}

		$sub_navigation_args = array(
			'active_subtab' => $this->active_subtab,
			'nav_items' => $nav_items,
		);

		return $sub_navigation_args;

	}


}
