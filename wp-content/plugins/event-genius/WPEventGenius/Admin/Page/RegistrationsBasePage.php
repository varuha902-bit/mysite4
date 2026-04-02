<?php
namespace WPEventGenius\Admin\Page;

use WPEventGenius\Admin\Actions\RegistrationRecord;
use WPEventGenius\Common\Database;
use WPEventGenius\Common\Queries\EventQuery;
use WPEventGenius\Common\Queries\RegistrationQuery;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RegistrationsBasePage extends BasePage {

	public function __construct() {
	}

	public function build() {}

    public function page_title() {
		return __( 'Registrations', 'event-genius' );
	}

	public function navigation_args() {
		$navigation_args = array(
			'active_tab' => $this->active_tab,
			'nav_items' => array(
				array(
					'id' => 'registrations',
					'title' => __( 'Registrations', 'event-genius' ),
					'url' => $this->nav_link( 'evge-registrations', array( 'tab' => 'registrations' ) ),
					'capability' => 'view_evge_registrations'
				),
				array(
					'id' => 'forms',
					'title' => __( 'Forms', 'event-genius' ),
					'url' => $this->nav_link( 'evge-registrations', array( 'tab' => 'forms' ) ),
					'capability' => 'manage_evge_registrations'
				),
			)
		);

		return $navigation_args;
	}

	public function navigation( $active_tab ) {
		$navigation_args = $this->navigation_args();
		$navigation_args = $this->filter_navigation_by_capability($navigation_args);
		$this->navigation_html( $navigation_args );
	}

	public function content() {
		$page = $this;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = ! empty( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : '';

        if ( $tab === 'forms' ) {
	        include_once( EVGE_ADMIN_TEMPLATE_PATH . 'evge/registrations/forms.php' );
        } else {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! empty( $_GET['registration_id'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	            $registration_record = new RegistrationRecord( new Database(), absint( $_GET['registration_id'] ) );

	            include_once( EVGE_ADMIN_TEMPLATE_PATH . 'evge/registrations/single-registration.php' );
	            do_action( 'evge_admin_modal' );

            } else {
	            include_once( EVGE_ADMIN_TEMPLATE_PATH . 'evge/registrations/registrations.php' );
            }
        }
    }


}
