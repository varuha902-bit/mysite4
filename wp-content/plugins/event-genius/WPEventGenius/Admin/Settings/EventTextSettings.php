<?php

namespace WPEventGenius\Admin\Settings;

use WPEventGenius\Common\Utils\Defaults;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class EventTextSettings extends BaseSettings {

	protected $page = 'evge_event_settings';

	protected $tab = 'text-event';


	public function __construct() {
	}

	public function settings() {
		$this->register_setting();

		add_settings_section(
			'evge_event_text',
			'',
			array( $this, 'section_callback' ),
			'evge_event_text'
		);

		$text_fields = array(
			array(
				'id' => 'date_and_time_text',
				'default' => Defaults::get( 'date_and_time_text' ),
				'tooltip' => __( '"Date and Time" appears as the heading to the section with the start and end date.', 'event-genius' ),
			),
			array(
				'id' => 'locations_text',
				'default' => Defaults::get( 'locations_text' ),
				'tooltip' => __( '"Locations" appears as the heading that describes the venue or venues for the event.', 'event-genius' ),
			),
			array(
				'id' => 'about_event_text',
				'default' => Defaults::get( 'about_event_text' ),
				'tooltip' => __( '"About the Event" appears as the heading to the section with additional event details.', 'event-genius' ),
			),
			array(
				'id' => 'about_organizer_text',
				'default' => Defaults::get( 'about_organizer_text' ),
				'tooltip' => __( '"About the Organizer" appears as the heading to the section with the event organizer information.', 'event-genius' ),
			),
			array(
				'id' => 'tags_text',
				'default' => Defaults::get( 'tags_text' ),
				'tooltip' => __( '"Tags" appears as the heading to the section that lists the tags.', 'event-genius' ),
			),
			array(
				'id' => 'categories_text',
				'default' => Defaults::get( 'categories_text' ),
				'tooltip' => __( '"Categories" appears as the heading to the section that lists the event categories', 'event-genius' ),
			),

		);
		$args = array(
			'id' => 'event_headings',
			'label' => __( 'Single Event Headings', 'event-genius' ),
			'text_fields' => $text_fields,
			'callback' => 'multi_text_field',
			'page' => 'evge_event_text',
			'section' => 'evge_event_text'
		);
		$this->add_setting_field( $args );

		$text_fields = array(
			array(
				'id' => 'add_to_calendar_text',
				'default' => Defaults::get( 'add_to_calendar_text' ),
				'tooltip' => __( '"Add to Calendar" appears on the event export button', 'event-genius' ),
			),
			array(
				'id' => 'see_map_text',
				'default' => Defaults::get( 'see_map_text' ),
				'tooltip' => __( '"See map" appears next to the location as a link to reveal a map.', 'event-genius' ),
			),
			array(
				'id' => 'learn_more_text',
				'default' => Defaults::get( 'learn_more_text' ),
				'tooltip' => __( '"Learn More" appears on the button in event listings that links to the full event details page.', 'event-genius' ),
			)
		);
		$args = array(
			'id' => 'misc',
			'label' => __( 'Misc', 'event-genius' ),
			'text_fields' => $text_fields,
			'callback' => 'multi_text_field',
			'page' => 'evge_event_text',
			'section' => 'evge_event_text'
		);
		$this->add_setting_field( $args );
	}


}
