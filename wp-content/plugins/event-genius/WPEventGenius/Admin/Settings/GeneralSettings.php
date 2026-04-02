<?php

namespace WPEventGenius\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

use WPEventGenius\Common\Utils\Defaults;
use WPEventGenius\Common\Utils\Notices;
use WPEventGenius\Common\Utils\Settings;
use WPEventGenius\Common\Services\CptSlugService;
use WPEventGenius\Common\Queries\EventQuery;
use WPEventGenius\Common\Queries\VenueQuery;
use WPEventGenius\Common\Queries\OrganizerQuery;

class GeneralSettings extends BaseSettings {

	protected $page = 'evge_event_settings';

	protected $tab = 'general';


	public function __construct() {
	}

	public function settings() {
		$this->register_setting();


		add_settings_section(
			'evge_event_settings_date',
			__( 'Time & Date', 'event-genius' ),
			array( $this, 'section_callback' ),
			'evge_event_settings_date'
		);

        $days_of_the_week = array(
            'sunday' => __( 'Sunday', 'event-genius' ),
            'monday' => __( 'Monday', 'event-genius' ),
            'tuesday' => __( 'Tuesday', 'event-genius' ),
            'wednesday' => __( 'Wednesday', 'event-genius' ),
            'thursday' => __( 'Thursday', 'event-genius' ),
            'friday' => __( 'Friday', 'event-genius' ),
            'saturday' => __( 'Saturday', 'event-genius' ),
        );


		$args = array(
			'id' => 'start_of_the_week',
			'label' => __( 'Start of the Week', 'event-genius' ) . $this->tooltip( __( 'Which day to use as the start of each week in the calendar.', 'event-genius' ) ),
			'default' => Defaults::get( 'start_of_the_week' ),
            'options' => $days_of_the_week,
			'callback' => 'select_field',
			'page' => 'evge_event_settings_date',
			'section' => 'evge_event_settings_date'
		);
		$this->add_setting_field( $args );

		$args = array(
			'id' => 'date_formats',
			'label' => __( 'Date Format', 'event-genius' ) . $this->tooltip( __( 'How dates are displayed throughout the plugin.', 'event-genius' ) ),
			'default' => '',
			'callback' => 'date_formats',
			'page' => 'evge_event_settings_date',
			'section' => 'evge_event_settings_date'
		);
		$this->add_setting_field( $args );

		// Custom slugs section: single block with description and collapsible two-column slug options.
		add_settings_section(
			'evge_event_settings_permalinks',
			__( 'Post Link Options', 'event-genius' ),
			array( $this, 'section_callback' ),
			'evge_event_settings_permalinks'
		);
		add_settings_field(
			'evge_custom_slugs',
			__( 'Custom Slugs', 'event-genius' ),
			array( $this, 'custom_slugs_field' ),
			'evge_event_settings_permalinks',
			'evge_event_settings_permalinks',
			array()
		);

		add_settings_section(
			'evge_event_settings_email',
			__( 'Email', 'event-genius' ),
			array( $this, 'section_callback' ),
			'evge_event_settings_email' // must match do_settings_section
		);

		$args = array(
			'id' => 'email_from_address',
			'label' => __( 'Email From Address', 'event-genius' ) . $this->tooltip( __( 'The email address that any emails sent by the plugin to attendees should be sent from.', 'event-genius' ) ),
			'default' => Defaults::get( 'email_from_address' ),
			'callback' => 'email_address_field',
			'page' => 'evge_event_settings_email',
			'section' => 'evge_event_settings_email'
		);
        $this->add_setting_field( $args );

        // Add uninstall settings section
        add_settings_section(
            'evge_event_settings_uninstall',
            __( 'Data Preservation', 'event-genius' ),
            array( $this, 'section_callback' ),
            'evge_event_settings_uninstall'
        );

        $args = array(
            'id' => 'preserve_data',
            'label' => __( 'Preserve Plugin Data', 'event-genius' ) . $this->tooltip( __( 'Whether to keep any plugin data (such as events, calendars, registrations, and settings) after uninstalling the plugin.', 'event-genius' ) ),
            'default' => 'enabled',
            'callback' => 'preserve_data_field',
            'page' => 'evge_event_settings_uninstall',
            'section' => 'evge_event_settings_uninstall'
        );
        $this->add_setting_field( $args );
	}

