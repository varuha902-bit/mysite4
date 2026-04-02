<?php
namespace WPEventGenius\Admin\Page;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class SettingsBasePage extends BasePage {

	public function __construct() {
	}

	public function build() {
	}

    public function page_title() {
		return __( 'Settings', 'event-genius' );
	}

    public function notices() {}

    public function defaults_explanation_notice() {
        ?>
            <div class="evge-defaults-explanation evge-highlight-text-notice">
                <span class="evge-asterisk">*</span> <?php esc_html_e( "These settings will only apply to new events and are overwritten by individual event settings. To change these settings for existing events, use the setting inside each event's edit screen.", 'event-genius' ); ?>
            </div>
            <?php
    }

    public function navigation_args() {
        $navigation_args = array(
            'active_tab' => $this->active_tab,
            'nav_items' => array(
                array(
                    'id' => 'general',
                    'title' => __( 'General', 'event-genius' ),
                    'url' => $this->nav_link( 'evge-settings', array( 'tab' => 'general' ) ),
                ),
                array(
                    'id' => 'events',
                    'title' => __( 'Events', 'event-genius' ),
                    'url' => $this->nav_link( 'evge-settings', array( 'tab' => 'events' ) ),
                ),
                array(
                    'id' => 'registration',
                    'title' => __( 'Registration', 'event-genius' ),
                    'url' => $this->nav_link( 'evge-settings', array( 'tab' => 'registration' ) ),
                ),
                array(
                    'id' => 'text',
                    'title' => __( 'Text & Translation', 'event-genius' ),
                    'url' => $this->nav_link( 'evge-settings', array( 'tab' => 'text', 'subtab' => 'event' ) ),
                ),
            )
        );

        return $navigation_args;
    }

    public function navigation( $active_tab ) {
        $navigation_args = $this->navigation_args();
        $navigation_args = $this->filter_navigation_by_capability($navigation_args);
        $navigation_args = apply_filters( 'evge_settings_navigation_args', $navigation_args, $active_tab );
        $this->navigation_html( $navigation_args );
    }

	public function content() {
		include_once( EVGE_ADMIN_TEMPLATE_PATH . 'evge/event-settings.php' );
    }

}
