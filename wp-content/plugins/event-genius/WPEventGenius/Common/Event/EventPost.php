<?php
namespace WPEventGenius\Common\Event;

use WPEventGenius\Common\Utils\DateFormatter;
use WPEventGenius\Common\Utils\Settings;
use WPEventGenius\Common\Utils\Templater;
use WPEventGenius\Common\Utils\Text;
use WPEventGenius\Common\Utils\Utils;
use WPEventGenius\Common\Utils\Icon;
use WPEventGenius\Common\Utils\EvgeDateTime;
use WPEventGenius\Common\Registration\Field\HoneyPotType;
use WPEventGenius\Common\Services\PaymentNoticeService;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}
class EventPost {

	protected $post_id;

	protected $post_meta;

	protected $registration_form;

	protected $venue_ids;

	protected $organizer_ids;

    protected $registration_counter;


	public function __construct( $post_id ){
		$this->post_id = $post_id;

		$this->post_meta = get_post_meta( $post_id );

		$this->registration_form = EVGE()->registration_form_service()->get_form_for_event( $post_id );
		
		$this->registration_form->set_fields();

		// Get venue IDs and reindex to sequential keys
		$venue_ids = empty( $this->post_meta['evge_venue_order'] ) ? array() : json_decode( $this->post_meta['evge_venue_order'][0], true );
		$this->venue_ids = array_values($venue_ids);
		
		// Get organizer IDs and reindex to sequential keys
		$organizer_ids = empty( $this->post_meta['evge_organizer_order'] ) ? array() : json_decode( $this->post_meta['evge_organizer_order'][0], true );
		$this->organizer_ids = array_values($organizer_ids);
	}

	public function set_registration_counter( $registration_counter ) {
		$this->registration_counter = $registration_counter;
	}

	public function get_the_id() {
		return $this->post_id;
	}

	public function get_form() {
		return $this->registration_form;
	}

	public function get_venue_ids() {
		$existing_venues = array();
		foreach ( $this->venue_ids as $venue_id ) {
			if ( $this->venue_exists( $venue_id ) ) {
				$existing_venues[] = $venue_id;
			}
		}
		return $existing_venues;
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
		$existing_organizers = array();
		foreach ( $this->organizer_ids as $organizer_id ) {
			if ( $this->organizer_exists( $organizer_id ) ) {
				$existing_organizers[] = $organizer_id;
			}
		}
		return $existing_organizers;
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
		return get_the_title( $this->post_id );
	}

	public function get_the_permalink() {
		return get_the_permalink( $this->post_id );
	}

	public function has_featured_image() {
		$image_id = get_post_thumbnail_id($this->post_id);
		return $image_id ? true : false;
	}

	public function get_the_featured_image($size = 'medium') {
		$image_id = get_post_thumbnail_id($this->post_id);
		if (!$image_id) {
			return '';
			return '<div class="evge-featured-placeholder-wrap"><img src="' . trailingslashit( EVGE_PLUGIN_URL ) . 'assets/images/front-end/svgs/featured-placeholder.svg' . '" alt="' . esc_attr( $this->get_the_alt() ) . '"></div>';
		}

		$attr = array(
			'class' => 'evge-event-image',
			'alt'   => get_the_title($this->post_id)
		);
		
		return wp_get_attachment_image($image_id, $size, false, $attr);
	}

	public function get_featured_image_url() {
		$thumbnail_id = get_post_thumbnail_id( $this->post_id );
		
		if ( $thumbnail_id ) {
			return wp_get_attachment_image_url( $thumbnail_id, 'full' );
		}
		
		// Return the default placeholder image URL
		return trailingslashit( EVGE_PLUGIN_URL ) . 'assets/images/front-end/svgs/featured-placeholder.svg';
	}

	public function is_all_day() {
		if ( ! empty( $this->post_meta['evge_all_day'] ) && ! empty( $this->post_meta['evge_all_day'][0] ) && $this->post_meta['evge_all_day'][0] === 'enabled' ) {
			return true;
		}
		return false;
	}

	public function get_the_date_summary( $format = 'full' ) {
		$start_date = !empty( $this->post_meta['evge_start_date'][0] ) ? $this->post_meta['evge_start_date'][0] : time();
		$end_date = !empty( $this->post_meta['evge_end_date'][0] ) ? $this->post_meta['evge_end_date'][0] : time();
		if ( $this->is_all_day() ) {
			if ( ! empty( $this->post_meta['evge_start_date'] ) ) {
				$start_string = DateFormatter::date_format( $this->post_meta['evge_start_date'][0], 'allday' );
				$end_string = DateFormatter::date_format( $this->post_meta['evge_end_date'][0], 'allday' );

				if ( $start_string === $end_string ) {
					return $start_string;
				} else {
					return $start_string . ' - ' . $end_string;
				}
			}
		}

		if ($format === 'brief') {
			return DateFormatter::date_string( 'brief', $start_date, $end_date, $this->get_the_timezone() );
		}
		return DateFormatter::date_string( 'date_summary', $start_date, $end_date, $this->get_the_timezone() );
	}

	public function get_the_full_date() {
		if ( $this->is_all_day() ) {
			if ( ! empty( $this->post_meta['evge_start_date'] ) ) {
				$start_string = DateFormatter::date_format( $this->post_meta['evge_start_date'][0], 'allday' );
				$end_string = DateFormatter::date_format( $this->post_meta['evge_end_date'][0], 'allday' );

				if ( $start_string === $end_string ) {
					return $start_string;
				} else {
					return $start_string . ' - ' . $end_string;
				}
			}
		}
		if ( ! empty( $this->post_meta['evge_start_date'] ) ) {
			return DateFormatter::date_string( 'full_date', $this->post_meta['evge_start_date'][0], $this->post_meta['evge_end_date'][0], $this->get_the_timezone() );
		}

		return '';
	}

	public function get_the_start_date() {
		if ( empty( $this->post_meta['evge_start_date'] ) ) {
			return gmdate( 'Y-m-d H:i:s' );
		}
		if ( $this->is_all_day() ) {
			return DateFormatter::date_format( $this->post_meta['evge_start_date'][0], 'allday' );
		}

		return $this->post_meta['evge_start_date'][0];
	}

	/**
	 * Get the raw start date value (always in parseable format)
	 * This method always returns a date string that can be parsed by DateTime,
	 * regardless of whether the event is all-day or not.
	 *
	 * @return string Date string in Y-m-d H:i:s format
	 */
	public function get_the_start_date_raw() {
		if ( empty( $this->post_meta['evge_start_date'] ) ) {
			return gmdate( 'Y-m-d H:i:s' );
		}

		return $this->post_meta['evge_start_date'][0];
	}

