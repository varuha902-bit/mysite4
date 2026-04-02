<?php
namespace WPEventGenius\Admin\CustomPostTypes;

use WPEventGenius\Common\Utils\Icon;
use WPEventGenius\Common\Utils\EvgeDateTime;
use WPEventGenius\Common\Services\CptSlugService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Organizer implements CustomPostType {

	public function __construct(){

	}
	public function get_post_type() {
		return EVGE_ORGANIZER_POST_TYPE;
	}

    public function register_taxonomies() {

    }
	public function register() {
		$labels = array(
			'name' => __( 'Organizers', 'event-genius' ),
			'singular_name' => __( 'Organizer', 'event-genius' ),
			'all_items' => __( 'All organizers', 'event-genius' ),
			'add_new_item' => __( 'Add new organizer', 'event-genius' ),
			'add_new' => __( 'New organizer', 'event-genius' ),
			'new_item' => __( 'New organizer', 'event-genius' ),
			'edit_item' => __( 'Edit organizer', 'event-genius' ),
			'view_item' => __( 'View organizer', 'event-genius' ),
			'search_items' => __( 'Search organizers', 'event-genius' ),
			'not_found' => __( 'No organizers found', 'event-genius' ),
			'not_found_in_trash' => __( 'No organizers found in trash', 'event-genius' )
		);
		$args = array(
			'labels' => $labels,
			'menu_icon' => 'dashicons-list-alt',
			'public' => true,
			'can_export' => true,
			'has_archive' => CptSlugService::get_slug( 'organizer_archive_slug' ),
			'show_ui' => true,
			'show_in_menu'         => false,
			'show_in_nav_menus'    => false,
			'archive_in_nav_menus' => false,
			'show_in_rest' => true,
			'show_in_admin_bar' => true,
			'capability_type' => array('evge_organizer', 'evge_organizers'),
			'map_meta_cap' => true,
			'taxonomies' => array(),
			'rewrite' => array( 'slug' => CptSlugService::get_slug( 'organizer_slug' ) ),
			'supports' => array( 'title', 'thumbnail', 'page-attributes', 'editor' )
		);
		register_post_type( EVGE_ORGANIZER_POST_TYPE, $args );
	}

	public function add_meta_boxes() {
		add_meta_box(
			'evge-name-metabox',
			esc_html__( 'Organizers', 'event-genius' ),
			array( $this, 'meta_box_display' ),
			EVGE_ORGANIZER_POST_TYPE,
			'normal',
			'high'
		);
	}

	public function meta_box_display() {

		wp_nonce_field( 'evge_save_post', 'evge_save_post' );

		foreach ( $this->sections() as $section ) {
            echo '<div class="evge-single-section">';
			if( !empty( $section['title'] ) ) :
           		echo '<h4>' . esc_html( $section['title'] ) . '</h4>';
			endif;
            foreach ( $section['subsections'] as $subsection ) {
                if ( ! empty( $subsection['callback'] ) ) {
                    call_user_func( array( $this, $subsection['callback'] ), $subsection );
                }
            }
			echo '</div>';

		}
	}

	public function sections() {
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
						'description' => __( 'A short description of the organizer. Appears in event pages.', 'event-genius' ),
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
			'contact' => array(
				'title' => __('Contact Information', 'event-genius'),
				'subsections' => array(
					'contact_details' => array(
						'priority' => 20,
						'id' => 'contact_details',
						'type' => 'contact_fields',
						'callback' => 'contact_fields_display',
						'settings' => array(
							'phone' => array(
								'key' => 'phone',
								'label' => __('Phone Number', 'event-genius'),
								'placeholder' => __('(555) 123-4567', 'event-genius'),
								'default' => '',
								'sanitization' => 'text',
								'tooltip' => __('Enter the organizer\'s primary contact number.', 'event-genius')
							),
							'email' => array(
								'key' => 'email',
								'label' => __('Email', 'event-genius'),
								'placeholder' => __('contact@example.com', 'event-genius'),
								'default' => '',
								'sanitization' => 'email',
								'tooltip' => __('Enter the organizer\'s primary email address.', 'event-genius')
							),
							'website' => array(
								'key' => 'website',
								'label' => __('Website', 'event-genius'),
								'placeholder' => __('https://example.com', 'event-genius'),
								'default' => '',
								'sanitization' => 'url',
								'tooltip' => __('Enter the official website of the organizer.', 'event-genius')
							)
						)
					)
				)
			),
			'social' => array(
				'title' => __('Social Media Links', 'event-genius'),
				'subsections' => array(
					'social_links' => array(
						'priority' => 30,
						'id' => 'social_links',
						'type' => 'social_fields',
						'callback' => 'social_fields_display',
						'settings' => array(
							'facebook' => array(
								'key' => 'facebook',
								'label' => __('Facebook', 'event-genius'),
								'default' => '',
								'sanitization' => 'url'
							),
							'twitter' => array(
								'key' => 'twitter',
								'label' => __('Twitter/X', 'event-genius'),
								'default' => '',
								'sanitization' => 'url'
							),
							'instagram' => array(
								'key' => 'instagram',
								'label' => __('Instagram', 'event-genius'),
								'default' => '',
								'sanitization' => 'url'
							),
							'linkedin' => array(
								'key' => 'linkedin',
								'label' => __('LinkedIn', 'event-genius'),
								'default' => '',
								'sanitization' => 'url'
							),
							'youtube' => array(
								'key' => 'youtube',
								'label' => __('YouTube', 'event-genius'),
								'default' => '',
								'sanitization' => 'url'
							)
						)
					)
				)
			)
		);

		foreach ($sections as $key => $section) {
			foreach ($section['subsections'] as $subsection_key => $subsection) {
				$sections[$key]['subsections'][$subsection_key]['settings'] = 
					$this->assign_values($subsection['settings']);
			}
		}

		return apply_filters('evge_organizer_sections', $sections);
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
                <label for="evge-<?php echo esc_attr( $section['settings'][0]['key'] ); ?>"><?php echo esc_html( $section['label'] ); ?></label>
                <?php if ( ! empty( $section['description'] ) ) : ?>
                    <div class="evge-tooltip-wrap">
                        <a href="javascript:void(0);" class="evge-tooltip-link">
                            <?php 
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo Icon::get( 'tooltip' ); ?>
                        </a>
                        <div class="evge-tooltip evge-shadow">
                            <p><?php echo esc_html( $section['description'] ); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="evge-single-wrap">
                <textarea id="evge-<?php echo esc_attr( $section['settings'][0]['key'] ); ?>" name="evge_<?php echo esc_attr( $section['settings'][0]['key'] ); ?>"><?php echo esc_textarea( $section['settings'][0]['value'] ); ?></textarea>
            </div>

        </div>
        <?php
	}
	public function text( $section ) {

		?>
        <div id="evge-single-setting-<?php echo esc_attr( $section['id'] ); ?>" class="evge-single-setting-wrap">
            <div class="evge-single-setting-label">
                <label for="evge-<?php echo esc_attr( $section['settings'][0]['key'] ); ?>"><?php echo esc_html( $section['label'] ); ?></label>
            </div>
            <div class="evge-single-wrap">
                <input type="text" id="evge-<?php echo esc_attr( $section['settings'][0]['key'] ); ?>" name="evge_<?php echo esc_attr( $section['settings'][0]['key'] ); ?>" value="<?php echo esc_attr( $section['settings'][0]['value'] ); ?>"></input>
            </div>

        </div>
		<?php
	}

	public function details_input_group( $section ) {
        global $post;
		?>
        <div id="evge-single-setting-<?php echo esc_attr( $section['id'] ); ?>" class="evge-single-setting-wrap">
			<?php foreach ( $section['settings'] as $single_setting ) : ?>
                <div class="evge-single-setting-label">
                    <label for="evge-<?php echo esc_attr( $single_setting['key'] ); ?>"><?php echo esc_html( $single_setting['label'] ); ?></label>
                </div>
                <?php if ( $single_setting['key'] === 'links' ) : ?>
                    <div class="evge-single-wrap evge-settings-list">
                        <?php foreach ( $single_setting['sub_settings'] as $sub_setting ) :
	                        $post_meta = array();
	                        if ( ! empty( $post ) ) {
		                        $post_meta = get_post_meta( $post->ID );
	                        }
                            $value = ! empty( $post_meta[ 'evge_' . $sub_setting['key'] ] ) ? $post_meta[ 'evge_' . $sub_setting['key'] ][0] : '';
                            ?>
                            <div class="evge-settings-list-item">
                                <label for="evge-link-<?php echo esc_attr( $sub_setting['key'] ); ?>"><?php echo esc_html( $sub_setting['label'] ); ?></label>
                                <input type="text" id="evge-link-<?php echo esc_attr( $sub_setting['key'] ); ?>" name="evge_<?php echo esc_attr( $sub_setting['key'] ); ?>" value="<?php echo esc_attr( $value ); ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>

                    <div class="evge-single-wrap">
                        <input type="text" id="evge-<?php echo esc_attr( $single_setting['key'] ); ?>" name="evge_<?php echo esc_attr( $single_setting['key'] ); ?>" value="<?php echo esc_attr( $single_setting['value'] ); ?>">
                    </div>
            <?php endif; ?>
			<?php endforeach; ?>
        </div>
		<?php
	}

	public function save_post( $post_id ) {
		if ( ! isset( $_POST['evge_save_post'] ) ) {
			return;
		}
		$nonce = sanitize_text_field(wp_unslash($_POST['evge_save_post']));
		if ( ! wp_verify_nonce( $nonce, 'evge_save_post' ) ) {
            return;
        }
		if ( isset( $_POST['evge_summary'] ) ) {
			update_post_meta( $post_id, 'evge_summary', sanitize_textarea_field( wp_unslash( $_POST['evge_summary'] ) ) );
		}
		if ( isset( $_POST['evge_email'] ) ) {
			update_post_meta( $post_id, 'evge_email', sanitize_email( wp_unslash( $_POST['evge_email'] ) ) );
		}
		if ( isset( $_POST['evge_phone'] ) ) {
			update_post_meta( $post_id, 'evge_phone', sanitize_text_field( wp_unslash( $_POST['evge_phone'] ) ) );
		}
		if ( isset( $_POST['evge_website'] ) ) {
			update_post_meta( $post_id, 'evge_website', sanitize_text_field( wp_unslash( $_POST['evge_website'] ) ) );
		}
		if ( isset( $_POST['evge_facebook'] ) ) {
			update_post_meta( $post_id, 'evge_facebook', sanitize_text_field( wp_unslash( $_POST['evge_facebook'] ) ) );
		}
		if ( isset( $_POST['evge_twitter'] ) ) {
			update_post_meta( $post_id, 'evge_twitter', sanitize_text_field( wp_unslash( $_POST['evge_twitter'] ) ) );
		}
		if ( isset( $_POST['evge_instagram'] ) ) {
			update_post_meta( $post_id, 'evge_instagram', sanitize_text_field( wp_unslash( $_POST['evge_instagram'] ) ) );
		}
		if ( isset( $_POST['evge_linkedin'] ) ) {
			update_post_meta( $post_id, 'evge_linkedin', sanitize_text_field( wp_unslash( $_POST['evge_linkedin'] ) ) );
		}
		if ( isset( $_POST['evge_youtube'] ) ) {
			update_post_meta( $post_id, 'evge_youtube', sanitize_text_field( wp_unslash( $_POST['evge_youtube'] ) ) );
		}


	}

	public function enqueue( $screen ) {
        if ( get_post_type() !== EVGE_ORGANIZER_POST_TYPE ) {
            return;
        }
		EVGE()->style_service()->enqueue_style( 'evge_custom_post_type_settings' );
        EVGE()->script_service()->enqueue_script( 'evge_admin_common' );
        EVGE()->script_service()->enqueue_script( 'evge_post_editor_common' );
	}

	public function contact_fields_display($section) {
		?>
		<div id="evge-single-setting-<?php echo esc_attr($section['id']); ?>" class="evge-single-setting-wrap">
			<div class="evge-settings-grid evge-settings-grid--2-columns">
				<?php foreach ($section['settings'] as $setting) : ?>
					<div class="evge-setting-field">
						<div class="evge-single-setting-label">
							<label for="evge_<?php echo esc_attr($setting['key']); ?>" class="evge-main-setting-label">
								<?php echo esc_html($setting['label']); ?>
							</label>
							<?php if (!empty($setting['tooltip'])) : ?>
								<div class="evge-tooltip-wrap">
									<a href="javascript:void(0);" class="evge-tooltip-link">
										<?php 
										// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										echo Icon::get('tooltip'); ?>
									</a>
									<div class="evge-tooltip evge-shadow">
										<p><?php echo esc_html($setting['tooltip']); ?></p>
									</div>
								</div>
							<?php endif; ?>
						</div>
						<div>
							<input type="<?php echo $setting['key'] === 'website' ? 'url' : ($setting['key'] === 'email' ? 'email' : 'text'); ?>"
								   id="evge_<?php echo esc_attr($setting['key']); ?>"
								   name="evge_<?php echo esc_attr($setting['key']); ?>"
								   value="<?php echo esc_attr($setting['value']); ?>"
								   placeholder="<?php echo esc_attr($setting['placeholder']); ?>">
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	public function social_fields_display($section) {
		?>
		<div id="evge-single-setting-<?php echo esc_attr($section['id']); ?>" class="evge-single-setting-wrap">
			<div class="evge-settings-grid evge-settings-grid--2-columns">
				<?php foreach ($section['settings'] as $setting) : ?>
					<div class="evge-setting-field">
						<div class="evge-single-setting-label">
							<label for="evge_<?php echo esc_attr($setting['key']); ?>" class="evge-main-setting-label">
								<?php echo esc_html($setting['label']); ?>
							</label>
						</div>
						<div>
							<input type="text"
								   id="evge_<?php echo esc_attr($setting['key']); ?>"
								   name="evge_<?php echo esc_attr($setting['key']); ?>"
								   value="<?php echo esc_attr($setting['value']); ?>"
								   placeholder="Username, URL, or @username">
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

}