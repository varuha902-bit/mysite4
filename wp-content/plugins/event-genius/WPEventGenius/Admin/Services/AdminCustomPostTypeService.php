<?php

namespace WPEventGenius\Admin\Services;

use WPEventGenius\Common\Database;
use WPEventGenius\Common\Queries\VenueQuery;
use WPEventGenius\Common\Queries\OrganizerQuery;
use WPEventGenius\Admin\CustomPostTypes\Event;
use WPEventGenius\Common\Event\EventPost;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AdminCustomPostTypeService {

	/**
	 * @var array
	 */
	private $custom_post_types;

	/**
	 * @var Database
	 */
	private $db;

	public function __construct( $custom_post_types ) {
		$this->custom_post_types = $custom_post_types;
		$this->db = new Database();
	}

	public function init_hooks() {
		add_action( 'init', array( $this, 'register_post_types' ) );

		add_action( 'add_meta_boxes', array( $this, 'meta_boxes' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ), 10, 1 );
		add_filter( 'get_edit_post_link', array( $this, 'modify_recurrence_edit_link' ), 10, 3 );

		// Add hook for deleting event timing data
		add_action( 'before_delete_post', array( $this, 'maybe_delete_event_timing' ) );
		
		// Add hooks for meta changes
		add_action( 'updated_post_meta', array( $this, 'handle_meta_update' ), 10, 4 );
		add_action( 'added_post_meta', array( $this, 'handle_meta_update' ), 10, 4 );
		
		foreach ( $this->custom_post_types as $custom_post_type ) {
			if ( method_exists( $custom_post_type, 'save_post' ) ) {
				add_action( 'save_post_' . $custom_post_type->get_post_type(), array( $custom_post_type, 'save_post' ), 10, 1 );
			}
			if ( method_exists( $custom_post_type, 'custom_columns' ) ) {
				add_filter( 'manage_' . $custom_post_type->get_post_type() . '_posts_columns', array( $custom_post_type, 'custom_columns' ), 10, 1 );
				add_filter( 'manage_' . $custom_post_type->get_post_type() . '_posts_custom_column', array( $custom_post_type, 'custom_columns_content' ), 10, 2 );
			}
		}
		add_action('wp_ajax_evge_check_series_queue_after_save', array($this, 'check_series_queue_callback_after_save'));

		add_action('wp_ajax_evge_check_series_queue', array($this, 'check_series_queue_callback'));
		add_action('wp_ajax_evge_process_series_queue_batch', array($this, 'process_series_queue_batch_callback'));

        add_action('wp_ajax_evge_trash_all_recurrences', array($this, 'trash_all_recurrences'));
        add_action('wp_ajax_evge_get_recurrence_trash_options', array($this, 'get_recurrence_trash_options'));
		add_action('wp_ajax_evge_get_recurrence_template_trash_options', array($this, 'get_recurrence_template_trash_options'));
		add_action('wp_ajax_evge_trash_template_event', array($this, 'trash_template_event'));
		add_action('wp_ajax_evge_undo_recurrence_creation', array($this, 'undo_recurrence_creation'));

        // Add hook for redirecting the main event listing page
        add_action('admin_init', array($this, 'maybe_redirect_event_listing'));

        // Handle permanent deletion
        add_action('before_delete_post', array($this, 'handle_permanent_deletion'), 10, 2);
        
        // Handle bulk actions
        add_action('bulk_post_deleted', array($this, 'handle_bulk_post_deleted'), 10, 2);

		// Refresh after save and new venue/organizer
		add_action( 'wp_ajax_evge_refresh_venue_list', array( $this, 'refresh_venue_list' ) );
		add_action( 'wp_ajax_evge_refresh_organizer_list', array( $this, 'refresh_organizer_list' ) );
	}

	public function register_post_types() {
		foreach ( $this->custom_post_types as $custom_post_type ) {
			$custom_post_type->register_taxonomies();

			$custom_post_type->register();
		}
	}

	public function custom_columns( $defaults ) {
		foreach ( $this->custom_post_types as $custom_post_type ) {
			$defaults = $custom_post_type->filter_default_columns( $defaults );
		}

		return $defaults;
	}

	public function custom_columns_content( $column_name, $post_id ) {
		foreach ( $this->custom_post_types as $custom_post_type ) {
			$custom_post_type->custom_column_content( $column_name, $post_id );
		}
	}

	public function meta_boxes() {
		foreach ( $this->custom_post_types as $custom_post_type ) {
			$custom_post_type->add_meta_boxes();
		}
	}

	public function save_post( $post_id ) {
		foreach ( $this->custom_post_types as $custom_post_type ) {
			$custom_post_type->save_post( $post_id );
		}
	}

	public function enqueue( $screen ) {
		foreach ( $this->custom_post_types as $custom_post_type ) {
			$custom_post_type->enqueue( $screen );
		}
	}

	public function maybe_delete_event_timing($post_id) {
		$post_type = get_post_type($post_id);
		if ($post_type === EVGE_EVENT_POST_TYPE) {
			foreach ($this->custom_post_types as $custom_post_type) {
				if ($custom_post_type instanceof \WPEventGenius\Admin\CustomPostTypes\Event) {
					$custom_post_type->delete_event_timing($post_id);
					break;
				}
			}
		}
	}

	/**
	 * Handle updates to post meta
	 */
	public function handle_meta_update($meta_id, $post_id, $meta_key, $meta_value) {
		$post_type = get_post_type($post_id);
		
		// Route meta updates to appropriate post type handler
		foreach ($this->custom_post_types as $custom_post_type) {
			if ($post_type === $custom_post_type->get_post_type() && 
				method_exists($custom_post_type, 'handle_meta_update')) {
				$custom_post_type->handle_meta_update($meta_id, $post_id, $meta_key, $meta_value);
				break;
			}
		}
	}

	/**
	 * AJAX callback to check series queue status
	 */
	public function check_series_queue_callback() {
		check_ajax_referer('evge_admin_nonce', 'nonce');
		
		if (! current_user_can('edit_evge_events')) {
			wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
		}

		$json = $this->check_series_queue();

		wp_send_json_success($json);
	}

	/**
	 * AJAX callback to check series queue status after editor save
	 */
	public function check_series_queue_callback_after_save() {
		check_ajax_referer('evge_post_save_nonce', 'nonce');

		if (! current_user_can('edit_evge_events')) {
			wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
		}

		$json = $this->check_series_queue();

		wp_send_json_success($json);
	}

	public function check_series_queue() {
		$queue = new \WPEventGenius\Common\Series\Queue\SeriesQueue($this->db);
		
		// Don't process if debug mode is enabled
		if ($queue->is_debug_mode_enabled()) {
			$queue_items = $queue->get_queue();
		} else {
			$queue->process_batch();
			$queue_items = $queue->get_queue();
		}
		
		// Count pending and processing items
		$pending_count = 0;
		$total_events = 0;
		$processed_events = 0;
		$is_creation = false;
		$series_id = null;
		
		foreach ($queue_items as $item) {
			if ($item['status'] === 'pending' || $item['status'] === 'processing') {
				$pending_count++;
				$total_events += $item['total_events'];
				$processed_events += $item['processed_events'];
				
				// Set is_creation and series_id from the first active task
				if (!$is_creation && isset($item['data']['type'])) {
					$is_creation = ($item['data']['type'] === \WPEventGenius\Common\Series\Queue\SeriesQueue::TASK_TYPE_CREATE);
					$series_id = isset($item['data']['series_id']) ? $item['data']['series_id'] : null;
				}
			}
		}
		
		// Calculate estimated batches needed
		$batch_size = \WPEventGenius\Common\Series\Queue\SeriesQueue::BATCH_SIZE;
		$remaining_events = $total_events - $processed_events;
		$estimated_batches = ceil($remaining_events / $batch_size);

		ob_start();
		?>
		<div class="evge-queue-processing-modal-content">
		<?php $this->add_queue_processing_modal_content(); ?>
		</div>
		<?php

		$modal_html = ob_get_clean();
		
		return [
			'pending_tasks' => $pending_count,
			'total_events' => $total_events,
			'processed_events' => $processed_events,
			'estimated_batches' => $estimated_batches,
			'batch_size' => $batch_size,
			'modal_html' => $modal_html,
			'is_creation' => $is_creation,
			'series_id' => $series_id
		];
	}

	/**
	 * AJAX callback to process a batch of the series queue
	 */
	public function process_series_queue_batch_callback() {
		// Security check
		check_ajax_referer('evge_admin_nonce', 'nonce');

		if (!current_user_can('edit_evge_events')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'event-genius')));
        }
		
		$queue = new \WPEventGenius\Common\Series\Queue\SeriesQueue($this->db);
		
		// Don't process if debug mode is enabled
		if (!$queue->is_debug_mode_enabled()) {
			// Process one batch
			$queue->process_batch();
		}
		
		// Get updated queue status
		$queue_items = $queue->get_queue();
		$pending_count = 0;
		$total_events = 0;
		$processed_events = 0;
		
		foreach ($queue_items as $item) {
			if ($item['status'] === 'pending' || $item['status'] === 'processing') {
				$pending_count++;
				$total_events += $item['total_events'];
				$processed_events += $item['processed_events'];
			}
		}
		
		$is_completed = ($pending_count === 0);
		
		wp_send_json_success([
			'pending_tasks' => $pending_count,
			'total_events' => $total_events,
			'processed_events' => $processed_events,
			'is_completed' => $is_completed
		]);
	}

	/**
	 * AJAX handler for trashing all recurrences of an event
	 */
	public function trash_all_recurrences() {
		check_ajax_referer('evge_admin_nonce', 'nonce');

        if (!current_user_can('edit_evge_events')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'event-genius')));
        }

        $series_id = isset($_POST['series_id']) ? absint($_POST['series_id']) : 0;
        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        $include_template_event = isset($_POST['include_template_event']) && $_POST['include_template_event'] === 'true';

        if (!$series_id && !$event_id) {
            wp_send_json_error(array('message' => __('Invalid series or event ID.', 'event-genius')));
        }

        $series_repo = new \WPEventGenius\Common\Series\EventSeriesRepository($this->db);
        
        // If we have an event ID but no series ID, get the series ID
        if ($event_id && !$series_id) {
            $series_id = $series_repo->get_series_id_for_event($event_id);
        }
        
        if (!$series_id) {
            wp_send_json_error(array('message' => __('No series found for this event.', 'event-genius')));
        }

        // Get the template event ID
        $template_id = $series_repo->get_template_event_id($series_id);

        if (!$template_id) {
            wp_send_json_error(array('message' => __('Template event not found.', 'event-genius')));
        }

        // Update the template event to have no recurrence as they will all be in the trash
        update_post_meta($template_id, 'evge_recurrence_type', 'none');

        $event_ids = $series_repo->get_series_events($series_id);

        if (empty($event_ids)) {
            wp_send_json_error(array('message' => __('No events found in this series.', 'event-genius')));
        }

        foreach ($event_ids as $event_id) {
            if ($event_id == $template_id && !$include_template_event) {
                continue;
            }
            
            // Check if this event was added to the series via settings
            $added_via_setting = get_post_meta($event_id, 'evge_series_added_via_setting', true);
            
            if ($added_via_setting && (int)$added_via_setting === (int)$series_id) {
                // Event was added via series settings - only remove the relationship, don't trash the event
                $series_repo->remove_relationship($series_id, $event_id);
            } else {
                // Event was created via recurrence - trash it
                wp_trash_post($event_id);
            }
        }

        // Generate success HTML
        $success_html = '<div class="evge-narrow-modal-inner evge-recurrence-modal-content-inner">
            <div class="evge-modal-section">
                <div class="evge-standard-dialog">
                    <p><strong>' . esc_html__('Success!', 'event-genius') . '</strong></p>
                    <p>' . esc_html__('All recurrences have been moved to trash.', 'event-genius') . '</p>
                </div>
                <div class="evge-modal-actions">
                    <a href="' . esc_url(get_edit_post_link($template_id)) . '" class="button">
                        ' . esc_html__('Edit Template Event', 'event-genius') . '
                    </a>
                    <button type="button" class="button evge-modal-close">
                        ' . esc_html__('Close', 'event-genius') . '
                    </button>
                </div>
            </div>
        </div>';

        wp_send_json_success(array(
            'html' => $success_html
        ));
	}

    /**
     * AJAX handler for getting series information
     */
    public function get_recurrence_trash_options() {
        check_ajax_referer('evge_admin_nonce', 'nonce');

        if (!current_user_can('edit_evge_events')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'event-genius')));
        }

        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        if (!$event_id) {
            wp_send_json_error(array('message' => __('Invalid event ID.', 'event-genius')));
        }

        $series_repo = new \WPEventGenius\Common\Series\EventSeriesRepository($this->db);
        $series_id = $series_repo->get_series_id_for_event($event_id);
        
        if (!$series_id) {
            wp_send_json_error(array('message' => __('No series found for this event.', 'event-genius')));
        }

        $event_count = $series_repo->get_series_event_count($series_id);

        $html = '<div class="evge-narrow-modal-inner evge-recurrence-modal-content-inner">
                    <div class="evge-modal-section">
                        <div class="evge-standard-dialog">
                            <p><strong>' . esc_html__('Trash Recurrence', 'event-genius') . '</strong></p>
                            <p>' . sprintf(
                                /* translators: %d: total number of events in the series */
                                esc_html__('This event is part of a series with %d total events.', 'event-genius'),
                                $event_count
                            ) . '</p>
							<p>' . sprintf(
                                /* translators: %d: total number of events in the series */
                                esc_html__('Trashing recurrences will not trash the template event.', 'event-genius'),
                                $event_count
                            ) . '</p>
                        </div>
                        <div class="evge-modal-actions">
                            <button type="button" class="button evge-trash-single-recurrence" data-recurrence-id="' . esc_attr($event_id) . '">
                                ' . esc_html__('Trash Single Recurrence', 'event-genius') . '
                            </button>
                            <button type="button" class="button evge-trash-all-recurrences" data-template-id="' . esc_attr($event_id) . '" data-series-id="' . esc_attr($series_id) . '">
                                ' . esc_html__('Trash All Recurrences', 'event-genius') . '
                            </button>
                            <button type="button" class="button evge-modal-close">
                                ' . esc_html__('Cancel', 'event-genius') . '
                            </button>
                        </div>
                    </div>
                </div>';

        wp_send_json_success(array(
            'series_id' => $series_id,
            'event_count' => $event_count,
            'html' => $html
        ));
    }

    /**
	 * Get the HTML for the template event trash options modal
	 */
	public function get_recurrence_template_trash_options() {
		// Verify nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'evge_admin_nonce')) {
			wp_send_json_error(array('message' => __('Invalid nonce.', 'event-genius')));
		}

		// Check user permissions
		if (!current_user_can('edit_evge_events')) {
			wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'event-genius')));
		}

		$event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;

		if (!$event_id) {
			wp_send_json_error(array('message' => __('Invalid event ID.', 'event-genius')));
		}

		$series_repo = new \WPEventGenius\Common\Series\EventSeriesRepository($this->db);
		$series_id = $series_repo->get_series_id_for_event($event_id);

		if (!$series_id) {
			wp_send_json_error(array('message' => __('No series found for this event.', 'event-genius')));
		}

		$template_id = $series_repo->get_template_event_id($series_id);

		if (!$template_id || $template_id != $event_id) {
			wp_send_json_error(array('message' => __('This event is not a template event.', 'event-genius')));
		}

		$event_count = $series_repo->get_series_event_count($series_id);

		$html = '<div class="evge-narrow-modal-inner evge-recurrence-modal-content-inner">
			<div class="evge-modal-section">
				<div class="evge-standard-dialog">
					<p><strong>' . esc_html__('Template Event Warning', 'event-genius') . '</strong></p>
					<p>' . sprintf(
						/* translators: %d: total number of events that will be deleted */
						esc_html__('This is a template event. Deleting it will delete all %d events in this series. This action cannot be undone.', 'event-genius'),
						$event_count
					) . '</p>
				</div>
				<div class="evge-modal-actions">
					<button type="button" class="button evge-trash-template-event" data-event-id="' . esc_attr($event_id) . '">
						' . esc_html__('Delete All Events', 'event-genius') . '
					</button>
					<button type="button" class="button evge-modal-close">
						' . esc_html__('Cancel', 'event-genius') . '
					</button>
				</div>
			</div>
		</div>';

		wp_send_json_success(array('html' => $html));
	}

    /**
     * Add queue processing modal content
     */
    public function add_queue_processing_modal_content() {
        ?>
            <h3><?php esc_html_e('Processing Recurring Events', 'event-genius'); ?></h3>
            <div class="evge-queue-processing-status">
                <p><?php esc_html_e('Please wait while we process your recurring events.', 'event-genius'); ?></p>
                <div class="evge-progress-bar-container">
                    <div class="evge-progress-bar" style="width: 0%;"></div>
                </div>
                <p class="evge-progress-text">
                    <span class="evge-processed-count">0</span> / <span class="evge-total-count">0</span> <?php esc_html_e('events processed', 'event-genius'); ?>
                </p>
            </div>
            <button type="button" class="button button-primary evge-modal-close"><?php esc_html_e('Close', 'event-genius'); ?></button>
			<button type="button" class="button button-secondary evge-undo-recurrence-create" disabled><?php esc_html_e('Delete Recurrences', 'event-genius'); ?></button>
        <?php
    }

    /**
     * Modify the edit link for recurrence events to point to the template event
     */
    public function modify_recurrence_edit_link($link, $post_id, $context) {
        if (get_post_type($post_id) !== EVGE_EVENT_POST_TYPE) {
            return $link;
        }

        $is_recurrence = get_post_meta($post_id, 'evge_is_recurrence', true);
        if ($is_recurrence) {
            return add_query_arg('recurrence_id', $post_id, (string)get_edit_post_link($is_recurrence, $context));
        }

        return $link;
    }
  
    /*
     * Redirect the default WordPress listing pages to our custom pages
     * Can be bypassed by adding evge_bypass=1 to the URL
     */
    public function maybe_redirect_event_listing() {
        global $pagenow;
        
        if ($pagenow !== 'edit.php') {
            return;
        }
        
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!isset($_GET['post_type'])) {
            return;
        }
        
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (isset($_GET['evge_bypass'])) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $post_type = sanitize_text_field(wp_unslash($_GET['post_type']));
        $redirect_url = null;

        switch ($post_type) {
            case EVGE_EVENT_POST_TYPE:
                $redirect_url = 'admin.php?page=evge-all-events';
                break;
            case EVGE_ORGANIZER_POST_TYPE:
                $redirect_url = 'admin.php?page=evge-all-events&tab=organizers';
                break;
            case EVGE_VENUE_POST_TYPE:
                $redirect_url = 'admin.php?page=evge-all-events&tab=venues';
                break;
        }

        // Check for series post type (Pro feature)
        if (defined('EVGE_SERIES_POST_TYPE') && $post_type === EVGE_SERIES_POST_TYPE) {
            $redirect_url = 'admin.php?page=evge-all-events&tab=series';
        }

        if ($redirect_url) {
            wp_safe_redirect(admin_url($redirect_url));
            exit;
        }
    }

    /**
     * Handle permanent deletion of a post
     * 
     * @param int $post_id The post ID being deleted
     * @param \WP_Post $post The post object being deleted
     */
    public function handle_permanent_deletion($post_id, $post) {
        if (!$this->is_managed_post_type($post->post_type)) {
            return;
        }

        switch ($post->post_type) {
            case 'evge_event':
                // Delete event data from custom tables
                $this->db->delete_event_custom_data($post_id);
                break;

            case 'evge_series':
                // Delete all relationships for this series
                $this->db->delete_all_series_relationships($post_id);
                break;
        }
    }

    /**
     * Handle bulk deletion of posts
     * 
     * @param array $deleted_posts Array of deleted post IDs
     * @param array $deleted_posts_count Count of deleted posts by post type
     */
    public function handle_bulk_post_deleted($deleted_posts, $deleted_posts_count) {
        foreach ($deleted_posts as $post_id) {
            $post = get_post($post_id);
            if ($this->is_managed_post_type($post->post_type)) {
                $this->handle_permanent_deletion($post_id, $post);
            }
        }
    }

    /**
     * Check if the post type is managed by this service
     * 
     * @param string $post_type The post type to check
     * @return bool Whether this post type is managed
     */
    protected function is_managed_post_type($post_type) {
        return in_array($post_type, array('evge_event', 'evge_series'));
    }

		/**
	 * Refresh the venue list after a successful save
	 */
	public function refresh_venue_list() {
		if (!current_user_can('edit_evge_events')) {
			wp_send_json_error('Unauthorized');
		}

		if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'evge_post_save_nonce')) {
			wp_send_json_error('Invalid nonce');
		}

		$post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
		if (!$post_id) {
			wp_send_json_error('Invalid post ID');
		}

		$event_post = new EventPost($post_id);
		$venue_query = new VenueQuery();
		$venue_query->add_wp_query();
		$venue_posts = $venue_query->get_venues();
		$selected_venues = $event_post->get_venue_ids();

		$event = new Event();
		ob_start();

		$event->multi_select_list('venue', $venue_posts, $selected_venues, 'venue', __('Venue', 'event-genius'));
		$html = ob_get_clean();

		wp_send_json_success(array('html' => $html));
	}

	/**
	 * Refresh the organizer list after a successful save
	 */
	public function refresh_organizer_list() {
		if (!current_user_can('edit_evge_events')) {
			wp_send_json_error('Unauthorized');
		}

		if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'evge_post_save_nonce')) {
			wp_send_json_error('Invalid nonce');
		}

		$post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
		if (!$post_id) {
			wp_send_json_error('Invalid post ID');
		}

		$event_post = new EventPost($post_id);
		$organizer_query = new OrganizerQuery();
		$organizer_query->add_wp_query();
		$organizer_posts = $organizer_query->get_organizers();
		$selected_organizers = $event_post->get_organizer_ids();
		$event = new Event();

		ob_start();
		$event->multi_select_list('organizer', $organizer_posts, $selected_organizers, 'organizer', __('Organizer', 'event-genius'));
		$html = ob_get_clean();

		wp_send_json_success(array('html' => $html));
	}

}