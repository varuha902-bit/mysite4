<?php
namespace WPEventGenius\Common\Utils;

use WPEventGenius\Common\Event\Event;
use WPEventGenius\Common\Registration\Registration\Registration;
use WPEventGenius\Common\Event\EventPost;
use WPEventGenius\Common\Event\VenuePost;
use WPEventGenius\Common\Utils\EventExport\Options;	

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class Placeholders {

	protected $registration;

	protected $event;

	protected $context;

	protected $show_admin_placeholders;

	public function __construct( Registration $registration, Event $event, $context = 'email', $show_admin_placeholders = false ) {
		$this->registration = $registration;
		$this->event        = $event;
		$this->context = $context;
		$this->show_admin_placeholders = $show_admin_placeholders;
	}

	public function replace( $text ) {
		$working_text = $this->normalize_url_placeholders( $text );

		$replace_fields = $this->data();
		foreach ( $replace_fields as $replace_data ) {
			// Skip admin placeholders if not allowed
			if ( !$this->show_admin_placeholders && isset($replace_data['category']) && $replace_data['category'] === 'admin' ) {
				$working_text = str_replace( $replace_data['placeholder'], '', $working_text );
				continue;
			}

			$working_value = is_array( $replace_data['value'] ) ? implode( ', ', $replace_data['value'] ) : $replace_data['value'];
			$working_text = str_replace( $replace_data['placeholder'], $working_value, $working_text );
		}

		// replace any remaining placeholders with empty strings
		$working_text = preg_replace('/{([^}]+)}/', '', $working_text);
		return $working_text;
	}

	/**
	 * Normalize URL placeholders before replacement.
	 * Converts http://{placeholder} and https://{placeholder} to {placeholder}
	 * so that href attributes (e.g. href="http://{ical-url}") are replaced
	 * with the actual URL by the normal placeholder replacement.
	 *
	 * @param string $text Content that may contain scheme-prefixed placeholders.
	 * @return string Content with scheme prefix stripped from placeholders.
	 */
	protected function normalize_url_placeholders( $text ) {
		if ( $text === null ) {
			$text = '';
		}

		return preg_replace( '#https?://(\{[^}]+\})#', '$1', $text );
	}

	public function data_categories() {
		$categories = array(
			array(
				'name' => 'event',
				'label' => __( 'Event', 'event-genius' ),
			),
			array(
				'name' => 'venue',
				'label' => __( 'Venue', 'event-genius' ),
			),
			array(
				'name' => 'registration',
				'label' => __( 'Registration', 'event-genius' ),
			),
			array(
				'name' => 'actions',
				'label' => __( 'Actions', 'event-genius' ),
			),
			array(
				'name' => 'admin',
				'label' => __( 'Admin Only', 'event-genius' ),
			),
		);

		$categories = apply_filters( 'evge_placeholders_categories', $categories );

		return $categories;
	}

	public function data() {
		$event_post = new EventPost( $this->event->get_post_id() );
		$event_export_options = new Options( $event_post );
		$event_export_options = $event_export_options->get_options();
		$gcal_link = $event_export_options['google']['url'];
		$ical_link = $event_export_options['ical']['url'];
		$data = array(
			'event_title' => array(
				'value' => $event_post->get_the_title(),
				'description' => __( 'The title of the event.', 'event-genius' ),
				'category' => 'event',
				'placeholder' => '{event-title}',
			),
			'venue_title' => array(
				'value' => $event_post->get_the_venue_title(),
				'description' => __( 'The title of the venue.', 'event-genius' ),
				'category' => 'venue',
				'placeholder' => '{venue-title}',
			),
			'venue_address' => array(
				'value' => $this->event->get( 'venue-address' ),
				'description' => __( 'The address of the venue.', 'event-genius' ),
				'category' => 'venue',
				'placeholder' => '{venue-address}',
			),
			'venue_city' => array(
				'value' => $this->event->get( 'venue-city' ),
				'description' => __( 'The city of the venue.', 'event-genius' ),
				'category' => 'venue',
				'placeholder' => '{venue-city}',
			),
			'venue_state' => array(
				'value' => $this->event->get( 'venue-state' ),
				'description' => __( 'The state of the venue.', 'event-genius' ),
				'category' => 'venue',
				'placeholder' => '{venue-state}',
			),
			'venue_zip' => array(
				'value' => $this->event->get( 'venue-zip' ),
				'description' => __( 'The zip code of the venue.', 'event-genius' ),
				'category' => 'venue',
				'placeholder' => '{venue-zip}',
			),
			'start_date' => array(
				'value' => DateFormatter::date_format( $event_post->get_the_start_date(), 'full' ),
				'description' => __( 'The start date of the event.', 'event-genius' ),
				'category' => 'event',
				'placeholder' => '{start-date}',
			),
			'start_time' => array(
				'value' => $event_post->get_the_start_time( true ),
				'description' => __( 'The start time of the event.', 'event-genius' ),
				'category' => 'event',
				'placeholder' => '{start-time}',
			),
			'end_date' => array(
				'value' => DateFormatter::date_format( $event_post->get_the_end_date(), 'full' ),
				'description' => __( 'The end date of the event.', 'event-genius' ),
				'category' => 'event',
				'placeholder' => '{end-date}',
			),
			'end_time' => array(
				'value' => $event_post->get_the_end_time( true ),
				'description' => __( 'The end time of the event.', 'event-genius' ),
				'category' => 'event',
				'placeholder' => '{end-time}',
			),
			'date_summary' => array(
				'value' => $event_post->get_the_date_summary(),
				'description' => __( 'The date summary of the event.', 'event-genius' ),
				'category' => 'event',
				'placeholder' => '{date-summary}',
			),
			'event_duration' => array(
				'value' => $event_post->get_the_duration_description(),
				'description' => __( 'The duration of the event (e.g., "2 hours" or "1 day 3 hours").', 'event-genius' ),
				'category' => 'event',
				'placeholder' => '{event-duration}',
			),
			'event_cost' => array(
				'value' => $event_post->get_the_cost_display(),
				'description' => __( 'The cost of the event with currency symbol.', 'event-genius' ),
				'category' => 'event',
				'placeholder' => '{event-cost}',
			),
			'venue_phone' => array(
				'value' => $event_post->get_the_venue_id() ? (new VenuePost($event_post->get_the_venue_id()))->get_the_phone() : '',
				'description' => __( 'The phone number of the venue.', 'event-genius' ),
				'category' => 'venue',
				'placeholder' => '{venue-phone}',
			),
			'event_link' => array(
				'value' => $event_post->get_the_permalink(),
				'description' => __( 'The URL to view the event on the website.', 'event-genius' ),
				'category' => 'event',
				'placeholder' => '{event-link}',
			),
			'organizer_name' => array(
				'value' => $event_post->get_the_organizer_id() ? get_the_title($event_post->get_the_organizer_id()) : '',
				'description' => __( 'The name of the event organizer.', 'event-genius' ),
				'category' => 'event',
				'placeholder' => '{organizer-name}',
			),
		'all_fields' => array(
			'value' => $this->all_fields(),
			'description' => __( 'All fields submitted in the registration form.', 'event-genius' ),
			'category' => 'registration',
			'placeholder' => '{all-fields}',
		),
		'registration_cancel_button' => array(
				'value' => $this->cancel_button( $this->context === 'email', $this->context ),
				'description' => __( 'The button to cancel the registration.', 'event-genius' ),
				'category' => 'actions',
				'placeholder' => '{registration-cancel-button}',
			),
			'gcal_link' => array(
				'value' => sprintf(
					'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
					esc_url($gcal_link),
					esc_html__('+ Google Calendar', 'event-genius')
				),
				'description' => __( '"+ Google Calendar" link to add this event to Google Calendar.', 'event-genius' ),
				'category' => 'actions',
				'placeholder' => '{gcal-link}',
			),
			'ical_link' => array(
				'value' => sprintf(
					'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
					esc_url($ical_link),
					esc_html__('+ iCal', 'event-genius')
				),
				'description' => __( '"+ iCal" link to download the iCal file for this event.', 'event-genius' ),
				'category' => 'actions',
				'placeholder' => '{ical-link}',
			),
			'gcal_url' => array(
				'value' => $gcal_link,
				'description' => __( 'The raw URL to add this event to Google Calendar.', 'event-genius' ),
				'category' => 'actions',
				'placeholder' => '{gcal-url}',
			),
			'ical_url' => array(
				'value' => $ical_link,
				'description' => __( 'The raw URL to download the iCal file for this event.', 'event-genius' ),
				'category' => 'actions',
				'placeholder' => '{ical-url}',
			),
			'manage_registration' => array(
				'value' => sprintf(
					'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
					esc_url(add_query_arg(
						array(
							'registration_id' => $this->registration->get_entry_id(),
							'tab' => 'registrations',
							'back_page' => 'evge-all-events',
							'page' => 'evge-registrations'
						),
						admin_url('admin.php')
					)),
					esc_html__('Manage Registration', 'event-genius')
				),
				'description' => __( 'The link to manage the registration in the admin.', 'event-genius' ),
				'category' => 'admin',
				'placeholder' => '{admin-manage-registration}',
			),
			'confirmation_code' => array(
				'value' => $this->get_confirmation_code(),
				'description' => __( 'The confirmation code for this registration.', 'event-genius' ),
				'category' => 'registration',
				'placeholder' => '{confirmation-code}',
			),
		);

		$registration_data = array();
		if ( ! empty( $this->registration ) ) {
			$registration_data = $this->registration->get_registration_data();
		}

		foreach ( $this->event->get_form()->get_fields() as $field ) {
			$key = $field->get_slug();
			$raw_value = ! empty( $registration_data[ $key ] ) ? $registration_data[ $key ] : '';
			$formatted_value = $this->format_field_value( $field, $raw_value );
			
			$data[ $key ] = array(
				'value' => $formatted_value,
				'description' => $field->get_label(),
				'category' => 'registration',
				'placeholder' => '{' . $key . '}',
			);
		}

		return apply_filters( 'evge_placeholders', $data, $this->registration, $this->event, $this->context );
	}

	public function placeholder_reference_table($data, $show_admin_placeholders = true, $categories = array()) {
		if ( empty( $categories ) ) {
			$categories = $this->data_categories();
		}
		Notices::exclamation(__('Click the button next to the placeholder to add it to your message.', 'event-genius'), ' evge-blue-exclamation');
		$html = '';
		
		$html .= '<div class="evge-placeholder-reference">';
		
		// Search field
		$html .= '<div class="evge-placeholder-header">';
		$html .= '<div class="evge-placeholder-tabs">';
		foreach ($categories as $category) {
			// Skip admin category if not allowed
			if (!$show_admin_placeholders && $category['name'] === 'admin') {
				continue;
			}
			$html .= sprintf(
				'<button type="button" class="evge-placeholder-tab" data-category="%s">%s</button>',
				esc_attr($category['name']),
				esc_html($category['label'])
			);
		}
		$html .= '</div>';
		
		$html .= '<div class="evge-placeholder-search">';
		$html .= '<img src="' . esc_url(EVGE_PLUGIN_URL . 'assets/images/admin/svgs/search.svg') . '" alt="">';
		$html .= '<input type="text" class="evge-placeholder-search-input" placeholder="' . esc_attr__('Search placeholders', 'event-genius') . '">';
		$html .= '</div>';
		$html .= '</div>';
		
		// Placeholder content
		$html .= '<div class="evge-placeholder-content">';
		foreach ($categories as $category) {
			// Skip admin category if not allowed
			if (!$show_admin_placeholders && $category['name'] === 'admin') {
				continue;
			}
			
			$html .= sprintf('<div class="evge-placeholder-category" data-category="%s">', esc_attr($category['name']));
			
			// Filter placeholders for this category
			$category_placeholders = array_filter($data, function($item) use ($category) {
				return isset($item['category']) && $item['category'] === $category['name'];
			});
			
			$total_items = count($category_placeholders);
			$counter = 0;
			
			foreach ($category_placeholders as $item) {
				$counter++;
				if (isset($item['placeholder']) && isset($item['description'])) {
					$html .= sprintf(
						'<div class="evge-placeholder-item%s">',
						$counter > 5 ? ' evge-placeholder-hidden' : ''
					);
					$html .= '<div class="evge-placeholder-info">';
					$html .= '<code class="evge-placeholder-code">' . esc_html($item['placeholder']) . '</code>';
					$html .= '<span class="evge-placeholder-description">' . esc_html($item['description']) . '</span>';
					$html .= '</div>';
					$html .= '<button type="button" class="evge-placeholder-insert" data-placeholder="' . esc_attr($item['placeholder']) . '">';
					$html .= '+ ' . esc_html__('Insert', 'event-genius');
					$html .= '</button>';
					$html .= '</div>';
				}
			}
			
			if ($total_items > 5) {
				$remaining = $total_items - 5;
				$html .= sprintf(
					'<button type="button" class="evge-show-more-placeholders">%s</button>',
					sprintf(
						/* translators: %d: total number of placeholders available */
						__('Show more', 'event-genius'),
						$total_items
					)
				);
			}
			
			$html .= '</div>';
		}
		$html .= '</div>'; // End placeholder-content
		
		$html .= '</div>'; // End placeholder-reference
		
		return $html;
	}

	protected function all_fields() {
		$data = $this->registration->get_registration_data();
		$all_fields_html = '<table><tbody>';
		foreach ( $this->event->get_form()->get_fields() as $field ) {
			$key = $field->get_slug();
			$value = ! empty( $data[ $key ] ) ? $data[ $key ] : '';
			
			// Get formatted field value using helper method
			$formatted_value = $this->format_field_value( $field, $value );
			
			$all_fields_html .= '<tr>';
			$all_fields_html .= '<td>';
			$all_fields_html .= esc_html( $field->get_label() ) . ':';
			$all_fields_html .= '</td>';
			$all_fields_html .= '<td>';
			$all_fields_html .= wp_kses_post( $formatted_value );
			$all_fields_html .= '</td>';

			$all_fields_html .= '</tr>';

		}

		$all_fields_html .= '</tbody></table>';

		return $all_fields_html;
	}

	/**
	 * Format a field value for display
	 * This method can be overridden by child classes to add custom formatting
	 * 
	 * @param object $field The field object
	 * @param mixed $value The field value
	 * @return string Formatted field value
	 */
	protected function format_field_value( $field, $value ) {
		if ( is_array( $value ) ) {
			$value = implode( ', ', $value );
		}
		return $value;
	}

	protected function cancel_button( $require_confirm = false, $context = 'email' ) {
		$base_action_url = Utils::get_action_url( $this->event );

		$query_args = array(
			'evge_action' => 'cancel',
			'evge_key' => $this->registration->get_action_key(),
			'evge_post' => $this->event->get_post_id(),
		);

		if ( $require_confirm ) {
			$query_args['evge_confirm'] = '1';
		}

		$button_url = add_query_arg( $query_args, $base_action_url );
		$button_text = Settings::get( 'cancel_button_text' );
		ob_start();
		if ( $context === 'email' ) {
			include trailingslashit( EVGE_PLUGIN_PATH ) . 'templates/event-genius/email/partials/cta-button.php';
		} else {
			?>
			<a href="<?php echo esc_url( $button_url ); ?>" target="_blank" style="text-decoration:underline;background-color:#ffffff;border:solid 1px #3498db;border-radius:5px;box-sizing:border-box;color:#3498db;cursor:pointer;display:inline-block;font-size:14px;font-weight:bold;margin:0;padding:12px 25px;text-decoration:none;text-transform:capitalize;background-color:#3498db;border-color:#3498db;color:#ffffff;"><?php echo esc_html( $button_text ); ?></a>
			<?php
		}

		$return = ob_get_contents();
		ob_end_clean();

		return $return;
	}

	/**
	 * Get confirmation code for the registration
	 * 
	 * @return string Confirmation code or empty string if not found
	 */
	protected function get_confirmation_code() {
		if ( empty( $this->registration ) ) {
			return '';
		}

		$registration_data = $this->registration->get_registration_data();
		
		// Check if confirmation_code is in registration data
		if ( isset( $registration_data['confirmation_code'] ) && ! empty( $registration_data['confirmation_code'] ) ) {
			return $registration_data['confirmation_code'];
		}

		// If not found, try to get it from the entry ID
		$entry_id = $this->registration->get_entry_id();
		if ( empty( $entry_id ) ) {
			return '';
		}

		// Get confirmation code from database
		$database = new \WPEventGenius\Common\Database();
		$where = array(
			array(
				'column' => 'id',
				'value' => $entry_id,
				'compare' => '=',
				'type' => 'int'
			)
		);
		$registrations = $database->registration_query( $where );
		
		if ( ! empty( $registrations[0]['confirmation_code'] ) ) {
			return $registrations[0]['confirmation_code'];
		}

		return '';
	}
}