<?php
namespace WPEventGenius\Common\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class Templater {

	public const TEMPLATE_PATH = 'templates/event-genius/';

	public $list_item_type;

	public function __construct( $list_item_type = null ) {
		$this->list_item_type = $list_item_type ?? Settings::get( 'default_archive_view' );
	}

	public function get_event_template_part( $part, $type = 'standard' ) {
		$template_manager = \WPEventGenius\Common\TemplateManager::instance();
		switch ( $part ) {
			case 'single-content' :
				return $template_manager->locate_template( 'events/single/partials/content.php' );
			case 'single-location' :
				return $template_manager->locate_template( 'events/single/partials/location.php' );
			case 'calendar-item' :
				switch ( $type ) {
					case 'list' :
						return $template_manager->locate_template( 'events/calendars/partials/list-item.php' );
					default :
						return $template_manager->locate_template( 'events/calendars/partials/grid-item.php' );
				}
			case 'pagination' :
				return $template_manager->locate_template( 'events/common/pagination.php' );
		}
	}

	public function get_registration_template_part( $part, $type = 'standard' ) {
		$template_manager = \WPEventGenius\Common\TemplateManager::instance();
		switch ( $part ) {
			case 'form' :
				return $template_manager->locate_template( 'registration/forms/registration/form.php' );
			case 'field' :
				return $template_manager->locate_template( 'registration/forms/registration/field.php' );
			case 'label' :
				return $template_manager->locate_template( 'registration/forms/registration/label.php' );
			case 'input' :
				return $template_manager->locate_template( 'registration/forms/registration/input.php' );
			case 'cancel_input' :
				return $template_manager->locate_template( 'registration/forms/cancel/cancel-input.php' );
		}
	}

	public function get_series_template_part( $part, $type = 'standard' ) {
		switch ( $part ) {
			case 'single-content' :
				return trailingslashit( EVGE_PLUGIN_PATH ) . self::TEMPLATE_PATH . 'pro/series/single/partials/content.php';
		}
	}

	public function get_organizer_template_part( $part, $type = 'standard' ) {
		$template_manager = \WPEventGenius\Common\TemplateManager::instance();
		switch ( $part ) {
			case 'content' :
				return $template_manager->locate_template( 'organizers/single/partials/content.php' );
		}
	}

	public function get_venue_template_part( $part, $type = 'standard' ) {
		$template_manager = \WPEventGenius\Common\TemplateManager::instance();
		switch ( $part ) {
			case 'content' :
				return $template_manager->locate_template( 'venues/single/partials/content.php' );
		}
	}

	public function classes() {
		return '';
	}

	public function evge_classes() {
		$color_theme = Settings::get( 'color_theme' );
		$color_theme_class = ( $color_theme !== 'light') ? ' evge-color-theme-' . $color_theme : '';
		return $color_theme_class;
	}

	public function calendar_classes() {
		return ' evge-calendar-' . $this->list_item_type();
	}

	public function list_item_type() {
		return $this->list_item_type;
	}
}