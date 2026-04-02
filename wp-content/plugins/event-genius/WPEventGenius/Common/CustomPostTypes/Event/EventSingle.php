<?php
namespace WPEventGenius\Common\CustomPostTypes\Event;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

use WPEventGenius\Common\Utils\Templater;
use WPEventGenius\Common\Utils\SEODetector;
use WPEventGenius\Common\Series\EventSeriesRepository;
use WPEventGenius\Common\Database;
use WPEventGenius\Common\Utils\Logger\RecurrenceLogger;

class EventSingle {

	public function __construct(){

	}

	public function init_custom_hooks() {
		add_filter( 'single_template', array( $this, 'maybe_alter_template' ), 10, 1 );
		add_filter( 'the_content', array( $this, 'maybe_alter_content' ), 10, 1 );
		add_action( 'evge_before_single_event_content', array( $this, 'before_event_content' ) );
		add_action( 'evge_after_single_event_content', array( $this, 'after_event_content' ) );

		add_action('wp_enqueue_scripts', array($this, 'enqueue'));

		// SEO: Canonical URL handling for recurring events
		add_action( 'template_redirect', array( $this, 'maybe_remove_default_canonical' ), 10 );

		// SEO: Validate recurring event instances and redirect invalid ones
		add_action( 'wp', array( $this, 'validate_recurring_event_instance' ), 10 );
	}


	public function is_cpt_page() {
		return is_singular( EVGE_EVENT_POST_TYPE );
	}

	public function get_template_type() {
		return get_option('evge_settings')['event_template'] ?? 'default';
	}

	public function maybe_alter_template( $template ) {
		if ( ! $this->is_cpt_page() ) {
			return $template;
		}

		// For block themes, always return the default template
		if ( wp_is_block_theme() ) {
			return $template;
		}

		$template_type = $this->get_template_type();

		// For custom templates, check theme directory first
		if ( $template_type === 'custom' ) {
			$theme_template = locate_template( array(
				'event-genius/single-event.php',
				'event-genius/events/single/single-event.php'
			));
			if ( $theme_template ) {
				return $theme_template;
			}
		}

		// For default template or if custom template not found, use plugin template
		if ( $template_type === 'default' || ($template_type === 'custom' && !$theme_template) ) {
			$template_path = EVGE()->template_manager()->locate_template('events/single/single-event.php');
			if ($template_path) {
				return $template_path;
			}
			// Fallback to plugin template
			return trailingslashit( EVGE_PLUGIN_PATH ) . 'templates/event-genius/events/single/single-event.php';
		}

		// For theme template or fallback, use default template
		return $template;
	}

	public function maybe_alter_content( $content ) {
		if ( ! $this->is_cpt_page() ) {
			return $content;
		}

		// For block themes, always return the default content
		if ( wp_is_block_theme() ) {
			return $content;
		}

		// Request modal since event single pages use modal triggers
		EVGE()->modal_service()->request_modal();

		$template_type = $this->get_template_type();
		
		// For theme template type, modify content to ensure proper display
		if ( $template_type === 'theme' ) {
			
			ob_start();
			$templater = new Templater();
    		?>

<div class="evge<?php echo esc_attr( $templater->evge_classes() ); ?>" data-evge-type="single-event">
	<div class="evge-content evge-template-theme">
		<div id="evge-single-main" class="evge-events-single-main<?php echo esc_attr( $templater->classes() ); ?>">
			<?php
	
			$post_id = get_the_ID();
			$this->before_event_content($post_id);
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $content;
			$this->after_event_content($post_id);
			?>
        </div>
    </div>
</div>
<?php
			return ob_get_clean();
		}

		return $content;
	}

	public function before_event_content( $event_id = 0 ) {
		$template_path = EVGE()->template_manager()->locate_template('events/single/partials/content-before.php');
		if ($template_path) {
			include $template_path;
			return;
		}
	}

	public function after_event_content( $event_id = 0 ) {
		$template_path = EVGE()->template_manager()->locate_template('events/single/partials/content-after.php');
		if ($template_path) {
			include $template_path;
			return;
		}
	}

	public function maybe_alter_query( $query ) {
		if ( ! $this->is_cpt_page() ) {
			return $query;
		}
		return $query;
	}