	public function get_the_end_date() {
		if ( empty( $this->post_meta['evge_end_date'] ) ) {
			return gmdate( 'Y-m-d H:i:s' );
		}
		if ( $this->is_all_day() && ! empty( $this->post_meta['evge_end_date'][0] ) ) {
			return DateFormatter::date_format( $this->post_meta['evge_end_date'][0], 'allday' );
		}

		return $this->post_meta['evge_end_date'][0];
	}

	/**
	 * Get the raw end date value (always in parseable format)
	 * This method always returns a date string that can be parsed by DateTime,
	 * regardless of whether the event is all-day or not.
	 *
	 * @return string Date string in Y-m-d H:i:s format
	 */
	public function get_the_end_date_raw() {
		if ( empty( $this->post_meta['evge_end_date'] ) ) {
			return gmdate( 'Y-m-d H:i:s' );
		}

		return $this->post_meta['evge_end_date'][0];
	}

	public function get_the_start_date_utc() {
		if ( empty( $this->post_meta['evge_start_date_utc'] ) ) {
			return gmdate( 'Y-m-d H:i:s' );
		}

		return $this->post_meta['evge_start_date_utc'][0];
	}

	public function get_the_end_date_utc() {
		if ( empty( $this->post_meta['evge_end_date_utc'] ) ) {
			return gmdate( 'Y-m-d H:i:s' );
		}

		return $this->post_meta['evge_end_date_utc'][0];
	}
	public function get_the_start_time($all_day_string = false) {
		$start_date = $this->get_the_start_date_raw();
		if (!$start_date) {
			return null;
		}

		if ($all_day_string && $this->is_all_day()) {
			return __('All Day', 'event-genius');
		}

		$date = new \DateTime($start_date);
		return $date->format(get_option('time_format'));
	}

	public function get_the_end_time($all_day_string = false) {
		$end_date = $this->get_the_end_date_raw();
		if (!$end_date) {
			return null;
		}

		if ($all_day_string && $this->is_all_day()) {
			return __('All Day', 'event-genius');
		}

		$date = new \DateTime($end_date);
		return $date->format(get_option('time_format'));
	}

	public function get_the_summary( $max_length = 280, $show_more = true, $use_content = true ) {
		if ( ! empty( $this->post_meta['evge_summary'] ) ) {
			return Text::maybe_shorten_text( nl2br( $this->post_meta['evge_summary'][0] ), $max_length, $show_more );
		}

		if ( $use_content ) {
			return Text::maybe_shorten_text( nl2br( get_post_field('post_content', $this->post_id) ), $max_length, $show_more );
		}
		return '';
	}

	public function get_the_cost_display() {
		if ( ! empty( $this->post_meta['evge_cost_display'] ) ) {
			$raw_display = $this->post_meta['evge_cost_display'][0];
			$find_replace = array(
				'{amount}' => $this->post_meta['evge_cost_amount'][0],
				'{symbol}' => $this->post_meta['evge_currency_symbol'][0],
			);

			$final_display = $raw_display;
			foreach ( $find_replace as $find => $replace ) {
				$final_display = str_replace( $find, $replace, $final_display );
			}

			if ( $final_display === $this->post_meta['evge_currency_symbol'][0] ) {
				return '';
			}

			return $final_display;
		}

		return '';
	}

	public function get_the_cost_amount() {
		if ( ! empty( $this->post_meta['evge_cost_amount'] ) ) {
			return $this->post_meta['evge_cost_amount'][0];
		}

		return '';
	}

	public function get_who_can_see_attendee_list() {
		if ( empty( $this->post_meta['evge_who_can_see_attendee_list'] ) ) {
			return Settings::get( 'who_can_see_attendee_list' );
		}
		return $this->post_meta['evge_who_can_see_attendee_list'][0];
	}

	public function currency_symbol_before() {
		if ( empty( $this->post_meta['evge_currency_symbol'] ) ) {
			return '';
		}
		if ( empty( $this->post_meta['evge_cost_display'] ) ) {
			return '';
		}
		$raw_display = $this->post_meta['evge_cost_display'][0];

		if ( strpos( $raw_display, '{symbol}' ) < strpos( $raw_display, '{amount}' ) ) {
			return $this->post_meta['evge_currency_symbol'][0];
		}

		return '';
	}

	public function currency_symbol_after() {
		if ( empty( $this->post_meta['evge_currency_symbol'] ) ) {
			return '';
		}
		if ( empty( $this->post_meta['evge_cost_display'] ) ) {
			return '';
		}
		$raw_display = $this->post_meta['evge_cost_display'][0];

		if ( strpos( $raw_display, '{symbol}' ) > strpos( $raw_display, '{amount}' ) ) {
			return $this->post_meta['evge_currency_symbol'][0];
		}

		return '';
	}

