<?php

namespace WPEventGenius\Admin\Settings;

use WPEventGenius\Common\Utils\Defaults;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class EventSettings extends BaseSettings {

	protected $page = 'evge_event_settings';

	protected $tab = 'events';

	public function __construct() {
	}


	public static function array_fields() {
		return array(
			'single_event_elements'
		);
	}

	public function settings() {
		$this->register_setting();

		add_settings_section(
			'evge_event_settings_display',
			__( 'Event Display (views)', 'event-genius' ),
			array( $this, 'section_callback' ),
			'evge_event_settings_display'
		);

		$template_options = array(
			'default' => __( 'WP Event Genius Template (recommended)', 'event-genius' ),
			'theme' => __( 'Theme Template (add before and after content)', 'event-genius' ),
			'custom' => __( 'Custom (Add customized files to your theme)', 'event-genius' ),
		);
		
		// For block themes, disable the template field but preserve the value
		$is_block_theme = function_exists( 'wp_is_block_theme' ) && wp_is_block_theme();
		
		$args = array(
			'id' => 'event_template',
			'label' => __( 'Event Template', 'event-genius' ) . $this->tooltip( __( 'The page template to use for single event pages.', 'event-genius' ) ),
			'default' => Defaults::get( 'event_template' ),
			'options' => $template_options,
			'callback' => 'select_field',
			'after_callback' => array( $this, 'event_template_description' ),
			'page' => 'evge_event_settings_display',
			'section' => 'evge_event_settings_display',
			'disabled' => $is_block_theme, // Disable field for block themes
		);
		$this->add_setting_field( $args );

		$args = array(
			'id' => 'show_export_options',
			'label' => __( 'Show Export Options', 'event-genius' ). $this->tooltip( __( 'Include the iCal and Google Calendar export options on the single event pages.', 'event-genius' ) ),
			'default' => Defaults::get( 'show_export_options' ), // 'enabled', 'disabled'
			'callback' => 'toggle_field',
			'page' => 'evge_event_settings_display',
			'section' => 'evge_event_settings_display',
			'disabled' => $is_block_theme, // Disable field for block themes
		);
		$this->add_setting_field( $args );

		$elements = array(
			'title' => __( 'Title', 'event-genius' ),
			'featured_image' => __( 'Featured Image', 'event-genius' ),
			'date' => __( 'Date', 'event-genius' ),
			'locations' => __( 'Locations', 'event-genius' ),
			'map' => __( 'Map', 'event-genius' ),
			'about_details' => __( 'About Details', 'event-genius' ),
			'organizers' => __( 'Organizers', 'event-genius' ),
			'categories' => __( 'Categories', 'event-genius' ),
			'tags' => __( 'Tags', 'event-genius' ),
			'capacity' => __( 'Capacity', 'event-genius' ),
		);
		$args = array(
			'id' => 'single_event_elements',
			'label' => __( 'Show in Single Event', 'event-genius' ) . $this->tooltip( __( 'What event information to display on single event pages.', 'event-genius' ) ),
			'default' => Defaults::get( 'single_event_elements' ),
            'options' => $elements,
			'callback' => 'multi_checkbox',
			'page' => 'evge_event_settings_display',
			'section' => 'evge_event_settings_display',
			'disabled' => $is_block_theme, // Disable field for block themes
		);
		$this->add_setting_field( $args );

		$args = array(
			'id' => 'color_theme',
			'label' => __( 'Color Theme', 'event-genius' ) . $this->tooltip( __( 'Use "Light" for themes with a light background and "Dark" for themes with a dark background.', 'event-genius' ) ),
			'default' => Defaults::get( 'color_theme' ),
			'callback' => 'color_theme',
			'page' => 'evge_event_settings_display',
			'section' => 'evge_event_settings_display'
		);
		$this->add_setting_field( $args );

		add_settings_section(
			'evge_event_settings_cost',
			__( 'Cost', 'event-genius' ),
			array( $this, 'section_callback' ),
			'evge_event_settings_cost'
		);

		$args = array(
			'id' => 'currency_symbol',
			'label' => __( 'Currency Symbol', 'event-genius' ) . $this->tooltip( __( 'The currency symbol to use for the cost of event registration.', 'event-genius' ) ),
			'default' => Defaults::get( 'currency_symbol' ),
			'callback' => 'text_field',
			'page' => 'evge_event_settings_cost',
			'section' => 'evge_event_settings_cost'
		);
		$this->add_setting_field( $args );

		$args = array(
			'id' => 'cost_display',
			'label' => __( 'Cost Display', 'event-genius' ). $this->tooltip( __( 'Controls how the cost of event registration is displayed. Use "{symbol}" and "{amount}" as placeholders.', 'event-genius' ) ),
			'default' => Defaults::get( 'cost_display' ),
			'callback' => 'text_field',
            'class' => 'regular-text',
			'page' => 'evge_event_settings_cost',
			'section' => 'evge_event_settings_cost'
		);
		$this->add_setting_field( $args );

		// Live Event Updates (keeps registration status, attendee counts, etc. current on cached event pages)
		add_settings_section(
			'evge_event_settings_dynamic_content',
			__( 'Live Event Updates', 'event-genius' ),
			array( $this, 'section_callback' ),
			'evge_event_settings_dynamic_content'
		);

		$args = array(
			'id' => 'enable_dynamic_content_refresh',
			'label' => __( 'Enable live updates', 'event-genius' ) . $this->tooltip( __( 'Automatically refresh registration status, attendee counts, and other dynamic content when cached event pages become stale. Recommended when using caching plugins.', 'event-genius' ) ),
			'default' => Defaults::get( 'enable_dynamic_content_refresh' ),
			'callback' => 'toggle_field',
			'page' => 'evge_event_settings_dynamic_content',
			'section' => 'evge_event_settings_dynamic_content'
		);
		$this->add_setting_field( $args );

		$args = array(
			'id' => 'dynamic_content_staleness_threshold',
			'label' => __( 'Staleness Threshold', 'event-genius' ) . $this->tooltip( __( 'How many minutes old cached content can be before it is considered stale and refreshed. Default is 3 minutes.', 'event-genius' ) ),
			'default' => Defaults::get( 'dynamic_content_staleness_threshold' ),
			'callback' => 'integer_field',
			'min' => 1,
			'after' => '<span style="margin-left: 8px;">' . esc_html__( 'minutes', 'event-genius' ) . '</span>',
			'page' => 'evge_event_settings_dynamic_content',
			'section' => 'evge_event_settings_dynamic_content'
		);
		$this->add_setting_field( $args );

		// Include events in main blog loop (homepage and tag archives)
		add_settings_section(
			'evge_event_settings_blog_loop',
			__( 'Blog Loop', 'event-genius' ),
			array( $this, 'section_callback' ),
			'evge_event_settings_blog_loop'
		);

		$args = array(
			'id' => 'show_events_in_main_loop',
			'label' => __( 'Include events in main blog loop', 'event-genius' ) . $this->tooltip( __( 'Show events with the site\'s other posts. When enabled, events will appear on the blog homepage and in tag archives. They will also continue to appear on the default events page.', 'event-genius' ) ),
			'default' => Defaults::get( 'show_events_in_main_loop' ),
			'callback' => 'toggle_field',
			'page' => 'evge_event_settings_blog_loop',
			'section' => 'evge_event_settings_blog_loop'
		);
		$this->add_setting_field( $args );
	}

	public function single_event_elements( $args ) {
		?>
            <div class="evge-toggle-setting">
                <input class="evge-toggle-setting-enabled" type="hidden" name="evge_event_settings[single_event_elements]" value="" data-multiple="1">

                <table class="evge-toggle-table">
	                <?php foreach ( $elements as $element => $status ) : ?>
                        <tr>
                            <td>
                                <a class="evge-settings-toggle-wrap" href="" data-value="<?php echo esc_attr( $element ); ?>">
					                <?php if ( $status === 'enabled' ) : ?>
                                        <span class="evge-settings-toggle evge-input-toggle--enabled" aria-label="<?php 
                                        /* translators: %s: name of the payment method */
                                        echo sprintf ( esc_html__( 'The %s payment method is currently enabled', 'event-genius' ), '' ); 
                                        ?>">Yes</span>
					                <?php else : ?>
                                        <span class="evge-settings-toggle evge-input-toggle--disabled" aria-label="<?php 
                                        /* translators: %s: name of the payment method */
                                        echo sprintf ( esc_html__( 'The %s payment method is currently disabled', 'event-genius' ), '' ); 
                                        ?>">No</span>
					                <?php endif; ?>
                                </a>
                            </td>
                            <td>
                                <label for="evge_event_settings[single_event_elements][<?php echo esc_attr( $element ); ?>]"><?php echo esc_html( $labels[$element] ); ?></label>
                            </td>
                        </tr>
	                <?php endforeach; ?>
                </table>


            </div>

        <?php
	}

    public function color_theme( $args ) {
	    $options = get_option( $args['option'], array() );
	    $default = isset( $args['default'] ) ? $args['default'] : '';
	    $value = isset( $options[ $args['id'] ] ) ? $options[ $args['id'] ] : $default;


        $theme_options = array(
            'light' => array(
                'id' => 'light',
                'label' => __( 'Light', 'event-genius' ),
                'image' => EVGE_PLUGIN_URL . 'assets/images/admin/settings/light-theme.svg',
            ),
            'dark' => array(
	            'id' => 'dark',
	            'label' => __( 'Dark', 'event-genius' ),
	            'image' => EVGE_PLUGIN_URL . 'assets/images/admin/settings/dark-theme.svg',
            ),
        );
        $class = count( $theme_options ) > 2 ? 'evge-setting-flex-column' : 'evge-flex-center evge-flex-large-gap';

	    ?>
        <div class="<?php echo esc_attr( $class ); ?>">
		    <?php foreach ( $theme_options as $option ) : ?>
                <label class="evge-flex-center">
                    <input type="radio" name="<?php echo esc_attr( $args['option'] ); ?>[<?php echo esc_attr( $args['id'] ); ?>]" value="<?php echo esc_attr( $option['id'] ); ?>" <?php checked( $option['id'], $value ); ?>>
                    <img src="<?php echo esc_url( $option['image'] ); ?>" alt="<?php echo esc_attr( $option['label'] ); ?>">
	                <?php echo esc_html( $option['label'] ); ?>
                </label>
		    <?php endforeach; ?>
        </div>
	    <?php
    }

	public function event_template_description( $args, $value ) {
		// Don't show template descriptions for block themes
		if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
			return;
		}
		?>
		<div class="evge-template-description-notices" style="margin-top: 12px;">
			<div class="evge-template-notice" data-template="default" <?php echo $value !== 'default' ? 'style="display: none;"' : ''; ?>>
				<?php $this->exclamation_notice( __( 'The WP Event Genius template provides a modern, responsive design optimized for events.', 'event-genius' ) ); ?>
			</div>
			<div class="evge-template-notice" data-template="theme" <?php echo $value !== 'theme' ? 'style="display: none;"' : ''; ?>>
				<?php $this->exclamation_notice( __( 'Your theme template will be used, and details about the event will be added before and after the content.', 'event-genius' ) ); ?>
			</div>
			<div class="evge-template-notice" data-template="custom" <?php echo $value !== 'custom' ? 'style="display: none;"' : ''; ?>>
				<?php $this->exclamation_notice( __( 'Copy the template files from event-genius/templates/event-genius to your theme directory to customize.', 'event-genius' ) ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Display notice for block themes explaining that settings are controlled by the template
	 */
	public function block_theme_notice() {
		// Get the Site Editor URL for the single event template
		$template_id = 'event-genius//single-evge_event';
		$site_editor_url = admin_url( 'site-editor.php?postType=wp_template&postId=' . urlencode( $template_id ) );
		
		$message = sprintf(
			/* translators: %1$s: opening link tag, %2$s: closing link tag */
			__( 'Block theme detected. %1$sEdit the template%2$s by searching for the Single Event Template to customize what sections are displayed.', 'event-genius' ),
			'<a href="' . esc_url( $site_editor_url ) . '">',
			'</a>'
		);
		
		$this->exclamation_notice( $message );
	}

}