	public function enqueue( $screen ) {
		if ( ! $this->is_cpt_page() || ! wp_is_block_theme() ) {
			return;
		}
		
		// Enqueue styles
		EVGE()->style_service()->enqueue_style('evge_common');
		EVGE()->style_service()->enqueue_style('evge_single_post');
		EVGE()->style_service()->enqueue_style('evge_registration_form');
		EVGE()->style_service()->enqueue_style('evge_attendee_list');

		// Enqueue scripts
		EVGE()->script_service()->enqueue_script('evge_common');
		EVGE()->script_service()->enqueue_script('evge_event');
		EVGE()->script_service()->enqueue_script('evge_registration_form');
		EVGE()->script_service()->enqueue_script('evge_single_post');
		EVGE()->script_service()->enqueue_script('evge_attendee_list');
		
		// Request modal since event single pages use modal triggers (for registration forms, etc.)
		EVGE()->modal_service()->request_modal();
	}

	public function maybe_add_modal() {
		if ( $this->is_cpt_page() ) {
			$template_path = EVGE()->template_manager()->locate_template('common/modal.php');
			if ($template_path) {
				include $template_path;
			}
		}
	}

	/**
	 * Remove default WordPress canonical for recurring event instances
	 * 
	 * @return void
	 */
	public function maybe_remove_default_canonical() {
		// CRITICAL: Exclude feeds
		if ( is_feed() ) {
			return;
		}

		if ( ! $this->is_cpt_page() ) {
			return;
		}

		$event_id = get_the_ID();
		$is_recurrence = get_post_meta( $event_id, 'evge_is_recurrence', true );

		// Only handle recurrences, not template events
		if ( ! $is_recurrence ) {
			return;
		}

		$seo_plugin = SEODetector::get_active_seo_plugin();

		// Handle based on active SEO plugin
		if ( $seo_plugin === 'yoast' ) {
			// Yoast handles canonical differently - filter their canonical
			add_filter( 'wpseo_canonical', array( $this, 'filter_yoast_canonical' ), 10, 1 );
			return; // Don't remove default, let Yoast handle it
		}

		if ( $seo_plugin === 'rank_math' ) {
			// Rank Math may override - check their filters
			add_filter( 'rank_math/frontend/canonical', array( $this, 'filter_rank_math_canonical' ), 10, 1 );
			return;
		}

		// No SEO plugin or WordPress default - use our custom canonical
		// CRITICAL: Check if rel_canonical exists before removing
		if ( has_action( 'wp_head', 'rel_canonical' ) ) {
			remove_action( 'wp_head', 'rel_canonical' );
			add_action( 'wp_head', array( $this, 'output_recurring_event_canonical' ), 5 );
		}
	}

	/**
	 * Output canonical URL for recurring event instance
	 * 
	 * @return void
	 */
	public function output_recurring_event_canonical() {
		if ( ! $this->is_cpt_page() ) {
			return;
		}

		$event_id = get_the_ID();
		$is_recurrence = get_post_meta( $event_id, 'evge_is_recurrence', true );

		if ( ! $is_recurrence ) {
			return;
		}

		// Get the permalink for this specific instance
		// Use get_post_permalink() to preserve context
		$canonical_url = get_post_permalink( $event_id );

		/**
		 * Filter the canonical URL for recurring event instances
		 * 
		 * @param string $canonical_url The canonical URL
		 * @param int    $event_id      The event ID
		 * @return string Modified canonical URL
		 */
		$canonical_url = apply_filters( 'evge_recurring_event_canonical_url', $canonical_url, $event_id );

		echo '<link rel="canonical" href="' . esc_url( $canonical_url ) . '" />' . "\n";
	}

	/**
	 * Filter Yoast SEO canonical URL for recurring event instances
	 * 
	 * @param string $canonical The canonical URL from Yoast
	 * @return string Modified canonical URL
	 */
	public function filter_yoast_canonical( $canonical ) {
		if ( ! $this->is_cpt_page() ) {
			return $canonical;
		}

		$event_id = get_the_ID();
		$is_recurrence = get_post_meta( $event_id, 'evge_is_recurrence', true );

		if ( $is_recurrence ) {
			$canonical_url = get_post_permalink( $event_id );

			/**
			 * Filter the canonical URL for recurring event instances (Yoast)
			 * 
			 * @param string $canonical_url The canonical URL
			 * @param int    $event_id      The event ID
			 * @return string Modified canonical URL
			 */
			return apply_filters( 'evge_recurring_event_canonical_url', $canonical_url, $event_id );
		}

		return $canonical;
	}

