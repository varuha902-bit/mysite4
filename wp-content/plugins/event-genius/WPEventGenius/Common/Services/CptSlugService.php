<?php

namespace WPEventGenius\Common\Services;

use WPEventGenius\Common\States;
use WPEventGenius\Common\Utils\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages custom post type and taxonomy URL slugs.
 * Handles default slugs, conflict detection on activation, and user-defined slugs from settings.
 */
class CptSlugService {

	const OPTION_KEY_PENDING_CHECK = 'evge_pending_slug_check';
	const OPTION_KEY_AUTO_PREFIXED  = 'evge_slug_auto_prefixed';
	const PREFIX_CONFLICT          = 'evge-';

	/** State key used in evge_states to flag that rewrite rules should be flushed on next init. */
	const STATE_PENDING_REWRITE_FLUSH = 'pending_rewrite_flush';

	/** State key: whether we ever auto-prefixed slugs (so the dashboard notice can be shown). */
	const STATE_SLUG_AUTO_PREFIXED_DONE = 'slug_auto_prefixed_done';

	/**
	 * Default slugs (keys used in settings and get_slug).
	 * Only slugs for CPTs/taxonomies that have a public rewrite are here.
	 *
	 * @return array<string, string>
	 */
	public static function get_default_slugs() {
		$defaults = array(
			'event_slug'          => 'event',
			'event_archive_slug'  => 'events',
			'venue_slug'          => 'venue',
			'venue_archive_slug'  => 'venues',
			'organizer_slug'      => 'organizer',
			'organizer_archive_slug' => 'organizers',
			'event_category_slug' => 'event-category',
			'event_tag_slug'      => 'event-tag',
			'series_slug'         => 'series',
		);
		return $defaults;
	}

	/**
	 * Slug keys that are visible for the free tier (no series).
	 *
	 * @return array<string>
	 */
	public static function get_slug_keys_for_free_tier() {
		return array(
			'event_slug',
			'event_archive_slug',
			'venue_slug',
			'venue_archive_slug',
			'organizer_slug',
			'organizer_archive_slug',
			'event_category_slug',
			'event_tag_slug',
		);
	}

	/**
	 * Slug keys that are visible for Pro tier (includes series).
	 *
	 * @return array<string>
	 */
	public static function get_slug_keys_for_current_tier() {
		$keys = self::get_slug_keys_for_free_tier();
		if ( function_exists( 'evge_is_pro_tier' ) && evge_is_pro_tier() ) {
			$keys[] = 'series_slug';
		}
		return $keys;
	}

	/**
	 * Get the effective slug for a given key (custom from settings or default).
	 *
	 * @param string $key One of the keys from get_default_slugs().
	 * @return string Sanitized slug for use in rewrite.
	 */
	public static function get_slug( $key ) {
		$defaults = self::get_default_slugs();
		if ( ! isset( $defaults[ $key ] ) ) {
			return '';
		}
		$custom = Settings::get( 'cpt_slugs' );
		if ( ! is_array( $custom ) ) {
			$custom = array();
		}
		$slug = isset( $custom[ $key ] ) && is_string( $custom[ $key ] ) && $custom[ $key ] !== ''
			? $custom[ $key ]
			: $defaults[ $key ];
		return self::sanitize_slug( $slug );
	}

	/**
	 * Sanitize a slug for use in rewrite (lowercase, no spaces, safe for URLs).
	 *
	 * @param string $slug Raw slug.
	 * @return string Sanitized slug.
	 */
	public static function sanitize_slug( $slug ) {
		$slug = sanitize_title( $slug );
		return $slug !== '' ? $slug : 'event';
	}

	public function __construct() {
	}

	public function init_hooks() {
		// Run before CPT registration so saved/conflict slugs are available.
		add_action( 'init', array( $this, 'maybe_run_pending_slug_check' ), 1 );
		// Flush rewrite rules when evge_settings is updated and cpt_slugs changed.
		add_action( 'update_option_evge_settings', array( $this, 'maybe_flush_rewrite_on_slug_change' ), 10, 3 );
		// Flush rewrite rules on next init when slugs changed or after auto-prefix (CPTs then use new slugs).
		add_action( 'init', array( $this, 'maybe_flush_after_slug_change_or_auto_prefix' ), 999 );
	}

