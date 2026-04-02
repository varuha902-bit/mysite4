<?php

namespace WPEventGenius\Common\Services;

use WPEventGenius\Common\States;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end Admin Notice Service
 * 
 * Provides a minimal, efficient way to show dismissable admin notices on the front-end
 * when configuration is needed or attention is required.
 */
class FrontEndAdminNoticeService {

	/**
	 * Constructor
	 */
	public function __construct() {}

	/**
	 * Initialize hooks
	 */
	public function init_hooks() {
		// Only show notices to users with appropriate capabilities
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		// Add notice container to footer
		add_action( 'wp_footer', array( $this, 'maybe_render_notices' ), 99 );
		
		// Add AJAX handler for dismissing notices
		add_action( 'wp_ajax_evge_frontend_notice_dismiss', array( $this, 'handle_notice_dismiss' ) );
		
		// Enqueue scripts and styles
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Add a notice that will be displayed immediately on the current page
	 * 
	 * @param string $id Unique notice ID
	 * @param string $type Notice type (warning, error, info, success)
	 * @param string $title Notice title
	 * @param string $message Notice message
	 * @param array $action Optional action button array with 'text' and 'url' keys
	 * @param bool $check_dismissed Whether to check if the notice has been dismissed (default: true)
	 * @return bool True if notice was added, false if dismissed or failed
	 */
	public static function add_notice( $id, $type, $title, $message, $action = null, $check_dismissed = true ) {
		// Only add notices for users with appropriate capabilities
		if ( ! current_user_can( 'edit_posts' ) ) {
			return false;
		}

		// Check if notice is dismissed (if requested)
		if ( $check_dismissed ) {
			$states = new States();
			$notice_state = $states->get_state( 'notices' );
			$dismissed = $notice_state['dismissed'] ?? array();
			
			if ( in_array( $id, $dismissed ) ) {
				return false;
			}
		}

		// Get existing dynamic notices
		$dynamic_notices = get_transient( 'evge_frontend_dynamic_notices' );
		if ( ! is_array( $dynamic_notices ) ) {
			$dynamic_notices = array();
		}

		// Create notice object
		$notice = array(
			'id'      => sanitize_key( $id ),
			'type'    => sanitize_key( $type ),
			'title'   => sanitize_text_field( $title ),
			'message' => sanitize_text_field( $message ),
			'dynamic' => true, // Mark as dynamic notice
		);

		if ( $action && is_array( $action ) ) {
			$notice['action'] = array(
				'text' => sanitize_text_field( $action['text'] ),
				'url'  => esc_url_raw( $action['url'] ),
			);
		}

		// Add to dynamic notices (avoid duplicates)
		$dynamic_notices[ $id ] = $notice;
		
		// Store in transient for current page load
		set_transient( 'evge_frontend_dynamic_notices', $dynamic_notices, 300 ); // 5 minutes

		return true;
	}

	/**
	 * Remove a dynamic notice
	 * 
	 * @param string $id Notice ID to remove
	 * @return bool True if notice was removed, false if not found
	 */
	public static function remove_notice( $id ) {
		$dynamic_notices = get_transient( 'evge_frontend_dynamic_notices' );
		if ( ! is_array( $dynamic_notices ) ) {
			return false;
		}

		if ( isset( $dynamic_notices[ $id ] ) ) {
			unset( $dynamic_notices[ $id ] );
			set_transient( 'evge_frontend_dynamic_notices', $dynamic_notices, 300 );
			return true;
		}

		return false;
	}

	/**
	 * Clear all dynamic notices
	 */
	public static function clear_notices() {
		delete_transient( 'evge_frontend_dynamic_notices' );
	}

	/**
	 * Dismiss notices by pattern (e.g., all notices starting with 'payment_gateway_config_')
	 * 
	 * @param string $pattern The pattern to match notice IDs against
	 * @return int Number of notices dismissed
	 */
	public static function dismiss_notices_by_pattern( $pattern ) {
		$states = new States();
		$dismissed_count = 0;

		// Dismiss static notices that match the pattern
		$notice_state = $states->get_state( 'notices' );
		$dismissed = $notice_state['dismissed'] ?? array();
		
		foreach ( $dismissed as $notice_id ) {
			if ( strpos( $notice_id, $pattern ) === 0 ) {
				$states->dismiss_notice( $notice_id );
				$dismissed_count++;
			}
		}

		// Dismiss dynamic notices that match the pattern
		$dynamic_notices = get_transient( 'evge_frontend_dynamic_notices' );
		if ( is_array( $dynamic_notices ) ) {
			foreach ( $dynamic_notices as $notice_id => $notice ) {
				if ( strpos( $notice_id, $pattern ) === 0 ) {
					$states->dismiss_notice( $notice_id );
					unset( $dynamic_notices[ $notice_id ] );
					$dismissed_count++;
				}
			}
			// Update the transient with the removed notices
			set_transient( 'evge_frontend_dynamic_notices', $dynamic_notices, 300 );
		}

		return $dismissed_count;
	}

	/**
	 * Enqueue required assets
	 */
	public function enqueue_assets() {
		// Only enqueue if user can see notices
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		// Enqueue minimal CSS
		EVGE()->style_service()->enqueue_style( 'evge-frontend-admin-notices' );

		// Enqueue minimal JavaScript
		EVGE()->script_service()->enqueue_script( 'evge-frontend-admin-notices' );
	}

	/**
	 * Maybe render notices if any are active
	 */
	public function maybe_render_notices() {
		$notices = $this->get_active_notices();
		
		if ( empty( $notices ) ) {
			return;
		}

		$this->render_notice_container( $notices );
	}

	/**
	 * Get active notices that should be displayed
	 * 
	 * @return array Array of notice objects
	 */
	private function get_active_notices() {
		$notices = array();

		// Get dynamic notices (added via add_notice method)
		$dynamic_notices = get_transient( 'evge_frontend_dynamic_notices' );
		if ( is_array( $dynamic_notices ) ) {
			foreach ( $dynamic_notices as $notice ) {
				$notices[] = $notice;
			}
		}

		return $notices;
	}

	/**
	 * Helper method to create a notice object
	 * 
	 * @param string $id Notice ID
	 * @param string $type Notice type (warning, error, info, success)
	 * @param string $title Notice title
	 * @param string $message Notice message
	 * @param array $action Optional action button
	 * @return array Notice object
	 */
	public static function create_notice( $id, $type, $title, $message, $action = null ) {
		$notice = array(
			'id'      => $id,
			'type'    => $type,
			'title'   => $title,
			'message' => $message,
		);

		if ( $action ) {
			$notice['action'] = $action;
		}

		return $notice;
	}

	/**
	 * Check if a notice has been dismissed
	 * 
	 * @param string $notice_id The notice ID to check
	 * @return bool True if dismissed, false otherwise
	 */
	private function is_notice_dismissed( $notice_id ) {
		$states = new States();
		$notice_state = $states->get_state( 'notices' );
		$dismissed = $notice_state['dismissed'] ?? array();
		
		return in_array( $notice_id, $dismissed );
	}

	/**
	 * Render the notice container
	 * 
	 * @param array $notices Array of notice objects
	 */
	private function render_notice_container( $notices ) {
		?>
		<div id="evge-frontend-admin-notices" class="evge-frontend-admin-notices">
			<?php foreach ( $notices as $notice ) : ?>
				<div class="evge-frontend-notice evge-frontend-notice-<?php echo esc_attr( $notice['type'] ); ?>" 
					 data-notice-id="<?php echo esc_attr( $notice['id'] ); ?>">
					
					<div class="evge-frontend-notice-content">
						<div class="evge-frontend-notice-top">
							<div class="evge-frontend-notice-icon">
								<?php echo $this->get_notice_icon( $notice['type'] ); ?>
							</div>
							
							<div class="evge-frontend-notice-text">
								<?php if ( ! empty( $notice['title'] ) ) : ?>
									<h4 class="evge-frontend-notice-title"><?php echo esc_html( $notice['title'] ); ?></h4>
								<?php endif; ?>
								
								<p class="evge-frontend-notice-message"><?php echo esc_html( $notice['message'] ); ?></p>

								<div class="evge-frontend-notice-actions">
									<?php if ( ! empty( $notice['action'] ) ) : ?>
										<a href="<?php echo esc_url( $notice['action']['url'] ); ?>" 
										   class="evge-frontend-notice-action">
											<?php echo esc_html( $notice['action']['text'] ); ?>
										</a>
									<?php endif; ?>
								</div>
							</div>
						</div>
						
						<button type="button" class="evge-frontend-notice-dismiss" 
								aria-label="<?php esc_attr_e( 'Dismiss notice', 'event-genius' ); ?>">
							<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M4.08331 4.08337L9.91665 9.91673M4.08331 9.91673L9.91665 4.08337" 
									  stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
						</button>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Get the appropriate icon for the notice type
	 * 
	 * @param string $type Notice type (warning, error, info, success)
	 * @return string SVG icon HTML
	 */
	private function get_notice_icon( $type ) {
		switch ( $type ) {
			case 'warning':
				return '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
					<circle cx="8" cy="8" r="8" fill="currentColor"/>
					<circle cx="8" cy="4" r="1" fill="white"/>
					<rect x="7" y="7" width="2" height="6" fill="white"/>
				</svg>';
			case 'error':
				return '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
					<circle cx="8" cy="8" r="8" fill="currentColor"/>
					<path d="M5 5L11 11M5 11L11 5" stroke="white" stroke-width="2" stroke-linecap="round"/>
				</svg>';
			case 'success':
				return '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
					<circle cx="8" cy="8" r="8" fill="currentColor"/>
					<path d="M6 8L7.5 9.5L10 7" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>';
			default:
				return '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
					<circle cx="8" cy="8" r="8" fill="currentColor"/>
					<circle cx="8" cy="6" r="1" fill="white"/>
					<rect x="7" y="8" width="2" height="4" fill="white"/>
				</svg>';
		}
	}

	/**
	 * Handle AJAX notice dismiss
	 */
	public function handle_notice_dismiss() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'evge_frontend_notice_dismiss' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		// Check permissions
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		// Get notice ID
		$notice_id = isset( $_POST['notice_id'] ) ? sanitize_key( $_POST['notice_id'] ) : '';
		if ( empty( $notice_id ) ) {
			wp_send_json_error( 'Missing notice ID' );
		}

		// Remove from dynamic notices if it's a dynamic notice
		$dynamic_notices = get_transient( 'evge_frontend_dynamic_notices' );
		if ( is_array( $dynamic_notices ) && isset( $dynamic_notices[ $notice_id ] ) ) {
			unset( $dynamic_notices[ $notice_id ] );
			set_transient( 'evge_frontend_dynamic_notices', $dynamic_notices, 300 );
		}

		// Dismiss the notice permanently
		$states = new States();
		$states->dismiss_notice( $notice_id );

		wp_send_json_success( array( 'notice_id' => $notice_id ) );
	}
} 