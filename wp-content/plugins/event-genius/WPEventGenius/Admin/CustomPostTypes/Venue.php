<?php
namespace WPEventGenius\Admin\CustomPostTypes;

use WPEventGenius\Common\Utils\Icon;
use WPEventGenius\Common\Utils\EvgeDateTime;
use WPEventGenius\Common\Utils\Utils;
use WPEventGenius\Common\Services\CptSlugService;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class Venue implements CustomPostType {

	public function __construct(){

	}
	public function get_post_type() {
		return EVGE_VENUE_POST_TYPE;
	}
	public function register_taxonomies() {

	}
	public function register() {
		$labels = array(
			'name' => __( 'Venues', 'event-genius' ),
			'singular_name' => __( 'Venue', 'event-genius' ),
			'all_items' => __( 'All venues', 'event-genius' ),
			'add_new_item' => __( 'Add new venue', 'event-genius' ),
			'add_new' => __( 'New venue', 'event-genius' ),
			'new_item' => __( 'New venue', 'event-genius' ),
			'edit_item' => __( 'Edit venue', 'event-genius' ),
			'view_item' => __( 'View venue', 'event-genius' ),
			'search_items' => __( 'Search venues', 'event-genius' ),
			'not_found' => __( 'No venues found', 'event-genius' ),
			'not_found_in_trash' => __( 'No venues found in trash', 'event-genius' )
		);
		$args = array(
			'labels' => $labels,
			'menu_icon' => 'dashicons-list-alt',
			'public' => true,
			'can_export' => true,
			'has_archive' => CptSlugService::get_slug( 'venue_archive_slug' ),
			'show_ui' => true,
			'show_in_menu'         => false,
			'show_in_nav_menus'    => false,
			'archive_in_nav_menus' => false,
			'show_in_rest' => true,
			'show_in_admin_bar' => true,
			'capability_type' => array('evge_venue', 'evge_venues'),
			'map_meta_cap' => true,
			'taxonomies' => array(),
			'rewrite' => array( 'slug' => CptSlugService::get_slug( 'venue_slug' ) ),
			'supports' => array( 'title', 'thumbnail', 'page-attributes', 'editor' )
		);
		register_post_type( EVGE_VENUE_POST_TYPE, $args );
	}

	public function add_meta_boxes() {
		add_meta_box(
			'evge-venue-metabox',
			esc_html__( 'WP Event Genius', 'event-genius' ),
			array( $this, 'meta_box_display' ),
			EVGE_VENUE_POST_TYPE,
			'normal',
			'high'
		);
	}

	public function meta_box_display() {

		wp_nonce_field( 'evge_save_post', 'evge_save_post' );

		foreach ( $this->sections() as $section ) {
            echo '<div class="evge-single-section">';
            echo '<h4>' . esc_html( $section['title'] ) . '</h4>';
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
			'location' => array(
				'title' => __('Location', 'event-genius'),
				'subsections' => array(
					'address' => array(
						'priority' => 10,
						'id' => 'address_details',
						'type' => 'address_fields',
						'callback' => 'address_fields_display',
						'settings' => array(
							'address_1' => array(
								'key' => 'address_1',
								'label' => __('Address Line 1', 'event-genius'),
								'placeholder' => __('123 Main St', 'event-genius'),
								'default' => '',
								'sanitization' => 'text'
							),
							'address_2' => array(
								'key' => 'address_2',
								'label' => __('Address Line 2', 'event-genius'),
								'placeholder' => __('Suite 500', 'event-genius'),
								'default' => '',
								'sanitization' => 'text'
							),
							'city' => array(
								'key' => 'city',
								'label' => __('City', 'event-genius'),
								'default' => '',
								'sanitization' => 'text'
							),
							'state' => array(
								'key' => 'state',
								'label' => __('State/Province', 'event-genius'),
								'default' => '',
								'sanitization' => 'text'
							),
							'postal_code' => array(
								'key' => 'postal_code',
								'label' => __('Zip Code', 'event-genius'),
								'default' => '',
								'sanitization' => 'text'
							),
							'country' => array(
								'key' => 'country',
								'label' => __('Country', 'event-genius'),
								'default' => '',
								'sanitization' => 'text',
								'tooltip' => __('Select the country where this venue is located.', 'event-genius')
							),
							'map_url' => array(
								'key' => 'map_url',
								'label' => __('Interactive Map URL', 'event-genius'),
								'placeholder' => '<iframe src="https://www.google.com/maps/embed?pb=!1m...',
								'default' => '',
								'sanitization' => 'url',
								'tooltip' => __('Provide a link to an interactive map for directions.', 'event-genius')
							)
						)
					),
				)
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
								'tooltip' => __('Enter the venue\'s primary contact number.', 'event-genius')
							),
							'website' => array(
								'key' => 'website',
								'label' => __('Website', 'event-genius'),
								'placeholder' => __('https://example.com', 'event-genius'),
								'default' => '',
								'sanitization' => 'url',
								'tooltip' => __('Enter the official website of the venue.', 'event-genius')
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

		return apply_filters('evge_venue_sections', $sections);
	}

	public function address_fields_display($section) {
		?>
		<div id="evge-single-setting-<?php echo esc_attr($section['id']); ?>" class="evge-single-setting-wrap">
			<!-- Address Lines -->
			<?php foreach (['address_1', 'address_2'] as $address_field) : 
				$setting = $section['settings'][$address_field];
			?>
				<div class="evge-settings-grid">
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
								   placeholder="<?php echo esc_attr($setting['placeholder']); ?>">
						</div>
					</div>
				</div>
			<?php endforeach; ?>

			<!-- City, State, Zip Group -->
			<div class="evge-settings-grid evge-settings-grid--3-columns">
				<?php foreach (['city', 'state', 'postal_code'] as $field) : 
					$setting = $section['settings'][$field];
				?>
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
								   value="<?php echo esc_attr($setting['value']); ?>">
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<!-- Country -->
			<div class="evge-settings-grid">
				<div class="evge-setting-field">
					<div class="evge-single-setting-label">
						<label for="evge_country" class="evge-main-setting-label">
							<?php echo esc_html($section['settings']['country']['label']); ?>
						</label>
						<?php if (!empty($section['settings']['country']['tooltip'])) : ?>
							<div class="evge-tooltip-wrap">
								<a href="javascript:void(0);" class="evge-tooltip-link">
									<?php 
									// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									echo Icon::get('tooltip'); ?>
								</a>
								<div class="evge-tooltip evge-shadow">
									<p><?php echo esc_html($section['settings']['country']['tooltip']); ?></p>
								</div>
							</div>
						<?php endif; ?>
					</div>
					<div>
						<input type="text" 
							   id="evge_country" 
							   name="evge_country" 
							   value="<?php echo esc_attr($section['settings']['country']['value']); ?>">
					</div>
				</div>
			</div>

			<!-- Map URL -->
			<div class="evge-settings-grid">
				<div class="evge-setting-field">
					<div class="evge-single-setting-label">
						<label for="evge_map_url" class="evge-main-setting-label">
							<?php echo esc_html($section['settings']['map_url']['label']); ?>
						</label>
						<?php if (!empty($section['settings']['map_url']['tooltip'])) : ?>
							<div class="evge-tooltip-wrap">
								<a href="javascript:void(0);" class="evge-tooltip-link">
									<?php 
									// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									echo Icon::get('tooltip'); ?>
								</a>
								<div class="evge-tooltip evge-shadow">
									<p><?php echo esc_html($section['settings']['map_url']['tooltip']); ?></p>
								</div>
							</div>
						<?php endif; ?>
					</div>
					<div>
						<input type="text" 
							   id="evge_map_url" 
							   name="evge_map_url" 
							   value="<?php echo esc_attr($section['settings']['map_url']['value']); ?>"
							   placeholder="<?php echo esc_attr($section['settings']['map_url']['placeholder']); ?>">
					</div>
				</div>
			</div>
		</div>
		<?php
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
							<input type="<?php echo $setting['key'] === 'website' ? 'url' : 'text'; ?>"
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
			<?php if (!empty($section['tooltip'])) : ?>
				<div class="evge-section-tooltip">
					<div class="evge-tooltip-wrap">
						<a href="javascript:void(0);" class="evge-tooltip-link">
							<?php 
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo Icon::get('tooltip'); ?>
						</a>
						<div class="evge-tooltip evge-shadow">
							<p><?php echo esc_html($section['tooltip']); ?></p>
						</div>
					</div>
				</div>
			<?php endif; ?>
			
			<!-- Facebook, Instagram -->
			<div class="evge-settings-grid evge-settings-grid--2-columns">
				<?php foreach (['facebook', 'instagram'] as $social) : 
					$setting = $section['settings'][$social];
				?>
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

			<!-- Twitter, LinkedIn -->
			<div class="evge-settings-grid evge-settings-grid--2-columns">
				<?php foreach (['twitter', 'linkedin'] as $social) : 
					$setting = $section['settings'][$social];
				?>
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

			<!-- YouTube -->
			<div class="evge-settings-grid evge-settings-grid--2-columns">
				<?php $setting = $section['settings']['youtube']; ?>
				<div class="evge-setting-field">
					<div class="evge-single-setting-label">
						<label for="evge_youtube" class="evge-main-setting-label">
							<?php echo esc_html($setting['label']); ?>
						</label>
					</div>
					<div>
						<input type="text"
							   id="evge_youtube"
							   name="evge_youtube"
							   value="<?php echo esc_attr($setting['value']); ?>"
							   placeholder="Channel name, URL, or channel ID">
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	protected function get_countries() {
		return array(
			'US' => __('United States', 'event-genius'),
			'CA' => __('Canada', 'event-genius'),
			'GB' => __('United Kingdom', 'event-genius'),
			// Add more countries as needed
		);
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

	public function save_post( $post_id ) {
		if ( ! isset( $_POST['evge_save_post'] ) ) {
			return;
		}
		$nonce = sanitize_text_field(wp_unslash($_POST['evge_save_post']));
		if ( ! wp_verify_nonce( $nonce, 'evge_save_post' ) ) {
			return;
		}
		
		// Location fields
		if ( isset( $_POST['evge_address_1'] ) ) {
			update_post_meta( $post_id, 'evge_address_1', sanitize_text_field( wp_unslash( $_POST['evge_address_1'] ) ) );
		}
		if ( isset( $_POST['evge_address_2'] ) ) {
			update_post_meta( $post_id, 'evge_address_2', sanitize_text_field( wp_unslash( $_POST['evge_address_2'] ) ) );
		}
		if ( isset( $_POST['evge_city'] ) ) {
			update_post_meta( $post_id, 'evge_city', sanitize_text_field( wp_unslash( $_POST['evge_city'] ) ) );
		}
		if ( isset( $_POST['evge_state'] ) ) {
			update_post_meta( $post_id, 'evge_state', sanitize_text_field( wp_unslash( $_POST['evge_state'] ) ) );
		}
		if ( isset( $_POST['evge_postal_code'] ) ) {
			update_post_meta( $post_id, 'evge_postal_code', sanitize_text_field( wp_unslash( $_POST['evge_postal_code'] ) ) );
		}
		if ( isset( $_POST['evge_country'] ) ) {
			update_post_meta( $post_id, 'evge_country', sanitize_text_field( wp_unslash( $_POST['evge_country'] ) ) );
		}
		if ( isset( $_POST['evge_map_url'] ) ) {
			$raw_url = Utils::parse_iframe_src( wp_unslash( $_POST['evge_map_url'] ) );
			update_post_meta( $post_id, 'evge_map_url', esc_url_raw( $raw_url ) );
		}
		
		// Contact fields
		if ( isset( $_POST['evge_phone'] ) ) {
			update_post_meta( $post_id, 'evge_phone', sanitize_text_field( wp_unslash( $_POST['evge_phone'] ) ) );
		}
		if ( isset( $_POST['evge_website'] ) ) {
			update_post_meta( $post_id, 'evge_website', esc_url_raw( wp_unslash( $_POST['evge_website'] ) ) );
		}
		
		// Social media fields
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
		if ( get_post_type() !== EVGE_VENUE_POST_TYPE ) {
			return;
		}
		EVGE()->style_service()->enqueue_style( 'evge_custom_post_type_settings' );
        EVGE()->script_service()->enqueue_script( 'evge_admin_common' );
		EVGE()->script_service()->enqueue_script( 'evge_post_editor_common' );

	}

}