	/**
	 * If activation set the pending check flag, run conflict detection and save prefixed slugs.
	 */
	public function maybe_run_pending_slug_check() {
		if ( ! get_option( self::OPTION_KEY_PENDING_CHECK, false ) ) {
			return;
		}
		delete_option( self::OPTION_KEY_PENDING_CHECK );
		$used_slugs = $this->get_used_slugs();
		$defaults   = self::get_default_slugs();
		$custom     = get_option( 'evge_settings', array() );
		if ( ! is_array( $custom ) ) {
			$custom = array();
		}
		if ( ! isset( $custom['cpt_slugs'] ) || ! is_array( $custom['cpt_slugs'] ) ) {
			$custom['cpt_slugs'] = array();
		}
		$any_prefixed = false;
		foreach ( $defaults as $key => $default_slug ) {
			if ( in_array( $default_slug, $used_slugs, true ) ) {
				$prefixed = self::PREFIX_CONFLICT . $default_slug;
				$custom['cpt_slugs'][ $key ] = $prefixed;
				$any_prefixed = true;
			}
		}
		if ( $any_prefixed ) {
			update_option( 'evge_settings', $custom );
			update_option( self::OPTION_KEY_AUTO_PREFIXED, true );
			self::set_slug_auto_prefixed_done();
			// Flush will run at init 999 via maybe_flush_after_auto_prefix.
		}
	}

