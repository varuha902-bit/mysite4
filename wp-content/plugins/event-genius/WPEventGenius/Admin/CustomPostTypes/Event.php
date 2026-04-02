<?php
namespace WPEventGenius\Admin\CustomPostTypes;

use WPEventGenius\Admin\CustomPostTypes\QuickCreate\EventCreate;
use WPEventGenius\Admin\CustomPostTypes\QuickCreate\OrganizerCreate;
use WPEventGenius\Admin\CustomPostTypes\QuickCreate\VenueCreate;
use WPEventGenius\Common\Event\EventPost;
use WPEventGenius\Common\Queries\OrganizerQuery;
use WPEventGenius\Common\Queries\VenueQuery;
use WPEventGenius\Common\Series\Queue\SeriesQueue;
use WPEventGenius\Common\Utils\DateFormatter;
use WPEventGenius\Common\Utils\Formatter;
use WPEventGenius\Common\Utils\Icon;
use WPEventGenius\Common\Utils\EvgeDateTime;
use WPEventGenius\Common\Utils\Settings;
use WPEventGenius\Common\Services\CptSlugService;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Event implements CustomPostType {

	/**
	 * @var \WP_Post
	 */
    protected $event_post;

    // Add this near the top of the class with other properties
    protected $db;

	public function __construct(){
        $this->db = new \WPEventGenius\Common\Database();
	}

    public function get_post_type() {
        return EVGE_EVENT_POST_TYPE;
    }

    /**
     * Call a callback function, supporting both string (current class method) and array (external class method) callbacks
     * 
     * @param string|array $callback The callback to execute
     * @param array $subsection The subsection data to pass to the callback
     */
    private function call_callback( $callback, $subsection ) {
        if ( is_array( $callback ) ) {
            // Array callback: [class, method]
            call_user_func( $callback, $subsection );
        } else {
            // String callback: method of current class
            call_user_func( array( $this, $callback ), $subsection );
        }
    }

    public function register_taxonomies() {
	    register_taxonomy( EVGE_EVENT_CATEGORY_TYPE, EVGE_EVENT_POST_TYPE, array(
            'label' => __( 'Event Categories', 'event-genius' ),
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_rest' => true, // add support for Gutenberg editor
            'query_var' => true,
            'rewrite' => array( 'slug' => CptSlugService::get_slug( 'event_category_slug' ) ),
            'capabilities' => array(
                'manage_terms' => 'manage_evge_categories',
                'edit_terms' => 'manage_evge_categories',
                'delete_terms' => 'manage_evge_categories',
                'assign_terms' => 'edit_evge_events'
            )
        ) );
        register_taxonomy( EVGE_EVENT_TAG_TYPE, EVGE_EVENT_POST_TYPE, array(
            'label' => __( 'Event Tags', 'event-genius' ),
            'hierarchical' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_rest' => true, // add support for Gutenberg editor
            'query_var' => true,
            'rewrite' => array( 'slug' => CptSlugService::get_slug( 'event_tag_slug' ) ),
            'capabilities' => array(
                'manage_terms' => 'manage_evge_tags',
                'edit_terms' => 'manage_evge_tags',
                'delete_terms' => 'manage_evge_tags',
                'assign_terms' => 'edit_evge_events'
            )
        ) );
    }

	public function register() {
		$labels = array(
			'name' => __( 'Events', 'event-genius' ),
			'singular_name' => __( 'Event', 'event-genius' ),
			'all_items' => __( 'All Events', 'event-genius' ),
			'add_new_item' => __( 'Add New Event', 'event-genius' ),
			'add_new' => __( 'New Event', 'event-genius' ),
			'new_item' => __( 'New Event', 'event-genius' ),
			'edit_item' => __( 'Edit Event', 'event-genius' ),
			'view_item' => __( 'View Event', 'event-genius' ),
			'search_items' => __( 'Search Events', 'event-genius' ),
			'not_found' => __( 'No events found', 'event-genius' ),
			'not_found_in_trash' => __( 'No events found in trash', 'event-genius' )
		);
		$args = array(
			'labels' => $labels,
			'menu_icon' => 'dashicons-list-alt',
			'public' => true,
			'can_export' => true,
			'has_archive' => CptSlugService::get_slug( 'event_archive_slug' ),
			'show_ui' => true,
			'show_in_menu'         => false,
			'show_in_nav_menus'    => false,
			'archive_in_nav_menus' => false,
			'show_in_rest' => true,
			'show_in_admin_bar' => true,
			'capability_type' => array('evge_event', 'evge_events'),
			'map_meta_cap' => true,
			'taxonomies' => array( 'evge_event_cat', 'evge_event_tag' ),
			'rewrite' => array( 'slug' => CptSlugService::get_slug( 'event_slug' ) ),
			'supports' => array( 'title', 'thumbnail', 'page-attributes', 'editor', 'author' )
		);
		register_post_type( EVGE_EVENT_POST_TYPE, $args );
	}

	public function custom_columns( $defaults ){


		// Add our custom columns
		$defaults['event_date'] = __( 'Date', 'event-genius' );

		return $defaults;
	}

	public function custom_columns_content( $column_name, $post_id ){
		$event_post = new \WPEventGenius\Common\Event\EventPost($post_id);

		switch ($column_name) {
			case 'event_date':
				echo wp_kses_post($event_post->get_the_date_summary('brief'));
				break;
		}
	}

	public function add_meta_boxes() {
		add_meta_box(
			'evge-event-metabox',
			esc_html__( 'WP Event Genius - Event Details', 'event-genius' ),
			array( $this, 'event_meta_box_display' ),
			EVGE_EVENT_POST_TYPE,
			'normal',
			'high'
		);
		add_meta_box(
			'evge-registration-metabox',
			esc_html__( 'Event Registration', 'event-genius' ),
			array( $this, 'registration_meta_box_display' ),
			EVGE_EVENT_POST_TYPE,
			'normal',
			'high'
		);
	}

	public function event_meta_box_display() {
		global $post;
		wp_nonce_field( 'evge_save_event_nonce', 'evge_save_event_nonce' );
		
		// Add hidden spans with original values
		$original_recurrence_type = get_post_meta($post->ID, 'evge_recurrence_type', true);
		$original_recurrence_end_date = get_post_meta($post->ID, 'evge_recurrence_end_date', true);
		echo '<span class="evge-original-recurrence-type" style="display:none;">' . esc_attr($original_recurrence_type) . '</span>';
		echo '<span class="evge-original-recurrence-end-date" style="display:none;">' . esc_attr($original_recurrence_end_date) . '</span>';
		
		foreach ( $this->event_sections() as $section ) {
            echo '<div class="evge-single-section">';
            if ( ! empty( $section['title'] ) ) {
	            echo '<h4>' . esc_html( $section['title'] ) . '</h4>';
            }
            foreach ( $section['subsections'] as $subsection ) {
                if ( ! empty( $subsection['callback'] ) ) {
                    $this->call_callback( $subsection['callback'], $subsection );
                }
            }
			echo '</div>';

		}

        $db = new \WPEventGenius\Common\Database();
		$queue = new \WPEventGenius\Common\Series\Queue\SeriesQueue($db);
		
		// Get updated queue status
		$queue_items = $queue->get_queue();
        // Check if this is a recurring event being edited or a template event with recurrence
        if ( ! empty( $queue_items ) ) {
            echo '<span class="evge-queue-processing-modal-trigger" style="display: none;"></span>';
        }

        do_action( 'evge_admin_modal' );
        echo '<div class="evge-recurrence-modal-content">';
        $this->add_recurring_warning_content();
        echo '</div>';

	}

	public function registration_meta_box_display() {

		foreach ( $this->registration_sections() as $key => $section ) {
			echo '<div id="evge-registration-' . esc_attr( $key ) . '" class="evge-single-section evge-registration-section">';
            if ( ! empty( $section['title'] ) ) {
                echo '<h4>';
                echo esc_html( $section['title'] );
                
                // Add Pro badge if specified
                if ( ! empty( $section['pro_badge'] ) && is_array( $section['pro_badge'] ) ) {
                    $ajax_data = isset( $section['pro_badge']['ajax_data'] ) ? $section['pro_badge']['ajax_data'] : array();
                    $modal_settings = isset( $section['pro_badge']['modal_settings'] ) ? $section['pro_badge']['modal_settings'] : array();
                    ?>
                    <a href="#" 
                       class="evge-upsell-pro-badge evge-modal-trigger"
                       data-evge-modal-content="ajax"
                       data-evge-ajax="<?php echo esc_attr( wp_json_encode( $ajax_data ) ); ?>"
                       data-evge-modal-settings="<?php echo esc_attr( wp_json_encode( $modal_settings ) ); ?>">
                        <?php esc_html_e( 'Pro', 'event-genius' ); ?>
                    </a>
                    <?php
                }
                
                echo '</h4>';
            }
			foreach ( $section['subsections'] as $subsection ) {
				if ( ! empty( $subsection['callback'] ) ) {
					$this->call_callback( $subsection['callback'], $subsection );
				}
			}
			echo '</div>';

		}
	}

    public function start_end_date_section() {
        // if it's past 8 AM local the start day should be tomorrow
        $start_time = strtotime( wp_date( 'Y-m-d 08:00:00' ) );
        if ( strtotime( wp_date( 'Y-m-d H:i:s' ) ) > $start_time ) {
            $start_date = wp_date( 'Y-m-d 08:00:00', time() + DAY_IN_SECONDS );
            $end_date = wp_date( 'Y-m-d 16:00:00', time() + DAY_IN_SECONDS );
        } else {
            $start_date = wp_date( 'Y-m-d 08:00:00' );
            $end_date = wp_date( 'Y-m-d 16:00:00' );
        }

        
        $section = array(
	        'priority' => 10,
	        'id' => 'start_end_date',
	        'type' => 'start_end_date',
	        'callback' => 'start_end_date_setting_display',
	        'label' => __( 'Date and Times', 'event-genius' ),
	        'settings' => array(
		        'start_date' => array(
			        'key' => 'start_date',
			        'default' => $start_date,
			        'sanitization' => 'date',
		        ),
		        'end_date' => array(
			        'key' => 'end_date',
			        'default' => $end_date,
			        'sanitization' => 'date',
		        ),
                'timezone' => array(
                    'key' => 'timezone',
                    'default' => 'default',
                    'sanitization' => 'text',
                ),
		        'all_day' => array(
			        'key' => 'all_day',
			        'default' => 'disabled',
			        'sanitization' => 'bool',
		        ),
		        'recurrence_type' => array(
			        'key' => 'recurrence_type',
			        'default' => 'none',
			        'sanitization' => 'text',
		        ),
		        'recurrence_end_date' => array(
			        'key' => 'recurrence_end_date',
			        'default' => '',
			        'sanitization' => 'date',
		        ),
	        )
        );

        return $section;
    }

    public function date_subsection() {
        return array(
	        'start_end_date' => $this->start_end_date_section(),
        );
    }

	public function event_sections() {
		$sections = array(
            'summary' => array(
	            'title' => '',
	            'subsections' => array(
		            'summary' => array(
			            'priority' => 10,
			            'id' => 'summary',
			            'type' => 'textarea',
			            'callback' => 'textarea',
			            'label' => __( 'Summary', 'event-genius' ),
			            'description' => __( 'A short description of the event. Appears in event listings.', 'event-genius' ),
			            'settings' => array(
				            array(
					            'key' => 'summary',
					            'default' => '',
					            'sanitization' => 'textarea',
				            )
			            )
		            ),
                ),
            ),
            'date' => array(
	            'title' => __( 'Date and Time', 'event-genius' ),
	            'subsections' => $this->date_subsection(),
            ),
            'location' => array(
	            'title' => __( 'Location', 'event-genius' ),
	            'subsections' => array(
		            'location' => array(
			            'priority' => 10,
			            'id' => 'venue',
			            'type' => 'venue',
			            'callback' => 'venue',
			            'label' => __( 'Venue', 'event-genius' ),
			            'settings' => array(
				            array(
					            'key' => 'venue',
					            'default' => '',
					            'sanitization' => 'integer',
				            )
			            )
		            ),
	            ),
            ),
            'organizer' => array(
	            'title' => __( 'Organizers', 'event-genius' ),
	            'subsections' => array(
		            'location' => array(
			            'priority' => 10,
			            'id' => 'organizer',
			            'type' => 'organizer',
			            'callback' => 'organizer',
			            'label' => __( 'Organizer', 'event-genius' ),
			            'settings' => array(
				            array(
					            'key' => 'organizer',
					            'default' => '',
					            'sanitization' => 'integer',
				            )
			            )
		            ),
	            ),
            ),
            'cost' => array(
	            'title' => __( 'Cost', 'event-genius' ),
	            'subsections' => array(
		            'cost_display' => array(
			            'priority' => 10,
			            'id' => 'cost_settings',
			            'type' => 'text',
			            'callback' => 'cost_settings',
			            'label' => __( 'Cost Display', 'event-genius' ),
			            'description' => __( 'What is displayed for the cost of the event.', 'event-genius' ),
			            'settings' => array(
				            'currency_symbol' => array(
					            'key' => 'currency_symbol',
					            'default' => Settings::get( 'currency_symbol' ),
					            'sanitization' => 'text',
				            ),
				            'cost_amount' => array(
					            'key' => 'cost_amount',
					            'default' => '',
					            'sanitization' => 'text',
				            ),
				            'cost_display' => array(
					            'key' => 'cost_display',
					            'default' => Settings::get( 'cost_display' ),
					            'sanitization' => 'text',
				            )
			            ),
                    ),
                ),

            ),

		);

        foreach ( $sections as $key => $section ) {
            foreach ( $section['subsections'] as $subsection_key => $subsection ) {
                $sections[ $key ]['subsections'][ $subsection_key ]['settings'] = $this->assign_values( $subsection['settings'] );
            }
        }


		return apply_filters( 'evge_single_event_sections_event', $sections );
	}

	public function registration_sections() {
		$sections = array(
			'general' => array(
				'title' => 'Registration',
				'subsections' => array(
					'allow_registration' => array(
						'priority' => 10,
						'id' => 'allow_registration',
						'type' => 'registration_enabled',
						'callback' => 'registration_enabled_display',
						'label' => __( 'Enabled', 'event-genius' ),
						'description' => '',
						'settings' => array(
							array(
								'key' => 'allow_registration',
								'default' => Settings::get( 'allow_registration' ),
								'sanitization' => 'text',
								'enabled_text' => __( 'Enabled', 'event-genius' ),
								'disabled_text' => __( 'Disabled', 'event-genius' ),
								'enabled_aria' => __( 'Registration is enabled', 'event-genius' ),
								'disabled_aria' => __( 'Registration is disabled', 'event-genius' ),
								'label' => __( 'Enabled', 'event-genius' )
							)
						)
					)

				),
			),
			'restrictions' => array(
				'title' => __( 'Restrictions', 'event-genius' ),
				'subsections' => array(
					'capacity' => array(
						'priority' => 20,
						'id' => 'capacity_settings',
						'type' => 'capacity_settings',
						'callback' => 'capacity_settings_display',
						'label' => __( 'Capacity', 'event-genius' ),
						'description' => '',
						'settings' => array(
							'unlimited_capacity' => array(
								'key' => 'unlimited_capacity',
								'default' => Settings::get( 'unlimited_capacity' ),
								'sanitization' => 'boolean'
							),
							'capacity' => array(
								'key' => 'capacity',
								'default' => Settings::get( 'capacity' ),
								'sanitization' => 'integer'
							)
						)
					),
					'timeline' => array(
						'priority' => 30,
						'id' => 'timeline',
						'type' => 'timeline_settings',
						'callback' => 'timeline_settings_display',
						'label' => __( 'Timeline', 'event-genius' ),
						'description' => '',
						'settings' => array(
							'open_type' => array(
								'key' => 'open_type',
								'default' => Settings::get( 'open_type' ),
								'sanitization' => 'text',
							),
							'relative_open_offset' => array(
								'key' => 'relative_open_offset',
								'default' => Settings::get( 'relative_open_offset' ),
								'sanitization' => 'int',
							),
							'relative_open_offset_type' => array(
								'key' => 'relative_open_offset_type',
								'default' => Settings::get( 'relative_open_offset_type' ),
								'sanitization' => 'text',
							),
							'open_date' => array(
								'key' => 'open_date',
								'default' => wp_date( 'Y-m-d' ),
								'sanitization' => 'date',
							),
							'close_type' => array(
                                'key' => 'close_type',
                                'default' => Settings::get( 'close_type' ),
                                'sanitization' => 'text',
                            ),
							'relative_close_offset' => array(
								'key' => 'relative_close_offset',
								'default' => Settings::get( 'relative_close_offset' ),
                                'sanitization' => 'int',
                            ),
							'relative_close_offset_type' => array(
								'key' => 'relative_close_offset_type',
								'default' => Settings::get( 'relative_close_offset_type' ),
                                'sanitization' => 'text',
							),
							'close_date' => array(
                                'key' => 'close_date',
                                'default' => wp_date( 'Y-m-d' ),
                                'sanitization' => 'date',
                            ),
						)
					),					
				),

			),
			'attendee_list' => array(
				'title' => __( 'Attendee List', 'event-genius' ),
				'subsections' => array(
					'show_attendee_list' => array(
						'priority' => 10,
						'id' => 'show_attendee_list',
						'type' => 'toggle_setting_display',
						'callback' => 'toggle_setting_display',
						'label' => __( 'Show Attendee List', 'event-genius' ),
						'description' => '',
						'settings' => array(
							array(
								'key' => 'show_attendee_list',
								'default' => Settings::get( 'show_attendee_list' ),
								'sanitization' => 'text',
								'name' => 'evge_show_attendee_list',
								'enabled_text' => __( 'Yes', 'event-genius' ),
								'disabled_text' => __( 'No', 'event-genius' ),
								'enabled_aria' => __( 'Attendee list is shown', 'event-genius' ),
								'disabled_aria' => __( 'Attendee list is hidden', 'event-genius' ),
								'label' => __( 'Show Attendee List', 'event-genius' )
							)
						)
					),
					'who_can_see_attendee_list' => array(
						'priority' => 20,
						'id' => 'who_can_see_attendee_list',
						'type' => 'radio_group',
						'callback' => 'radio_group_display',
						'label' => __( 'Who Can See Attendee List?', 'event-genius' ),
						'description' => '',
						'options' => array(
							'everyone' => __( 'Everyone', 'event-genius' ),
							'logged_in' => __( 'Logged-in users', 'event-genius' )
						),
						'settings' => array(
							array(
								'key' => 'who_can_see_attendee_list',
								'default' => Settings::get( 'who_can_see_attendee_list' ),
								'sanitization' => 'text'
							)
						)
					)
				)
			)
		);

		foreach ( $sections as $key => $section ) {
			foreach ( $section['subsections'] as $subsection_key => $subsection ) {
				$sections[ $key ]['subsections'][ $subsection_key ]['settings'] = $this->assign_values( $subsection['settings'] );
			}
		}


		return apply_filters( 'evge_single_event_sections_registration', $sections );
	}

    public function assign_values( $settings ) {
        global $post;

	    $post_meta = array();
        if ( ! empty( $post ) ) {
            $post_meta = get_post_meta( $post->ID );
        }
	
		


        foreach ( $settings as $key => $setting ) {
            if ( isset( $post_meta[ 'evge_' . $setting['key'] ] ) ) {
	            $settings[ $key ]['value'] = $post_meta[ 'evge_' . $setting['key'] ][0];
            } else {
	            $settings[ $key ]['value'] = $setting['default'];
            }
        }


        return $settings;
    }

	public function textarea( $section ) {

        ?>
        <div id="evge-single-setting-<?php echo esc_attr( $section['id'] ); ?>" class="evge-single-setting-wrap">
            <div class="evge-single-setting-label">
                <label for="evge-<?php echo esc_attr( $section['settings'][0]['key'] ); ?>" class="evge-main-setting-label"><?php echo esc_html( $section['label'] ); ?></label>
                <?php if ( ! empty( $section['description'] ) ) : ?>
                    <div class="evge-tooltip-wrap">
                        <a href="javascript:void(0);" class="evge-tooltip-link">
                            <?php 
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo Icon::get( 'tooltip' ); 
                            ?>
                        </a>
                        <div class="evge-tooltip evge-shadow">
                            <p><?php echo esc_html( $section['description'] ); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="evge-single-wrap">
                <textarea id="evge-<?php echo esc_attr( $section['settings'][0]['key'] ); ?>" name="evge_<?php echo esc_attr( $section['settings'][0]['key'] ); ?>" rows="4"><?php echo esc_textarea( $section['settings'][0]['value'] ); ?></textarea>
            </div>

        </div>
        <?php
	}
	public function text( $section ) {

		?>
        <div id="evge-single-setting-<?php echo esc_attr( $section['id'] ); ?>" class="evge-single-setting-wrap">
		<?php if ( !empty( $section['label'] ) ) : ?>
            <div class="evge-single-setting-label">
                <label for="evge-<?php echo esc_attr( $section['settings'][0]['key'] ); ?>" class="evge-main-setting-label"><?php echo esc_html( $section['label'] ); ?></label>
            </div>
            <?php endif; ?>
            <div class="evge-single-wrap">
                <input type="text" id="evge-<?php echo esc_attr( $section['settings'][0]['key'] ); ?>" name="evge_<?php echo esc_attr( $section['settings'][0]['key'] ); ?>" value="<?php echo esc_attr( $section['settings'][0]['value'] ); ?>">
            </div>

        </div>
		<?php
	}

	public function settings_row_display( $section, $fields ) {
        ?>
        <div id="evge-single-setting-<?php echo esc_attr( $section['id'] ); ?>" class="evge-single-setting-wrap">
            <div class="evge-settings-grid">
                <?php foreach ( $fields as $field ) : ?>
                    <div class="evge-setting-field<?php echo !empty($field['class']) ? ' ' . esc_attr($field['class']) : ''; ?>">
                        <div class="evge-single-setting-label">
                            <label for="evge_<?php echo esc_attr( $field['key'] ); ?>" class="evge-main-setting-label">
                                <?php echo esc_html( $field['label'] ); ?>
                            </label>
                            <?php if ( !empty( $field['tooltip'] ) ) : ?>
                                <div class="evge-tooltip-wrap">
                                    <a href="javascript:void(0);" class="evge-tooltip-link">
                                        <?php 
                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                        echo Icon::get( 'tooltip' ); 
                                        ?>
                                    </a>
                                    <div class="evge-tooltip evge-shadow">
                                        <p><?php echo esc_html( $field['tooltip'] ); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <input type="<?php echo esc_attr( $field['type'] ?? 'text' ); ?>" 
                                   id="evge_<?php echo esc_attr( $field['key'] ); ?>" 
                                   name="evge_<?php echo esc_attr( $field['key'] ); ?>" 
                                   value="<?php echo esc_attr( $section['settings'][$field['key']]['value'] ); ?>"
                                   <?php echo !empty($field['placeholder']) ? ' placeholder="' . esc_attr($field['placeholder']) . '"' : ''; ?>
                                   <?php echo !empty($field['pattern']) ? ' pattern="' . esc_attr($field['pattern']) . '"' : ''; ?>
                                   class="<?php echo !empty($field['input_class']) ? esc_attr($field['input_class']) : ''; ?>">
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

	public function cost_settings( $section ) {
        $fields = array(
            array(
                'key' => 'currency_symbol',
                'label' => __( 'Currency Symbol', 'event-genius' ),
                'input_class' => 'evge-small-text evge-currency-symbol',
                'pattern' => '[^0-9]*', // Add pattern to prevent digits
                'type' => 'text'
            ),
            array(
                'key' => 'cost_amount',
                'label' => __( 'Amount', 'event-genius' ),
                'type' => 'number',
                'input_class' => 'evge-medium-text'
            ),
            array(
                'key' => 'cost_display',
                'label' => __( 'Display Text', 'event-genius' ),
                'placeholder' => __( 'e.g., Free, Donation', 'event-genius' ),
                'tooltip' => __( 'Use {symbol} to display the currency symbol and {amount} to display the cost amount. Example: "{symbol}{amount}"', 'event-genius' )
            )
        );

        $this->settings_row_display( $section, $fields );
	}


	public function capacity_settings_display( $section ) {
        ?>
        <div id="evge-single-setting-<?php echo esc_attr( $section['id'] ); ?>" class="evge-single-section evge-capacity-settings-wrap">
            <div class="evge-single-setting-label">
                <label for="evge_capacity" class="evge-main-setting-label">
                    <?php esc_html_e( 'Event Capacity', 'event-genius' ); ?>
                </label>
            </div>
        
            <div class="evge-flex evge-flex-center">
                <div class="evge-capacity-input">
                    <input type="number" 
                           id="evge_capacity" 
                           name="evge_capacity" 
                           value="<?php echo esc_attr( $section['settings']['capacity']['value'] ); ?>"
                           min="0"
                           <?php echo $section['settings']['unlimited_capacity']['value'] !== 'enabled' ? '' : 'disabled'; ?>>
                </div>
                
                <?php
                $this->toggle_setting_display( array(
                    'name' => 'evge_unlimited_capacity',
                    'value' => $section['settings']['unlimited_capacity']['value'],
                    'enabled_text' => __( 'Yes', 'event-genius' ),
                    'disabled_text' => __( 'No', 'event-genius' ),
                    'enabled_aria' => __( 'Capacity is unlimited', 'event-genius' ),
                    'disabled_aria' => __( 'Capacity is limited', 'event-genius' ),
                    'label' => __( 'Unlimited Capacity', 'event-genius' ),
                    'wrapper_class' => 'evge-capacity-toggle'
                ) );
                ?>
            </div>
        </div>
        <?php
    }

	public function select( $section ) {

		?>
        <div id="evge-single-setting-<?php echo esc_attr( $section['id'] ); ?>" class="evge-single-setting-wrap evge-sub-section-wrap">
            <?php if ( !empty( $section['label'] ) ) : ?>
            <div class="evge-single-setting-label">
                <label for="evge-<?php echo esc_attr( $section['settings'][0]['key'] ); ?>" class="evge-main-setting-label"><?php echo esc_html( $section['label'] ); ?></label>
            </div>
            <?php endif; ?>
            <div class="evge-single-wrap">
                <select name="evge_<?php echo esc_attr( $section['id'] ); ?>">
                    <?php foreach ( $section['options'] as $option_key => $option_label ) : ?>
                        <option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( $option_key, $section['settings'][0]['value'] ); ?>><?php echo esc_html( $option_label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

        </div>
		<?php
	}

	public function start_end_date_setting_display( $section ) {
		global $post;

        $selected_timezone = empty( $section['settings']['timezone']['value'] ) || 'default' === $section['settings']['timezone']['value'] ? 'default' : $section['settings']['timezone']['value'];
		$timezone = $selected_timezone === 'default' ? wp_timezone_string() : $selected_timezone;
		$start_date_time = new EvgeDateTime( new \DateTime( $section['settings']['start_date']['value'], DateFormatter::timezone_object( $timezone ) ) );
		$end_date_time = new EvgeDateTime( new \DateTime( $section['settings']['end_date']['value'], DateFormatter::timezone_object( $timezone ) ) );
		
		// Check if event is part of a series and not a recurring event
		$is_part_of_series = false;
		$series_id = false;
		$series_title = '';
		$series_edit_link = '';
		
		if ( ! empty( $post ) && ! empty( $post->ID ) ) {
			$repository = new \WPEventGenius\Common\Series\EventSeriesRepository( $this->db );
			$series_id = $repository->get_series_id_for_event( $post->ID );
			
			if ( $series_id ) {
				// Verify the series post exists and isn't in the trash
				$series_post_status = get_post_status( $series_id );
				if ( $series_post_status && $series_post_status !== 'trash' ) {
					// Check if this is NOT a recurring event
					$recurrence_type = get_post_meta( $post->ID, 'evge_recurrence_type', true );
					$is_recurrence = get_post_meta( $post->ID, 'evge_is_recurrence', true );
					
					// Event is part of series and not a recurring event
					if ( ( empty( $recurrence_type ) || $recurrence_type === 'none' ) && empty( $is_recurrence ) ) {
						$is_part_of_series = true;
						$series_title = get_the_title( $series_id );
						$series_edit_link = get_edit_post_link( $series_id );
					}
				}
			}
		}
		
		?>
        <div id="evge-single-setting-<?php echo esc_attr( $section['id'] ); ?>" class="evge-single-setting-wrap">

            <div class="evge-datetime-settings-grid">
                <!-- Existing date/time settings code -->
                <div class="evge-datetime-field">
                    <div class="evge-single-setting-label">
                        <label for="evge_start_date" class="evge-main-setting-label"><?php esc_html_e( 'Start', 'event-genius' ) ?></label>
                    </div>
                    <div>
                        <input id="evge_start_date" type="datetime-local" name="evge_start_date" value="<?php echo esc_attr( $start_date_time->to_datetime_local_format() ); ?>">
                    </div>
                </div>

                <div class="evge-datetime-separator">
                    -
                </div>

                <div class="evge-datetime-field">
                    <div class="evge-single-setting-label">
                        <label for="evge_end_date" class="evge-main-setting-label"><?php esc_html_e( 'End', 'event-genius' ) ?></label>
                    </div>
                    <div>
                        <input id="evge_end_date" type="datetime-local" name="evge_end_date" value="<?php echo esc_attr( $end_date_time->to_datetime_local_format() ); ?>">
                    </div>
                </div>

                <div class="evge-datetime-field">
                    <div class="evge-single-setting-label">
                        <label for="evge_timezone" class="evge-main-setting-label"><?php esc_html_e( 'Timezone', 'event-genius' ) ?></label>
                    </div>
                    <div>
                        <select id="evge_timezone" name="evge_timezone">
                            <option value="default" <?php selected( 'default', $selected_timezone ); ?>><?php esc_html_e( 'Default', 'event-genius' ); ?> (<?php echo esc_html( wp_timezone_string() ); ?>)</option>
                            <?php foreach ( \DateTimeZone::listIdentifiers() as $timezone_identifier ) : ?>
                                <option value="<?php echo esc_attr( $timezone_identifier ); ?>" <?php selected( $selected_timezone, $timezone_identifier ); ?>><?php echo esc_html( $timezone_identifier ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="evge-extra-setting">
                <div id="evge-single-setting-all-day">
		            <?php
		            $this->toggle_setting_display( array(
			            'name' => 'evge_all_day',
			            'value' => $section['settings']['all_day']['value'],
			            'label' => __( 'All Day Event', 'event-genius' ),
			            'enabled_aria' => __( 'All Day Event is enabled', 'event-genius' ),
			            'disabled_aria' => __( 'All Day Event is disabled', 'event-genius' )
		            ) );
		            ?>
                </div>
                <?php if ( $is_part_of_series ) : ?>
                    <?php \WPEventGenius\Common\Utils\Notices::link( sprintf( __( 'This event is a part of the series %s', 'event-genius' ), '<a href="' . esc_url( $series_edit_link ) . '">' . esc_html( $series_title ) . '</a>' ) ); ?>
                <?php else : ?>
                    <div id="evge-single-recurrence-schedule">
                        <div class="evge-single-wrap">
                            <div class="evge-single-setting-label">
                                <label for="evge_recurrence_type" class="evge-main-setting-label">
                                    <?php esc_html_e('Repeat', 'event-genius'); ?>
                                </label>
                            </div>
                            <select id="evge_recurrence_type" name="evge_recurrence_type">
                                <option value="none" <?php selected($section['settings']['recurrence_type']['value'], 'none'); ?>>
                                    <?php esc_html_e('Does not repeat', 'event-genius'); ?>
                                </option>
                                <option value="daily" <?php selected($section['settings']['recurrence_type']['value'], 'daily'); ?>>
                                    <?php esc_html_e('Daily', 'event-genius'); ?>
                                </option>
                                <option value="weekly" data-format="<?php 
                                    /* translators: %s: day of the week (e.g. "Wednesday") */
                                    esc_attr_e('Weekly (every %s)', 'event-genius'); ?>" 
                                    <?php selected($section['settings']['recurrence_type']['value'], 'weekly'); ?>>
                                    <?php esc_html_e('Weekly', 'event-genius'); ?>
                                </option>
                                <option value="monthly" data-format="<?php 
                                    /* translators: %s: day of the month relative to the day of the week, week number (e.g. "3rd Thursday") */
                                    esc_attr_e('Monthly (every %s)', 'event-genius'); ?>"
                                    <?php selected($section['settings']['recurrence_type']['value'], 'monthly'); ?>>
                                    <?php esc_html_e('Monthly', 'event-genius'); ?>
                                </option>
                                <option value="weekday" <?php selected($section['settings']['recurrence_type']['value'], 'weekday'); ?>>
                                    <?php esc_html_e('Every Weekday (Monday through Friday)', 'event-genius'); ?>
                                </option>
                            </select>
                        </div>
                        
                        <div id="evge-recurrence-end-date" class="<?php echo $section['settings']['recurrence_type']['value'] === 'none' ? 'evge-hidden' : ''; ?>">
                            <div class="evge-single-setting-label">
                                <label for="evge_recurrence_end_date" class="evge-main-setting-label">
                                    <?php esc_html_e('End Repeat', 'event-genius'); ?>
                                </label>
                            </div>
                            <div class="evge-single-wrap">
                                <input type="date" 
                                       id="evge_recurrence_end_date" 
                                       name="evge_recurrence_end_date" 
                                       value="<?php echo esc_attr($section['settings']['recurrence_end_date']['value'] ?: gmdate('Y-m-d', strtotime('+2 months'))); ?>"
                                       min="<?php echo esc_attr(gmdate('Y-m-d')); ?>">
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
	}

	public function multi_select_list($section, $queried_posts, $selected_posts, $post_type, $label) {
		?>
        <div id="evge-single-setting-<?php echo esc_attr( $post_type ); ?>" class="evge-single-setting-wrap">
            <div class="evge-single-setting-label">
                <label for="evge_<?php echo esc_attr( $post_type ); ?>_0" class="evge-main-setting-label"><?php echo esc_html( $label ); ?></label>
            </div>
            <div class="evge-multi-select-list">
                <div class="evge-item-list">
                    <?php 
                    $first = true;
                    if ( empty( $selected_posts ) ) {
                        $selected_posts = array( 0 );
                    }
                    foreach ( $selected_posts as $selected_post ) : ?>
                        <div class="evge-single-wrap evge-flex evge-flex-center<?php echo $first ? ' evge-primary-item' : ''; ?>">
                            <select name="evge_<?php echo esc_attr( $post_type ); ?>[]">
                                <option value="" class="evge-empty-select"><?php esc_html_e( 'Select', 'event-genius' ); ?></option>
                                <?php foreach ( $queried_posts as $queried_post ) : ?>
                                    <option value="<?php echo esc_attr( $queried_post->ID ); ?>" <?php selected( $selected_post, $queried_post->ID ); ?>><?php echo esc_html( $queried_post->post_title ); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($first) : ?>
                                <a class="evge-add-button button-secondary evge-create-new" data-post-type="<?php echo esc_attr( $post_type ); ?>">
                                    <span class="evge-icon-text">
                                        <?php 
                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                        echo Icon::get( 'plus' ); ?>
                                        <?php esc_html_e( 'Create New', 'event-genius' ); ?>
                                    </span>
                                </a>
                            <?php else : ?>
                                <a href="#" class="evge-item-remove">
                                    <?php 
                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                    echo Icon::get( 'close' ); 
                                    ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        <?php 
                        $first = false;
                    endforeach; ?>
                </div>
            </div>
            <a href="#" class="evge-add-item-link">
                + <?php 
                /* translators: %s: lowercase label of the item type being added (e.g. "ticket", "session") */
                printf( esc_html__( 'Add another %s', 'event-genius' ), esc_html( strtolower( $label ) ) ); 
                ?>
            </a>
        </div>
		<?php
	}


	public function timeline_settings_display( $section ) {
        $selected_timezone = empty( $section['settings']['timezone']['value'] ) || 'default' === $section['settings']['timezone']['value'] ? 'default' : $section['settings']['timezone']['value'];
		$timezone = $selected_timezone === 'default' ? wp_timezone_string() : $selected_timezone;
        ?>
        <div id="evge-single-setting-<?php echo esc_attr( $section['id'] ); ?>" class="evge-single-section evge-timeline-settings-wrap">
            <div class="evge-timeline-grid">
                <!-- Registration Opens -->
                <div class="evge-timeline-field">
                    <div class="evge-single-setting-label">
                        <label for="evge_open_type" class="evge-main-setting-label">
                            <?php esc_html_e( 'Registration Opens', 'event-genius' ); ?>
                        </label>
                    </div>
                    <div>
                        <select id="evge_open_type" name="evge_open_type">
                            <option value="immediately" <?php selected( 'immediately', $section['settings']['open_type']['value'] ); ?>><?php esc_html_e( 'Immediately', 'event-genius' ); ?></option>
                            <option value="relative" <?php selected( 'relative', $section['settings']['open_type']['value'] ); ?>><?php esc_html_e( 'Relative to Event Start', 'event-genius' ); ?></option>
                            <option value="custom" <?php selected( 'custom', $section['settings']['open_type']['value'] ); ?>><?php esc_html_e( 'Custom Date', 'event-genius' ); ?></option>
                        </select>
                    </div>

                    <div class="evge-open-type-sub" data-type="relative">
                        <div class="evge-flex evge-flex-center evge-timeline-offset">
                            <input type="number" 
                                   id="evge_relative_open_offset" 
                                   name="evge_relative_open_offset" 
                                   value="<?php echo esc_attr( $section['settings']['relative_open_offset']['value'] ); ?>"
                                   min="0">
                            <select id="evge_relative_open_offset_type" name="evge_relative_open_offset_type">
                                <option value="days" <?php selected( 'days', $section['settings']['relative_open_offset_type']['value'] ); ?>><?php esc_html_e( 'Days', 'event-genius' ); ?></option>
                                <option value="hours" <?php selected( 'hours', $section['settings']['relative_open_offset_type']['value'] ); ?>><?php esc_html_e( 'Hours', 'event-genius' ); ?></option>
                            </select>
                            <?php esc_html_e( 'before event starts', 'event-genius' ); ?>
                        </div>
                    </div>

                    <?php
                    $open_date = $section['settings']['open_date']['value'];
                    $open_date_time = new EvgeDateTime( new \DateTime( $open_date, DateFormatter::timezone_object( $this->get_event_timezone() ) ) );
                    $open_date_formatted = $open_date_time->to_datetime_local_format();
                    ?>
                    <div class="evge-open-type-sub" data-type="custom">
                        <input type="datetime-local" 
                               id="evge_open_date" 
                               name="evge_open_date" 
                               value="<?php echo esc_attr( $open_date_formatted ); ?>">
                    </div>
                </div>
                <!-- Registration Closes -->
                <div class="evge-timeline-field">
                    <div class="evge-single-setting-label">
                        <label for="evge_close_type" class="evge-main-setting-label">
                            <?php esc_html_e( 'Registration Closes', 'event-genius' ); ?>
                        </label>
                    </div>
                    <div>
                        <select id="evge_close_type" name="evge_close_type">
                            <option value="never" <?php selected( 'never', $section['settings']['close_type']['value'] ); ?>><?php esc_html_e( 'Never', 'event-genius' ); ?></option>
                            <option value="relative" <?php selected( 'relative', $section['settings']['close_type']['value'] ); ?>><?php esc_html_e( 'Relative to Event Start', 'event-genius' ); ?></option>
                            <option value="custom" <?php selected( 'custom', $section['settings']['close_type']['value'] ); ?>><?php esc_html_e( 'Custom Date', 'event-genius' ); ?></option>
                        </select>
                    </div>

                    <div class="evge-close-type-sub" data-type="relative">
                        <div class="evge-flex evge-flex-center evge-timeline-offset">
                            <input type="number" 
                                   id="evge_relative_close_offset" 
                                   name="evge_relative_close_offset" 
                                   value="<?php echo esc_attr( $section['settings']['relative_close_offset']['value'] ); ?>"
                                   min="0">
                            <select id="evge_relative_close_offset_type" name="evge_relative_close_offset_type">
                                <option value="days" <?php selected( 'days', $section['settings']['relative_close_offset_type']['value'] ); ?>><?php esc_html_e( 'Days', 'event-genius' ); ?></option>
                                <option value="hours" <?php selected( 'hours', $section['settings']['relative_close_offset_type']['value'] ); ?>><?php esc_html_e( 'Hours', 'event-genius' ); ?></option>
                            </select>
                            <?php esc_html_e( 'before event starts', 'event-genius' ); ?>
                        </div>
                    </div>
                 <?php
                    $close_date = $section['settings']['close_date']['value'];
                    $close_date_time = new EvgeDateTime( new \DateTime( $close_date, DateFormatter::timezone_object( $this->get_event_timezone() ) ) );
                    $close_date_formatted = $close_date_time->to_datetime_local_format();
                    ?>
                    <div class="evge-close-type-sub" data-type="custom">
                        <input type="datetime-local" 
                               id="evge_close_date" 
                               name="evge_close_date" 
                               value="<?php echo esc_attr( $close_date_formatted ); ?>">
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

	public function venue($section) {
        $venue_query = new VenueQuery();
		$venue_query->add_wp_query();
        $venue_posts = $venue_query->get_venues();

		global $post;

		if ( ! empty( $post ) && empty( $this->event_post ) ) {
            $this->event_post = new EventPost( $post->ID );
		}
		$selected_venues = empty( $this->event_post ) ? array( '' ) : $this->event_post->get_venue_ids();

        $this->multi_select_list( $section, $venue_posts, $selected_venues, 'venue', __( 'Venue', 'event-genius' ) );

        $fields = array(
	        array(
		        'key' => 'title',
		        'label' => __( 'Venue Title', 'event-genius' ),
	        ),array(
                'key' => 'address',
                'label' => __( 'Street Address', 'event-genius' ),
            ),
            array(
                'key' => 'city',
                'label' => __( 'City', 'event-genius' ),
            ),
	        array(
		        'key' => 'state',
		        'label' => __( 'State', 'event-genius' ),
	        ),
            array(
                'key' => 'country',
                'label' => __( 'Country', 'event-genius' ),
            ),
            array(
                'key' => 'postal_code',
                'label' => __( 'Postal Code', 'event-genius' ),
            ),
            array(
                'key' => 'phone',
                'label' => __( 'Venue Phone', 'event-genius' ),
            ),
            array(
                'key' => 'website',
                'label' => __( 'Venue Website', 'event-genius' ),
            ),
	        array(
		        'key' => 'map_url',
		        'label' => __( 'Interactive Map URL', 'event-genius' ),
	        ),
        )
        ?>
        <div class="evge-venue-quick-create evge-standout-box" style="display: none;">

            <div class="evge-quick-create-header">
                <h3><?php esc_html_e('Create New Venue', 'event-genius'); ?></h3>
                <a class="evge-icon-link evge-tiny-button evge-undo-create" data-post-type="venue">
                    <span class="evge-icon-text">
                        <?php 
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo Icon::get('close'); 
                        ?>
                    </span>
                </a>
            </div>
            <div class="evge-section-explanation">
            <?php
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo Icon::get( 'info' ); ?><?php esc_html_e('Add details below and save your event to create and assign a new venue', 'event-genius') ?>
            </div>
            <div class="evge-quick-create-form">
                <div class="evge-settings-grid">
                    <div class="evge-setting-field">
                        <div class="evge-single-setting-label">
                            <label for="evge_new_venue_title">
                                <?php esc_html_e('Venue Name', 'event-genius'); ?>
                            </label>
                        </div>
                        <input type="text" id="evge_new_venue_title" name="evge_new_venue_title">
                    </div>
                </div>

                <!-- Image Upload -->
                <div class="evge-settings-grid">
                    <div class="evge-setting-field">
                        <div class="evge-single-setting-label">
                            <label><?php esc_html_e('Venue Image', 'event-genius'); ?></label>
                        </div>
                        <div class="evge-image-upload-wrap">
                            <input type="hidden" name="evge_new_venue_image_id" id="evge_new_venue_image_id" value="">
                            <div class="evge-image-preview"></div>
                            <button type="button" class="button evge-upload-image">
                                <?php esc_html_e('Upload Image', 'event-genius'); ?>
                            </button>
                            <button type="button" class="button evge-remove-image" style="display:none;">
                                <?php esc_html_e('Remove Image', 'event-genius'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Address Fields -->
                <div class="evge-settings-grid">
                    <div class="evge-setting-field">
                        <div class="evge-single-setting-label">
                            <label for="evge_new_venue_address">
                                <?php esc_html_e('Street Address', 'event-genius'); ?>
                            </label>
                        </div>
                        <input type="text" id="evge_new_venue_address_1" name="evge_new_venue_address_1" style="margin-bottom: 5px;">
                        <input type="text" id="evge_new_venue_address_2" name="evge_new_venue_address_2">
                    </div>
                </div>
                <div class="evge-2-cols">
                    <div class="evge-settings-grid">
                        <div class="evge-setting-field">
                            <div class="evge-single-setting-label">
                                <label for="evge_new_venue_city">
                                    <?php esc_html_e('City', 'event-genius'); ?>
                                </label>
                            </div>
                            <input type="text" id="evge_new_venue_city" name="evge_new_venue_city">
                        </div>
                    </div>
                    <div class="evge-settings-grid">
                        <div class="evge-setting-field">
                            <div class="evge-single-setting-label">
                                <label for="evge_new_venue_state">
                                    <?php esc_html_e('State', 'event-genius'); ?>
                                </label>
                            </div>
                            <input type="text" id="evge_new_venue_state" name="evge_new_venue_state">
                        </div>
                    </div>
                    <div class="evge-settings-grid">
                        <div class="evge-setting-field">
                            <div class="evge-single-setting-label">
                                <label for="evge_new_venue_postal_code">
                                    <?php esc_html_e('Postal Code', 'event-genius'); ?>
                                </label>
                            </div>
                            <input type="text" id="evge_new_venue_postal_code" name="evge_new_venue_postal_code">
                        </div>
                    </div>
                    <div class="evge-settings-grid">
                        <div class="evge-setting-field">
                            <div class="evge-single-setting-label">
                                <label for="evge_new_venue_country">
                                    <?php esc_html_e('Country', 'event-genius'); ?>
                                </label>
                            </div>
                            <input type="text" id="evge_new_venue_country" name="evge_new_venue_country">
                        </div>
                    </div>
                </div>
                <div class="evge-settings-grid">
                    <div class="evge-setting-field">
                        <div class="evge-single-setting-label">
                            <label for="evge_new_venue_map_url">
					            <?php esc_html_e('Map URL', 'event-genius'); ?>
                            </label>
                        </div>
                        <input type="text" id="evge_new_venue_map_url" name="evge_new_venue_map_url" placeholder='<iframe src="https://www.google.com/maps/embed?pb=!1m...'>
                    </div>
                </div>
                <!-- Contact Fields -->
                <div class="evge-settings-grid">
                    <div class="evge-setting-field">
                        <div class="evge-single-setting-label">
                            <label for="evge_new_venue_phone">
                                <?php esc_html_e('Phone', 'event-genius'); ?>
                            </label>
                        </div>
                        <input type="text" id="evge_new_venue_phone" name="evge_new_venue_phone">
                    </div>
                </div>

                <div class="evge-settings-list">
                    <div class="evge-single-setting-label">
						<label for="evge-links">Links</label>
					</div>
                    <div class="evge-settings-grid">
                        <div class="evge-setting-field">
                            <div class="evge-single-setting-label">
                                <label for="evge-link-website>">
                                    <span class="screen-reader-text"><?php esc_html_e('Website', 'event-genius'); ?></span>
                                    <span class="dashicons dashicons-admin-site-alt3"></span>
                                </label>
                            </div>
                            <input type="url" id="evge_new_venue_website" name="evge_new_venue_website" placeholder="Website">
                        </div>
                    </div>

                    <!-- Social Links -->
                    <div class="evge-settings-grid">
                        <div class="evge-setting-field">
                            <div class="evge-single-setting-label">
                                <label for="evge-link-facebook">
                                    <span class="screen-reader-text"><?php esc_html_e('Facebook', 'event-genius'); ?></span>
                                    <span class="dashicons dashicons-facebook"></span>
                                </label>
                            </div>
                            <input type="url" id="evge_new_venue_facebook" name="evge_new_venue_facebook" placeholder="Facebook">
                        </div>
                    </div>
                    <div class="evge-settings-grid">
                        <div class="evge-setting-field">
                            <div class="evge-single-setting-label">
                                <label for="evge-link-twitter">
                                    <span class="screen-reader-text"><?php esc_html_e('Twitter', 'event-genius'); ?></span>
                                    <span class="dashicons dashicons-twitter"></span>
                                </label>
                            </div>
                            <input type="url" id="evge_new_venue_twitter" name="evge_new_venue_twitter" placeholder="X (Twitter)">
                        </div>
                    </div>
                    <div class="evge-settings-grid">
                        <div class="evge-setting-field">
                            <div class="evge-single-setting-label">
                                <label for="evge-link-instagram">
                                    <span class="screen-reader-text"><?php esc_html_e('Instagram', 'event-genius'); ?></span>
                                    <span class="dashicons dashicons-instagram"></span>
                                </label>
                            </div>
                            <input type="url" id="evge_new_venue_instagram" name="evge_new_venue_instagram" placeholder="Instagram">
                        </div>
                    </div>
                    <div class="evge-settings-grid">
                        <div class="evge-setting-field">
                            <div class="evge-single-setting-label">
                                <label for="evge-link-linkedin">
                                    <span class="screen-reader-text"><?php esc_html_e('LinkedIn', 'event-genius'); ?></span>
                                    <span class="dashicons dashicons-linkedin"></span>
                                </label>
                            </div>
                            <input type="url" id="evge_new_venue_linkedin" name="evge_new_venue_linkedin" placeholder="LinkedIn">
                        </div>
                    </div>
                    <div class="evge-settings-grid">
                        <div class="evge-setting-field">
                            <div class="evge-single-setting-label">
                                <label for="evge-link-youtube">
                                    <span class="screen-reader-text"><?php esc_html_e('YouTube', 'event-genius'); ?></span>
                                    <span class="dashicons dashicons-youtube"></span>
                                </label>
                            </div>
                            <input type="url" id="evge_new_venue_youtube" name="evge_new_venue_youtube" placeholder="YouTube">
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <?php
	}

	public function organizer($section) {
		$organizer_query = new OrganizerQuery();
		$organizer_query->add_wp_query();
		$organizer_posts = $organizer_query->get_organizers();

		global $post;

		if ( ! empty( $post ) && empty( $this->event_post ) ) {
			$this->event_post = new EventPost( $post->ID );
		}
		$selected_organizers = empty( $this->event_post ) ? array( '' ) : $this->event_post->get_organizer_ids();
		$this->multi_select_list( $section, $organizer_posts, $selected_organizers, 'organizer', __( 'Organizer', 'event-genius' ) );
		$fields = array(
			array(
				'key' => 'summary',
				'label' => __( 'Summary', 'event-genius' ),
				'type' => 'textarea'
			),
			array(
				'key' => 'email',
				'label' => __( 'Email', 'event-genius' ),
			),
			array(
				'key' => 'phone',
				'label' => __( 'Phone', 'event-genius' ),
			),
			array(
				'key' => 'links',
				'label' => __( 'Links', 'event-genius' ),
				'sub_settings' => array(
					array(
						'key' => 'website',
						'icon' => 'admin-site-alt3',
						'label' => __( 'Website', 'event-genius' )
					),
					array(
						'key' => 'facebook',
						'icon' => 'facebook',
						'label' => __( 'Facebook', 'event-genius' )
					),
					array(
						'key' => 'twitter',
						'icon' => 'twitter',
						'label' => __( 'Twitter', 'event-genius' )
					),
					array(
						'key' => 'instagram',
						'icon' => 'instagram',
						'label' => __( 'Instagram', 'event-genius' )
					),
					array(
						'key' => 'linkedin',
						'icon' => 'linkedin',
						'label' => __( 'LinkedIn', 'event-genius' )
					),
					array(
						'key' => 'youtube',
						'icon' => 'youtube',
						'label' => __( 'YouTube', 'event-genius' )
					)
				)
			)
		);

		?>
		<div class="evge-organizer-quick-create evge-standout-box" style="display: none;">
			<div class="evge-quick-create-header">
                <?php 
                /* translators: %s: The word "Organizer" */
                printf(esc_html__('Create New %s', 'event-genius'), esc_html__( 'Organizer', 'event-genius' )); 
                ?>
				<a class="evge-icon-link evge-tiny-button evge-undo-create" data-post-type="organizer">
					<span class="evge-icon-text">
						<?php 
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo Icon::get('close'); 
                        ?>
					</span>
				</a>
			</div>

			<div class="evge-quick-create-form">
				<!-- Title field -->
				<div class="evge-settings-grid">
					<div class="evge-setting-field">
						<div class="evge-single-setting-label">
							<label for="evge_new_organizer_title">
								<?php esc_html_e('Organizer Name', 'event-genius'); ?>
							</label>
						</div>
						<input type="text" id="evge_new_organizer_title" name="evge_new_organizer_title">
					</div>
				</div>

				<!-- Image Upload -->
				<div class="evge-settings-grid">
					<div class="evge-setting-field">
						<div class="evge-single-setting-label">
							<label><?php esc_html_e('Organizer Image', 'event-genius'); ?></label>
						</div>
						<div class="evge-image-upload-wrap">
							<input type="hidden" name="evge_new_organizer_image_id" id="evge_new_organizer_image_id" value="">
							<div class="evge-image-preview"></div>
							<button type="button" class="button evge-upload-image">
								<?php esc_html_e('Upload Image', 'event-genius'); ?>
							</button>
							<button type="button" class="button evge-remove-image" style="display:none;">
								<?php esc_html_e('Remove Image', 'event-genius'); ?>
							</button>
						</div>
					</div>
				</div>

				<!-- Existing organizer fields -->
				<?php foreach ($fields as $field) : ?>
					<div id="evge-single-setting-new-organizer-<?php echo esc_attr($field['key']); ?>" class="evge-single-setting-wrap">
						<div class="evge-single-setting-label">
							<label for="evge-<?php echo esc_attr($field['key']); ?>"><?php echo esc_html($field['label']); ?></label>
						</div>
						<?php if ($field['key'] === 'summary') : ?>
							<div class="evge-single-wrap">
								<textarea id="evge-<?php echo esc_attr($field['key']); ?>" 
										  name="evge_new_organizer_<?php echo esc_attr($field['key']); ?>" 
										  rows="4"></textarea>
							</div>
						<?php elseif ($field['key'] === 'links') : ?>
							<div class="evge-single-wrap evge-settings-list">
								<?php foreach ($field['sub_settings'] as $sub_setting) : ?>
									<div class="evge-settings-list-item">
										<label for="evge-link-<?php echo esc_attr($sub_setting['key']); ?>">
                                            <?php if ( !empty( $sub_setting['icon'] ) ) : ?>
                                                <span class="screen-reader-text"><?php echo esc_html( $sub_setting['label'] ); ?></span>
                                                <span class="dashicons dashicons-<?php echo esc_attr( $sub_setting['icon'] ); ?>"></span>
                                            <?php else : ?>
                                                <?php echo esc_html( $sub_setting['label'] ); ?>
                                            <?php endif; ?>
										</label>
										<input type="url" 
											   id="evge-link-<?php echo esc_attr($sub_setting['key']); ?>" 
											   name="evge_new_organizer_<?php echo esc_attr($sub_setting['key']); ?>" 
											   value=""
                                               placeholder="<?php echo esc_html( $sub_setting['label'] ); ?>">
									</div>
								<?php endforeach; ?>
							</div>
						<?php else : ?>
							<div class="evge-single-wrap">
								<input type="text" 
									   id="evge-<?php echo esc_attr($field['key']); ?>" 
									   name="evge_new_organizer_<?php echo esc_attr($field['key']); ?>" 
									   value="">
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	public function save_post($post_id) {
        if ( empty( $_POST['evge_save_event_nonce'] ) ) {
            return;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $nonce = sanitize_text_field(wp_unslash($_POST['evge_save_event_nonce']));
        if ( ! wp_verify_nonce( $nonce, 'evge_save_event_nonce' ) ) {
            return;
        }

        if ( empty( $_POST['evge_start_date'] ) ) {
            return;
        }

		// Get sanitized post data
		$sanitized = EventCreate::sanitize_event_data($_POST);
		$changes = array();

		// Check if we should update the global registration setting
		if (!empty($_POST['evge_set_registration_default']) && isset($sanitized['evge_allow_registration'])) {
			$evge_settings = get_option('evge_settings', array());
			$evge_settings['allow_registration'] = $sanitized['evge_allow_registration'];
			update_option('evge_settings', $evge_settings);
		}

		// Check for timezone changes
		if ($diff = $this->get_value_difference(get_post_meta($post_id, 'evge_timezone', true), $sanitized['evge_timezone'])) {
			$changes['timezone'] = $diff;
		}

		// Check for date changes
		if ($diff = $this->get_value_difference(get_post_meta($post_id, 'evge_start_date', true), $sanitized['evge_start_date'])) {
			$changes['start_date'] = $diff;
		}
		if ($diff = $this->get_value_difference(get_post_meta($post_id, 'evge_end_date', true), $sanitized['evge_end_date'])) {
			$changes['end_date'] = $diff;
		}

		// Check for recurrence changes
		if (isset($sanitized['evge_recurrence_type'])) {
			$recurrence_type = $sanitized['evge_recurrence_type'];
			if ($diff = $this->get_value_difference(get_post_meta($post_id, 'evge_recurrence_type', true), $recurrence_type)) {
				$changes['recurrence_type'] = $diff;
			}
		}
		
		if (isset($sanitized['evge_recurrence_end_date'])) {
			$recurrence_end_date_str = $sanitized['evge_recurrence_end_date'];
			if ($diff = $this->get_value_difference(get_post_meta($post_id, 'evge_recurrence_end_date', true), $recurrence_end_date_str)) {
				$changes['recurrence_end_date'] = $diff;
			}
		}

		// Update all post meta
		EventCreate::update_event_meta($post_id, $sanitized);

		// Handle venue assignments and creation
		$venue_ids = !empty($_POST['evge_venue']) ? array_map('absint', $_POST['evge_venue']) : array();

		if (!empty($_POST['evge_quick_create_venue'])) {
			$title = isset($_POST['evge_new_venue_title']) ? sanitize_text_field(wp_unslash($_POST['evge_new_venue_title'])) : '';
			$venue_create = new VenueCreate(array('post_title' => $title));
            $image_id = !empty($_POST['evge_new_venue_image_id']) ? absint($_POST['evge_new_venue_image_id']) : null;
			$new_post_id = $venue_create->insert_new_post($image_id);
			
			if ($new_post_id) {
				$expected_fields = $venue_create->expected_input_names();
                $venue_expected_data = array();
                foreach ($expected_fields as $field) {
                    $venue_expected_data[$field] = isset($_POST[$field]) ? $_POST[$field] : '';
                }
				$sanitized_post_meta_array = $venue_create->sanitize_input_for_meta_key_pairs($venue_expected_data);
				$venue_create->insert_meta($sanitized_post_meta_array);
				// Add the new venue ID to the array of existing venues
				$venue_ids[] = $new_post_id;
			}
		}

		$this->save_post_meta_id_array($post_id, 'evge_venue', $venue_ids);

		// Handle organizer assignments and creation
		$organizer_ids = !empty($_POST['evge_organizer']) ? array_map('absint', $_POST['evge_organizer']) : array();
		if (!empty($_POST['evge_quick_create_organizer'])) {
			$title = isset($_POST['evge_new_organizer_title']) ? sanitize_text_field(wp_unslash($_POST['evge_new_organizer_title'])) : '';
			$organizer_create = new OrganizerCreate(array('post_title' => $title));
            $image_id = !empty($_POST['evge_new_organizer_image_id']) ? absint($_POST['evge_new_organizer_image_id']) : null;
			$new_post_id = $organizer_create->insert_new_post($image_id);
			
			if ($new_post_id) {
				$expected_fields = $organizer_create->expected_input_names();
                $organizer_expected_data = array();
                foreach ($expected_fields as $field) {
                    $organizer_expected_data[$field] = isset($_POST[$field]) ? $_POST[$field] : '';
                }
				$sanitized_post_meta_array = $organizer_create->sanitize_input_for_meta_key_pairs($organizer_expected_data);

				$organizer_create->insert_meta($sanitized_post_meta_array);
				// Add the new organizer ID to the array of existing organizers
				$organizer_ids[] = $new_post_id;
			}
		}

		$this->save_post_meta_id_array($post_id, 'evge_organizer', $organizer_ids);

		// Create date objects for sync_event_timing
		$timezone = $sanitized['evge_timezone'];
		$start_date = new EvgeDateTime(new \DateTime($sanitized['evge_start_date'], DateFormatter::timezone_object($timezone)));
		$end_date = new EvgeDateTime(new \DateTime($sanitized['evge_end_date'], DateFormatter::timezone_object($timezone)));

		// Sync timing data to the events table
		$this->sync_event_timing($post_id, $start_date, $end_date, $timezone);

		// Get recurrence type and end date
		$recurrence_type = isset($sanitized['evge_recurrence_type']) ? $sanitized['evge_recurrence_type'] : 'none';
		
		// Create recurrence end date object
		if (isset($sanitized['evge_recurrence_end_date'])) {
			$recurrence_end_date = new EvgeDateTime(new \DateTime($sanitized['evge_recurrence_end_date'], DateFormatter::timezone_object($timezone)));
		} else {
			// Default to 2 months from start date
			$recurrence_end_date = new \DateTime($start_date->format('Y-m-d'));
			$recurrence_end_date->modify('+2 months');
			$recurrence_end_date = new EvgeDateTime($recurrence_end_date);
			update_post_meta($post_id, 'evge_recurrence_end_date', $recurrence_end_date->format('Y-m-d'));
		}

		$pattern_data = array();
		if ($recurrence_type !== 'none') {
			// Create recurrence pattern
			$pattern_data = array(
				'type' => $recurrence_type,
				'interval' => 1,
				'start_date' => $start_date->format('Y-m-d H:i:s'),
				'end_date' => $recurrence_end_date->format('Y-m-d H:i:s'),
			);

			//$this->maybe_queue_series_update($post_id, $pattern_data);
		}

		// Manage recurrence series with the new detailed changes
		$this->manage_recurrence_series($post_id, $recurrence_type, $pattern_data, $changes);

        do_action( 'evge_event_saved', $post_id );
	}

	public function enqueue($screen) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ((empty( $_GET['page'] ) || $_GET['page'] !== 'evge-all-events') && get_post_type() !== EVGE_EVENT_POST_TYPE) {
			return;
		}
		
		wp_enqueue_media();
		EVGE()->style_service()->enqueue_style( 'evge_custom_post_type_settings' );
		
        EVGE()->script_service()->enqueue_script( 'evge_admin_common' );
        EVGE()->script_service()->enqueue_script( 'evge_settings' );
        EVGE()->script_service()->enqueue_script( 'evge_post_editor_common' );
        $screen = get_current_screen();
        if ( $screen && $screen->is_block_editor ) {
            EVGE()->script_service()->enqueue_script( 'evge_post_editor_block' );
        } else {
            EVGE()->script_service()->enqueue_script( 'evge_post_editor_classic' );
        }
	}

    protected function save_post_meta_id_array($post_id, $meta_key, $meta_array_values) {
        // Get existing values from post meta
        $existing_values = get_post_meta($post_id, $meta_key);
        
        // Convert all values to integers for consistent comparison and remove duplicates
        $absint_meta_array_values = array_unique(array_filter(array_map('absint', $meta_array_values)));
        
        if (empty($absint_meta_array_values)) {
            // Delete all related meta if there are no valid values
            delete_post_meta($post_id, $meta_key . '_order');
            foreach ($existing_values as $value) {
                delete_post_meta($post_id, $meta_key, $value);
            }
            return;
        }
        
        // Save the order of the IDs to honor the preferred order of venues/organizers
        update_post_meta($post_id, $meta_key . '_order', wp_json_encode($absint_meta_array_values));

        // Find values to add (in new array but not in existing)
        $to_add = array_diff($absint_meta_array_values, $existing_values);
        
        // Find values to remove (in existing but not in new array)
        $to_remove = array_diff($existing_values, $absint_meta_array_values);

        // Remove old values
        foreach ($to_remove as $value) {
            delete_post_meta($post_id, $meta_key, $value);
        }

        // Add new values
        foreach ($to_add as $value) {
            if (!empty($value)) {
                add_post_meta($post_id, $meta_key, $value);
            }
        }
    }

    public function toggle_setting_display( $args ) {
		$args = isset( $args['settings'] ) ? $args['settings'][0] : $args;
        $value = isset( $args['value'] ) ? $args['value'] : 'disabled';
        $name = isset( $args['name'] ) ? $args['name'] : '';
        $enabled_text = isset( $args['enabled_text'] ) ? $args['enabled_text'] : __( 'Yes', 'event-genius' );
        $disabled_text = isset( $args['disabled_text'] ) ? $args['disabled_text'] : __( 'No', 'event-genius' );
        $enabled_aria = isset( $args['enabled_aria'] ) ? $args['enabled_aria'] : __( 'Setting is enabled', 'event-genius' );
        $disabled_aria = isset( $args['disabled_aria'] ) ? $args['disabled_aria'] : __( 'Setting is disabled', 'event-genius' );
        $wrapper_class = isset( $args['wrapper_class'] ) ? ' ' . $args['wrapper_class'] : '';
        ?>
        <div class="evge-toggle-setting<?php echo esc_attr( $wrapper_class ); ?>">
            <input class="evge-toggle-setting-enabled" 
                   type="hidden" 
                   name="<?php echo esc_attr( $name ); ?>" 
                   value="<?php echo $value === 'enabled' ? 'enabled' : 'disabled'; ?>">
            <a class="evge-settings-toggle-wrap" href="">
                <?php if ( $value === 'enabled' ) : ?>
                    <span class="evge-settings-toggle evge-input-toggle--enabled" 
                          aria-label="<?php echo esc_attr( $enabled_aria ); ?>">
                        <?php echo esc_html( $enabled_text ); ?>
                    </span>
                <?php else : ?>
                    <span class="evge-settings-toggle evge-input-toggle--disabled" 
                          aria-label="<?php echo esc_attr( $disabled_aria ); ?>">
                        <?php echo esc_html( $disabled_text ); ?>
                    </span>
                <?php endif; ?>
            </a>
            <?php if ( !empty( $args['label'] ) ) : ?>
                <label><?php echo esc_html( $args['label'] ); ?></label>
            <?php endif; ?>
        </div>
        <?php
    }

    public function registration_enabled_display( $section ) {
        ?>
        <div id="evge-single-setting-<?php echo esc_attr( $section['id'] ); ?>" class="evge-registration-enabled-wrap evge-single-section">
            <div class="evge-flex evge-flex-center">
                <?php 
                $this->toggle_setting_display( array(
                    'name' => 'evge_allow_registration',
                    'value' => $section['settings'][0]['value'],
                    'enabled_text' => __( 'Enabled', 'event-genius' ),
                    'disabled_text' => __( 'Disabled', 'event-genius' ),
                    'enabled_aria' => __( 'Registration is enabled', 'event-genius' ),
                    'disabled_aria' => __( 'Registration is disabled', 'event-genius' ),
                    'label' => __( 'Enabled', 'event-genius' ),
                    'wrapper_class' => 'evge-registration-toggle'
                ) );
                ?>
                
                <div class="evge-registration-default-setting">
                    <label>
                        <input type="checkbox" name="evge_set_registration_default" value="1">
                        <?php esc_html_e( 'Apply this setting to all new events', 'event-genius' ); ?>
                    </label>
                </div>
            </div>
        </div>
        <?php
    }



    public function radio_group_display( $section ) {
        ?>
        <div id="evge-single-setting-<?php echo esc_attr( $section['id'] ); ?>" class="evge-single-setting-wrap">
            <div class="evge-single-setting-label">
                <label class="evge-main-setting-label">
                    <?php echo esc_html( $section['label'] ); ?>
                </label>
            </div>
            <div class="evge-radio-group">
                <?php foreach ( $section['options'] as $value => $label ) : ?>
                    <label>
                        <input type="radio" 
                               name="evge_<?php echo esc_attr( $section['settings'][0]['key'] ); ?>" 
                               value="<?php echo esc_attr( $value ); ?>"
                               <?php checked( $value, $section['settings'][0]['value'] ); ?>>
                        <?php echo esc_html( $label ); ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    // Add this method to handle event deletion
    public function delete_event_timing($post_id) {
        $this->db->delete_event_timing($post_id);
    }

    // Update the save_post() method to call sync_event_timing()
    protected function sync_event_timing($post_id, $start_date, $end_date, $timezone) {
        $this->db->sync_event_timing($post_id, $start_date, $end_date, $timezone);
    }

    /**
     * Handle updates to event meta that affect timing
     */
    public function handle_meta_update($meta_id, $post_id, $meta_key, $meta_value) {
        // List of meta keys that affect timing
        $timing_meta_keys = array(
            'evge_start_date',
            'evge_end_date',
            'evge_timezone',
            'evge_all_day'
        );

        // Only proceed if this is a timing-related meta key
        if (!in_array($meta_key, $timing_meta_keys)) {
            return;
        }

        // Get all necessary meta values
        $start_date_str = get_post_meta($post_id, 'evge_start_date', true);
        $end_date_str = get_post_meta($post_id, 'evge_end_date', true);
        $timezone = get_post_meta($post_id, 'evge_timezone', true);
        $all_day = get_post_meta($post_id, 'evge_all_day', true);

        // Validate the data
        if (!$this->validate_event_timing($start_date_str, $end_date_str, $timezone)) {
            return;
        }

        // Use default timezone if not set
        $timezone = empty($timezone) || $timezone === 'default' ? wp_timezone_string() : $timezone;

        try {
            // Create DateTime objects
            $start_date = new EvgeDateTime(
                new \DateTime($start_date_str, DateFormatter::timezone_object($timezone))
            );
            $end_date = new EvgeDateTime(
                new \DateTime($end_date_str, DateFormatter::timezone_object($timezone))
            );

            // Handle all-day events
            if ($all_day) {
                $start_date->set_time(0, 0);
                $end_date->set_time(23, 59);
            }

            // Sync the timing data
            $this->sync_event_timing($post_id, $start_date, $end_date, $timezone);

        } catch (\Exception $e) {
            // Log error or handle invalid dates
            
        }
    }

    /**
     * Validate event timing data
     */
    protected function validate_event_timing($start_date, $end_date, $timezone) {
        // Check if required values are present
        if (empty($start_date) || empty($end_date)) {
            return false;
        }

        // Validate timezone if provided
        if (!empty($timezone) && $timezone !== 'default') {
            try {
                new \DateTimeZone($timezone);
            } catch (\Exception $e) {
                return false;
            }
        }

        try {
            // Validate date formats
            $start = new \DateTime($start_date);
            $end = new \DateTime($end_date);

            // Ensure end date is after start date
            if ($end < $start) {
                return false;
            }

        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    protected function queue_series_update($post_id, $changes) {
        $db = new \WPEventGenius\Common\Database();
        $repository = new \WPEventGenius\Common\Series\EventSeriesRepository($db);
        $queue = new \WPEventGenius\Common\Series\Queue\SeriesQueue($db);
        
        $series_id = $repository->get_series_id_for_template($post_id);
        if (!$series_id) {
            return;
        }

        // Queue the update task
        $queue->enqueue_update(array(
            'series_id' => $series_id,
            'changes' => $changes,
            'template_id' => $post_id
        ));
    }

    /**
     * Handle recurrence series management
     * 
     * @param int $post_id The post ID
     * @param array $changes Array of detected changes (or null if first time)
     */
    protected function manage_recurrence_series($post_id, $recurrence_type, $pattern_data, $changes = null) {
        $repository = new \WPEventGenius\Common\Series\EventSeriesRepository(new \WPEventGenius\Common\Database());
        $series_id = $repository->get_series_id_for_template($post_id);
        
        // Skip recurrence management if this event was added to a series via series settings
        // (not as a template event for recurrence)
        $added_via_series_settings = get_post_meta($post_id, 'evge_series_added_via_setting', true);
        if ($added_via_series_settings) {
            return;
        }
        
        // Limit recurrence end date to maximum of 1 year from start date
        if (isset($pattern_data['start_date']) && isset($pattern_data['end_date'])) {
            $start_date = new \DateTime($pattern_data['start_date']);
            $end_date = new \DateTime($pattern_data['end_date']);
            $max_end_date = clone $start_date;
            $max_end_date->modify('+1 year');
            
            // If end date is more than a year after start date, adjust it
            if ($end_date > $max_end_date) {
                $pattern_data['end_date'] = $max_end_date->format('Y-m-d H:i:s');
            }
        }
        
        if ($recurrence_type === 'none') {
            if ($series_id) {
                $this->delete_series_and_events($series_id, $post_id);
            }
            delete_post_meta($post_id, 'evge_series_created');
            return;
        }

	    // No existing series - create new one
        if (!$series_id) {
            $this->create_new_series($post_id, $pattern_data);
            return;
        }

        // No existing events in series - reset and recreate with same series
        if ($series_id && empty($repository->get_series_events($series_id))) {
            $this->reset_series_and_recreate($series_id, $post_id, $pattern_data);
            return;
        }


        // Existing series - check for changes
        $schedule_changed = isset($changes['recurrence_type']) ||
                            isset($changes['recurrence_end_date']);

        if ($schedule_changed) {
            // Reset existing events and recreate with new schedule using same series
            $this->reset_series_and_recreate($series_id, $post_id, $pattern_data);
        } else {
            // Update existing events
            $this->queue_series_update($post_id, $changes);
        }
    }

    /**
     * Delete a series and all its events
     */
    protected function delete_series_and_events($series_id, $template_id) {
        $db = new \WPEventGenius\Common\Database();
        $repository = new \WPEventGenius\Common\Series\EventSeriesRepository($db);
        
        // Delete series
        $repository->delete_series($series_id, $template_id);
    }

    /**
     * Reset a series and recreate events using the same series post
     */
    protected function reset_series_and_recreate($series_id, $template_id, $pattern_data) {
        $db = new \WPEventGenius\Common\Database();
        $repository = new \WPEventGenius\Common\Series\EventSeriesRepository($db);
        
        // Reset the series (preserves the series post, clears events)
        $repository->reset_series($series_id, $template_id);
        
        // Recreate events using the same series ID
        $repository->recreate_series_events($series_id, $template_id, $pattern_data);
    }

    /**
     * Create a new series from the template event
     */
    protected function create_new_series($post_id, $pattern_data) {
        $db = new \WPEventGenius\Common\Database();
        $queue = new \WPEventGenius\Common\Series\Queue\SeriesQueue($db);

        // Create series data
        $series_data = array(
            'title' => get_the_title($post_id) . ' Series',
            'meta' => array(
                'template_event_id' => $post_id
            )
        );

        // Create pattern object
        $pattern = new \WPEventGenius\Common\Series\RecurrencePattern($pattern_data);

        // Queue series creation
        $queue->enqueue(array(
            'type' => SeriesQueue::TASK_TYPE_CREATE,
            'series_data' => $series_data,
            'pattern' => $pattern,  // Pass the pattern object, not the array
            'template_id' => $post_id
        ));

        // Mark this event as having a series queued
        update_post_meta($post_id, 'evge_series_created', true);
    }

    /**
     * Compare two values and return their differences
     * If dates, includes the time difference
     * 
     * @param mixed $from Original value
     * @param mixed $to New value
     * @return array|null Array of differences or null if no change
     */
    protected function get_value_difference($from, $to) {
        // Return null if values are the same
        if ($from === $to) {
            return null;
        }

        // Try to parse as dates
        try {
            $from_date = new \DateTime($from);
            $to_date = new \DateTime($to);
            
            // If both are valid dates, return with time difference
            return array(
                'from' => $from,
                'to' => $to,
                'difference' => $to_date->getTimestamp() - $from_date->getTimestamp()
            );
        } catch (\Exception $e) {
            // Not dates, return simple from/to comparison
            return array(
                'from' => $from,
                'to' => $to
            );
        }
    }

    /**
     * Get the timezone for an event
     * 
     * @param int $post_id The post ID (defaults to current post)
     * @return string The timezone string
     */
    protected function get_event_timezone($post_id = null) {
        if (empty($post_id)) {
            $post_id = get_the_ID();
        }
        
        $timezone = get_post_meta($post_id, 'evge_timezone', true);
        
        if (empty($timezone) || $timezone === 'default') {
            $timezone = wp_timezone_string();
        }
        
        return $timezone;
    }


    public function add_recurring_warning_content() {
        ?>
            <div class="evge-recurrence-modal-content-inner">
            <p><?php esc_html_e('Any changes made to this event will be applied to all recurring events in the series.', 'event-genius'); ?></p>
                <p class="evge-recurring-warning-registration-text" style="display: none;">
                    <strong><?php esc_html_e('Registrations for existing recurrences will be reset.', 'event-genius'); ?></strong>
                </p>

                <button type="button" class="button button-primary evge-continue-save"><?php esc_html_e('Continue Save', 'event-genius'); ?></button>
                <button type="button" class="button button-secondary evge-modal-close"><?php esc_html_e('Cancel', 'event-genius'); ?></button>
            </div>
        <?php
    }

    /**
     * Modify the edit link for events to point to our custom management page
     *
     * @param string $link    The edit link
     * @param int    $post_id Post ID
     * @param string $context The link context
     * @return string Modified edit link
     */
    public function modify_edit_link($link, $post_id, $context) {
        if (get_post_type($post_id) !== EVGE_EVENT_POST_TYPE) {
            return $link;
        }

        // Check if this is a recurrence
        $is_recurrence = get_post_meta($post_id, 'evge_is_recurrence', true);
        
        // If it's a recurrence, we need to link to the template event with the recurrence ID
        if ($is_recurrence) {
            return admin_url(sprintf(
                'admin.php?page=evge-all-events&action=edit&post=%d&recurrence_id=%d',
                $is_recurrence,
                $post_id
            ));
        }

        // Regular event link
        return admin_url(sprintf(
            'admin.php?page=evge-all-events&action=edit&post=%d',
            $post_id
        ));
    }

    public function modify_add_new_url($url, $path) {
        if (strpos($path, 'post-new.php?post_type=' . EVGE_EVENT_POST_TYPE) !== false) {
            return admin_url('admin.php?page=evge-all-events&tab=single&action=new');
        }
        return $url;
    }
	
}
