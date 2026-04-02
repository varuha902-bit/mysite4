<?php

namespace WPEventGenius\Common\Services;

use WPEventGenius\Common\Utils\Icon;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reusable Embed Instructions Service
 * 
 * Provides a context-agnostic way to generate embed instructions modals
 * for blocks and shortcodes throughout the plugin.
 * 
 * @package WPEventGenius
 * @since 1.0.0
 */
class EmbedInstructionsService {

	/**
	 * Whether hooks have been initialized
	 * 
	 * @var bool
	 */
	private static $hooks_initialized = false;

	/**
	 * Initialize hooks (only once)
	 */
	public function init_hooks() {
		if ( self::$hooks_initialized ) {
			return;
		}
		
		add_action( 'evge_embed_instructions_after', array( $this, 'add_other_blocks_section' ), 10, 1 );
		add_filter( 'evge_single_event_sections_registration', array( $this, 'add_blocks_shortcodes_section' ), 99, 1 );
		
		// Post editor blocks notice
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_post_editor_notice_assets' ) );
		add_action( 'admin_footer', array( $this, 'maybe_render_post_editor_notice' ) );
		add_action( 'wp_ajax_evge_dismiss_post_editor_blocks_notice', array( $this, 'ajax_dismiss_post_editor_notice' ) );
		
		self::$hooks_initialized = true;
	}

	/**
	 * Constructor - auto-initialize hooks
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Generate embed button HTML
	 * 
	 * This is a generic helper method that can be used to add embed buttons
	 * to any modal throughout the plugin.
	 * 
	 * @param array $args {
	 *     Optional. Configuration for the embed button.
	 * 
	 *     @type string $ajax_action The AJAX action name for the embed instructions handler
	 *     @type string $modal_width  The width of the embed instructions modal ('narrow', 'medium', 'max', etc.)
	 *     @type string $button_class Additional CSS classes for the button
	 *     @type string $button_title The title/tooltip text for the button
	 * }
	 * @return string The HTML for the embed button
	 */
	public static function get_embed_button_html( $args = array() ) {
		$defaults = array(
			'ajax_action' => '',
			'modal_width' => 'medium',
			'button_class' => '',
			'button_title' => __( 'Embed Instructions', 'event-genius' ),
		);

		$args = wp_parse_args( $args, $defaults );

		if ( empty( $args['ajax_action'] ) ) {
			return '';
		}

		// Create the JSON array for the embed instructions modal
		$embed_json_array = array(
			'action' => $args['ajax_action'],
		);

		$button_classes = array(
			'evge-embed-button',
			'evge-admin-button',
			'button',
			'evge-admin-secondary-button',
			'evge-modal-trigger',
		);

		if ( ! empty( $args['button_class'] ) ) {
			$button_classes[] = $args['button_class'];
		}

		$modal_settings = array(
			'width' => $args['modal_width'],
		);

		ob_start();
		?>
		<button class="<?php echo esc_attr( implode( ' ', $button_classes ) ); ?>" 
				data-evge-modal-content="ajax" 
				data-evge-ajax="<?php echo esc_attr( wp_json_encode( $embed_json_array ) ); ?>" 
				data-evge-modal-settings="<?php echo esc_attr( wp_json_encode( $modal_settings ) ); ?>"
				title="<?php echo esc_attr( $args['button_title'] ); ?>">
			<span class="evge-icon-text">
				<?php 
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo \WPEventGenius\Common\Utils\Icon::get( 'code' ); 
				?>
				<?php esc_html_e( 'Embed', 'event-genius' ); ?>
			</span>
		</button>
		<?php
		return ob_get_clean();
	}

	/**
	 * Check if Classic Editor plugin is active
	 * 
	 * @return bool True if Classic Editor is active
	 */
	private function is_classic_editor_active() {
		return defined( 'CLASSIC_EDITOR_VERSION' ) || class_exists( 'Classic_Editor' );
	}