	/**
	 * Collect all URL slugs currently in use by post types, taxonomies, and pages.
	 *
	 * @return array<string> List of slugs (e.g. 'event', 'venue', 'tribe_events').
	 */
	protected function get_used_slugs() {
		$slugs = array();
		// Post types (excluding our own so we don't conflict with ourselves on re-runs).
		$our_post_types = array( 'evge_event', 'evge_venue', 'evge_organizer', 'evge_series' );
		$post_types     = get_post_types( array( 'public' => true ), 'objects' );
		foreach ( $post_types as $pt ) {
			if ( in_array( $pt->name, $our_post_types, true ) ) {
				continue;
			}
			if ( ! empty( $pt->rewrite ) && is_array( $pt->rewrite ) && isset( $pt->rewrite['slug'] ) ) {
				$slugs[] = $pt->rewrite['slug'];
			}
			if ( ! empty( $pt->has_archive ) && is_string( $pt->has_archive ) ) {
				$slugs[] = $pt->has_archive;
			}
		}
		// Taxonomies (excluding our own).
		$our_taxonomies = array( 'evge_event_cat', 'evge_event_tag' );
		$taxonomies     = get_taxonomies( array( 'public' => true ), 'objects' );
		foreach ( $taxonomies as $tax ) {
			if ( in_array( $tax->name, $our_taxonomies, true ) ) {
				continue;
			}
			if ( ! empty( $tax->rewrite ) && is_array( $tax->rewrite ) && isset( $tax->rewrite['slug'] ) ) {
				$slugs[] = $tax->rewrite['slug'];
			}
		}
		// Reserved / common WordPress and page slugs (avoid overwriting).
		$reserved = array( 'feed', 'embed', 'trackback', 'attachment', 'page', 'paged', 'comments', 'author', 'category', 'tag', 'archives' );
		$slugs    = array_merge( $slugs, $reserved );
		// Pages with top-level slug (post_name) that could conflict.
		$pages = get_posts( array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'post_parent'    => 0,
			'fields'         => 'ids',
		) );
		foreach ( $pages as $page_id ) {
			$page = get_post( $page_id );
			if ( $page && isset( $page->post_name ) && $page->post_name !== '' ) {
				$slugs[] = $page->post_name;
			}
		}
		return array_unique( $slugs );
	}

	/**
	 * When evge_settings is updated, if cpt_slugs changed then flush rewrite rules.
	 *
	 * @param mixed  $old_value Old value of the option.
	 * @param mixed  $value     New value of the option.
	 * @param string $option    Option name.
	 */
	public function maybe_flush_rewrite_on_slug_change( $old_value, $value, $option ) {
		$old_slugs = is_array( $old_value ) && isset( $old_value['cpt_slugs'] ) ? $old_value['cpt_slugs'] : array();
		$new_slugs = is_array( $value ) && isset( $value['cpt_slugs'] ) ? $value['cpt_slugs'] : array();


		if ( ! is_array( $old_slugs ) ) {
			$old_slugs = array();
		}
		if ( ! is_array( $new_slugs ) ) {
			$new_slugs = array();
		}
		if ( $this->cpt_slugs_changed( $old_slugs, $new_slugs ) ) {
			$this->set_pending_rewrite_flush();
		}
	}

	/**
	 * Backup flush when any option is updated; only act when option is evge_settings.
	 *
	 * @param string $option    Option name.
	 * @param mixed  $old_value Old value.
	 * @param mixed  $value     New value.
	 */
	public function maybe_flush_rewrite_on_updated_option( $option, $old_value, $value ) {
		if ( $option !== 'evge_settings' ) {
			return;
		}
		$old_slugs = is_array( $old_value ) && isset( $old_value['cpt_slugs'] ) ? $old_value['cpt_slugs'] : array();
		$new_slugs = is_array( $value ) && isset( $value['cpt_slugs'] ) ? $value['cpt_slugs'] : array();
		if ( ! is_array( $old_slugs ) ) {
			$old_slugs = array();
		}
		if ( ! is_array( $new_slugs ) ) {
			$new_slugs = array();
		}
		if ( $this->cpt_slugs_changed( $old_slugs, $new_slugs ) ) {
			$this->set_pending_rewrite_flush();
		}
	}

	/**
	 * Flag that rewrite rules must be flushed on the next request (CPTs will then use new slugs).
	 */
	protected function set_pending_rewrite_flush() {
		$states = new States();
		$states->set_state( self::STATE_PENDING_REWRITE_FLUSH, true );
	}

	/**
	 * Compare two cpt_slugs arrays (order-independent, normalizes for comparison).
	 *
	 * @param array<string, string> $old_slugs
	 * @param array<string, string> $new_slugs
	 * @return bool True if any slug value changed.
	 */
	protected function cpt_slugs_changed( $old_slugs, $new_slugs ) {
		$keys = array_unique( array_merge( array_keys( $old_slugs ), array_keys( $new_slugs ) ) );
		foreach ( $keys as $key ) {
			$old_val = isset( $old_slugs[ $key ] ) ? (string) $old_slugs[ $key ] : '';
			$new_val = isset( $new_slugs[ $key ] ) ? (string) $new_slugs[ $key ] : '';
			if ( $old_val !== $new_val ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * On next init (after CPTs are registered with new slugs): flush if slug change or auto-prefix set the flag.
	 */
	public function maybe_flush_after_slug_change_or_auto_prefix() {
		$states = new States();
		$pending_flush = $states->get_state( self::STATE_PENDING_REWRITE_FLUSH );
		if ( $pending_flush ) {
			flush_rewrite_rules();
			$states->set_state( self::STATE_PENDING_REWRITE_FLUSH, false );
		}
		if ( get_option( self::OPTION_KEY_AUTO_PREFIXED, false ) ) {
			flush_rewrite_rules();
			delete_option( self::OPTION_KEY_AUTO_PREFIXED );
		}
	}

	/**
	 * Whether the plugin ever auto-prefixed slugs (for showing the notice once).
	 *
	 * @return bool
	 */
	public static function was_slug_auto_prefixed() {
		$states = new States();
		return (bool) $states->get_state( self::STATE_SLUG_AUTO_PREFIXED_DONE );
	}

	/**
	 * Mark that we auto-prefixed slugs so the dashboard notice can be shown.
	 */
	public static function set_slug_auto_prefixed_done() {
		$states = new States();
		$states->set_state( self::STATE_SLUG_AUTO_PREFIXED_DONE, true );
	}
}