	/**
	 * Filter Rank Math canonical URL for recurring event instances
	 * 
	 * @param string $canonical The canonical URL from Rank Math
	 * @return string Modified canonical URL
	 */
	public function filter_rank_math_canonical( $canonical ) {
		if ( ! $this->is_cpt_page() ) {
			return $canonical;
		}

		$event_id = get_the_ID();
		$is_recurrence = get_post_meta( $event_id, 'evge_is_recurrence', true );

		if ( $is_recurrence ) {
			$canonical_url = get_post_permalink( $event_id );

			/**
			 * Filter the canonical URL for recurring event instances (Rank Math)
			 * 
			 * @param string $canonical_url The canonical URL
			 * @param int    $event_id      The event ID
			 * @return string Modified canonical URL
			 */
			return apply_filters( 'evge_recurring_event_canonical_url', $canonical_url, $event_id );
		}

		return $canonical;
	}

	/**
	 * Validate recurring event instance and redirect if invalid
	 * 
	 * @return void
	 */
	public function validate_recurring_event_instance() {
		if ( ! $this->is_cpt_page() ) {
			return;
		}

		$event_id = get_the_ID();
		$is_recurrence = get_post_meta( $event_id, 'evge_is_recurrence', true );

		// Not a recurrence, no validation needed
		if ( ! $is_recurrence ) {
			return;
		}

		// Get the template event ID
		$template_id = $is_recurrence; // evge_is_recurrence stores template ID
		$template_event = get_post( $template_id );

		if ( ! $template_event ) {
			// Template doesn't exist - redirect to archive
			$redirect_url = get_post_type_archive_link( EVGE_EVENT_POST_TYPE );

			if ( ! $redirect_url ) {
				$redirect_url = home_url( '/' );
			}

			/**
			 * Filter the redirect URL when template event doesn't exist
			 * 
			 * @param string $redirect_url The redirect URL
			 * @param int    $event_id     The invalid event ID
			 * @param int|null $template_id The template ID (null in this case)
			 * @return string Modified redirect URL
			 */
			$redirect_url = apply_filters( 'evge_invalid_recurrence_redirect_url', $redirect_url, $event_id, null );

			wp_safe_redirect( $redirect_url, 301 );
			exit;
		}

		// Verify this instance exists in the series
		$repository = new EventSeriesRepository( new Database() );
		$series_id = $repository->get_series_id_for_template( $template_id );

		if ( ! $series_id ) {
			// No series found - redirect to template
			$redirect_url = get_permalink( $template_id );

			/**
			 * Filter the redirect URL when no series is found
			 * 
			 * @param string $redirect_url The redirect URL
			 * @param int    $event_id     The invalid event ID
			 * @param int    $template_id  The template event ID
			 * @return string Modified redirect URL
			 */
			$redirect_url = apply_filters( 'evge_invalid_recurrence_redirect_url', $redirect_url, $event_id, $template_id );

			wp_safe_redirect( $redirect_url, 301 );
			exit;
		}

		$series_events = $repository->get_series_events( $series_id );
		if ( ! in_array( $event_id, $series_events, true ) ) {
			// This instance doesn't exist in the series - redirect to template
			$redirect_url = get_permalink( $template_id );

			/**
			 * Filter to allow disabling redirects for invalid instances
			 * 
			 * @param bool $confirm_redirect Whether to proceed with redirect
			 * @param int  $event_id        The invalid event ID
			 * @param int  $template_id     The template event ID
			 * @return bool Modified redirect confirmation
			 */
			$confirm_redirect = apply_filters( 'evge_invalid_recurrence_redirect', true, $event_id, $template_id );

			if ( $confirm_redirect ) {
				/**
				 * Action hook fired when redirecting invalid recurrence instance
				 * 
				 * @param int $event_id    The invalid event ID
				 * @param int $template_id The template event ID
				 */
				do_action( 'evge_invalid_recurrence_redirect', $event_id, $template_id );

				// Log for debugging (if logger available)
				if ( class_exists( 'WPEventGenius\Common\Utils\Logger\RecurrenceLogger' ) ) {
					RecurrenceLogger::log(
						sprintf(
							'Invalid recurrence instance requested (ID: %d), redirecting to template (ID: %d)',
							$event_id,
							$template_id
						),
						array(
							'event_id'    => $event_id,
							'template_id' => $template_id,
							'series_id'   => $series_id,
						)
					);
				}

				/**
				 * Filter the redirect URL for invalid instances
				 * 
				 * @param string $redirect_url The redirect URL
				 * @param int    $event_id     The invalid event ID
				 * @param int    $template_id  The template event ID
				 * @return string Modified redirect URL
				 */
				$redirect_url = apply_filters( 'evge_invalid_recurrence_redirect_url', $redirect_url, $event_id, $template_id );

				wp_safe_redirect( $redirect_url, 301 );
				exit;
			}
		}
	}

}