	/**
	 * Row labels for the slug options table (human-readable, not the slug key).
	 *
	 * @return array<string, string>
	 */
	protected function get_slug_row_labels() {
		return array(
			'event_slug'             => __( 'Single event', 'event-genius' ),
			'event_archive_slug'      => __( 'Events archive', 'event-genius' ),
			'venue_slug'             => __( 'Single venue', 'event-genius' ),
			'venue_archive_slug'     => __( 'Venues archive', 'event-genius' ),
			'organizer_slug'         => __( 'Single organizer', 'event-genius' ),
			'organizer_archive_slug' => __( 'Organizers archive', 'event-genius' ),
			'event_category_slug'    => __( 'Event category', 'event-genius' ),
			'event_tag_slug'         => __( 'Event tag', 'event-genius' ),
			'series_slug'            => __( 'Series', 'event-genius' ),
		);
	}

	/**
	 * Get example URL for a slug key and whether it can be linked (content exists).
	 *
	 * @param string $key        Slug key (e.g. event_slug, event_archive_slug).
	 * @param string $slug_value Current slug value for building URLs.
	 * @return array{ 'url' => string, 'can_link' => bool }
	 */
	protected function get_slug_example_for_key( $key, $slug_value ) {
		if ( ! $slug_value ) {
			return array( 'url' => '', 'can_link' => false );
		}
		$is_archive = ( strpos( $key, '_archive_slug' ) !== false );
		$archive_url = home_url( '/' . $slug_value . '/' );

		switch ( $key ) {
			case 'event_slug':
				$event_query = new EventQuery( array( 'qtype' => 'upcoming', 'posts_per_page' => 1 ) );
				$event_query->add_wp_query();
				$events = $event_query->get_events();
				if ( ! empty( $events ) && isset( $events[0]->ID ) ) {
					$url = get_permalink( $events[0]->ID );
					return array( 'url' => $url ? $url : $archive_url, 'can_link' => (bool) $url );
				}
				return array( 'url' => home_url( '/' . $slug_value . '/sample/' ), 'can_link' => false );

			case 'event_archive_slug':
				return array( 'url' => $archive_url, 'can_link' => true );

			case 'venue_slug': {
				$venue_query = new VenueQuery( array( 'posts_per_page' => 1 ) );
				$venue_query->add_wp_query();
				$venues = $venue_query->get_venues();
				if ( ! empty( $venues ) && isset( $venues[0]->ID ) ) {
					$url = get_permalink( $venues[0]->ID );
					return array( 'url' => $url ? $url : home_url( '/' . $slug_value . '/' ), 'can_link' => (bool) $url );
				}
				return array( 'url' => home_url( '/' . $slug_value . '/sample/' ), 'can_link' => false );
			}
			case 'venue_archive_slug':
				return array( 'url' => $archive_url, 'can_link' => true );

			case 'organizer_slug': {
				$organizer_query = new OrganizerQuery( array( 'posts_per_page' => 1 ) );
				$organizer_query->add_wp_query();
				$organizers = $organizer_query->get_organizers();
				if ( ! empty( $organizers ) && isset( $organizers[0]->ID ) ) {
					$url = get_permalink( $organizers[0]->ID );
					return array( 'url' => $url ? $url : home_url( '/' . $slug_value . '/' ), 'can_link' => (bool) $url );
				}
				return array( 'url' => home_url( '/' . $slug_value . '/sample/' ), 'can_link' => false );
			}
			case 'organizer_archive_slug':
				return array( 'url' => $archive_url, 'can_link' => true );

			case 'event_category_slug': {
				$terms = get_terms( array( 'taxonomy' => EVGE_EVENT_CATEGORY_TYPE, 'number' => 1, 'hide_empty' => false ) );
				if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
					$link = get_term_link( $terms[0] );
					return array( 'url' => is_wp_error( $link ) ? $archive_url : $link, 'can_link' => ! is_wp_error( $link ) );
				}
				return array( 'url' => home_url( '/' . $slug_value . '/sample/' ), 'can_link' => false );
			}
			case 'event_tag_slug': {
				$terms = get_terms( array( 'taxonomy' => EVGE_EVENT_TAG_TYPE, 'number' => 1, 'hide_empty' => false ) );
				if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
					$link = get_term_link( $terms[0] );
					return array( 'url' => is_wp_error( $link ) ? $archive_url : $link, 'can_link' => ! is_wp_error( $link ) );
				}
				return array( 'url' => home_url( '/' . $slug_value . '/sample/' ), 'can_link' => false );
			}
			case 'series_slug': {
				if ( ! defined( 'EVGE_SERIES_POST_TYPE' ) ) {
					return array( 'url' => $archive_url, 'can_link' => true );
				}
				$series = get_posts( array( 'post_type' => EVGE_SERIES_POST_TYPE, 'post_status' => 'publish', 'posts_per_page' => 1 ) );
				if ( ! empty( $series ) ) {
					$url = get_permalink( $series[0]->ID );
					return array( 'url' => $url ? $url : $archive_url, 'can_link' => (bool) $url );
				}
				return array( 'url' => $archive_url, 'can_link' => true );
			}
			default:
				return array( 'url' => $archive_url, 'can_link' => $is_archive );
		}
	}

	/**
	 * Truncate URL for display: strip scheme and limit last path segment to 12 characters + ellipsis.
	 *
	 * @param string $url Full URL.
	 * @param int    $max_segment_length Max characters for the last path segment before adding '...'.
	 * @return string Display URL (e.g. evge-development.test/event/startup-pitch.../).
	 */
	protected function truncate_url_for_display( $url, $max_segment_length = 12 ) {
		$parsed = wp_parse_url( $url );
		if ( empty( $parsed['host'] ) ) {
			return $url;
		}
		$display = $parsed['host'];
		$path    = isset( $parsed['path'] ) ? trim( $parsed['path'], '/' ) : '';
		if ( $path !== '' ) {
			$segments = explode( '/', $path );
			$last     = array_pop( $segments );
			if ( strlen( $last ) > $max_segment_length ) {
				$last = substr( $last, 0, $max_segment_length ) . '...';
			}
			$segments[] = $last;
			$display   .= '/' . implode( '/', $segments ) . '/';
		} else {
			$display .= '/';
		}
		return $display;
	}

	/**
	 * Single "Custom slugs" block: description, "Show slug options" link, and hidden two-column table.
	 */
	public function custom_slugs_field() {
		$options   = get_option( 'evge_settings', array() );
		$cpt_slugs = isset( $options['cpt_slugs'] ) && is_array( $options['cpt_slugs'] ) ? $options['cpt_slugs'] : array();
		$defaults  = CptSlugService::get_default_slugs();
		$keys      = CptSlugService::get_slug_keys_for_current_tier();
		$labels    = $this->get_slug_row_labels();
		?>
		<div class="evge-custom-slugs-wrap">
			<p class="evge-setting-description evge-custom-slugs-description">
				<?php esc_html_e( 'Customize the URL slugs used for the links to events, venues, organizers, and related archives.', 'event-genius' ); ?>
			</p>
			<p class="evge-custom-slugs-toggle-wrap">
				<button type="button" class="button button-secondary evge-show-slug-options" aria-expanded="false" aria-controls="evge-slug-options-content">
					<span class="evge-show-slug-options-text"><?php esc_html_e( 'Show slug options', 'event-genius' ); ?></span>
					<span class="evge-hide-slug-options-text" style="display: none;"><?php esc_html_e( 'Hide slug options', 'event-genius' ); ?></span>
				</button>
			</p>
			<div id="evge-slug-options-content" class="evge-slug-options-content" role="region" aria-label="<?php esc_attr_e( 'Slug options', 'event-genius' ); ?>" hidden>
				<table class="evge-settings-sub-group evge-slug-options-table">
					<?php
					foreach ( $keys as $key ) {
						if ( ! isset( $defaults[ $key ] ) || ! isset( $labels[ $key ] ) ) {
							continue;
						}
						$value     = isset( $cpt_slugs[ $key ] ) ? $cpt_slugs[ $key ] : $defaults[ $key ];
						$name_attr = 'evge_settings[cpt_slugs][' . esc_attr( $key ) . ']';
						$id_attr   = 'evge_cpt_slug_' . str_replace( '_', '-', $key );
						$example   = $this->get_slug_example_for_key( $key, $value );
						?>
						<tr>
							<td class="evge-slug-options-label"><?php echo esc_html( $labels[ $key ] ); ?></td>
							<td>
								<div class="evge-slug-row">
									<div class="evge-slug-input-wrap">
										<label for="<?php echo esc_attr( $id_attr ); ?>" class="screen-reader-text"><?php echo esc_html( $labels[ $key ] ); ?></label>
										<input id="<?php echo esc_attr( $id_attr ); ?>" class="evge-long-input evge-block-input evge-slug-input" type="text" name="<?php echo esc_attr( $name_attr ); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $defaults[ $key ] ); ?>">
									</div>
									<?php if ( $example['url'] ) : ?>
										<?php
										$display_url = $this->truncate_url_for_display( $example['url'], 12 );
										$title_attr  = ' title="' . esc_attr( $example['url'] ) . '"';
										?>
										<p class="evge-slug-example">
											<?php
											if ( $example['can_link'] ) {
												echo wp_kses( __( 'ex: ', 'event-genius' ), array() );
												echo '<a href="' . esc_url( $example['url'] ) . '" target="_blank" rel="noopener noreferrer" class="evge-slug-example-link" ' . $title_attr . '>' . esc_html( $display_url ) . '</a>';
											} else {
												echo wp_kses( __( 'ex: ', 'event-genius' ), array() );
												echo '<span class="evge-slug-example-url" ' . $title_attr . '>' . esc_html( $display_url ) . '</span>';
											}
											?>
										</p>
									<?php endif; ?>
								</div>
							</td>
						</tr>
						<?php
					}
					?>
				</table>
			</div>
		</div>
		<?php
	}

	public function email_address_field( $args ) {
		$options = get_option( $args['option'], array() );
		$default = isset( $args['default'] ) ? $args['default'] : '';
		$value = isset( $options[ $args['id'] ] ) ? $options[ $args['id'] ] : $default;

        $use_custom_args = $args;
        $use_custom_args['id'] = $args['id'] . '_custom';
		?>
        <div class="evge-setting-flex-column">

            <div id="evge-email-address-custom" class="evge-flex-center">
                <?php $this->toggle_field( $use_custom_args ); ?><label for="evge-email-address-custom-field"><?php esc_html_e( 'Use Custom', 'event-genius' ); ?></label>
            </div>
            <div id="evge-email-address-custom-value">
                <label for="evge_<?php echo esc_attr( $args['id'] ); ?>" class="screen-reader-text"><?php echo esc_html( __( 'Custom From Email Address', 'event-genius' ) ); ?></label>
                <input id="evge_<?php echo esc_attr( $args['id'] ); ?>" class="evge-long-input evge-block-input" type="text" name="<?php echo esc_attr( $args['option'] ); ?>[<?php echo esc_attr( $args['id'] ); ?>]" value="<?php echo esc_attr( $value ); ?>">
            </div>
            <?php 
            /* translators: 1: opening link tag to forms page, 2: closing link tag */
            Notices::exclamation( sprintf( __( 'Configure emails inside your event registration forms %1$shere%2$s', 'event-genius' ), 
                '<a href="' . esc_url( admin_url( 'admin.php?page=evge-registrations&tab=forms&subtab=email' ) ) . '">', 
                '</a>' ) ); 
            ?>
        </div>
		<?php
	}

	public function timezone_explanation( $args ) {
		?>
        <p><?php 
        /* translators: %s: WordPress timezone setting (e.g. "UTC+2") */
        echo sprintf( esc_html__( "Your WordPress site's timezone is %s.", 'event-genius' ), 
            '<strong>' . esc_html( wp_timezone_string() ) . '</strong>' ); 
        ?> <a href="<?php echo esc_url( admin_url( 'options-general.php') ); ?>"><?php esc_html_e( "Go to settings", 'event-genius' ); ?></a></p>
		<?php
	}

	public function date_formats( $args ) {
        $static_datetime = strtotime('2025-03-27 14:30:00');

		$full_date_formats = array(
			'D, F j, Y' => date_i18n('D, F j, Y', $static_datetime),
			'F j, Y' => date_i18n('F j, Y', $static_datetime),
			'D, j F Y' => date_i18n('D, j F Y', $static_datetime),
			'j F Y'   => date_i18n('j F Y', $static_datetime),
			'F j Y'  => date_i18n('F j Y', $static_datetime),
			'Y F j'  => date_i18n('Y F j', $static_datetime), 
			'l, j F Y'    => date_i18n('l, j F Y', $static_datetime),
			'l, F j Y' => date_i18n('l, F j Y', $static_datetime),
			'D, j M Y' => date_i18n('D, j M Y', $static_datetime), 
			'custom' => __( 'Custom', 'event-genius' )
		);
		$summary_date_formats = array(
			'D, F j, Y' => date_i18n('D, F j, Y', $static_datetime),
			'F j, Y' => date_i18n('F j, Y', $static_datetime),
			'D, j F Y' => date_i18n('D, j F Y', $static_datetime),
			'j F Y'   => date_i18n('j F Y', $static_datetime),
			'D, j M Y' => date_i18n('D, j M Y', $static_datetime),
			'M d' => date_i18n('M d', $static_datetime),
			'd M'    => date_i18n('d M', $static_datetime),
			'custom' => __( 'Custom', 'event-genius' )
		);
		$registration_timeline_date_formats = array(
			'F j g:i A' => date_i18n('F j g:i A', $static_datetime), // e.g., March 27, 2025 2:00 PM
			'j F H:i' => date_i18n('j F H:i', $static_datetime), // e.g., March 27, 2025 2:00 PM
			'j F Y H:i'   => date_i18n('j F Y H:i', $static_datetime),    // e.g., 2025-03-27 14:00
			'F j Y g:i A'  => date_i18n('F j Y g:i A', $static_datetime),  // e.g., 27/03/2025 2:00 PM
			'custom' => __( 'Custom', 'event-genius' )
		);
		$time_formats = array(
			'ga'       => date_i18n('ga', $static_datetime) . ' (' . date_i18n('g:ia', $static_datetime) . ')',       // e.g., 2 pm
			'g:ia'     => date_i18n('g:ia', $static_datetime),     // e.g., 2:30 PM
			'gA'       => date_i18n('gA', $static_datetime) . ' (' . date_i18n('g:iA', $static_datetime) . ')',       // e.g., 2 pm
			'g:iA'     => date_i18n('g:iA', $static_datetime),     // e.g., 2:30 PM
			'g a'       => date_i18n('g a', $static_datetime) . ' (' . date_i18n('g:i a', $static_datetime) . ')',       // e.g., 2 pm
			'g:i a'       => date_i18n('g:i a', $static_datetime),       // e.g., 2 pm
			'g A'       => date_i18n('g A', $static_datetime) . ' (' . date_i18n('g:i A', $static_datetime) . ')',       // e.g., 2 pm
			'g:i A'     => date_i18n('g:i A', $static_datetime),     // e.g., 2:30 PM'H\hi'      => date_i18n('H\hi', $static_datetime),      // e.g., 14h30
			'H:i'=> date_i18n('H:i', $static_datetime), // e.g., 14:30 hrs
            'custom' => __( 'Custom', 'event-genius' )
		);
        $full_datetime_format = '';
        ?>

        <table class="evge-settings-sub-group">
            <tr>
                <?php
                $full_datetime_format = Settings::get( 'full_date_format' );
                $custom_date_format = Settings::get( 'full_date_format_custom' );
                ?>
                <td><?php esc_html_e('Full Date', 'event-genius'); ?>:</td>
                <td>
                    <div class="evge-datetime-setting-wrap">
                        <select name="<?php echo esc_attr( $args['option'] ); ?>[full_date_format]">
                            <?php foreach ($full_date_formats as $value => $label) { ?>
                            <option value="<?php echo esc_attr($value);?>" <?php if ( $full_datetime_format === $value ) { echo 'selected'; } ?>><?php echo esc_html( $label ); ?></option>
                            <?php } ?>
                        </select>
                        <div class="evge-datetime-setting-custom" style="display: none;">
                            <label for="evge_full_date_format_custom" class="screen-reader-text"><?php esc_html_e('Custom Date Format', 'event-genius'); ?></label>:
                            <input id="evge_full_date_format_custom" type="text" name="<?php echo esc_attr( $args['option'] ); ?>[full_date_format_custom]" value="<?php echo esc_attr( $custom_date_format ); ?>">
                            <a href="https://wpeventgenius.com/docs/date-time-formatting/?utm_campaign=evge-free&utm_source=settings-page&utm_medium=date-settings-full&utm_content=dateguide" target="_blank"><?php esc_html_e('Format Guide', 'event-genius'); ?></a>
                        </div>
                    </div>

                </td>
            </tr>
            <tr>
	            <?php
	            $date_summary = Settings::get( 'date_summary_format' );
                $custom_date_format = Settings::get( 'date_summary_format_custom' );
	            ?>
                <td><?php esc_html_e('Date Summary', 'event-genius'); ?>:</td>
                <td>
                    <div class="evge-datetime-setting-wrap">
                        <select name="<?php echo esc_attr( $args['option'] ); ?>[date_summary_format]">
                            <?php foreach ($summary_date_formats as $value => $label) { ?>
                                <option value="<?php echo esc_attr($value);?>" <?php if ( $date_summary === $value ) { echo 'selected'; } ?>><?php echo esc_html( $label ); ?></option>
                            <?php } ?>
                        </select>
                        <div class="evge-datetime-setting-custom" style="display: none;">
                            <label for="evge_date_summary_format_custom" class="screen-reader-text"><?php esc_html_e('Custom Date Format', 'event-genius'); ?></label>:
                            <input id="evge_date_summary_format_custom" type="text" name="<?php echo esc_attr( $args['option'] ); ?>[date_summary_format_custom]" value="<?php echo esc_attr( $custom_date_format ); ?>">
                            <a href="https://wpeventgenius.com/docs/date-time-formatting/?utm_campaign=evge-free&utm_source=settings-page&utm_medium=date-settings-summary&utm_content=dateguide" target="_blank"><?php esc_html_e('Format Guide', 'event-genius'); ?></a>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
	            <?php
	            $registration_timeline = Settings::get( 'registration_timeline_format' );
                $custom_date_format = Settings::get( 'registration_timeline_format_custom' );
	            ?>
                <td><?php esc_html_e('Registration Timeline', 'event-genius'); ?>:</td>
                <td>
                    <div class="evge-datetime-setting-wrap">
                        <select name="<?php echo esc_attr( $args['option'] ); ?>[registration_timeline_format]">
                            <?php foreach ($registration_timeline_date_formats as $value => $label) { ?>
                                <option value="<?php echo esc_attr($value);?>" <?php if ( $registration_timeline === $value ) { echo 'selected'; } ?>><?php echo esc_html( $label ); ?></option>
                            <?php } ?>
                        </select>
                        <div class="evge-datetime-setting-custom" style="display: none;">
                            <label for="evge_registration_timeline_format_custom" class="screen-reader-text"><?php esc_html_e('Custom Date Format', 'event-genius'); ?></label>:
                            <input id="evge_registration_timeline_format_custom" type="text" name="<?php echo esc_attr( $args['option'] ); ?>[registration_timeline_format_custom]" value="<?php echo esc_attr( $custom_date_format ); ?>">
                            <a href="https://wpeventgenius.com/docs/date-time-formatting/?utm_campaign=evge-free&utm_source=settings-page&utm_medium=date-settings-summary&utm_content=dateguide" target="_blank"><?php esc_html_e('Format Guide', 'event-genius'); ?></a>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
	            <?php
	            $time_format = Settings::get( 'time_format' );
	            $custom_time_format = Settings::get( 'time_format_custom' );
	            ?>
                <td><?php esc_html_e('Time', 'event-genius'); ?>:</td>
                <td>
                    <div class="evge-datetime-setting-wrap">
                        <select name="<?php echo esc_attr( $args['option'] ); ?>[time_format]">
                            <?php foreach ($time_formats as $value => $label) { ?>
                                <option value="<?php echo esc_attr($value);?>" <?php if ( $time_format === $value ) { echo 'selected'; } ?>><?php echo esc_html( $label ); ?></option>
                            <?php } ?>
                        </select>
                        <div class="evge-datetime-setting-custom" style="display: none;">
                            <label for="evge_time_format_custom" class="screen-reader-text"><?php esc_html_e('Custom Time Format', 'event-genius'); ?></label>:
                            <input id="evge_time_format_custom" type="text" name="<?php echo esc_attr( $args['option'] ); ?>[time_format_custom]" value="<?php echo esc_attr( $custom_time_format ); ?>">
                            <a href="https://wpeventgenius.com/docs/date-time-formatting/#time?utm_campaign=evge-free&utm_source=settings-page&utm_medium=date-settings-summary&utm_content=dateguide" target="_blank"><?php esc_html_e('Format Guide', 'event-genius'); ?></a>
                        </div>
                    </div>

                </td>
            </tr>
            <tr>
                <td><?php esc_html_e('Timezone', 'event-genius'); ?>:</td>
                <td>
                    <?php $this->timezone_explanation( array() ); ?>
                </td>
            </tr>
        </table>
        <?php

	}

    public function preserve_data_field( $args ) {
        $options = get_option( $args['option'], array() );
        $default = isset( $args['default'] ) ? $args['default'] : 'enabled';
        $value = isset( $options[ $args['id'] ] ) ? $options[ $args['id'] ] : $default;
        ?>
        <div class="evge-setting-flex-column">
            <div id="evge-preserve-data" class="evge-flex-center">
                <?php $this->toggle_field( $args ); ?>
            </div>
            <div class="evge-setting-description">
                <p>
                    <?php esc_html_e( 'Disable this option only if you want to completely and permanently delete all plugin data when the plugin is uninstalled.', 'event-genius' ); ?>
                </p>
            
            </div>
        </div>
        <?php
    }

}