	/**
	 * Add section showing other available blocks
	 * 
	 * @param array $args The embed instructions arguments
	 */
	public function add_other_blocks_section( $args ) {
		// Don't show block information if Classic Editor is active
		if ( $this->is_classic_editor_active() ) {
			return;
		}

		// Get available blocks (excluding the current one)
		$available_blocks = $this->get_available_blocks( $args['block_name'] ?? '' );
		
		if ( empty( $available_blocks ) ) {
			return;
		}

		// Determine tier for UTM campaign
		$tier = evge_get_tier();
		$utm_campaign = 'evge-' . $tier;

		// Build documentation URL with UTM params
		$doc_url = add_query_arg(
			array(
				'utm_campaign' => $utm_campaign,
				'utm_source' => 'embed-instructions',
				'utm_medium' => 'documentation-link',
				'utm_content' => 'shortcodes-guide',
			),
			'https://wpeventgenius.com/docs/how-to-use-shortcodes-in-wp-event-genius/'
		);

		?>
		<div class="evge-embed-section evge-embed-other-blocks-section">
			<h3><?php esc_html_e( 'Other Available Blocks', 'event-genius' ); ?></h3>
			<div class="evge-other-blocks-intro">
				<p><?php esc_html_e( 'Event Genius includes several other blocks you can use like a registration form, attendee list, calendar, and more. Search for "Event Genius" in the block editor to see all available blocks.', 'event-genius' ); ?></p>
				<button type="button" class="button evge-other-blocks-show-more" aria-expanded="false">
                    <span class="evge-icon-text">
						<?php echo Icon::get( 'plus' ); ?>
                        <?php esc_html_e( 'Show More', 'event-genius' ); ?>
					</span>
				</button>
			</div>
			<div class="evge-other-blocks-content-wrapper" style="display: none;">
				<div class="evge-other-blocks-grid">
					<div class="evge-other-blocks-content">
						<ul class="evge-other-blocks-list">
							<?php foreach ( $available_blocks as $block ) : ?>
								<li class="evge-other-block-item">
									<strong><?php echo esc_html( $block['title'] ); ?></strong>
									<?php if ( ! empty( $block['description'] ) ) : ?>
										<p class="evge-other-block-description"><?php echo esc_html( $block['description'] ); ?></p>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
					<?php if ( ! empty( $args['other_blocks_example_image_path'] ) ) : ?>
						<div class="evge-other-blocks-examples">
							<img src="<?php echo esc_url( $args['other_blocks_example_image_path'] ); ?>" alt="<?php esc_attr_e( 'Block example', 'event-genius' ); ?>">
						</div>
					<?php endif; ?>
				</div>
                <p style="margin-top: 30px;">
					<?php
					printf(
						/* translators: %s: Link to shortcodes documentation */
						esc_html__( 'For more information about shortcodes and blocks, see our %s.', 'event-genius' ),
						'<a href="' . esc_url( $doc_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'shortcodes documentation', 'event-genius' ) . '</a>'
					);
					?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Get list of available blocks (excluding the current one)
	 * 
	 * @param string $current_block_name The block name to exclude
	 * @return array Array of available blocks with title and description
	 */
	private function get_available_blocks( $current_block_name = '' ) {
		$blocks = array();

		// Registration Form Block (always available)
		if ( $current_block_name !== 'wp-event-genius/registration-form' ) {
			$blocks[] = array(
				'name' => 'wp-event-genius/registration-form',
				'title' => __( 'Registration Form', 'event-genius' ),
				'description' => __( 'Display a customizable registration form for your events.', 'event-genius' ),
			);
		}

		// Attendee List Block (always available)
		if ( $current_block_name !== 'wp-event-genius/attendee-list' ) {
			$blocks[] = array(
				'name' => 'wp-event-genius/attendee-list',
				'title' => __( 'Attendee List', 'event-genius' ),
				'description' => __( 'Show a list of registered attendees for your events with customizable display options.', 'event-genius' ),
			);
		}

		// Calendar Block (always available)
		if ( $current_block_name !== 'wp-event-genius/calendar' ) {
			$blocks[] = array(
				'name' => 'wp-event-genius/calendar',
				'title' => __( 'Calendar', 'event-genius' ),
				'description' => __( 'Display a beautiful calendar of events with filtering and search capabilities.', 'event-genius' ),
			);
		}

		// My Registrations Block (Pro tier only)
		if ( function_exists( 'evge_is_pro_tier' ) && evge_is_pro_tier() ) {
			if ( $current_block_name !== 'wp-event-genius/my-registrations' ) {
				$blocks[] = array(
					'name' => 'wp-event-genius/my-registrations',
					'title' => __( 'My Registrations', 'event-genius' ),
					'description' => __( 'Display a list of events the logged-in user has registered for.', 'event-genius' ),
				);
			}
		}

		// Admin Check In Block (Standard tier only)
		if ( function_exists( 'evge_is_standard_tier' ) && evge_is_standard_tier() ) {
			if ( $current_block_name !== 'wp-event-genius/admin-check-in' ) {
				$blocks[] = array(
					'name' => 'wp-event-genius/admin-check-in',
					'title' => __( 'Check In', 'event-genius' ),
					'description' => __( 'Display an admin check-in interface for events.', 'event-genius' ),
				);
			}
		}

		return $blocks;
	}

	/**
	 * Generate embed instructions modal content
	 * 
	 * @param array $args {
	 *     Configuration for the embed instructions
	 *     
	 *     @type string $block_name The block name (e.g., 'wp-event-genius/admin-check-in')
	 *     @type string $block_title The display title for the block (e.g., 'Admin Check In')
	 *     @type string $shortcode The shortcode (e.g., '[event_genius_admin_check_in]')
	 *     @type string $shortcode_title Optional. Title for shortcode section. Defaults to 'Shortcode'
	 *     @type string $description Optional. Additional description text
	 *     @type array  $shortcode_attributes Optional. Array of shortcode attributes to show
	 *     @type string $example_image_path Optional. URL path to an example image for the block instructions
	 * }
	 * @return string HTML content for the embed instructions modal
	 */
	public function get_embed_instructions_content( $args ) {
		$defaults = array(
			'block_name' => '',
			'block_title' => '',
			'shortcode' => '',
			'shortcode_title' => __( 'Shortcode', 'event-genius' ),
			'description' => '',
			'shortcode_attributes' => array(),
			'example_image_path' => '',
		);

		$args = wp_parse_args( $args, $defaults );

		// Validate required fields
		if ( empty( $args['block_name'] ) || empty( $args['shortcode'] ) ) {
			return '';
		}

		ob_start();
		?>
        <div class="evge-modal-heading">
            <h2><?php esc_html_e( 'Embed Instructions', 'event-genius' ); ?></h2>
        </div>
		<div class="evge-embed-instructions-modal evge-modal-padding evge-modal-body">

			
			<?php if ( ! empty( $args['description'] ) ) : ?>
				<p class="evge-embed-description"><?php echo wp_kses_post( $args['description'] ); ?></p>
			<?php endif; ?>

			<!-- Block Instructions -->
			<?php if ( ! empty( $args['block_name'] ) && ! $this->is_classic_editor_active() ) : ?>
				<div class="evge-embed-section evge-embed-block-section">
					<div class="evge-block-instructions-grid">
						<div class="evge-block-instructions-content">
                            <h3><?php esc_html_e( 'Using the Block Editor', 'event-genius' ); ?></h3>
							<ol>
								<li><?php esc_html_e( 'Edit a page or post and click the + button to add a new block.', 'event-genius' ); ?></li>
								<li>
									<?php
									printf(
										/* translators: %s: Block title */
										esc_html__( 'Search for "%s" and select it.', 'event-genius' ),
										esc_html( $args['block_title'] )
									);
									?>
								</li>
								<li><?php esc_html_e( 'The block will be added to your page and will display on the front end.', 'event-genius' ); ?></li>
							</ol>
							<div class="evge-block-info">
								<strong><?php esc_html_e( 'Block Name:', 'event-genius' ); ?></strong>
								<code class="evge-block-name"><?php echo esc_html( $args['block_name'] ); ?></code>
							</div>
						</div>
						<?php if ( ! empty( $args['example_image_path'] ) ) : ?>
							<div class="evge-block-instructions-image">
								<img src="<?php echo esc_url( $args['example_image_path'] ); ?>" alt="<?php echo esc_attr( sprintf( __( 'Example of %s block', 'event-genius' ), $args['block_title'] ) ); ?>">
							</div>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>

			<!-- Shortcode Instructions -->
			<?php if ( ! empty( $args['shortcode'] ) ) : ?>
				<div class="evge-embed-section evge-embed-shortcode-section">
					<h3><?php echo esc_html( $args['shortcode_title'] ); ?></h3>
					<p><?php esc_html_e( 'Copy this shortcode and paste it into any post or page:', 'event-genius' ); ?></p>
                    <div class="evge-inline-block-wrapper">
                        <div class="evge-shortcode-container">
                            <code class="evge-shortcode" data-evge-copy-content="embed-shortcode"><?php echo esc_html( $args['shortcode'] ); ?></code>
                            <button type="button" class="button evge-copy-shortcode" 
                                    data-evge-copy-trigger="embed-shortcode">
                                <?php esc_html_e( 'Copy', 'event-genius' ); ?>
                            </button>
                        </div>
                    </div>
					<div class="evge-copy-success" style="display: none;" data-evge-copy-success="embed-shortcode">
						<?php esc_html_e( 'Shortcode copied!', 'event-genius' ); ?>
					</div>

					<?php if ( ! empty( $args['shortcode_attributes'] ) ) : ?>
						<div class="evge-shortcode-attributes">
							<h4><?php esc_html_e( 'Available Attributes:', 'event-genius' ); ?></h4>
							<ul>
								<?php foreach ( $args['shortcode_attributes'] as $attr => $description ) : ?>
									<li>
										<code><?php echo esc_html( $attr ); ?></code>
										<?php if ( is_string( $description ) ) : ?>
											<span class="evge-attr-description"><?php echo esc_html( $description ); ?></span>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			
			<?php
			/**
			 * Action hook to add custom content after embed instructions
			 * 
			 * @param array $args The embed instructions arguments
			 */
			do_action( 'evge_embed_instructions_after', $args );
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get embed instructions content via AJAX
	 * 
	 * This is a helper method that can be used in AJAX handlers.
	 * It expects the same $args structure as get_embed_instructions_content().
	 * 
	 * @param array $args Configuration for the embed instructions
	 * @return void Sends JSON response
	 */
	public function ajax_get_embed_instructions( $args ) {
		$content = $this->get_embed_instructions_content( $args );
		
		if ( empty( $content ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid embed configuration.', 'event-genius' ) ) );
			return;
		}

		wp_send_json_success( array( 'html' => $content ) );
	}

	/**
	 * Add blocks and shortcodes section to registration sections
	 * 
	 * @param array $sections The event sections array
	 * @return array Modified sections array
	 */
	public function add_blocks_shortcodes_section( $sections ) {
		$sections['blocks_shortcodes'] = array(
			'title' => __( 'Blocks and Shortcodes for this Event', 'event-genius' ),
			'subsections' => array(
				'blocks_shortcodes_info' => array(
					'priority' => 10,
					'id' => 'blocks_shortcodes_info',
					'type' => 'blocks_shortcodes',
					'callback' => array( $this, 'blocks_shortcodes_display' ),
					'label' => '',
					'description' => __( 'Use a block or shortcode to display the registration form or attendee list on another page or post.', 'event-genius' ),
					'settings' => array()
				),
			),
		);

		return $sections;
	}

	/**
	 * Display blocks and shortcodes information
	 * 
	 * @param array $section The section data
	 */
	public function blocks_shortcodes_display( $section ) {
		global $post;
		$event_id = ! empty( $post ) ? $post->ID : 0;
		$is_classic_editor = $this->is_classic_editor_active();
		
		// Get shortcodes for this event
		$registration_form_shortcode = sprintf( '[event_genius_registration_form event="%d"]', $event_id );
		$attendee_list_shortcode = sprintf( '[event_genius_attendees event="%d"]', $event_id );
		
		?>
		<div id="evge-single-setting-<?php echo esc_attr( $section['id'] ); ?>" class="evge-single-setting-wrap evge-blocks-shortcodes-wrap">
			<div class="evge-blocks-shortcodes-intro">
				<?php if ( ! empty( $section['description'] ) ) : ?>
					<?php if ( $is_classic_editor ) : ?>
						<p><?php esc_html_e( 'Use a shortcode to display the registration form or attendee list on another page or post.', 'event-genius' ); ?></p>
					<?php else : ?>
						<p><?php echo esc_html( $section['description'] ); ?></p>
					<?php endif; ?>
				<?php endif; ?>
				<button type="button" class="button evge-collapsible-toggle" 
						data-evge-collapsible-target="blocks-shortcodes-content" 
						aria-expanded="false">
					<span class="evge-icon-text">
						<?php 
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo Icon::get( 'plus' ); 
						?>
						<?php esc_html_e( 'Show More', 'event-genius' ); ?>
					</span>
				</button>
			</div>
			<div class="evge-blocks-shortcodes-content-wrapper evge-collapsible-content" 
				 data-evge-collapsible-id="blocks-shortcodes-content" 
				 style="display: none;">
				<div class="evge-blocks-shortcodes-content">
					<!-- Registration Form Section -->
					<div class="evge-blocks-shortcodes-item">
						<h4><?php esc_html_e( 'Registration Form', 'event-genius' ); ?></h4>
						<p><?php esc_html_e( 'Display the registration form for this event on any page or post.', 'event-genius' ); ?></p>
						
						<div class="evge-shortcode-example">
							<strong><?php esc_html_e( 'Shortcode:', 'event-genius' ); ?></strong>
							<div class="evge-inline-block-wrapper">
								<div class="evge-shortcode-container">
									<code class="evge-shortcode" data-evge-copy-content="registration-form-shortcode"><?php echo esc_html( $registration_form_shortcode ); ?></code>
									<button type="button" class="button evge-copy-shortcode" 
											data-evge-copy-trigger="registration-form-shortcode">
										<?php esc_html_e( 'Copy', 'event-genius' ); ?>
									</button>
								</div>
							</div>
							<div class="evge-copy-success" style="display: none;" data-evge-copy-success="registration-form-shortcode">
								<?php esc_html_e( 'Shortcode copied!', 'event-genius' ); ?>
							</div>
						</div>
						
						<?php if ( ! $is_classic_editor ) : ?>
							<div class="evge-block-example">
								<strong><?php esc_html_e( 'Block:', 'event-genius' ); ?></strong>
								<p><?php esc_html_e( 'Search for "Registration Form" in the block editor and select this event.', 'event-genius' ); ?></p>
								<code class="evge-block-name">wp-event-genius/registration-form</code>
							</div>
						<?php endif; ?>
					</div>
					
					<!-- Attendee List Section -->
					<div class="evge-blocks-shortcodes-item">
						<h4><?php esc_html_e( 'Attendee List', 'event-genius' ); ?></h4>
						<p><?php esc_html_e( 'Display a list of registered attendees for this event on any page or post.', 'event-genius' ); ?></p>
						
						<div class="evge-shortcode-example">
							<strong><?php esc_html_e( 'Shortcode:', 'event-genius' ); ?></strong>
							<div class="evge-inline-block-wrapper">
								<div class="evge-shortcode-container">
									<code class="evge-shortcode" data-evge-copy-content="attendee-list-shortcode"><?php echo esc_html( $attendee_list_shortcode ); ?></code>
									<button type="button" class="button evge-copy-shortcode" 
											data-evge-copy-trigger="attendee-list-shortcode">
										<?php esc_html_e( 'Copy', 'event-genius' ); ?>
									</button>
								</div>
							</div>
							<div class="evge-copy-success" style="display: none;" data-evge-copy-success="attendee-list-shortcode">
								<?php esc_html_e( 'Shortcode copied!', 'event-genius' ); ?>
							</div>
						</div>
						
						<?php if ( ! $is_classic_editor ) : ?>
							<div class="evge-block-example">
								<strong><?php esc_html_e( 'Block:', 'event-genius' ); ?></strong>
								<p><?php esc_html_e( 'Search for "Attendee List" in the block editor and select this event.', 'event-genius' ); ?></p>
								<code class="evge-block-name">wp-event-genius/attendee-list</code>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Check if post editor notice should be shown
	 * 
	 * @return bool
	 */
	private function should_show_post_editor_notice() {
		// Only show to users who can edit posts
		if ( ! current_user_can( 'edit_posts' ) ) {
			return false;
		}

		// Check if notice is dismissed
		$states = new \WPEventGenius\Common\States();
		$notice_state = $states->get_state( 'notices' );
		$dismissed = $notice_state['dismissed'] ?? array();
		
		if ( in_array( 'post_editor_blocks_notice', $dismissed ) ) {
			return false;
		}

		// Only show on post/page editor screens
		$screen = get_current_screen();
		if ( ! $screen ) {
			return false;
		}

		// Check if we're on post.php or post-new.php for posts or pages
		$is_post_editor = ( $screen->base === 'post' || $screen->base === 'post-new' ) 
			&& in_array( $screen->post_type, array( 'post', 'page' ), true );

		return $is_post_editor;
	}

	/**
	 * Enqueue assets for post editor notice if needed
	 * 
	 * @param string $hook Current admin page hook
	 */
	public function maybe_enqueue_post_editor_notice_assets( $hook ) {
		if ( ! $this->should_show_post_editor_notice() ) {
			return;
		}

		// Enqueue admin common styles and scripts
		EVGE()->style_service()->enqueue_style( 'evge_admin_common' );
		EVGE()->script_service()->enqueue_script( 'evge_admin_common' );

		// Localize script with data
		$is_classic_editor = $this->is_classic_editor_active();
		wp_localize_script( 'evge_admin_common', 'evgePostEditorBlocksNotice', array(
			'nonce' => wp_create_nonce( 'evge_dismiss_post_editor_blocks_notice' ),
			'isClassicEditor' => $is_classic_editor,
		) );
	}

	/**
	 * Maybe render the post editor notice
	 */
	public function maybe_render_post_editor_notice() {
		if ( ! $this->should_show_post_editor_notice() ) {
			return;
		}

		$this->render_post_editor_notice();
	}

	/**
	 * Render the post editor notice
	 */
	private function render_post_editor_notice() {
		$is_classic_editor = $this->is_classic_editor_active();
		$available_blocks = $this->get_available_blocks();
		?>
		<div id="evge-post-editor-blocks-notice" class="evge-post-editor-blocks-notice" style="display: none;">
			<div class="evge-post-editor-blocks-notice-content">
				<div class="evge-post-editor-blocks-notice-header">
					<div class="evge-post-editor-blocks-notice-icon">
						<img src="<?php echo esc_url( EVGE_PLUGIN_URL . 'assets/images/admin/genius.png' ); ?>" alt="<?php esc_attr_e( 'Event Genius', 'event-genius' ); ?>">
					</div>
					<div class="evge-post-editor-blocks-notice-text">
						<?php if ( $is_classic_editor ) : ?>
							<p><?php esc_html_e( 'Event Genius shortcodes are available to embed events, registration forms, calendars, and more.', 'event-genius' ); ?></p>
						<?php else : ?>
							<p><?php esc_html_e( 'Event Genius blocks are available to embed events, registration forms, calendars, and more. Search for "Event Genius" when adding a new block.', 'event-genius' ); ?></p>
						<?php endif; ?>
					</div>
					<button type="button" class="evge-post-editor-blocks-notice-dismiss" aria-label="<?php esc_attr_e( 'Dismiss notice', 'event-genius' ); ?>">
						<?php 
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo Icon::get( 'close' ); 
						?>
					</button>
				</div>
				<?php if ( ! $is_classic_editor && ! empty( $available_blocks ) ) : ?>
					<div class="evge-post-editor-blocks-notice-expandable">
						<div class="evge-post-editor-blocks-notice-actions">
							<button type="button" class="button evge-post-editor-blocks-notice-show-more" aria-expanded="false">
								<span class="evge-icon-text">
									<?php 
									// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									echo Icon::get( 'plus' ); 
									?>
									<?php esc_html_e( 'Show More', 'event-genius' ); ?>
								</span>
							</button>
							<button type="button" class="button evge-post-editor-blocks-notice-dismiss-btn">
								<?php esc_html_e( 'Dismiss', 'event-genius' ); ?>
							</button>
						</div>
						<div class="evge-post-editor-blocks-notice-details" style="display: none;">
							<h4><?php esc_html_e( 'Available Blocks', 'event-genius' ); ?></h4>
							<ul class="evge-post-editor-blocks-list">
								<?php foreach ( $available_blocks as $block ) : ?>
									<li>
										<strong><?php echo esc_html( $block['title'] ); ?></strong>
										<?php if ( ! empty( $block['description'] ) ) : ?>
											<span class="evge-block-description"><?php echo esc_html( $block['description'] ); ?></span>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX handler to dismiss the post editor notice
	 */
	public function ajax_dismiss_post_editor_notice() {
		check_ajax_referer( 'evge_dismiss_post_editor_blocks_notice', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'event-genius' ) ) );
			return;
		}

		$states = new \WPEventGenius\Common\States();
		$states->dismiss_notice( 'post_editor_blocks_notice' );

		wp_send_json_success();
	}

	/**
	 * Reset the post editor blocks notice (undismiss it)
	 * This can be called programmatically to reset the notice state
	 */
	public function reset_post_editor_notice() {
		$states = new \WPEventGenius\Common\States();
		$states->undismiss_notice( 'post_editor_blocks_notice' );
	}
}