	public function get_payment_json() {
		// Get form settings for guest count limits
		$form = $this->get_form();
		$min_guest_count = method_exists( $form, 'get_min_guest_count' ) ? $form->get_min_guest_count() : 0;
		$max_guest_count = method_exists( $form, 'get_max_guest_count' ) ? $form->get_max_guest_count() : 10;
		
		// Calculate effective limits considering remaining event capacity
		$event_capacity = $this->get_the_capacity();
		$effective_max = $max_guest_count; // Start with form's max guest count
		
		// If event has a capacity limit, check remaining capacity
		if ( $event_capacity > 0 ) {
			// Ensure registration counter is available
			if ( empty( $this->registration_counter ) ) {
				$factory = new \WPEventGenius\Common\Services\RegistrationObjectFactory();
				$this->registration_counter = $factory->create_registration_counter( $this->get_the_id(), new \WPEventGenius\Common\Database() );
			}
			
			$remaining_capacity = $this->registration_counter->get_remaining_count( $event_capacity );			
			// Use the smaller of form max and remaining capacity
			$effective_max = min( $max_guest_count, $remaining_capacity );
		}
		
		// If max setting is lower than min setting, set max to min
		if ( $effective_max < $min_guest_count ) {
			$effective_max = $min_guest_count;
		}
		
		// Set default quantity to minimum guest count (or 1 if no minimum)
		$default_quantity = max( $min_guest_count, 1 );
		
		// Get current user's capacity usage if logged in
		$current_user_capacity = 0;
		if ( is_user_logged_in() ) {
			$event_goer = EVGE()->event_goer();
			$event_goer->set_event( $this );
			
			// Initialize the event goer to query for existing registration data
			// Only init if not already initialized for this event (to avoid duplicate queries)
			$current_event = $event_goer->event();
			$needs_init = true;
			if ( $current_event && method_exists( $current_event, 'get_the_id' ) ) {
				// Check if already initialized for this event
				$event_status = $event_goer->get_event_status();
				if ( $event_status && $current_event->get_the_id() === $this->get_the_id() ) {
					$needs_init = false;
				}
			}
			
			if ( $needs_init ) {
				$event_goer->init( $this );
			}
			
			// Check if user has existing registration
			if ( $event_goer->has_made_submission_for_event() ) {
				$event_status = $event_goer->get_event_status();
				if ( $event_status && method_exists( $event_status, 'calculate_total_guest_count' ) ) {
					$current_user_capacity = $event_status->calculate_total_guest_count();
				}
			}
		}
		
		// But it should always be at least 1 (the main registrant)
		$min_quantity = max( $min_guest_count, 1 );
		
		$payment_json = array(
			'costs' => array(
				'quantity' => $default_quantity,
				'eventCost' => $this->get_the_cost_amount(),
				'subTotal' => $this->get_the_cost_amount(),
				'fees' => array(
					'gateways' => array()
				),
				'total' => 0
			),
			'restrictions' => array(
				'maxQuantity' => $effective_max,
				'minQuantity' => $min_quantity,
				'currentUserCapacity' => $current_user_capacity,
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

	public function about_event_heading() {
		return Settings::get( 'about_event_text' );
	}

	public function location_heading() {
		if ( $this->num_venues() > 1 ) {
			return Settings::get( 'locations_text' );
		}
		return Settings::get( 'location_text' );
	}

	public function organizer_heading() {
		if ( $this->num_organizers() > 1 ) {
			return Settings::get( 'about_organizers_text' );
		}
		return Settings::get( 'about_organizer_text' );
	}

	public function categories_heading() {
		return Settings::get( 'categories_text' );
	}

	public function tags_heading() {
		return Settings::get( 'tags_text' );
	}

	public function add_to_calendar_text() {
		return Settings::get( 'add_to_calendar_text' );
	}

	public function see_map_text() {
		return Settings::get( 'see_map_text' );
	}

	public function get_accept_payments() {
		// Get the payment status from event meta or settings
		$payment_status = ! empty( $this->post_meta['evge_payment_status'][0] ) ? $this->post_meta['evge_payment_status'][0] : Settings::get( 'accept_payments' );

		// Normalize the payment status - handle both string and boolean values
		$is_enabled = $payment_status === 'enabled';

		// If payments are enabled, check if any active gateways exist
		if ( $is_enabled ) {
			$active_gateways = EVGE()->active_gateway_services();

			if ( empty( $active_gateways ) ) {
				return false; // No active gateways, so disable payments
			}
		}
		
		return $is_enabled;
	}

	/**
	 * Check if payment gateways are needed but not configured
	 * 
	 * @return bool True if payment gateways are needed but not configured
	 */
	public function needs_payment_gateway_configuration() {
		// Check if the event has a cost
		$cost_amount = $this->get_the_cost_amount();
		if ( empty( $cost_amount ) || $cost_amount <= 0 ) {
			return false; // No cost, no payment needed
		}

		// Check if any payment gateways are enabled and configured
		$active_gateways = EVGE()->active_gateway_services();
		if ( ! empty( $active_gateways ) ) {
			return false; // Gateways are configured
		}

		return true; // Payment gateways needed but not configured
	}

	public function get_the_venue_id() {
		if ( ! empty( $this->get_venue_ids() ) ) {
			$venue_id = $this->get_venue_ids()[0];
			// Check if the venue actually exists before returning it
			return $this->venue_exists( $venue_id ) ? $venue_id : 0;
		}

		return 0;
	}

	public function get_the_organizer_id() {
		if ( ! empty( $this->get_organizer_ids() ) ) {
			$organizer_id = $this->get_organizer_ids()[0];
			// Check if the organizer actually exists before returning it
			return $this->organizer_exists( $organizer_id ) ? $organizer_id : 0;
		}

		return 0;
	}

	public function get_the_venue_title() {
		$venue_id = $this->get_the_venue_id();
		if ( empty( $venue_id ) ) {
			return '';
		}
		return get_the_title( $venue_id );
	}

	public function get_the_capacity() {
		if (! empty($this->post_meta['evge_unlimited_capacity']) && $this->post_meta['evge_unlimited_capacity'][0] === 'enabled') {
			return 0;
		}
		if ( ! empty( $this->post_meta['evge_capacity'] ) ) {
			return (int) $this->post_meta['evge_capacity'][0];
		}

		return '';
	}

	public function get_the_capacity_display( $show_unlimited = false ) {
		if ( empty( $this->get_the_capacity() ) ) {
			if ( $show_unlimited ) {
				return '∞';
			}
			return '';
		}

		return $this->get_the_capacity();
	}

	public function get_the_form_id() {
		if ( ! empty( $this->post_meta['evge_registration_form'] ) ) {
			return $this->post_meta['evge_registration_form'][0];
		}

		return 1;
	}

	public function get_the_timezone() {
		$timezone_string = 'default';
		if ( ! empty( $this->post_meta['evge_timezone'] ) ) {
			$timezone_string = $this->post_meta['evge_timezone'][0];
		}

		if ( $timezone_string === 'default' ) {
			return wp_timezone_string();
		}

		return $timezone_string;
	}

	public function get_the_about_items( $view = null ) {
		$about_items = array();
		
		// Show duration if registration is disabled, or if registration is enabled but we're in list or single view
		$show_duration = $this->get_allow_registration() !== 'enabled' || 
		                 ( $this->get_allow_registration() === 'enabled' && in_array( $view, array( 'list', 'single' ), true ) );
		
		if ( $show_duration ) {
			$about_items[] = array(
				'slug' => 'duration',
				'icon' => 'duration',
				'text' => $this->get_the_duration_description(),
			);
		}
		if ( $this->get_allow_registration() === 'enabled' ) {
			// Determine registration text based on status
			if ( $this->registration_is_open() && ! $this->registration_has_closed() && ! $this->registration_has_filled() ) {
				// Registration is open - show "Registration Available" with capacity text
				$registration_text = __( 'Registration Available', 'event-genius' );
				$capacity_text = $this->get_registration_capacity_text_short();
				if ( ! empty( $capacity_text ) ) {
					$registration_text .= ' (' . $capacity_text . ')';
				}
			} else {
				// Replace entire text with short status message
				if ( $this->registration_has_closed() ) {
					$registration_text = Settings::get( 'registration_closed_text_short' );
				} elseif ( $this->registration_has_filled() ) {
					$registration_text = Settings::get( 'registration_filled_text_short' );
				} elseif ( ! $this->registration_is_open() ) {
					$registration_text = Settings::get( 'registration_not_open_text_short' );
					
					// Handle placeholders for not open text
					$open_date = $this->get_registration_open_date();
					if ( ! empty( $open_date ) ) {
						// Create EvgeDateTime object with proper timezone
						$open_date_time = new EvgeDateTime( new \DateTime( $open_date, DateFormatter::timezone_object( $this->get_the_timezone() ) ) );
						$current_time = new EvgeDateTime( new \DateTime( 'now', DateFormatter::timezone_object( $this->get_the_timezone() ) ) );
						
						// Format the open date using DateFormatter
						$formatted_date = DateFormatter::date_format( $open_date_time->format( 'Y-m-d H:i:s' ), 'registration_timeline' );
						
						// Replace placeholders
						$registration_text = str_replace( '{open-date}', $formatted_date, $registration_text );
						
						// Calculate countdown if needed
						if ( strpos( $registration_text, '{open-countdown}' ) !== false ) {
							// Calculate time difference
							$time_diff = $open_date_time->timestamp() - $current_time->timestamp();
							
							// Calculate days, hours, and minutes
							$days = floor( $time_diff / (60 * 60 * 24) );
							$hours = floor( ( $time_diff % (60 * 60 * 24) ) / (60 * 60) );
							$minutes = floor( ( $time_diff % (60 * 60) ) / 60 );
							
							// Build the countdown string
							$countdown_parts = array();
							if ( $days > 0 ) {
								/* translators: %d: Number of days */
								$countdown_parts[] = sprintf( _n( '%d day', '%d days', $days, 'event-genius' ), $days );
							}
							if ( $hours > 0 ) {
								/* translators: %d: Number of hours */
								$countdown_parts[] = sprintf( _n( '%d hour', '%d hours', $hours, 'event-genius' ), $hours );
							}
							if ( $minutes > 0 ) {
								/* translators: %d: Number of minutes */
								$countdown_parts[] = sprintf( _n( '%d minute', '%d minutes', $minutes, 'event-genius' ), $minutes );
							}
							
							$countdown = implode( ', ', $countdown_parts );
							$registration_text = str_replace( '{open-countdown}', $countdown, $registration_text );
						}
					}
				}
			}
			
			$about_items[] = array(
				'slug' => 'registration',
				'icon' => 'registration',
				'text' => $registration_text,
				'is_dynamic' => true,
				'content_type' => 'registration-status',
				'event_id' => $this->get_the_id(),
				'update_endpoint' => 'registration-status',
			);
			
			// Add attendee list if enabled and registration is open, but only on single event page
			if ( $this->get_show_attendee_list() === 'enabled' && $view === 'single' ) {

				if ( ! empty( $this->registration_counter ) ) {
					$edit_json_array = [
						'action' => 'evge_get_shortcode_content',
						'event_id' => $this->get_the_id(),
					];
					$edit_json_settings = array( 'width' => 'full' );
					$attendee_list_about = array(
						'slug' => 'attendees',
						'icon' => 'person',
						/* translators: %s: Number of confirmed attendees */
						'text' => sprintf( _n( '%s Attendee', '%s Attendees', $this->registration_counter->get_confirmed_count(), 'event-genius' ), $this->registration_counter->get_confirmed_count() ),
						'is_dynamic' => true,
						'content_type' => 'attendee-count',
						'event_id' => $this->get_the_id(),
						'update_endpoint' => 'attendee-count',
						'atts' => array(
							'data-evge-ajax' => wp_json_encode( $edit_json_array ),
							'data-evge-modal-content' => 'ajax',
							'data-evge-modal-settings' => wp_json_encode( $edit_json_settings ),
						),
						'link' => '#evge-attendees-list',
						'down_caret' => true,
					);

					// remove link if user is not logged in and the related event meta is set to logged in users only
					if ( ! is_user_logged_in() && $this->get_who_can_see_attendee_list() === 'logged_in' ) {
						unset( $attendee_list_about['link'] );
						unset( $attendee_list_about['down_caret'] );
					} else if ( empty( $this->registration_counter->get_confirmed_count( ) ) ) {
						unset( $attendee_list_about['link'] );
						unset( $attendee_list_about['down_caret'] );
					}
					
					$about_items[] = $attendee_list_about;
				}
			}
		}

		$about_items = apply_filters( 'evge_event_about_items', $about_items, $this->post_id );

		return $about_items;

	}

	public function get_duration() {
		if ( $this->is_all_day() ) {
			if ( ! empty( $this->post_meta['evge_start_date'] ) ) {
				$start_string = gmdate( 'M d Y', strtotime( $this->post_meta['evge_start_date'][0] ) );
				$end_string = gmdate( 'M d Y', strtotime( $this->post_meta['evge_end_date'][0] ) );

				$hours_between = Utils::hours_between_dates( $start_string, $end_string, $this->get_the_timezone() );

				return $hours_between + 24;
			}

		}
		$start_date = isset( $this->post_meta['evge_start_date'][0] ) ? $this->post_meta['evge_start_date'][0] : '0000-00-00 00:00:00';
		$end_date = isset( $this->post_meta['evge_end_date'][0] ) ? $this->post_meta['evge_end_date'][0] : '0000-00-00 00:00:00';
		return Utils::hours_between_dates( $start_date, $end_date, $this->get_the_timezone() );
	}

	public function get_the_duration_description() {
		$duration = $this->get_duration();
		if ( is_numeric( $duration ) ) {
			$days = floor( $duration / 24 );
			$hours = round( $duration - ($days * 24), 2 );
			if ( $days > 0 ) {
				if ( $hours === (float)0 ) {
					if ( $days === (float)1 ) {
						/* translators: %s: number of days */
						return sprintf( __( '%s day', 'event-genius' ), $days );
					}
					/* translators: %s: number of days */
					return sprintf( __( '%s days', 'event-genius' ), $days );
				}
				if ( $days === (float)1 ) {
					if ( $hours === (float)1 ) {
						/* translators: 1: number of days, 2: number of hours */
						return sprintf( __( '%1$s day %2$s hour', 'event-genius' ), $days, $hours );
					}
					/* translators: 1: number of days, 2: number of hours */
					return sprintf( __( '%1$s day %2$s hours', 'event-genius' ), $days, $hours );
				}				

				if ( $hours === (float)1 ) {
					/* translators: 1: number of days, 2: number of hours */
					return sprintf( __( '%1$s days %2$s hour', 'event-genius' ), $days, $hours );
				}
				/* translators: 1: number of days, 2: number of hours */
				return sprintf( __( '%1$s days %2$s hours', 'event-genius' ), $days, $hours );
			}
			if ( $hours === (float)1 ) {
				/* translators: %s: number of hours */
				return sprintf( __( '%s hour', 'event-genius' ), $hours );
			}
			/* translators: %s: number of hours */
			return sprintf( __( '%s hours', 'event-genius' ), round( $duration, 2 ) );
		} else {
			return $duration;
		}
	}

	public function get_the_content() {
		$content = get_post_field('post_content', $this->post_id);
		// Apply WordPress content filters to ensure proper block rendering
		return apply_filters('the_content', $content);
	}

	public function get_allow_registration() {
		if ( empty( $this->post_meta['evge_allow_registration'] ) ) {
			return Settings::get( 'allow_registration' );
		}
		return $this->post_meta['evge_allow_registration'][0];
	}

	public function get_show_attendee_list() {
		if ( empty( $this->post_meta['evge_show_attendee_list'] ) ) {
			return Settings::get( 'show_attendee_list' );
		}
		return $this->post_meta['evge_show_attendee_list'][0];
	}

	public function get_show_export_options() {
		return Settings::get( 'show_export_options' );
	}

	public function get_registration_open_date() {
		$open_type = !empty($this->post_meta['evge_open_type']) ? $this->post_meta['evge_open_type'][0] : 'immediately';

		if ($open_type === 'immediately') {
			return 100;
		}

		if ($open_type === 'custom' && !empty($this->post_meta['evge_open_date'])) {
			return $this->post_meta['evge_open_date'][0];
		}

		if ($open_type === 'relative') {
			$open_relative_offset = !empty($this->post_meta['evge_relative_open_offset']) ? absint($this->post_meta['evge_relative_open_offset'][0]) : 0;
			$open_relative_unit = !empty($this->post_meta['evge_relative_open_offset_type']) ? $this->post_meta['evge_relative_open_offset_type'][0] : 'hours';
			
			// Create DateTime objects with proper timezone
			$timezone = new \DateTimeZone($this->get_the_timezone());
			$start_date = new \DateTime($this->get_the_start_date_raw(), $timezone);
			
			// Create a clone to modify
			$open_date = clone $start_date;
			
			// Calculate the interval based on the unit
			if ($open_relative_unit === 'hours') {
				$interval = new \DateInterval('PT' . $open_relative_offset . 'H');
			} else {
				$interval = new \DateInterval('P' . $open_relative_offset . 'D');
			}
			
			// Subtract the interval from the start date
			$open_date->sub($interval);
			
			return $open_date->format('Y-m-d H:i:s');
		}

		return 100;
	}

	public function registration_is_open() {
		$open_type = ! empty( $this->post_meta['evge_open_type'] ) ? $this->post_meta['evge_open_type'][0] : 'immediately';
		if ( $open_type === 'immediately' ) {
			return true;
		}
		if ( $open_type === 'custom' ) {
			$open_date = ! empty( $this->post_meta['evge_open_date_utc'] ) ? strtotime( $this->post_meta['evge_open_date_utc'][0] ) : 0;

			if ( $open_date > time() ) {
				return false;
			}
			return true;
		}
		if ( $open_type === 'relative' ) {
			$open_relative_offset = ! empty( $this->post_meta['evge_relative_open_offset'] ) ? absint( $this->post_meta['evge_relative_open_offset'][0] ) : 0;
			$open_relative_unit = ! empty( $this->post_meta['evge_relative_open_offset_type'] ) ? $this->post_meta['evge_relative_open_offset_type'][0] : 'hours';
			if ( $open_relative_unit === 'hours' ) {
				$seconds_before = $open_relative_offset * 3600;
			} else {
				$seconds_before = $open_relative_offset * 86400;
			}

			if ( strtotime( $this->post_meta['evge_start_date_utc'][0] ) - $seconds_before > time() ) {
				return false;
			}
			return true;
		}

		return true;
	}

	public function registration_has_closed() {
		$close_type = ! empty( $this->post_meta['evge_close_type'] ) ? $this->post_meta['evge_close_type'][0] : 'immediately';
		if ( $close_type === 'never' ) {
			return false;
		}
		if ( $close_type === 'custom' ) {
			$close_date = ! empty( $this->post_meta['evge_close_date'] ) ? strtotime( $this->post_meta['evge_close_date'][0] ) : 0;
			if ( $close_date < strtotime(wp_date('Y-m-d H:i:s')) ) {
				return true;
			}
			return false;
		}
		if ( $close_type === 'relative' ) {
			$close_relative_offset = ! empty( $this->post_meta['evge_relative_close_offset'] ) ? absint( $this->post_meta['evge_relative_close_offset'][0] ) : 0;
			$close_relative_unit = ! empty( $this->post_meta['evge_relative_close_offset_type'] ) ? $this->post_meta['evge_relative_close_offset_type'][0] : 'hours';
			if ( $close_relative_unit === 'hours' ) {
				$seconds_before = $close_relative_offset * 3600;
			} else {
				$seconds_before = $close_relative_offset * 86400;
			}
			if ( strtotime( $this->post_meta['evge_start_date_utc'][0] ) - $seconds_before < time() ) {
				return true;
			}
			return false;
		}

		return false;
	}

	public function cancellation_has_closed() {
		if ( Settings::get( 'allow_cancellation' ) !== 'enabled' ) {
			return true;
		}

		$close_type = Settings::get( 'cancellation_close_type' );
		if ( $close_type === 'same' ) {
			return $this->registration_has_closed();
		}
		if ( $close_type === 'relative' ) {
			$close_relative_offset = Settings::get( 'cancellation_relative_close_offset' );
			$close_relative_unit = Settings::get( 'cancellation_relative_close_offset_unit' );
			if ( $close_relative_unit === 'hours' ) {
				$seconds_before = $close_relative_offset * 3600;
			} else {
				$seconds_before = $close_relative_offset * 86400;
			}
			if ( strtotime( $this->post_meta['evge_start_date_utc'][0] ) - $seconds_before < time() ) {
				return true;
			}
			return false;
		}

		return true;
	}

	public function get_the_list_cta() {
		return '<a href=" ' . esc_url( $this->get_the_permalink() ) . '">' . esc_html__( 'Read More', 'event-genius' ) . '</a>';
	}

	/**
	 * Get the CTA buttons for event listings (list, grid, month views)
	 * Returns Register button (if registration is enabled and active) and Learn More button
	 * 
	 * @param array $args Optional arguments
	 *   - 'bulk_registration' (bool): Whether bulk registration is enabled
	 * @return string HTML for listing CTA buttons
	 */
	public function get_the_listing_cta( $args = array() ) {
		$event_id = $this->get_the_id();
		$buttons = array();
		
		// Check if bulk registration is enabled from args
		$bulk_registration_enabled = ! empty( $args['bulk_registration'] );
		
		// Register button (only if registration is enabled and active)
		if ( $this->get_allow_registration() === 'enabled' 
			&& ! $this->registration_has_closed() 
			&& $this->registration_is_open() 
			&& ! $this->registration_has_filled() ) {
			
			$event_goer = EVGE()->event_goer();
			$event_goer->set_event( $this );
			
			if ( $event_goer && $event_goer->can_register_for_event() ) {
				// Pass bulk registration status in args to get_the_cta
				$cta_args = array_merge( array( 'hide_already_registered' => true ), $args );
				$buttons[] = $this->get_the_cta( $cta_args );
			}
		}
		
		// Learn More button (always shown)
		$learn_more_text = Settings::get( 'learn_more_text' );
		$learn_more_target = $bulk_registration_enabled ? ' target="_blank" rel="noopener noreferrer"' : '';
		$buttons[] = '<a href="' . esc_url( $this->get_the_permalink() ) . '" class="evge-button evge-secondary evge-gray-button evge-learn-more"' . $learn_more_target . '>
			<span class="evge-icon-text">
				' . esc_html( $learn_more_text ) . '
				' . Icon::get( 'right-chevron' ) . '
			</span>
		</a>';
		
		if ( empty( $buttons ) ) {
			return '';
		}
		
		$html = '<div class="evge-event-list-actions evge-flex-center">' . implode( '', $buttons ) . '</div>';
		
		// Allow filtering of the listing CTA HTML (for backward compatibility)
		$html = apply_filters( 'evge_event_listing_cta', $html, $this );
		
		return $html;
	}

	public function registration_has_filled() {
		if (empty($this->get_the_capacity())) {
			return false;
		}
		
		if ( empty( $this->registration_counter ) ) {
			return false;
		}
		
		// Count confirmed + pending (payment processing) toward capacity
		return $this->registration_counter->get_active_count() >= $this->get_the_capacity();
	}

	public function get_the_cta( $args = array() ) {
		// Get form-specific register button settings
		$form = $this->get_form();
		$button_color = $form->get_register_button_text_color();
		$button_background = $form->get_register_button_background_color();
		$button_border = $form->get_register_button_border_color();

		$styles_array = array();
		if ( ! empty( $button_color ) ) {
			$styles_array[] = 'color: ' . esc_attr( $button_color );
		}

		if ( ! empty( $button_background ) ) {
			$styles_array[] = 'background-color: ' . esc_attr( $button_background );
		}

		if ( ! empty( $button_border ) ) {
			$styles_array[] = 'border: 1px solid ' . esc_attr( $button_border );
		}
		$style_att = '';
		if ( ! empty( $styles_array ) ) {
			$styles = implode( ';', $styles_array );
			$style_att = ' style="' . $styles . '"';
		}
		$cta = '';
		if ( $this->get_allow_registration() === 'enabled' ) {
			// Check and show payment-related notices
			PaymentNoticeService::check_and_show_payment_notices( $this );
			
			if ( $this->registration_has_closed() ) {
				$cta = $this->closed_message();
			} elseif ( ! $this->registration_is_open() ) {
				$cta = $this->not_open_until_message();
			} else {
				if ( $this->registration_has_filled() ) {
					$cta = $this->filled_message();
					// Show cancellation CTA (already registered tool) even when registration is filled,
					// as long as cancellation hasn't closed and registration hasn't closed
					$cancellation_cta = '';
					if ( ! $this->cancellation_has_closed() && empty( $args['hide_already_registered'] ) ) {
						$cancellation_cta = $this->get_the_cancellation_cta();
					}
					$cta = $cta . $cancellation_cta;
				} else {

					// Check if current user can register for this event
					$event_goer = EVGE()->event_goer();
					$event_goer->set_event( $this );
					if ( ! $event_goer || ! $event_goer->can_register_for_event() ) {
						// Show login to register button instead of registration button
						$login_button_text = __( 'Log In to Register', 'event-genius' );
						$login_url = wp_login_url( get_permalink() );
						
						$cta = '<a href="' . esc_url( $login_url ) . '" class="evge-cta evge-button"' . $style_att . '>' . 
						esc_html( $login_button_text ) . '</a>';
					} else {
						// User can register, show registration button
						$edit_json_settings = array( 'width' => 'full' );

						// If user has a pending registration that requires payment, show checkout instead
						$register_button_text = apply_filters( 'evge_form_register_button_text', $form->get_register_button_text(), $this );
						$registration_cta = '<button class="evge-cta evge-modal-trigger evge-checkout-cache-' . $this->get_the_id() . '" data-evge-modal-settings="' . esc_attr( wp_json_encode( $edit_json_settings ) ) . '" data-evge-modal-content="ajax" data-evge-ajax="' . esc_attr( $this->event_json( 'evge_get_registration_content' ) ) . '"' . $style_att . '>' . esc_html( $register_button_text ) . '</button>';
						
						// Enqueue registration form script (ScriptService handles duplicate prevention)
						EVGE()->script_service()->enqueue_script('evge_common');
						EVGE()->script_service()->enqueue_script('evge_registration_form');
						
						$cancellation_cta = '';
						if ( ! $this->cancellation_has_closed() && empty( $args['hide_already_registered'] ) ) {
							$cancellation_cta = $this->get_the_cancellation_cta();
						}
						$cta = $registration_cta . $cancellation_cta;
					}
				}
			}
			
		} else {
			$button_class = 'evge-cta';
			$cta = $this->list_export_html( $button_class, $styles_array );
		}
		
		$cta = apply_filters( 'evge_event_single_cta', $cta, $this, $args );
		return $cta;
	}

    public function submit_button_style_att() {
	    // Get form-specific button settings
	    $form = $this->get_form();
	    $button_color = $form->get_submit_button_text_color();
	    $button_background = $form->get_submit_button_background_color();
	    $border_color = $form->get_submit_button_border_color();

	    $styles_array = array();
	    if ( ! empty( $button_color ) ) {
		    $styles_array[] = 'color: ' . esc_attr( $button_color );
	    }

	    if ( ! empty( $button_background ) ) {
		    $styles_array[] = 'background-color: ' . esc_attr( $button_background );
	    }

	    if ( ! empty( $border_color ) ) {
		    $styles_array[] = 'border: 1px solid ' . esc_attr( $border_color );
	    }

	    $style_att = '';
	    if ( ! empty( $styles_array ) ) {
		    $styles = implode( ';', $styles_array );
		    $style_att = ' style="' . $styles . '"';
	    }

        return $style_att;

    }

	/**
	 * Get the event ID input field(s) for the registration form
	 * 
	 * For single event registration, returns a single hidden input.
	 * Can be overridden in child classes for bulk registration.
	 * 
	 * @return string HTML for the event ID input field(s)
	 */
	public function get_event_id_input() {
		return '<input type="hidden" 
			name="event_id" 
			value="' . esc_attr( $this->get_the_id() ) . '">';
	}

	public function closed_message() {
		$closed_text = Settings::get( 'registration_closed_text' );
		return '<div class="evge-closed-message evge-status-message">' . esc_html( $closed_text ) . '</div>';

	}

	public function not_open_until_message() {
		// Get the message from settings
		$message = Settings::get('registration_not_open_text');
		
		// Get the registration open date
		$open_date = $this->get_registration_open_date();
		
		if (! empty($open_date)) {
			// Create EVGEDateTime object with proper timezone
			$open_date_time = new EvgeDateTime(new \DateTime($open_date, DateFormatter::timezone_object($this->get_the_timezone())));
			$current_time = new EvgeDateTime(new \DateTime('now', DateFormatter::timezone_object($this->get_the_timezone())));
			
			// Calculate time difference
			$time_diff = $open_date_time->timestamp() - $current_time->timestamp();
			
			// Calculate days, hours, and minutes
			$days = floor($time_diff / (60 * 60 * 24));
			$hours = floor(($time_diff % (60 * 60 * 24)) / (60 * 60));
			$minutes = floor(($time_diff % (60 * 60)) / 60);
			
			// Format the open date using DateFormatter
			$formatted_date = DateFormatter::date_format($open_date_time->format('Y-m-d H:i:s'), 'registration_timeline');
			
			// Build the countdown string
			$countdown_parts = array();
			if ($days > 0) {
				/* translators: %d: Number of days */
				$countdown_parts[] = sprintf(_n('%d day', '%d days', $days, 'event-genius'), $days);
			}
			if ($hours > 0) {
				/* translators: %d: Number of hours */
				$countdown_parts[] = sprintf(_n('%d hour', '%d hours', $hours, 'event-genius'), $hours);
			}
			if ($minutes > 0) {
				/* translators: %d: Number of minutes */
				$countdown_parts[] = sprintf(_n('%d minute', '%d minutes', $minutes, 'event-genius'), $minutes);
			}
			
			$countdown = implode(', ', $countdown_parts);
			
			// Replace placeholders
			$message = str_replace('{open-date}', $formatted_date, $message);
			$message = str_replace('{open-countdown}', $countdown, $message);
		}

		return '<div class="evge-not-open-message evge-status-message">' . esc_html($message) . '</div>';
	}

	public function filled_message() {
		$filled_text = Settings::get('registration_filled_text');
		return '<div class="evge-filled-message evge-status-message">' . esc_html($filled_text) . '</div>';
	}

	public function list_export_html( $button_class, $styles_array = array() ) {
		$event_post = $this;
		$template_path = EVGE()->template_manager()->locate_template('common/calendar-export.php');
		if ($template_path) {
			ob_start();
			include $template_path;
			$return = ob_get_contents();
			ob_end_clean();
			return $return;
		}
		return '';
	}

	public function get_registration_capacity_text() {
		if ( empty( $this->registration_counter ) ) {
			return '';
		}
		
		$remaining = empty( $this->get_the_capacity() ) ? '∞' : $this->registration_counter->get_remaining_count( $this->get_the_capacity() );
		// Count confirmed + pending (payment processing) toward capacity for display
		$count = $this->registration_counter->get_active_count();
		
		if ( empty( $this->get_the_capacity() ) ) {
			return '∞';
		}

		// Get the raw text template
		$raw_text = Settings::get( 'registration_capacity_text' );
		
		// Check which placeholder is used in the template
		$is_remaining = strpos($raw_text, '{remaining}') !== false;
		$is_count = strpos($raw_text, '{count}') !== false;
		
		// Determine if we should use singular text based on the placeholder used
		$use_singular = false;
		if ($is_remaining) {
			$use_singular = $remaining === 1;
		} elseif ($is_count) {
			$use_singular = $count === 1;
		}
		
		// Get the appropriate text template
		$raw_text = $use_singular ? 
			Settings::get( 'registration_capacity_text_singular' ) : 
			Settings::get( 'registration_capacity_text' );

		$find_replace = array(
			'{capacity}' => $this->get_the_capacity_display(),
			'{remaining}' => $remaining,
			'{count}' => $count,
		);

		$final_text = $raw_text;
		foreach ( $find_replace as $find => $replace ) {
			$final_text = str_replace( $find, $replace, $final_text );
		}

		return $final_text;
	}

	public function get_registration_capacity_text_short() {
		if ( empty( $this->registration_counter ) ) {
			return '';
		}
		
		$remaining = empty( $this->get_the_capacity() ) ? '∞' : $this->registration_counter->get_remaining_count( $this->get_the_capacity() );
		// Count confirmed + pending (payment processing) toward capacity for display
		$count = $this->registration_counter->get_active_count();
		
		if ( empty( $this->get_the_capacity() ) ) {
			return '∞';
		}

		// Get the raw text template
		$raw_text = Settings::get( 'registration_capacity_text_short' );
		
		// Check which placeholder is used in the template
		$is_remaining = strpos($raw_text, '{remaining}') !== false;
		$is_count = strpos($raw_text, '{count}') !== false;
		
		// Determine if we should use singular text based on the placeholder used
		$use_singular = false;
		if ($is_remaining) {
			$use_singular = $remaining === 1;
		} elseif ($is_count) {
			$use_singular = $count === 1;
		}
		
		// Get the appropriate text template
		$raw_text = $use_singular ? 
			Settings::get( 'registration_capacity_text_singular' ) : 
			Settings::get( 'registration_capacity_text_short' );

		$find_replace = array(
			'{remaining}' => $remaining,
			'{capacity}' => $this->get_the_capacity(),
			'{count}' => $count,
		);

		return str_replace( array_keys( $find_replace ), array_values( $find_replace ), $raw_text );
	}

	public function get_the_cancellation_cta() {
		$json_settings =  array( 'width' => 'narrow' );
		$already_registered_text = Settings::get( 'already_registered' );

		$cancellation_cta = '<a href="javascript:void(0);" class="evge-modal-trigger evge-already-registered" data-evge-modal-content="ajax" data-evge-ajax="' . esc_attr( $this->event_json( 'evge_get_already_registered_content' ) ) . '" data-evge-modal-settings="' . esc_attr( wp_json_encode( $json_settings ) ) . '" >' . esc_html( $already_registered_text ) . '</a>';
		
		// Apply filter to allow Pro functionality to modify the cancellation CTA
		return apply_filters( 'evge_event_cancellation_cta', $cancellation_cta, $this );
	}

	public function get_the_alt() {
		return $this->get_the_title() . ' ' . $this->get_the_date_summary();
	}


	public function get_tags() {
		$tags = get_the_terms( $this->post_id, 'evge_event_tag' );

		$return = array();
		if ( ! empty( $tags ) ) {
			foreach ( $tags as $tag ) {
				$return[] = array(
					'slug' => $tag->slug,
					'label' => $tag->name,
					'id' => $tag->term_id,
				);
			}
		}

		return $return;
	}

	public function display_form_fields( $registration_data = array(), $flags = array() ) {
		$templater = new Templater();
		
		// Check if we should output a wrapper div
		// Default to true for backward compatibility (admin forms, etc.)
		// But form.php can disable it by passing output_wrapper => false in flags
		$output_wrapper = ! isset( $flags['output_wrapper'] ) || $flags['output_wrapper'] !== false;
		
		if ( $output_wrapper ) {
			?>
			<div class="evge-registration-form-fields">
			<?php
		}
		
		// Use get_form() instead of $this->registration_form to allow overrides
		// (e.g., BulkEventPost can return merged form)
		$form = $this->get_form();
		foreach ( $form->get_fields() as $field ) {
			if ( $field->is_hidden( $flags ) ) {
				continue;
			}
			include $templater->get_registration_template_part( 'field' );
		}
		
		if ( $output_wrapper ) {
			?>
			</div>
			<?php
		}
	}

	public function maybe_display_honeypot() {
		if ( is_user_logged_in() ) {
			return;
		}	

		$args = array(
			'id' => 'honeypot',
			'slug' => 'honeypot',
			'label' => __('If you see this, please leave the field blank.', 'event-genius'),
			'type' => 'honeypot',
			'value' => '',
			'placeholder' => '',
			'default' => '',
			'options' => array(),
			'main_show' => true,
			'main_required' => false,
			'guest_show' => true,
			'guest_required' => false,
			'show_in_attendee_list' => false,
			'validation' => array(
				'min' => 0,
				'max' => 0
			),
			'error_message' => '',
			'special_validation' => array(),
			'misc' => array()
		);	
		$field = new HoneyPotType( $args );
		$templater = new Templater();
		$flags = array();
		include $templater->get_registration_template_part( 'field' );
	}

	public function get_the_color() {
		// Default list event color
		return '#1A79C1';
	}

	private function event_json( $action ) {
		$json_array = array(
			'event_id' => $this->post_id,
			'action' => $action
		);

		return wp_json_encode( $json_array );
	}

	/**
	 * Get event JSON data for AJAX requests (public wrapper for event_json)
	 * 
	 * @param string $action The action to perform
	 * @return string JSON encoded data
	 */
	public function get_event_json( $action ) {
		return $this->event_json( $action );
	}

	public function get_categories() {
		$categories = get_the_terms($this->post_id, EVGE_EVENT_CATEGORY_TYPE);
		
		$return = array();
		if (!empty($categories)) {
			foreach ($categories as $category) {
				$return[] = array(
					'slug' => $category->slug,
					'label' => $category->name,
					'id' => $category->term_id,
				);
			}
		}
		
		return $return;
	}

	/**
	 * Check if a specific section should be displayed on the single event page
	 * 
	 * @param string $section_key The section identifier (e.g., 'organizer', 'categories', 'tags')
	 * @return bool Whether the section should be displayed
	 */
	public function should_show_section($section_key) {
		$hide_title_and_featured_image = wp_is_block_theme() || Settings::get('event_template') === 'theme';

		if ($hide_title_and_featured_image) {
			if ($section_key === 'featured_image' || $section_key === 'title') {
				return false;
			}
		}

		$enabled_sections = Settings::get('single_event_elements');

		if ($section_key === 'capacity' && $this->get_the_capacity() === 0) {
			return false;
		}
		
		// If settings are not set or invalid, show all sections by default
		if (!is_array($enabled_sections)) {
			return true;
		}
		
		return in_array($section_key, $enabled_sections, true);
	}

	/**
	 * Get the recurrence icon if this event is recurring
	 * 
	 * @return string HTML for the recurring icon or empty string
	 */
	public function recurrence_display() {
		$recurrence_type = get_post_meta($this->post_id, 'evge_recurrence_type', true);
		$is_recurrence = get_post_meta($this->post_id, 'evge_is_recurrence', true);
		
		if ($recurrence_type && $recurrence_type !== 'none' || $is_recurrence) {
			return Icon::get('recurring') . ' ';
		}
		
		return '';
	}

	/**
	 * Check if a venue post exists and is published
	 * 
	 * @param int $venue_id The venue post ID to check
	 * @return bool Whether the venue exists and is published
	 */
	public function venue_exists( $venue_id ) {
		if ( empty( $venue_id ) ) {
			return false;
		}
		
		$post_status = get_post_status( $venue_id );
		return $post_status === 'publish';
	}

	/**
	 * Check if an organizer post exists and is published
	 * 
	 * @param int $organizer_id The organizer post ID to check
	 * @return bool Whether the organizer exists and is published
	 */
	public function organizer_exists( $organizer_id ) {
		if ( empty( $organizer_id ) ) {
			return false;
		}
		
		$post_status = get_post_status( $organizer_id );
		return $post_status === 'publish';
	}

}
