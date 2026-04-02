<?php

namespace WPEventGenius\Admin\Services;

use WPEventGenius\Admin\Page\AllEvents\AllEventsPage;
use WPEventGenius\Admin\Page\AllEvents\CalendarsPage;
use WPEventGenius\Admin\Page\AllEvents\EventSinglePage;
use WPEventGenius\Admin\Page\AllEvents\OrganizersPage;
use WPEventGenius\Admin\Page\AllEvents\RegistrationOverviewPage;
use WPEventGenius\Admin\Page\AllEvents\VenuesPage;
use WPEventGenius\Admin\Page\DashboardPage;
use WPEventGenius\Admin\Page\SupportPage;
use WPEventGenius\Admin\Page\EventsPage;
use WPEventGenius\Admin\Page\Registrations\FormBuilder\BasePage;
use WPEventGenius\Admin\Page\Registrations\FormBuilder\BuilderPage;
use WPEventGenius\Admin\Page\Registrations\FormBuilder\EmailPage;
use WPEventGenius\Admin\Page\Registrations\FormBuilder\SettingsPage;
use WPEventGenius\Admin\Page\Registrations\RegistrationsPage;
use WPEventGenius\Admin\Page\Registrations\SingleRegistrationPage;
use WPEventGenius\Admin\Page\Settings\EventsSettingsPage;
use WPEventGenius\Admin\Page\Settings\GeneralSettingsPage;
use WPEventGenius\Admin\Page\Settings\RegistrationSettingsPage;
use WPEventGenius\Admin\Page\Settings\Text\EventTextSettingsPage;
use WPEventGenius\Admin\Page\Settings\Text\RegistrationTextSettingsPage;
use WPEventGenius\Admin\Page\SettingsBasePage;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class MenusService {

	public function __construct() {
	}

	public function init_hooks() {
		add_action( 'admin_menu', array( $this, 'add_menus' ) );

		add_filter( 'submenu_file', array( $this, 'remove_submenus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	public function add_menus() {

		$menu_name = esc_html__( 'Events', 'event-genius' );

		// Main "Events" plugin menu page
		add_menu_page(
			$menu_name,
			$menu_name,
			'edit_evge_events',
			'evge-events',
			array( $this, 'menu_page' ),
			'dashicons-calendar-alt',
			2
		);

		$submenu_name = esc_html__( 'Dashboard', 'event-genius' );
		add_submenu_page(
			'evge-events',
			$submenu_name,
			$submenu_name,
			'edit_evge_events',
			'evge-dashboard',
			array( $this, 'menu_page' ),
			2
		);

		$submenu_name = esc_html__( 'All Events', 'event-genius' );
		add_submenu_page(
			'evge-events',
			$submenu_name,
			$submenu_name,
			'edit_evge_events',
			'evge-all-events',
			array( $this, 'all_events_page' ),
			5
		);

		$submenu_name = esc_html__( 'Registrations', 'event-genius' );
		add_submenu_page(
			'evge-events',
			$submenu_name,
			$submenu_name,
			'view_evge_registrations',
			'evge-registrations',
			array( $this, 'registrations_page' ),
			6
		);

		$settings = esc_html__( 'Settings', 'event-genius' );
		add_submenu_page(
			'evge-events',
			$settings,
			$settings,
			'manage_options',
			'evge-settings',
			array( $this, 'settings_page' ),
			7
		);

		$support = esc_html__( 'Support', 'event-genius' );
		add_submenu_page(
			'evge-events',
			$support,
			$support,
			'manage_options',
			'evge-support',
			array( $this, 'support_page' ),
			10
		);


		$menu_title = esc_html__( 'Registrations', 'event-genius' );

		// TODO: Capability and new registration count
		$new_registrations_count = 0;

		if ( $new_registrations_count > 0 ) {
			$menu_title .= ' <span class="update-plugins evge-notice-admin-reg-count"><span>' . esc_html( $new_registrations_count ) . '</span></span>';
		}

	

	}

	public function maybe_select_parent_menu( $parent_file ) {
		global $submenu_file, $current_screen;

		if ( $this->is_evge_page() ) {
			$parent_file = 'evge-events';
		}

		if ( $this->is_evge_tax_page() ) {
			$parent_file = 'evge-events';
		}

		return $parent_file;
	}

	public function remove_submenus( $submenu_file ) {
		remove_submenu_page( 'evge-events', 'evge-events' );

		return $submenu_file;
	}

	public function menu_page() {
		$page = new DashboardPage();
		$page->build();
		$page->render( 'dashboard' );
	}

	public function settings_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$active_tab = ! empty( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$active_subtab = ! empty( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : '';

		// Allow plugins to handle custom tabs
		$page = apply_filters( 'evge_settings_page_instance', null, $active_tab, $active_subtab );

		if ( $page === null ) {
			if ( $active_tab === 'general' ) {
				$page = new GeneralSettingsPage();
			} elseif ( $active_tab === 'events' ) {
				$page = new EventsSettingsPage();
			} elseif ( $active_tab === 'registration' ) {
				$page = new RegistrationSettingsPage();
			} elseif ( $active_tab === 'text' ) {
				if ( $active_subtab === 'registration' ) {
					$page = new RegistrationTextSettingsPage();
				} elseif ( $active_subtab === 'series' && function_exists( 'evge_is_pro_tier' ) && evge_is_pro_tier() ) {
					if ( class_exists( 'WPEventGenius\Admin\Page\Settings\Text\SeriesTextSettingsPage' ) ) {
						$page = new \WPEventGenius\Admin\Page\Settings\Text\SeriesTextSettingsPage();
					} else {
						$page = new EventTextSettingsPage();
					}
				} else {
					$page = new EventTextSettingsPage();
				}
			} elseif ( $active_tab === 'event-text' ) {
				$page = new EventTextSettingsPage();
			} else {
				$page = new SettingsBasePage();
			}
		}
		
		$page->build();
		$page->render( $active_tab );
	}

	public function registration_settings_page() {
		$page = new RegistrationSettingsPage();
		$page->build();
		$page->render('registration-settings');
	}

	public function support_page() {

		$page = new SupportPage();
		$page->build();
		$page->render( 'support' );
		include_once( EVGE_ADMIN_TEMPLATE_PATH . 'evge/support.php' );
	}


	public function admin_page() {
		include_once( EVGE_ADMIN_TEMPLATE_PATH . 'registration/main.php' );
	}

	public function all_events_page() {
		$active_tab = 'evge-all-events';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$custom_post_type = ! empty( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$taxonomy = ! empty( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = ! empty( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = ! empty( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$view = ! empty( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : get_user_meta( get_current_user_id(), 'evge_all_events_view', true );

		if ( ! empty( $custom_post_type ) ) {
			$active_tab = $custom_post_type;
		} elseif ( ! empty( $taxonomy ) ) {
			$active_tab = $taxonomy;
		} elseif ( ! empty( $page ) ) {
			if ( ! empty( $tab ) ) {
				$active_tab = $tab;
			} else {
				$active_tab = $page;
			}
		} elseif ( ! empty( $tab ) ) {
			$active_tab = $tab;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$subtab = ! empty( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : '';

		if ( $active_tab === 'calendars' ) {
			$page = new CalendarsPage();
			$page->build();
		} elseif ( $active_tab === 'venues' ) {
			$page = new VenuesPage();
			$page->build();
		} elseif ( $active_tab === 'organizers' ) {
			$page = new OrganizersPage();
			$page->build();
		} elseif ( $active_tab === 'series' ) {
			// Only load SeriesPage if Pro version is active and user has capability
			if ( class_exists( 'WPEventGenius\Admin\Pro\Page\AllEvents\SeriesPage' ) && current_user_can( 'edit_evge_series' ) ) {
				$page = new \WPEventGenius\Admin\Pro\Page\AllEvents\SeriesPage();
				$page->build();
			} else {
				// Fallback to All Events page if Series tab is not available
				$page = new AllEventsPage();
				$page->build();
			}
		} else {
			if ( $subtab === 'single' ) {	
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$event_id = ! empty( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
				$page = new EventSinglePage( $event_id );
				$page->build();
			} else {
				if ( $view !== 'grid' ) {
					$page = new AllEventsPage();
					$page->build();
				} else {
					$page = new RegistrationOverviewPage();
					$page->build();
				}

			}
		}

		$page->render( $active_tab );

	}

	public function registrations_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = ! empty( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$subtab = ! empty( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : '';

		$active_tab = $tab;

		if ( ! empty( $subtab ) ) {
			$active_tab .= '-' . $subtab;
		}

		if ( $tab === 'forms' ) {
			// Use FormBuilderHomePage for Standard tier, otherwise use BasePage
			if ( evge_is_standard_tier() ) {
				$page = new \WPEventGenius\Standard\Admin\Page\FormBuilderHomePage();
			} else {
				$page = new BasePage();
			}

		} else {
			if ( $subtab === 'single' ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$event_id = ! empty( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
				$page = new EventSinglePage( $event_id );
			} else {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( ! empty( $_GET['registration_id'] ) ) {
					$page = new SingleRegistrationPage();
				} else {
					$page = new RegistrationsPage();
				}
			}

		}

		$page->build();
		$page->render( $active_tab );
	}

	public function enqueue( $screen ) {
		EVGE()->style_service()->enqueue_style( 'evge_admin_global' );

		if ( ! $this->is_evge_page() && ! $this->is_evge_tax_page() ) {
			return;
		}

		EVGE()->style_service()->enqueue_style( 'evge_admin_common' );
		EVGE()->style_service()->enqueue_style( 'evge_admin_custom_post_type_settings' );
		EVGE()->style_service()->enqueue_style( 'evge_admin_calendar_builder' );
		EVGE()->style_service()->enqueue_style( 'evge_admin_form_builder' );
		EVGE()->style_service()->enqueue_style( 'evge_admin_blocks_shared' );
		
		EVGE()->script_service()->enqueue_script( 'evge_admin_taxonomy' );

		if ( ! $this->is_evge_page() ) {
			return;
		}

		EVGE()->script_service()->enqueue_script( 'evge_admin_common' );
		EVGE()->script_service()->enqueue_script( 'evge_admin_components_filter_bar' );

	}

	public function is_evge_tax_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['taxonomy'] ) ) {
			return false;
		}
		$taxonomy = sanitize_key(wp_unslash($_GET['taxonomy']));
		// phpcs:ignore
		return strpos( $taxonomy, 'evge_' ) === 0;
	}

	public function is_evge_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['page'] ) ) {
			return false;
		}
		$page = sanitize_key(wp_unslash($_GET['page']));
		// phpcs:ignore 
		return strpos( $page, 'evge-' ) === 0;
	}
}