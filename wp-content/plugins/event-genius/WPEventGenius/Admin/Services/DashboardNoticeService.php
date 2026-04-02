<?php

namespace WPEventGenius\Admin\Services;

use WPEventGenius\Admin\Notice;
use WPEventGenius\Common\Queries\EventQuery;
use WPEventGenius\Common\States;
use WPEventGenius\Admin\BaseAdminPage;
use WPEventGenius\Common\Services\CptSlugService;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class DashboardNoticeService {
	public function __construct() {
	}

	public function init_hooks() {
		add_action( 'admin_init', array( $this, 'maybe_dismiss' ) );
		add_action( 'admin_print_scripts', array( $this, 'admin_remove_unrelated_notices' ) );
		add_action( 'evge_dashboard_top', array( $this, 'maybe_notice' ) );


		add_action( 'wp_ajax_evge_notice_action', array( $this, 'notice_action' ) );
	}

	public function maybe_notice() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$notices = array();
		$slug_notice = $this->dashboard_notice_slug_auto_prefixed();
		if ( $slug_notice ) {
			$notices[] = $slug_notice;
		}
		$rating_notice = $this->dashboard_notice_rating();
		if ( $rating_notice ) {
			$notices[] = $rating_notice;
		}
		if ( ! empty( $notices ) ) {
			include_once EVGE_ADMIN_TEMPLATE_PATH . 'evge/partials/dashboard/notice.php';
		}
	}

	/**
	 * Notice shown when we auto-prefixed slugs due to conflicts on activation.
	 *
	 * @return Notice|null
	 */
	public function dashboard_notice_slug_auto_prefixed() {
		if ( ! CptSlugService::was_slug_auto_prefixed() ) {
			return null;
		}
		if ( $this->is_notice_dismissed( 'slug_auto_prefixed' ) ) {
			return null;
		}
		$settings_url = admin_url( 'admin.php?page=evge-settings&tab=general' );
		return new Notice( array(
			'id'        => 'slug_auto_prefixed',
			'image_url' => EVGE_PLUGIN_URL . 'assets/images/admin/genius.png',
			'heading'   => __( 'Event URL slugs changed', 'event-genius' ),
			'content'   => __( 'We detected that you might have another events plugin installed that uses the same URL slugs. We added the "evge-" prefix to avoid conflicts. You can change or remove these in Settings if you prefer.', 'event-genius' ),
			'primary_cta' => array(
				'text' => __( 'Go to Settings', 'event-genius' ),
				'url'  => $settings_url,
			),
			'other_ctas' => array(),
		) );
	}

	public function dashboard_notice_rating() {
		/* TODO: Not showing new user rating as of  */
		return;


		/* TODO: If person does not have access to events */
		if ( false ) {
			return;
		}

		/* TODO: If person does not seem to be using the plugin, return */
		if ( $this->rating_notice_is_active() === false ) {
			return;
		}

		$notice = new Notice( array(
			'id' => 'rating',
			'is_two_step_notice' => true,
			'image_url' => EVGE_PLUGIN_URL . 'assets/images/admin/genius.png',
			'heading' => __( 'Enjoying WP Event Genius?', 'event-genius' ),
			'heading_step_2' => __( "That's Great!", 'event-genius' ),
			'content_step_2' => __( 'We are so happy to hear that. Would you mind leaving us a review on WordPress.org?', 'event-genius' ),
			'primary_cta_step_2' => array(
				'text' => __( "Yes I'd love to!", 'event-genius' ),
				'url' => 'https://wordpress.org/support/plugin/event-genius/reviews/#new-post',
			),
			'other_ctas' => array(
				array(
					'text' => __( 'Yes', 'event-genius' ),
					'action' => 'step_trigger',
					'url' => '',
				),
				array(
					'text' => __( 'No', 'event-genius' ),
					'action' => 'dismiss',
					'redirect' => 1,
					'url' => 'https://wpeventgenius.com/support/feedback?utm_campaign=evge-free&utm_source=dashboard&utm_medium=rating-notice&utm_content=no',
				)
			),
			'other_ctas_step_2' => array(
				array(
					'text' => __( "I already have", 'event-genius' ),
					'action' => 'dismiss',
					'url' => '',
				),
				array(
					'text' => __( "No thanks", 'event-genius' ),
					'action' => 'dismiss',
					'url' => '',
				),
				array(
					'text' => __( "Ask me later", 'event-genius' ),
					'action' => 'later',
					'url' => '',
				),
			),
		) );

		return $notice;
	}

	public function notice_action() {
		check_ajax_referer( 'evge-admin', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( 'no permission' );
		}

		$notice_id = isset( $_POST['id'] ) ? sanitize_key( $_POST['id'] ) : '';
		$action = isset( $_POST['notice_action'] ) ? sanitize_key( $_POST['notice_action'] ) : '';

		if ( empty( $notice_id ) || empty( $action ) ) {
			wp_send_json_error( array( 'message' => 'Invalid request' ) );
		}

		if ( $action === 'dismiss' ) {
			$states = new States();
			$states->dismiss_notice( $notice_id );
		} elseif ( $action === 'later' ) {
			set_transient( 'evge_' . $notice_id . '_delay', true, WEEK_IN_SECONDS );
		} elseif ( $action === 'close' ) {
			if ( $notice_id !== 'rating' ) {
				$states = new States();
				$states->dismiss_notice( $notice_id );
			} else {
				set_transient( 'evge_' . $notice_id . '_delay', true, WEEK_IN_SECONDS );
			}
		}


		wp_send_json_success( array( 'id' => $notice_id ) );

	}

	public function rating_notice_is_active() {
		// Check if notice is dismissed
		if ( $this->is_notice_dismissed( 'rating' ) ) {
			return false;
		}

		$states = new States();
		if ( absint( $states->get_state( 'first_install' ) ) > strtotime( '-2 weeks' ) ) {
			return false;
		}

		if ( get_transient( 'evge_rating_delay' ) ) {
			return false;
		}

		$event_query = new EventQuery( array( 'qtype' => 'all', 'posts_per_page' => 1 ) );
		$event_query->add_wp_query();
		$events = $event_query->get_events();
		if ( empty( $events ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if a specific notice has been dismissed
	 *
	 * @param string $notice_id The ID of the notice to check
	 * @return bool True if notice is dismissed, false otherwise
	 */
	public function is_notice_dismissed( $notice_id ) {
		$states = new States();
		$notice_state = $states->get_state( 'notices' );
		$dismissed = $notice_state['dismissed'] ?? array();
		
		return in_array( $notice_id, $dismissed );
	}

	public function maybe_dismiss() {
		if ( empty( $_GET['evge_notice_dismiss'] ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		if ( ! isset( $_GET['evge_notice_nonce'] ) || ! wp_verify_nonce( sanitize_text_field(wp_unslash($_GET['evge_notice_nonce'])), 'evge_notice_dismiss' ) ) {
			return;
		}

		$states = new States();
		$states->dismiss_notice( sanitize_key( wp_unslash( $_GET['evge_notice_dismiss'] ) ) );
	}

	public function dashboard_notice_smtp() {
		// Don't show on non-email pages

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $tab !== 'evge-registrations' ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $tab !== 'forms' ) {
			return false;
		}

		// Check if notice was dismissed
		if ( $this->is_notice_dismissed( 'smtp_recommendation' ) ) {
			return false;
		}

		// Check if any SMTP plugin is active
		$smtp_plugins = BaseAdminPage::detect_smtp_plugins();
		if ( ! empty( $smtp_plugins ) ) {
			return false;
		}

		$dismiss_url = add_query_arg( array(
			'evge_notice_dismiss' => 'smtp_recommendation',
			'evge_notice_nonce' => wp_create_nonce( 'evge_notice_dismiss' ),
		) );

		return new Notice( array(
			'id' => 'smtp_recommendation',
			'is_two_step_notice' => false,
			'image_url' => EVGE_PLUGIN_URL . 'assets/images/admin/genius.png',
			'heading' => __( 'Improve Your Email Deliverability', 'event-genius' ),
			'content' => __( "We noticed you haven't set up an SMTP plugin yet. To ensure your event emails reach attendees reliably, we recommend using WP Mail SMTP and a third-party SMTP service. This will help prevent your emails from going to spam folders and improve delivery rates.", 'event-genius' ),
			'primary_cta' => array(
				'text' => __( 'Install WP Mail SMTP', 'event-genius' ),
				'url' => admin_url( 'plugin-install.php?s=wp+mail+smtp&tab=search&type=term' ),
			),
			'other_ctas' => array(
				array(
					'text' => __( 'Learn More', 'event-genius' ),
					'action' => '',
					'url' => 'https://wpeventgenius.com/docs/troubleshooting-email-issues-confirmation-and-notification/?utm_campaign=evge-free&utm_source=form-builder&utm_medium=smtp-notice&utm_content=learn-more',
					'target' => '_blank',
				),
				array(
					'text' => __( 'Dismiss', 'event-genius' ),
					'action' => 'dismiss',
					'url' => $dismiss_url,
				)
			)
		) );
	}

	public function admin_remove_unrelated_notices() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['page'] ) ) {
			return;
		}

		// phpcs:ignore
		if ( strpos( $_GET['page'], 'evge-' ) !== 0 ) {
			return;
		}

		// Extra banned classes and callbacks from third-party plugins.
		$blacklist = array(
			'classes'   => array(),
			'callbacks' => array(),
		);

		global $wp_filter;
		foreach ( array( 'user_admin_notices', 'admin_notices', 'all_admin_notices' ) as $notices_type ) {
			if ( empty( $wp_filter[ $notices_type ]->callbacks ) || ! is_array( $wp_filter[ $notices_type ]->callbacks ) ) {
				continue;
			}
			foreach ( $wp_filter[ $notices_type ]->callbacks as $priority => $hooks ) {
				foreach ( $hooks as $name => $arr ) {
					if ( is_object( $arr['function'] ) && $arr['function'] instanceof Closure ) {
						unset( $wp_filter[ $notices_type ]->callbacks[ $priority ][ $name ] );
						continue;
					}
					
					$class = '';
					if ( is_array( $arr['function'] ) && ! empty( $arr['function'][0] ) && is_object( $arr['function'][0] ) ) {
						$class = strtolower( get_class( $arr['function'][0] ) );
					}
					
					if (
						! empty( $class ) &&
						strpos( $class, 'evge' ) !== false &&
						! in_array( $class, $blacklist['classes'], true )
					) {
						continue;
					}
					if (
						! empty( $name ) && (
							strpos( $name, 'evge' ) === false ||
							in_array( $class, $blacklist['classes'], true ) ||
							in_array( $name, $blacklist['callbacks'], true )
						)
					) {
						unset( $wp_filter[ $notices_type ]->callbacks[ $priority ][ $name ] );
					}
				}
			}
		}
	}
}