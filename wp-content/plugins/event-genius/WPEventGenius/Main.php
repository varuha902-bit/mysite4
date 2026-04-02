<?php

namespace WPEventGenius;

use WPEventGenius\Admin\CustomPostTypes\Event;
use WPEventGenius\Admin\CustomPostTypes\Organizer;
use WPEventGenius\Admin\CustomPostTypes\Venue;
use WPEventGenius\Admin\CustomPostTypes\Series;
use WPEventGenius\Admin\Services\AdminCustomPostTypeService;
use WPEventGenius\Admin\Services\CalendarBuilderService;
use WPEventGenius\Admin\Services\DashboardNoticeService;
use WPEventGenius\Admin\Services\DashboardPageService;
use WPEventGenius\Admin\Services\FormBuilderService;
use WPEventGenius\Admin\Services\MenusService;
use WPEventGenius\Admin\Services\PageService;
use WPEventGenius\Admin\Services\SettingsService;
use WPEventGenius\Common\CustomPostTypes\Event\EventArchive;
use WPEventGenius\Common\CustomPostTypes\Event\EventBase;
use WPEventGenius\Common\CustomPostTypes\Event\EventSingle;
use WPEventGenius\Common\CustomPostTypes\Organizer\OrganizerArchive;
use WPEventGenius\Common\CustomPostTypes\Organizer\OrganizerBase;
use WPEventGenius\Common\CustomPostTypes\Organizer\OrganizerSingle;
use WPEventGenius\Common\CustomPostTypes\Venue\VenueArchive;
use WPEventGenius\Common\CustomPostTypes\Venue\VenueBase;
use WPEventGenius\Common\CustomPostTypes\Venue\VenueSingle;
use WPEventGenius\Common\Services\AttendeeListService;
use WPEventGenius\Common\Services\BlogLoopService;
use WPEventGenius\Common\Services\CacheClearingService;
use WPEventGenius\Common\Services\CalendarActionsService;
use WPEventGenius\Common\Services\CptSlugService;
use WPEventGenius\Common\Services\SEOService;
use WPEventGenius\Common\Services\TranslationService;
use WPEventGenius\Common\Services\ModalService;
use WPEventGenius\Common\Database;
use WPEventGenius\Common\Registration\EventGoer\LoggedInEventGoer;
use WPEventGenius\Common\Registration\EventGoer\VisitorEventGoer;
use WPEventGenius\Common\Registration\Payment\Gateways\Offline;
use WPEventGenius\Common\Services\ActionService;
use WPEventGenius\Common\Services\CustomPostTypeService;
use WPEventGenius\Common\Services\IntegrationService;
use WPEventGenius\Common\Services\RegistrationFormService;
use WPEventGenius\Common\Utils\Settings;
use WPEventGenius\Common\Services\CronService;
use WPEventGenius\Common\Services\FrontEndAdminNoticeService;
use WPEventGenius\Common\Services\RelationshipSyncService;
use WPEventGenius\Common\Services\ScriptService;
use WPEventGenius\Common\Services\StyleService;
use WPEventGenius\Common\Services\UpsellService;
use WPEventGenius\Common\TemplateManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class Main {
	protected static $instance;

	/**
	 * @var RegistrationFormService
	 */
	private $registration_form_service;

	private $style_service;

	private $script_service;

	private $template_manager;

	private $modal_service;

	private $event_goer;

	protected $gateways;

	protected $gateway_services;

	protected $admin_gateways;

	/**
	 * Constructor - initialize core services
	 */
	protected function __construct() {

		// Use ProRegistrationFormService only if Pro is actually active (not just if class exists)
		if ( function_exists( 'evge_is_free_version' ) && ! evge_is_free_version() && class_exists( 'WPEventGenius\Pro\Services\ProRegistrationFormService' ) ) {
			$this->registration_form_service = new \WPEventGenius\Pro\Services\ProRegistrationFormService( new \WPEventGenius\Common\Services\RegistrationObjectFactory() );
		} else {
			$this->registration_form_service = new RegistrationFormService( new \WPEventGenius\Common\Services\RegistrationObjectFactory() );
		}
		
		$this->script_service = new ScriptService();
		$this->style_service = new StyleService();
		$this->modal_service = new ModalService();
	}

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new static();
		}
		return self::$instance;
	}

	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'event-genius' ), '1.0.0' );
	}

	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'event-genius' ), '1.0.0' );
	}

	public function registration_form_service() {
		return $this->registration_form_service;
	}

	public function script_service() {
		return $this->script_service;
	}	

	public function style_service() {
		return $this->style_service;
	}

	public function modal_service() {
		return $this->modal_service;
	}

	public function template_manager() {
		if ( ! $this->template_manager ) {
			$this->template_manager = TemplateManager::instance();
		}
		return $this->template_manager;
	}

	/**
	 * @return EventGoer\EventGoer
	 */
	public function event_goer() {
		return $this->event_goer;
	}

	/**
	 * Main initialization method that orchestrates the plugin setup
	 */
	public function init() {
		// Initialize core services (shared between free and pro)
		$this->init_core_services();
		
		// Initialize free-only services
		$this->init_free_services();
		
		// Initialize pro-only services (if pro version)
		$this->init_pro_services();
		
		// Initialize admin-specific services
		$this->init_admin_services();
	}

	/**
	 * Initialize components that depend on WordPress being fully loaded
	 */
	public function init_components() {
		// Initialize payment gateways (moved to init hook)
		$this->init_payment_gateways();
	}

	/**
	 * Initialize core services that are shared between free and pro versions
	 */
	protected function init_core_services() {
		$db = new Database();
		
		// Slug service must run before CPT registration (handles conflict check and get_slug).
		$cpt_slug_service = new CptSlugService();
		$cpt_slug_service->init_hooks();

		// Initialize custom post types
		$this->init_custom_post_types();
		
		// Initialize core services
		$settings_service = new SettingsService();
		$settings_service->init_hooks();

		$admin_menu_service = new MenusService();
		$admin_menu_service->init_hooks();

		$admin_page_service = new PageService();
		$admin_page_service->init_hooks();

		$calendar_actions = new CalendarActionsService();
		$calendar_actions->init_hooks();

		$attendee_calendar_service = new AttendeeListService();
		$attendee_calendar_service->init_hooks();

		// Initialize dynamic content service (AJAX endpoint)
		$dynamic_content_service = new \WPEventGenius\Common\Services\DynamicContentService();
		$dynamic_content_service->init_hooks();

		// Initialize cache clearing service
		$cache_clearing_service = new CacheClearingService();
		$cache_clearing_service->init_hooks();

		$this->registration_form_service->init_hooks();

		$this->script_service->init_hooks();
		$this->style_service->init_hooks();
		$this->modal_service->init_hooks();

		$action_service = new ActionService( new \WPEventGenius\Common\Services\RegistrationObjectFactory() );
		$action_service->init_hooks();

		// Initialize relationship sync service
		$relationship_sync_service = new RelationshipSyncService();
		$relationship_sync_service->init_hooks();

		// Initialize SEO service
		$seo_service = new SEOService();
		$seo_service->init_hooks();

		// Initialize event goer
		$this->init_event_goer($db);

		$calendar_builder_service = new CalendarBuilderService();
		$calendar_builder_service->init_hooks();

		$translation_service = new TranslationService();
		$translation_service->init_hooks();

		// Initialize cron service with dependency injection
		$cron_service = new CronService($db);
		$cron_service->init_hooks();

		// Initialize blocks using BlockLoaderService
		$block_loader = new \WPEventGenius\Common\Services\BlockLoaderService();
		$block_loader->init();

		// Initialize front-end admin notice service
		$frontend_admin_notice_service = new FrontEndAdminNoticeService();
		$frontend_admin_notice_service->init_hooks();

		// Initialize embed instructions service
		$embed_instructions_service = new \WPEventGenius\Common\Services\EmbedInstructionsService();
		$embed_instructions_service->init_hooks();

		// Initialize integrations (e.g., TranslatePress)
		$integration_service = new IntegrationService();
		$integration_service->init();

		// Initialize block theme template service
		$block_theme_template_service = new \WPEventGenius\BlockTheme\Services\BlockThemeTemplateService();
		$block_theme_template_service->init();

	}

	/**
	 * Initialize free-only services
	 */
	protected function init_free_services() {
		// Initialize upsell service for free version
		$upsell_service = new UpsellService();
		$upsell_service->init_hooks();
	}

	/**
	 * Initialize pro-only services
	 */
	protected function init_pro_services() {
				// Initialize admin gateways
		// free version, no services initialized here
	}

	/**
	 * Initialize admin-specific services
	 */
	protected function init_admin_services() {
		if (!is_admin()) {
			return;
		}

		// Ensure cron is scheduled when in admin
		$cron_service = new CronService(new Database());
		$cron_service->ensure_scheduled();

		$form_builder_service = new FormBuilderService();
		$form_builder_service->init_hooks();

		$dashboard_admin_page_service = new DashboardPageService();
		$dashboard_admin_page_service->init_hooks();

		$dashboard_notice_service = new DashboardNoticeService();
		$dashboard_notice_service->init_hooks();


	}

	/**
	 * Initialize custom post types
	 */
	protected function init_custom_post_types() {
		// Admin custom post types
		$custom_post_types = array(
			new Event(),
			new Venue(),
			new Organizer(),
			new Series(),
		);
		$cpt_service = new AdminCustomPostTypeService( $custom_post_types );
		$cpt_service->init_hooks();

		// Common custom post types
		$custom_post_types = array(
			array(
				new EventBase(),
				new EventArchive(),
				new EventSingle(),
			),
			array(
				new OrganizerBase(),
				new OrganizerArchive(),
				new OrganizerSingle(),
			),
			array(
				new VenueBase(),
				new VenueArchive(),
				new VenueSingle(),
			),
		);
		$cpt_service = new CustomPostTypeService( $custom_post_types );
		$cpt_service->init_hooks();

		$blog_loop_service = new BlogLoopService();
		$blog_loop_service->init_hooks();
	}

	/**
	 * Initialize event goer based on user login status
	 */
	protected function init_event_goer($db) {
		$factory = new \WPEventGenius\Common\Services\RegistrationObjectFactory();
		
		if ( \is_user_logged_in() ) {
			$this->event_goer = $factory->create_logged_in_event_goer( \get_current_user_id(), $db );
		} else {
			$this->event_goer = $factory->create_visitor_event_goer( 0, $db );
		}
	}

	/**
	 * Initialize payment gateways
	 */
	protected function init_payment_gateways() {
		$this->init_admin_gateways();

		$gateways = array();
		$gateways[] = new Offline();

		$this->gateways = apply_filters( 'evge_payment_gateways', $gateways );

		$gateway_services = array();
		$gateway_services[] = new Common\Registration\Payment\Gateways\OfflineService();

		$this->gateway_services = apply_filters( 'evge_payment_gateway_services', $gateway_services );

		$main_gateway_service = new Common\Registration\Payment\Gateways\GatewayService();

		$main_gateway_service->listeners();

		foreach ( $this->gateway_services as $gateway_service ) {
			$gateway_service->listeners();
		}
	}

	/**
	 * Initialize admin gateways
	 */
	protected function init_admin_gateways() {
		$admin_gateways = array();
		$admin_gateways[] = new Admin\Services\Gateways\OfflineAdmin();

		$admin_gateways = apply_filters( 'evge_payment_gateway_admin', $admin_gateways );
		// If no gateway order is set, use default payment gateway settings
		$payment_gateways = Settings::get( 'payment_gateways' );
		if ( ! empty( $payment_gateways ) && is_array( $payment_gateways ) ) {
			$gateway_order = array_keys( $payment_gateways );
		} else {
			// Fallback to default order if no settings exist
			$gateway_order = array( 'paypal_standard', 'offline' );
		}

		// Order gateways based on gateway_order setting
		if ( ! empty( $gateway_order ) ) {
			$ordered_gateways = array();
			foreach ( $gateway_order as $gateway_id ) {
				foreach ( $admin_gateways as $gateway ) {
					$details = $gateway->details();
					if ( $details['identity_key'] === $gateway_id ) {
						$ordered_gateways[] = $gateway;
					}
				}
			}
			if ( count( $ordered_gateways ) !== count( $admin_gateways ) ) {
				foreach ( $admin_gateways as $gateway ) {
					$details = $gateway->details();
					if ( ! in_array( $details['identity_key'], $gateway_order ) ) {
						$ordered_gateways[] = $gateway;
					}
				}
			}
			$admin_gateways = $ordered_gateways;
		}
		$this->admin_gateways = $admin_gateways;

		foreach ( $this->admin_gateways as $gateway_admin ) {
			$gateway_admin->init_hooks();
		}

		$payment_method_admin = new Admin\Services\PaymentMethodAdmin();
		$payment_method_admin->init_hooks();
	}

	/**
	 * @return array
	 */
	public function gateways() {
		return $this->gateways;
	}

	/**
	 * @return array
	 */
	public function gateway_services() {
		return $this->gateway_services;
	}

	public function admin_gateways() {
		return $this->admin_gateways;
	}

	public function get_gateway( $gateway_name ) {
		foreach ( $this->gateways as $gateway ) {
			if ( $gateway->identity_key() === $gateway_name ) {
				return $gateway;
			}
		}
		return false;
	}

	public function get_gateway_service( $gateway_name ) {
		foreach ( $this->gateway_services as $gateway ) {
			if ( $gateway->identity_key() === $gateway_name ) {
				return $gateway;
			}
		}
		return false;
	}

	public function active_gateway_services() {		
		// If no gateway order is set, use default payment gateway settings
		$payment_gateways = Settings::get( 'payment_gateways' );
		if ( ! empty( $payment_gateways ) && is_array( $payment_gateways ) ) {
			$gateway_order = array_keys( $payment_gateways );
		} else {
			// Fallback to default order if no settings exist
			$gateway_order = array( 'paypal_standard', 'offline' );
		}
		
		$active_gateways = array();
		$registered_admin_gateways = $this->admin_gateways();

		if ( count( $registered_admin_gateways ) > 1 && ! empty( $gateway_order ) ) {
			if ( count( $registered_admin_gateways ) > count( $gateway_order ) ) {
				foreach ( $registered_admin_gateways as $gateway ) {
					$details = $gateway->details();
					if ( ! in_array( $details['identity_key'], $gateway_order ) ) {
						$gateway_order[] = $details['identity_key'];
					}
				}
			}
			foreach ( $gateway_order as $gateway_id ) {

				$gateway_admin_service = EVGE()->get_admin_gateway( $gateway_id );
				if ( empty( $gateway_admin_service ) ) {
					continue;
				}
				$details = $gateway_admin_service->details();
				if ( ! empty( $details )
				     && $details['is_configured']
				     && $details['enabled'] ) {
					$active_gateways[] = EVGE()->get_gateway_service( $gateway_id );
				}
			}
		} else {
			$admin_gateways = EVGE()->admin_gateways();
			$active_gateways = array();
			foreach ( $admin_gateways as $gateway ) {
				$details = $gateway->details();
				if ( $details['is_configured'] && $details['enabled'] ) {
					$gateway_service = EVGE()->get_gateway_service( $details['identity_key'] );
					$active_gateways[] = $gateway_service;
				}
			}
		}

		return $active_gateways;
	}

	public function active_gateway_add_on() {
		$possible = array(
			'stripe',
			'paypal_standard',
			'woocommerce',
		);

		foreach ( $possible as $gateway ) {
			if ( EVGE()->add_on_active( $gateway ) ) {
				return $gateway;
			}
		}

		return 'none';
	}

	public function add_on_active( $add_on ) {
		switch( $add_on ) {
			case 'woocommerce':
				return defined ( 'EVGE_WOO_VERSION' );
			case 'stripe':
				return defined ( 'EVGE_STRIPE_GATEWAY_VERSION' );
			case 'paypal_standard':
				return defined ( 'EVGE_PAYPAL_STANDARD_VERSION' );
		}
	}


	public function get_admin_gateway( $gateway_name ) {
		foreach ( $this->admin_gateways as $gateway ) {
			$details = $gateway->details();
			if ( $details['identity_key'] === $gateway_name ) {
				return $gateway;
			}
		}
		return false;
	}
}