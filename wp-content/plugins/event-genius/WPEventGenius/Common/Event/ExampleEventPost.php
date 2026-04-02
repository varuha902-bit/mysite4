<?php
namespace WPEventGenius\Common\Event;

use WPEventGenius\Common\Registration\Field\FieldHandler;
use WPEventGenius\Common\Registration\Form\Form;
use WPEventGenius\Common\Services\RegistrationObjectFactory;
use WPEventGenius\Common\Utils\DateFormatter;
use WPEventGenius\Common\Utils\Settings;
use WPEventGenius\Common\Utils\Templater;
use WPEventGenius\Common\Utils\Text;
use WPEventGenius\Common\Utils\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class ExampleEventPost extends EventPost {

	protected $post_id;

	protected $post_meta;

	protected $registration_form;

	protected $venue_ids;

	protected $organizer_ids;


	public function __construct( $post_id, $form_id ){
		$this->post_id = 0;

		$this->post_meta = array();

		$factory = new RegistrationObjectFactory();
		$this->registration_form = $factory->create_form( $form_id );
		$this->registration_form->set_fields();

		$this->venue_ids = empty( $this->post_meta['evge_venue_order'] ) ? array( 0 ) : json_decode( $this->post_meta['evge_venue_order'][0], true );
		$this->organizer_ids = empty( $this->post_meta['evge_organizer_order'] ) ? array( 0 ) : json_decode( $this->post_meta['evge_organizer_order'][0], true );
	}

	public function get_the_id() {
		return $this->post_id;
	}

	public function get_venue_ids() {
		return $this->venue_ids;
	}

	public function num_venues() {
		if ( ! empty( $this->venue_ids ) && is_array( $this->venue_ids ) && $this->venue_ids[0] === 0 ) {
			return 0;
		}
		if ( ! is_array( $this->venue_ids ) ) {
			return 0;
		}
		return count( $this->venue_ids );
	}

	public function get_organizer_ids() {
		return $this->organizer_ids;
	}

	public function num_organizers() {
		if ( ! empty( $this->organizer_ids ) && is_array( $this->organizer_ids ) && $this->organizer_ids[0] === 0 ) {
			return 0;
		}
		if ( ! is_array( $this->organizer_ids ) ) {
			return 0;
		}
		return count( $this->organizer_ids );

	}

	public function get_the_title() {
		return 'Example Event';
	}

	public function get_the_permalink() {
		return get_the_permalink();
	}

	public function get_the_featured_image($size = 'medium') {
		return '<div class="evge-featured-placeholder-wrap"><img src=" ' .trailingslashit( EVGE_PLUGIN_URL ) . 'assets/images/front-end/svgs/featured-placeholder.svg' . '" alt="' . esc_attr( $this->get_the_alt() ) . '"></div>';
	}

	public function get_the_date_summary( $format = 'full' ) {
		return DateFormatter::date_format( time(), 'summary' ) . ' • ' . DateFormatter::time_format( time(), 'summary' );
	}

	public function get_the_full_date() {
		return '';
	}

	public function get_the_start_date() {
		return wp_date( 'Y-m-d 08:00:00' );

	}

	public function get_the_end_date() {
		return wp_date( 'Y-m-d 17:00:00' );

	}

	public function get_the_summary( $max_length = 280, $show_more = true, $use_content = true ) {
		return '';
	}

	public function get_the_cost_display() {
		return '';
	}

	public function get_the_cost_amount() {
		return '';
	}

	public function currency_symbol_before() {
		$raw_display = Settings::get( 'cost_display' );

		if ( strpos( $raw_display, '{symbol}' ) < strpos( $raw_display, '{amount}' ) ) {
			return Settings::get( 'currency_symbol' );
		}

		return '';
	}

	public function currency_symbol_after() {
		$raw_display = Settings::get( 'cost_display' );

		if ( strpos( $raw_display, '{symbol}' ) > strpos( $raw_display, '{amount}' ) ) {
			return Settings::get( 'currency_symbol' );
		}

		return '';
	}

	public function get_payment_json() {
		$payment_json = array(
			'costs' => array(
				'quantity' => 1,
				'eventCost' => $this->get_the_cost_amount(),
				'subTotal' => $this->get_the_cost_amount(),
				'fees' => array(
					'gateways' => array()
				),
				'total' => 0
			),
			'restrictions' => array(
				'maxQuantity' => $this->get_the_capacity(),
			),
		);

		$gateways = EVGE()->gateways();
		foreach ( $gateways as $gateway ) {
			$fees = $gateway->surcharge_setting();
			$payment_json['costs']['fees']['gateways'][ $gateway->identity_key() ] = array(
				'feeFlat' => $fees['flat_cost'],
				'feePercent' => $fees['percent_cost'],
				'feeCalculated' => 0,
			);
		}

		return json_encode( $payment_json );

	}

	public function get_the_venue_title() {
		return 'Example Venue';
	}

	public function get_the_form_id() {
		return $this->registration_form->get_id();
	}

	public function get_form() {
		return $this->registration_form;
	}

	public function get_the_about_items( $view = null ) {
		$about_items = array();
		if ( true ) {
			$about_items[] = array(
				'slug' => 'duration',
				'icon' => 'duration',
				'text' => $this->get_the_duration_description(),
			);
		}
		if ( true ) {
			$about_items[] = array(
				'slug' => 'registration',
				'icon' => 'registration',
				'text' => \WPEventGenius\Common\Utils\Settings::get( 'registration_available_text' ),
			);
		}

		$about_items = apply_filters( 'evge_event_about_items', $about_items, $this->post_id );

		return $about_items;

	}

	public function get_duration() {
		return 5;
	}

	public function get_the_content() {
		return 'Example Content';
	}

	public function get_allow_registration() {
		if ( empty( $this->post_meta['evge_allow_registration'] ) ) {
			return Settings::get( 'allow_registration' );
		}
		return $this->post_meta['evge_allow_registration'][0];
	}

	public function registration_is_open() {
		return true;
	}

	public function registration_has_closed() {
		return false;
	}

	public function get_the_list_cta() {
		return'';
	}

	public function get_the_cta( $args = array() ) {
		return '';
	}


}