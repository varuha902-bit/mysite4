<?php

namespace WPEventGenius\Admin\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CalendarBuilderService {
	/**
	 * Initialize hooks
	 */
	public function init_hooks() {
		add_action('wp_ajax_evge_update_calendar_preview', [$this, 'handle_preview_update']);
		add_action('wp_ajax_evge_save_calendar', [$this, 'handle_ajax_save']);
		add_action('wp_ajax_evge_get_terms', [$this, 'handle_get_terms']);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
		add_action('admin_init', [$this, 'handle_save']);
		add_action('evge_calendar_edit_form', [$this, 'render_builder']);

		add_action('admin_init', [$this, 'handle_add_calendar']);
		add_action('wp_ajax_evge_get_calendar_content', [$this, 'handle_get_shortcode_content']);
		add_action('wp_ajax_evge_get_calendar_embed_instructions', [$this, 'ajax_get_calendar_embed_instructions']);
		add_action('wp_ajax_evge_delete_calendar_modal', [$this, 'handle_delete_calendar_modal']);
		add_action('admin_init', [$this, 'handle_delete_calendar']);
	}

	/**
	 * Handle AJAX preview updates
	 */
	public function handle_preview_update() {
		check_ajax_referer('evge_calendar_builder', 'security');

		if (!current_user_can('edit_evge_events')) {
			wp_send_json_error(['message' => __('Permission denied', 'event-genius')]);
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$calendar_settings = isset($_POST['calendar_settings']) ? json_decode(wp_unslash($_POST['calendar_settings']), true) : [];
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$filters = isset($_POST['calendar_settings_filters']) ? json_decode(wp_unslash($_POST['calendar_settings_filters']), true) : [];
		$calendar_settings['filters'] = $filters;
		$settings = $this->sanitize_settings($calendar_settings ?? []);
		$calendar_id = isset($_POST['calendar_id']) ? sanitize_key(wp_unslash($_POST['calendar_id'])) : 0;
		$key = $calendar_id;
		set_transient('evge_calendar_preview_' . $key, $settings, 3600);

		// Store settings temporarily in transient for preview
		$preview_settings = wp_json_encode($settings);

		// Generate preview HTML
		$html = do_shortcode("[event_genius_calendar preview='" . $key . "']");

		wp_send_json_success([
			'html' => $html,
			'settings' => $settings
		]);
	}

	/**
	 * Handle saving calendar data
	 */
	public function handle_save() {
		if (!isset($_POST['evge_calendar_nonce']) || 
			!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['evge_calendar_nonce'])), 'evge_save_calendar')) {
			return;
		}

		if (!current_user_can('edit_evge_events')) {
			wp_die(esc_html__('You do not have permission to perform this action.', 'event-genius'));
		}

		// Get and sanitize calendar name
		$calendar_name = isset($_POST['calendar_name']) ? sanitize_text_field(wp_unslash($_POST['calendar_name'])) : '';
		if (empty($calendar_name)) {
			wp_redirect(add_query_arg(['error' => 'name_required']));
			exit;
		}

		// phpcs:ignore 
		$settings = isset($_POST['calendar_settings']) ? $this->sanitize_settings($_POST['calendar_settings']) : [];
		// Get calendar ID if editing
		$calendar_id = isset($_POST['calendar_id']) ? sanitize_key(wp_unslash($_POST['calendar_id'])) : 0;

		if ($calendar_id) {
			// Update existing calendar
			$result = wp_update_term($calendar_id, 'evge_calendar', [
				'name' => $calendar_name
			]);

			if (is_wp_error($result)) {
				wp_redirect(add_query_arg([
					'calendar_id' => $calendar_id,
					'error' => 'update_failed'
				]));
				exit;
			}

			update_term_meta($calendar_id, 'evge_calendar_settings', $settings);
		} else {
			// Create new calendar
			$result = wp_insert_term($calendar_name, 'evge_calendar');

			if (is_wp_error($result)) {
				wp_redirect(add_query_arg(['error' => 'create_failed']));
				exit;
			}

			$calendar_id = $result['term_id'];
			add_term_meta($calendar_id, 'evge_calendar_settings', $settings, true);
		}

		// Redirect with success message
		wp_redirect(add_query_arg([
			'calendar_id' => $calendar_id,
			'message' => 'saved'
		]));
		exit;
	}

	/**
	 * Enqueue builder assets
	 */
	public function enqueue_assets($hook) {
		if (!$this->is_calendar_builder_screen()) {
			return;
		}

		EVGE()->style_service()->enqueue_style( 'evge_calendar' );
		EVGE()->style_service()->enqueue_style( 'evge_calendar_builder' );

		EVGE()->script_service()->enqueue_script( 'select2' );
		EVGE()->style_service()->enqueue_style( 'select2' );

		wp_enqueue_style('wp-color-picker');
		wp_enqueue_script('wp-color-picker');

		EVGE()->script_service()->enqueue_script( 'evge_admin_calendar_builder' );
		
	}

	/**
	 * Sanitize calendar settings
	 */
	private function sanitize_settings($settings) {
		$defaults = [
			'view' => 'month',
			'num' => 12,
			'events_per_day' => 3,
			'filters' => [],
			'color' => '#3498db',
			'filter_relationship' => 'AND',
			'show_toolbar' => 'enabled',
			'bulk_registration_enabled' => 'disabled'
		];

		$sanitized = [];

		// View type
		$sanitized['view'] = in_array($settings['view'], ['month', 'grid', 'list']) 
			? $settings['view'] 
			: $defaults['view'];

		// Events per page
		$sanitized['num'] = min(100, max(1, absint($settings['num'] ?? 12)));

		// Events per day
		$sanitized['events_per_day'] = min(100, max(1, absint($settings['events_per_day'] ?? 3)));

			// Filter relationship
		$sanitized['filter_relationship'] = in_array($settings['filter_relationship'], ['AND', 'OR']) 
			? $settings['filter_relationship'] 
			: $defaults['filter_relationship'];

		// Show toolbar
		$sanitized['show_toolbar'] = isset($settings['show_toolbar']) && $settings['show_toolbar'] !== 'disabled' ? 'enabled' : 'disabled';

		// Filter bar options
		$default_filter_bar_options = [
			'search' => true,
			'venue' => true,
			'category' => false,
			'tag' => false,
			'time_filter' => true,
			'display' => true
		];
		
		if (isset($settings['filter_bar_options']) && is_array($settings['filter_bar_options'])) {
			$sanitized['filter_bar_options'] = [];
			foreach ($default_filter_bar_options as $key => $default_value) {
				$sanitized['filter_bar_options'][$key] = isset($settings['filter_bar_options'][$key]) 
					&& ($settings['filter_bar_options'][$key] === '1' || $settings['filter_bar_options'][$key] === true || $settings['filter_bar_options'][$key] === 'true');
			}
		} else {
			$sanitized['filter_bar_options'] = $default_filter_bar_options;
		}

		// Bulk registration enabled (Premium feature)
		if (function_exists('evge_is_premium_tier') && evge_is_premium_tier()) {
			$sanitized['bulk_registration_enabled'] = isset($settings['bulk_registration_enabled']) && $settings['bulk_registration_enabled'] !== 'disabled' ? 'enabled' : 'disabled';
		}

		// Sanitize filters
		$sanitized['filters'] = [];
		if (!empty($settings['filters'])) {
			if (is_string($settings['filters'])) {
				$settings['filters'] = json_decode($settings['filters'], true);
			}
			foreach ($settings['filters'] as $filter) {
				if (is_string($filter)) {
					$filter = json_decode($filter, true);
				}
				
				if (!is_array($filter)) {
					continue;
				}

				$sanitized_filter = [
					'type' => in_array($filter['type'], ['category', 'tag']) ? $filter['type'] : 'category',
					'action' => in_array($filter['action'], ['include', 'exclude']) ? $filter['action'] : 'include',
					'terms' => array_map('absint', (array) ($filter['terms'] ?? []))
				];

				if (!empty($sanitized_filter['terms'])) {
					$sanitized['filters'][] = $sanitized_filter;
				}
			}
		}

		// Color
		$sanitized['color'] = sanitize_hex_color($settings['color']) ?: $defaults['color'];

		return $sanitized;
	}

	/**
	 * Check if current screen is calendar builder
	 */
	private function is_calendar_builder_screen() {
		return true;
		$screen = get_current_screen();
		return $screen && $screen->taxonomy === 'evge_calendar';
	}

	/**
	 * Handle AJAX save
	 */
	public function handle_ajax_save() {
		check_ajax_referer('evge_calendar_builder', 'security');

		if (!current_user_can('edit_evge_events')) {
			wp_send_json_error(['message' => __('Permission denied', 'event-genius')]);
		}

		// Get and sanitize calendar name
		$calendar_name = isset($_POST['calendar_name']) ? sanitize_text_field(wp_unslash($_POST['calendar_name'])) : '';

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$calendar_settings = isset($_POST['calendar_settings']) ? json_decode(wp_unslash($_POST['calendar_settings']), true) : [];
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$filters = isset($_POST['calendar_settings_filters']) ? json_decode(wp_unslash($_POST['calendar_settings_filters']), true) : [];
		$calendar_settings['filters'] = $filters;
		$settings = $this->sanitize_settings($calendar_settings ?? []);

		// Get calendar ID if editing
		$calendar_id = isset($_POST['calendar_id']) ? sanitize_key(wp_unslash($_POST['calendar_id'])) : 0;

		if ($calendar_id) {
			if ( $calendar_id === 'default' ) {
				$evge_settings = get_option( 'evge_settings', array() );
				if ( ! is_array( $evge_settings ) ) {
					$evge_settings = array();
				}
				$evge_settings['default_calendar_settings'] = wp_json_encode( $settings );
				update_option( 'evge_settings', $evge_settings );
			} else {
	// Update existing calendar
				$result = wp_update_term($calendar_id, 'evge_calendar', [
					'name' => $calendar_name
				]);

				if (is_wp_error($result)) {
					wp_send_json_error(['message' => $result->get_error_message()]);
				}

				update_term_meta($calendar_id, 'evge_calendar_settings', $settings);
			}
			
		} else {
			// Create new calendar
			$result = wp_insert_term($calendar_name, 'evge_calendar');

			if (is_wp_error($result)) {
				wp_send_json_error(['message' => $result->get_error_message()]);
			}

			$calendar_id = $result['term_id'];
			add_term_meta($calendar_id, 'evge_calendar_settings', $settings, true);
		}

		wp_send_json_success([
			'calendar_id' => $calendar_id,
			'message' => __('Calendar saved successfully', 'event-genius')
		]);
	}

	/**
	 * Handle AJAX request to get taxonomy terms
	 */
	public function handle_get_terms() {
		check_ajax_referer('evge_calendar_builder', 'security');

		if (!current_user_can('edit_evge_events')) {
			wp_send_json_error(['message' => __('Permission denied', 'event-genius')]);
		}

		$taxonomy = isset($_POST['taxonomy']) ? sanitize_key(wp_unslash($_POST['taxonomy'])) : '';
		if (!in_array($taxonomy, [EVGE_EVENT_CATEGORY_TYPE, EVGE_EVENT_TAG_TYPE])) {
			wp_send_json_error(['message' => __('Invalid taxonomy', 'event-genius')]);
		}

		$terms = get_terms([
			'taxonomy' => $taxonomy,
			'hide_empty' => false,
		]);

		if (is_wp_error($terms)) {
			wp_send_json_error(['message' => $terms->get_error_message()]);
		}

		$terms_data = array_map(function($term) {
			return [
				'term_id' => $term->term_id,
				'name' => $term->name,
			];
		}, $terms);

		wp_send_json_success($terms_data);
	}

	/**
	 * Render a summary of an existing filter
	 *
	 * @param string $type The filter type (category or tag)
	 * @param string $action The filter action (include or exclude)
	 * @param array $terms Array of term IDs
	 * @param int $index The filter index
	 * @return string The HTML for the filter summary
	 */
	public function render_filter_summary($type, $action, $terms, $index) {
		// Get term names
		$taxonomy = $type === 'category' ? EVGE_EVENT_CATEGORY_TYPE : EVGE_EVENT_TAG_TYPE;
		$term_objects = get_terms([
			'taxonomy' => $taxonomy,
			'include' => $terms,
			'hide_empty' => false,
		]);

		if (is_wp_error($term_objects)) {
			return '';
		}

		// Prepare filter data
		$filter_data = [
			'type' => $type,
			'action' => $action,
			'terms' => $terms
		];

		$prefix = $type === 'category' ? __('Cat:', 'event-genius') : __('Tag:', 'event-genius');

		$terms_text = implode(', ', wp_list_pluck($term_objects, 'name'));
    
		// If terms text is too long, truncate it
		if (strlen($terms_text) > 30) {
			$terms_text = substr($terms_text, 0, 27) . '...';
		}
    
		$action_text = $action === 'exclude' ? '(-)' : '';
    
		ob_start();
		?>
		<div class="evge-filter-item">
			<input type="hidden" name="calendar_settings[filters][]" value='<?php echo esc_attr(json_encode($filter_data)); ?>'>
			<div class="evge-filter-content">
				<div class="evge-filter-terms">
					<span class="evge-filter-type"><?php echo esc_html($prefix); ?></span>
					<span class="evge-filter-names"><?php echo esc_html($terms_text); ?></span>
				</div>
				<div class="evge-filter-meta">
					<?php echo esc_html($action_text); ?>
				</div>
			</div>
			<button type="button" class="evge-remove-filter" aria-label="<?php esc_attr_e('Remove filter', 'event-genius'); ?>"><svg width="8" height="8" viewBox="0 0 8 8" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0.616118 0.616115C1.10427 0.12796 1.89573 0.127962 2.38389 0.616118L4 2.23224L5.61611 0.616118C6.10427 0.127962 6.89573 0.12796 7.38388 0.616115C7.87204 1.10427 7.87204 1.89573 7.38389 2.38388L5.76776 4.00001L7.38389 5.61614C7.87204 6.10429 7.87204 6.89575 7.38388 7.3839C6.89573 7.87206 6.10427 7.87206 5.61611 7.3839L4 5.76778L2.38389 7.3839C1.89573 7.87206 1.10427 7.87206 0.616118 7.3839C0.127962 6.89575 0.12796 6.10429 0.616115 5.61614L2.23224 4.00001L0.616115 2.38388C0.12796 1.89573 0.127962 1.10427 0.616118 0.616115Z" fill="#AAAAAA"/></svg></button>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Handle adding a new calendar
	 */
	public function handle_add_calendar() {
		if (!isset($_POST['action']) || $_POST['action'] !== 'evge_add_calendar') {
			return;
		}
	
		// Verify nonce
		if (!isset($_POST['evge_calendar_nonce']) || 
			!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['evge_calendar_nonce'])), 'evge_add_calendar')) {
			wp_die(esc_html__('Security check failed', 'event-genius'));
		}
	
		// Check permissions
		if (!current_user_can('edit_evge_events')) {
			wp_die(esc_html__('You do not have permission to perform this action', 'event-genius'));
		}
	
		// Insert the new calendar term
		$result = wp_insert_term(
			__('New Calendar', 'event-genius'), // This will be updated after we get the ID
			'evge_calendar'
		);
	
		if (is_wp_error($result)) {
			wp_die(esc_html($result->get_error_message()));
		}
	
		$calendar_id = $result['term_id'];
	
		// Update the name with the ID
		wp_update_term($calendar_id, 'evge_calendar', [
			'name' => sprintf(
                /* translators: %d: unique ID number of the calendar */
                __('Calendar %d', 'event-genius'), 
                $calendar_id
            )
		]);
	
		// Add default settings
		update_term_meta($calendar_id, 'evge_calendar_settings', [
			'view_type' => 'month',
			'events_per_page' => 12,
			'filters' => [],
			'color' => '#3498db'
		]);
	
		// Redirect to edit screen
		wp_safe_redirect(add_query_arg([
			'page' => 'evge-all-events',
			'tab' => 'calendars',
			'calendar_id' => $calendar_id
		], admin_url('admin.php')));
		exit;
	}

	public function handle_get_shortcode_content() {
		check_ajax_referer('evge_calendar_builder', 'security');

		if (!current_user_can('edit_evge_events')) {
			wp_send_json_error(['message' => __('Permission denied', 'event-genius')]);
		}

		$calendar_id = isset($_POST['calendar_id']) ? sanitize_key($_POST['calendar_id']) : 0;
		if (!$calendar_id) {
			wp_send_json_error(['message' => __('Invalid calendar ID', 'event-genius')]);
		}

		ob_start();
		?>
		<div class="evge-cal-shortcode">
			<?php if ($calendar_id === 'default') : ?>
				<p style="margin-bottom: 20px;">
					<?php 
					$archive_url = get_post_type_archive_link(EVGE_EVENT_POST_TYPE);
					if ($archive_url) {
						printf(
							/* translators: %s: URL to the events archive page */
							esc_html__('The default calendar will display events from the: %s', 'event-genius'),
							'<a href="' . esc_url($archive_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Events Archive', 'event-genius') . '</a>'
						);
					}
					?>
				</p>
				<p style="margin-bottom: 20px;">
					<?php esc_html_e('A default calendar, displayed on the events post type archive page, is automatically created when you install the plugin. All of your created events are automatically added to this calendar and available on this page without needing a shortcode or block.', 'event-genius'); ?>
				</p>
				<p>
					<?php esc_html_e('Create a new calendar to display events using a shortcode or block.', 'event-genius'); ?>
				</p>
			<?php endif; ?>
			<?php if ($calendar_id !== 'default') : ?>
				<p><?php esc_html_e('Copy this shortcode and paste it into any post or page:', 'event-genius'); ?></p>
				<div class="evge-shortcode-container">
					<code class="evge-shortcode" data-evge-copy-content="calendar-shortcode">[event_genius_calendar id="<?php echo esc_attr($calendar_id); ?>"]</code>
					<button type="button" class="button evge-copy-shortcode" 
							data-evge-copy-trigger="calendar-shortcode">
						<?php esc_html_e('Copy', 'event-genius'); ?>
					</button>
				</div>
			<div class="evge-copy-success" style="display: none;" data-evge-copy-success="calendar-shortcode">
				<?php esc_html_e('Shortcode copied!', 'event-genius'); ?>
			</div>
			<?php if ( ! ( defined( 'CLASSIC_EDITOR_VERSION' ) || class_exists( 'Classic_Editor' ) ) ) : ?>
				<p><?php esc_html_e('Or use the calendar block in the block editor.', 'event-genius'); ?></p>
			<?php endif; ?>
			<?php endif; ?>

		</div>
		<?php
		$html = ob_get_clean();

		wp_send_json_success(['html' => $html]);
	}

	/**
	 * AJAX handler for calendar embed instructions modal
	 */
	public function ajax_get_calendar_embed_instructions() {
		// Verify nonce (admin AJAX uses 'evge-admin' nonce)
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'evge-admin' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'event-genius' ) ) );
			return;
		}

		if ( ! current_user_can( 'edit_evge_events' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'event-genius' ) ) );
			return;
		}

		// Get calendar ID if provided (optional, for future use)
		$calendar_id = isset( $_POST['calendar_id'] ) ? sanitize_key( wp_unslash( $_POST['calendar_id'] ) ) : '';

		// For the default calendar, return the old HTML format
		if ( $calendar_id === 'default' ) {
			ob_start();
			?>
			<div class="evge-modal-heading">
				<h2><?php esc_html_e( 'Embed Instructions', 'event-genius' ); ?></h2>
			</div>
			<div class="evge-embed-instructions-modal evge-modal-padding evge-modal-body">
				<div class="evge-cal-shortcode">
					<p style="margin-bottom: 20px;">
						<?php 
						$archive_url = get_post_type_archive_link( EVGE_EVENT_POST_TYPE );
						if ( $archive_url ) {
							printf(
								/* translators: %s: URL to the events archive page */
								esc_html__( 'The default calendar will display events from the: %s', 'event-genius' ),
								'<a href="' . esc_url( $archive_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Events Archive', 'event-genius' ) . '</a>'
							);
						}
						?>
					</p>
					<p style="margin-bottom: 20px;">
						<?php esc_html_e( 'A default calendar, displayed on the events post type archive page, is automatically created when you install the plugin. All of your created events are automatically added to this calendar and available on this page without needing a shortcode or block.', 'event-genius' ); ?>
					</p>
					<p>
						<?php esc_html_e( 'Create a new calendar to display events using a shortcode or block.', 'event-genius' ); ?>
					</p>
				</div>
			</div>
			<?php
			$content = ob_get_clean();
			wp_send_json_success( array( 'html' => $content ) );
			return;
		}

		// Use the reusable embed instructions service for non-default calendars
		$embed_service = new \WPEventGenius\Common\Services\EmbedInstructionsService();
		
		$is_classic_editor = defined( 'CLASSIC_EDITOR_VERSION' ) || class_exists( 'Classic_Editor' );
		$description = $is_classic_editor 
			? __( 'You can embed a calendar on any page or post using a shortcode.', 'event-genius' )
			: __( 'You can embed a calendar on any page or post using either the block editor or a shortcode.', 'event-genius' );
		
		$args = array(
			'block_name' => 'wp-event-genius/calendar',
			'block_title' => __( 'Event Genius Calendar', 'event-genius' ),
			'shortcode' => $calendar_id ? '[event_genius_calendar id="' . esc_attr( $calendar_id ) . '"]' : '[event_genius_calendar]',
			'shortcode_title' => __( 'Shortcode', 'event-genius' ),
			'description' => $description,
			'example_image_path' => EVGE_PLUGIN_URL . 'assets/images/admin/settings/block-example-calendar.png',
			'other_blocks_example_image_path' => EVGE_PLUGIN_URL . 'assets/images/admin/settings/block-example-all.png',
		);

		$embed_service->ajax_get_embed_instructions( $args );
	}

	/**
	 * Handle the delete calendar modal content
	 */
	public function handle_delete_calendar_modal() {
		check_ajax_referer('evge_calendar_builder', 'security');

		if (!current_user_can('edit_evge_events')) {
			wp_send_json_error(['message' => __('Permission denied', 'event-genius')]);
		}

		$calendar_id = isset($_POST['calendar_id']) ? sanitize_key($_POST['calendar_id']) : 0;
		if (!$calendar_id) {
			wp_send_json_error(['message' => __('Invalid calendar ID', 'event-genius')]);
		}

		$calendar = get_term($calendar_id, 'evge_calendar');
		if (is_wp_error($calendar)) {
			wp_send_json_error(['message' => __('Calendar not found', 'event-genius')]);
		}
		$modal_settings = array( 
			'width' => 'narrow',
			'noHeader' => true 
		);
		$modal_settings_json = wp_json_encode( $modal_settings );
		$icon_html = '<div class="evge-alert-icon evge-unknown evge-shadow"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M64 64c0-17.7-14.3-32-32-32S0 46.3 0 64V320c0 17.7 14.3 32 32 32s32-14.3 32-32V64zM32 480a40 40 0 1 0 0-80 40 40 0 1 0 0 80z"/></svg></div>';
		ob_start();
		?>

<div class="evge-dynamic evge-modal-settings" data-evge-modal-settings="<?php echo esc_attr( $modal_settings_json ); ?>">
    <div class="evge-narrow-modal-content">
        <div class="evge-narrow-modal-inner">
            <div class="evge-modal-section">
                <div class="evge-status-message">
	                <?php if ( ! empty( $icon_html )) : ?>
                        <div class="evge-cancel-request-icon">
			                <?php 
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo $icon_html; ?>
                        </div>
	                <?php endif; ?>
                    <div class="evge-delete-confirm evge-standard-dialog">
                        <p>
			                <?php esc_html_e( 'This cannot be undone. Are you sure you want to delete this calendar?', 'event-genius' ); ?>

                        </p>

                    </div>
                </div>

                <div class="evge-delete-confirm-buttons evge-standard-dialog-buttons">
                    <form method="post" class="evge-delete-calendar-form">
						<?php wp_nonce_field('evge_delete_calendar', 'evge_calendar_nonce'); ?>
						<input type="hidden" name="evge_delete_calendar_id" value="<?php echo esc_attr($calendar_id); ?>">
						<button type="submit" class="evge-destructive" name="evge_action" value="confirm">
							<?php esc_html_e('Confirm', 'event-genius'); ?>
						</button>
						<button type="submit" class="evge-dialog-secondary evge-action-modal-close" name="evge_action" value="cancel">
							<?php esc_html_e('Cancel', 'event-genius'); ?>
						</button>

					</form>
                </div>
            </div>

        </div>
    </div>

</div>
		<?php
		$html = ob_get_clean();

		wp_send_json_success(['html' => $html]);
	}

	/**
	 * Handle the actual calendar deletion (supports both POST and GET)
	 */
	public function handle_delete_calendar() {
		// Check if this is a calendar deletion submission (POST or GET)
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : ( isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '' );
		
		// Support both old modal approach (POST with evge_action) and new GET approach
		$is_modal_delete = ! empty( $_POST['evge_delete_calendar_id'] ) && ! empty( $_POST['evge_action'] ) && $_POST['evge_action'] === 'confirm';
		$is_get_delete = $action === 'evge_delete_calendar';
		
		if ( ! $is_modal_delete && ! $is_get_delete ) {
			return;
		}

		// Get calendar ID from POST or GET
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$calendar_id = isset( $_POST['evge_delete_calendar_id'] ) ? absint( $_POST['evge_delete_calendar_id'] ) : ( isset( $_GET['calendar_id'] ) ? absint( $_GET['calendar_id'] ) : 0 );
		
		if ( empty( $calendar_id ) ) {
			return;
		}

		// Verify nonce (check both POST and GET)
		$nonce = isset( $_POST['evge_calendar_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['evge_calendar_nonce'] ) ) : ( isset( $_GET['evge_delete_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['evge_delete_nonce'] ) ) : '' );
		
		if ( $is_modal_delete ) {
			// Old modal approach uses 'evge_delete_calendar' nonce
			if ( ! wp_verify_nonce( $nonce, 'evge_delete_calendar' ) ) {
				return;
			}
		} else {
			// New GET approach uses calendar-specific nonce
			if ( ! wp_verify_nonce( $nonce, 'evge_delete_calendar_' . $calendar_id ) ) {
				return;
			}
		}

		if ( ! current_user_can( 'edit_evge_events' ) ) {
			return;
		}

		// Don't allow deleting the default calendar (calendar_id is 'default')
		if ( $calendar_id === 'default' ) {
			wp_die( __( 'Cannot delete the default calendar', 'event-genius' ) );
		}

		// Get the calendar term
		$calendar = get_term( $calendar_id, 'evge_calendar' );
		if ( is_wp_error( $calendar ) ) {
			return;
		}

		$result = wp_delete_term( $calendar_id, 'evge_calendar' );
		if ( is_wp_error( $result ) ) {
			return;
		}

		// redirect to the calendars page
		wp_redirect( add_query_arg( [
			'page' => 'evge-all-events',
			'tab' => 'calendars',
			'message' => 'deleted'
		], admin_url( 'admin.php' ) ) );
		exit;
	}
}