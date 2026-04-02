<?php

namespace WPEventGenius\BlockTheme\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Service to register block theme templates and blocks for Event Genius post types
 */
class BlockThemeTemplateService {

	/**
	 * Initialize the service
	 */
	/**
	 * Plugin namespace for templates
	 *
	 * @var string
	 */
	private $namespace = 'event-genius';

	/**
	 * Initialize the service
	 */
	public function init() {
		// Only register templates and blocks for block themes
		if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
			return;
		}

		add_filter( 'get_block_templates', array( $this, 'register_templates' ), 25, 3 );
		add_filter( 'get_block_template', array( $this, 'get_template_by_id' ), 10, 3 );
		
		// Register block category
		add_filter( 'block_categories_all', array( $this, 'register_block_category' ), 10, 2 );
		
		// Register blocks on init hook (register_block_type requires init hook)
		add_action( 'init', array( $this, 'register_block_theme_blocks' ), 10 );
		
		// Hide template-only blocks from normal post/page editor
		add_filter( 'allowed_block_types_all', array( $this, 'filter_template_only_blocks' ), 10, 2 );
		
		// Add dynamic classes to wrapper blocks
		add_filter( 'render_block', array( $this, 'inject_wrapper_classes' ), 10, 2 );
	}

	/**
	 * Register custom block category for Event Genius blocks
	 *
	 * @param array   $categories Array of block categories.
	 * @param WP_Post $post       Post being edited.
	 * @return array Modified array of block categories.
	 */
	public function register_block_category( $categories, $post ) {
		// Early exit if not a block theme
		if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
			return $categories;
		}

		// Create the Event Genius category
		$event_genius_category = array(
			'slug'  => 'event-genius',
			'title' => __( 'Event Genius', 'event-genius' ),
			'icon'  => null, // Can be set to a dashicon name if desired
		);

		// Remove Event Genius category if it already exists (to avoid duplicates)
		$categories = array_filter( $categories, function( $category ) {
			return isset( $category['slug'] ) && $category['slug'] !== 'event-genius';
		} );

		// Always place Event Genius category at the beginning
		return array_merge( array( $event_genius_category ), array_values( $categories ) );
	}

	/**
	 * Filter template-only blocks from normal post/page editor
	 * 
	 * All block theme blocks should only be available in the Site Editor (template editor),
	 * not in the normal post/page editor. These blocks are designed for use in templates only.
	 *
	 * @param bool|array $allowed_block_types Array of block type slugs, or boolean to enable/disable all.
	 * @param WP_Block_Editor_Context $block_editor_context The current block editor context.
	 * @return bool|array Modified array of allowed block types.
	 */
	public function filter_template_only_blocks( $allowed_block_types, $block_editor_context ) {
		// Early exit if not a block theme
		if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
			return $allowed_block_types;
		}

		// All block theme blocks that should be hidden from normal editor
		// These match the blocks built by build-blocks.php
		$template_only_blocks = array(
			'wp-event-genius/event-featured-image',
			'wp-event-genius/event-date',
			'wp-event-genius/event-location',
			'wp-event-genius/event-about-items',
			'wp-event-genius/event-content-heading',
			'wp-event-genius/event-export',
			'wp-event-genius/event-categories',
			'wp-event-genius/event-tags',
			'wp-event-genius/event-cta',
			'wp-event-genius/event-organizers',
			'wp-event-genius/organizer-content',
			'wp-event-genius/venue-content',
			'wp-event-genius/series-content',
		);

		// If we're in the Site Editor (template editor) or editing a template/template part, allow all blocks
		$is_template_editor = (
			( isset( $block_editor_context->name ) && in_array( $block_editor_context->name, array( 'core/edit-site', 'core/edit-template' ), true ) ) ||
			( isset( $block_editor_context->post ) && in_array( $block_editor_context->post->post_type, array( 'wp_template', 'wp_template_part' ), true ) )
		);

		if ( $is_template_editor ) {
			return $allowed_block_types;
		}
		
		// For normal post/page editor, remove template-only blocks
		// If $allowed_block_types is true (all blocks allowed), we need to get all registered blocks
		// and filter out the template-only ones
		if ( $allowed_block_types === true ) {
			$all_blocks = array_keys( \WP_Block_Type_Registry::get_instance()->get_all_registered() );
			return array_values( array_diff( $all_blocks, $template_only_blocks ) );
		}
		
		// If it's already an array, filter out template-only blocks
		if ( is_array( $allowed_block_types ) ) {
			return array_values( array_diff( $allowed_block_types, $template_only_blocks ) );
		}
		
		return $allowed_block_types;
	}

	/**
	 * Register blocks that are only available for block themes
	 */
	public function register_block_theme_blocks() {
		// Only register for block themes
		if ( ! wp_is_block_theme() ) {
			return;
		}

		$event_featured_image_block = new \WPEventGenius\BlockTheme\Blocks\EventFeaturedImage\Block();
		$event_featured_image_block->init();

		$event_date_block = new \WPEventGenius\BlockTheme\Blocks\EventDate\Block();
		$event_date_block->init();

		$event_location_block = new \WPEventGenius\BlockTheme\Blocks\EventLocation\Block();
		$event_location_block->init();

		$event_about_items_block = new \WPEventGenius\BlockTheme\Blocks\EventAboutItems\Block();
		$event_about_items_block->init();

		$event_content_heading_block = new \WPEventGenius\BlockTheme\Blocks\EventContentHeading\Block();
		$event_content_heading_block->init();

		$event_export_block = new \WPEventGenius\BlockTheme\Blocks\EventExport\Block();
		$event_export_block->init();

		$event_categories_block = new \WPEventGenius\BlockTheme\Blocks\EventCategories\Block();
		$event_categories_block->init();

		$event_tags_block = new \WPEventGenius\BlockTheme\Blocks\EventTags\Block();
		$event_tags_block->init();

		$event_cta_block = new \WPEventGenius\BlockTheme\Blocks\EventCta\Block();
		$event_cta_block->init();

		$event_organizers_block = new \WPEventGenius\BlockTheme\Blocks\EventOrganizers\Block();
		$event_organizers_block->init();

		// Register content blocks for organizer, venue, and series
		$organizer_content_block = new \WPEventGenius\BlockTheme\Blocks\OrganizerContent\Block();
		$organizer_content_block->init();

		$venue_content_block = new \WPEventGenius\BlockTheme\Blocks\VenueContent\Block();
		$venue_content_block->init();
		
		// Series block only if Pro tier is available
		if ( function_exists( 'evge_is_pro_tier' ) && evge_is_pro_tier() ) {
			$series_content_block = new \WPEventGenius\BlockTheme\Blocks\SeriesContent\Block();
			$series_content_block->init();
		}
	}

	/**
	 * Register block theme templates
	 *
	 * @param array  $query_result Array of template objects.
	 * @param array  $query        Optional. Arguments to retrieve templates.
	 * @param string $template_type Optional. The template type (wp_template or wp_template_part).
	 * @return array Modified array of template objects.
	 */
	public function register_templates( $query_result, $query, $template_type = 'wp_template' ) {
		// Early exit if not a block theme
		if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
			return $query_result;
		}

		// Only process wp_template, not template parts
		if ( 'wp_template' !== $template_type ) {
			return $query_result;
		}

		// Check if we're querying for a specific slug
		$slug = isset( $query['slug__in'] ) ? $query['slug__in'] : null;
		$post_type = isset( $query['post_type'] ) ? $query['post_type'] : null;

		// Determine which templates to add
		$templates_to_add = array();

		// Single event template
		$should_add_single = false;
		if ( null === $slug && null === $post_type ) {
			// General query - add our templates
			$should_add_single = true;
		} elseif ( is_array( $slug ) && in_array( 'single-evge_event', $slug, true ) ) {
			// Querying for our specific template
			$should_add_single = true;
		} elseif ( EVGE_EVENT_POST_TYPE === $post_type ) {
			// Querying for event post type templates
			$should_add_single = true;
		}

		if ( $should_add_single ) {
			$template = $this->get_or_create_single_event_template();
			if ( $template ) {
				$templates_to_add[] = $template;
			}
		}

		// Archive event template
		$should_add_archive = false;
		if ( null === $slug && null === $post_type ) {
			// General query - add our templates
			$should_add_archive = true;
		} elseif ( is_array( $slug ) && in_array( 'archive-evge_event', $slug, true ) ) {
			// Querying for our specific archive template
			$should_add_archive = true;
		} elseif ( EVGE_EVENT_POST_TYPE === $post_type ) {
			// Querying for event post type templates (includes archive)
			$should_add_archive = true;
		}

		if ( $should_add_archive ) {
			$template = $this->get_or_create_archive_template();
			if ( $template ) {
				$templates_to_add[] = $template;
			}
		}

		// Single organizer template
		$should_add_organizer = false;
		if ( null === $slug && null === $post_type ) {
			$should_add_organizer = true;
		} elseif ( is_array( $slug ) && in_array( 'single-evge_organizer', $slug, true ) ) {
			$should_add_organizer = true;
		} elseif ( EVGE_ORGANIZER_POST_TYPE === $post_type ) {
			$should_add_organizer = true;
		}

		if ( $should_add_organizer ) {
			$template = $this->get_or_create_single_organizer_template();
			if ( $template ) {
				$templates_to_add[] = $template;
			}
		}

		// Single venue template
		$should_add_venue = false;
		if ( null === $slug && null === $post_type ) {
			$should_add_venue = true;
		} elseif ( is_array( $slug ) && in_array( 'single-evge_venue', $slug, true ) ) {
			$should_add_venue = true;
		} elseif ( EVGE_VENUE_POST_TYPE === $post_type ) {
			$should_add_venue = true;
		}

		if ( $should_add_venue ) {
			$template = $this->get_or_create_single_venue_template();
			if ( $template ) {
				$templates_to_add[] = $template;
			}
		}

		// Single series template (Pro only)
		$should_add_series = false;
		if ( function_exists( 'evge_is_pro_tier' ) && evge_is_pro_tier() ) {
			if ( null === $slug && null === $post_type ) {
				$should_add_series = true;
			} elseif ( is_array( $slug ) && in_array( 'single-evge_series', $slug, true ) ) {
				$should_add_series = true;
			} elseif ( defined( 'EVGE_SERIES_POST_TYPE' ) && EVGE_SERIES_POST_TYPE === $post_type ) {
				$should_add_series = true;
			}
		}

		if ( $should_add_series ) {
			$template = $this->get_or_create_single_series_template();
			if ( $template ) {
				$templates_to_add[] = $template;
			}
		}

		// Add templates that don't already exist
		foreach ( $templates_to_add as $template ) {
			$exists = false;
			foreach ( $query_result as $existing_template ) {
				if ( isset( $existing_template->id ) && $existing_template->id === $template->id ) {
					$exists = true;
					break;
				}
			}
			if ( ! $exists ) {
				$query_result[] = $template;
			}
		}

		return $query_result;
	}

	/**
	 * Get template by ID (for REST API lookups)
	 *
	 * @param WP_Block_Template|null $template The found template.
	 * @param string                  $id       Template ID.
	 * @param string                  $type     Template type.
	 * @return WP_Block_Template|null Template object or null.
	 */
	public function get_template_by_id( $template, $id, $type ) {
		// Early exit if not a block theme
		if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
			return $template;
		}

		if ( 'wp_template' !== $type ) {
			return $template;
		}

		// Parse ID: "event-genius//single-evge_event" or "event-genius//archive-evge_event", etc.
		if ( strpos( $id, $this->namespace . '//' ) === 0 ) {
			$slug = str_replace( $this->namespace . '//', '', $id );

			if ( 'single-evge_event' === $slug ) {
				return $this->get_or_create_single_event_template();
			}

			if ( 'archive-evge_event' === $slug ) {
				return $this->get_or_create_archive_template();
			}

			if ( 'single-evge_organizer' === $slug ) {
				return $this->get_or_create_single_organizer_template();
			}

			if ( 'single-evge_venue' === $slug ) {
				return $this->get_or_create_single_venue_template();
			}

			if ( 'single-evge_series' === $slug ) {
				return $this->get_or_create_single_series_template();
			}
		}

		return $template;
	}

	/**
	 * Get or create the single event template
	 *
	 * @return WP_Block_Template|null Template object or null if not found.
	 */
	private function get_or_create_single_event_template() {
		// First, try to find existing template in database
		$template = $this->find_existing_template( 'single-evge_event' );

		// If not found, create it
		if ( ! $template ) {
			$template_file = EVGE_PLUGIN_PATH . 'templates/block-themes/single-evge_event.html';
			$template = $this->create_template_in_database(
				'single-evge_event',
				$template_file,
				__( 'Single Event', 'event-genius' ),
				__( 'Template for displaying a single event', 'event-genius' )
			);
		}

		return $template;
	}

	/**
	 * Get or create the archive event template
	 *
	 * @return WP_Block_Template|null Template object or null if not found.
	 */
	private function get_or_create_archive_template() {
		// First, try to find existing template in database
		$template = $this->find_existing_template( 'archive-evge_event' );

		// If not found, create it
		if ( ! $template ) {
			$template_file = EVGE_PLUGIN_PATH . 'templates/block-themes/archive-evge_event.html';
			$template = $this->create_template_in_database(
				'archive-evge_event',
				$template_file,
				__( 'Calendar (Events Archive)', 'event-genius' ),
				__( 'Displays the default calendar on /events/', 'event-genius' )
			);
		}

		return $template;
	}

	/**
	 * Get or create the single organizer template
	 *
	 * @return WP_Block_Template|null Template object or null if not found.
	 */
	private function get_or_create_single_organizer_template() {
		// First, try to find existing template in database
		$template = $this->find_existing_template( 'single-evge_organizer' );

		// If not found, create it
		if ( ! $template ) {
			$template_file = EVGE_PLUGIN_PATH . 'templates/block-themes/single-evge_organizer.html';
			$template = $this->create_template_in_database(
				'single-evge_organizer',
				$template_file,
				__( 'Single Organizer', 'event-genius' ),
				__( 'Template for displaying a single organizer', 'event-genius' )
			);
		}

		return $template;
	}

	/**
	 * Get or create the single venue template
	 *
	 * @return WP_Block_Template|null Template object or null if not found.
	 */
	private function get_or_create_single_venue_template() {
		// First, try to find existing template in database
		$template = $this->find_existing_template( 'single-evge_venue' );

		// If not found, create it
		if ( ! $template ) {
			$template_file = EVGE_PLUGIN_PATH . 'templates/block-themes/single-evge_venue.html';
			$template = $this->create_template_in_database(
				'single-evge_venue',
				$template_file,
				__( 'Single Venue', 'event-genius' ),
				__( 'Template for displaying a single venue', 'event-genius' )
			);
		}

		return $template;
	}

	/**
	 * Get or create the single series template
	 *
	 * @return WP_Block_Template|null Template object or null if not found.
	 */
	private function get_or_create_single_series_template() {
		// Only create if Pro tier is available
		if ( ! function_exists( 'evge_is_pro_tier' ) || ! evge_is_pro_tier() ) {
			return null;
		}

		// First, try to find existing template in database
		$template = $this->find_existing_template( 'single-evge_series' );

		// If not found, create it
		if ( ! $template ) {
			$template_file = EVGE_PLUGIN_PATH . 'templates/block-themes/single-evge_series.html';
			$template = $this->create_template_in_database(
				'single-evge_series',
				$template_file,
				__( 'Single Series', 'event-genius' ),
				__( 'Template for displaying a single series', 'event-genius' )
			);
		}

		return $template;
	}

	/**
	 * Find existing template in database
	 *
	 * @param string $slug Template slug.
	 * @return WP_Block_Template|null Template object or null if not found.
	 */
	private function find_existing_template( $slug ) {
		$query = new \WP_Query(
			array(
				'post_name__in'  => array( $slug ),
				'post_type'      => 'wp_template',
				'post_status'    => array( 'auto-draft', 'draft', 'publish', 'trash' ),
				'posts_per_page' => 1,
				'no_found_rows'  => true,
				'tax_query'      => array(
					array(
						'taxonomy' => 'wp_theme',
						'field'    => 'name',
						'terms'    => $this->namespace,
					),
				),
			)
		);

		if ( empty( $query->posts ) ) {
			return null;
		}

		return $this->hydrate_template( $query->posts[0] );
	}

	/**
	 * Create template in database
	 *
	 * @param string $slug Template slug.
	 * @param string $template_file Path to template file.
	 * @param string $title Template title.
	 * @param string $description Template description.
	 * @return WP_Block_Template|null Template object or null on failure.
	 */
	private function create_template_in_database( $slug, $template_file, $title, $description ) {
		// Security: Validate that template file is within plugin directory to prevent directory traversal
		$plugin_path = realpath( EVGE_PLUGIN_PATH );
		$template_path = realpath( $template_file );
		
		if ( false === $template_path || strpos( $template_path, $plugin_path ) !== 0 ) {
			// Invalid path - use default content instead
			$template_content = $this->get_default_template_content();
		} else {
			// Ensure wp_theme taxonomy term exists
			$this->ensure_taxonomy_term();

			// Get template HTML content
			$template_content = file_exists( $template_file )
				? file_get_contents( $template_file )
				: $this->get_default_template_content();
		}

		// Inject theme attribute into template parts
		$template_content = $this->inject_theme_attribute( $template_content );

		// Get a safe author ID - use current user if available, otherwise fall back to admin (ID 1)
		$author_id = get_current_user_id();
		if ( empty( $author_id ) ) {
			// Fall back to first admin user, or user ID 1 if that doesn't exist
			$admin_users = get_users( array(
				'role'   => 'administrator',
				'number' => 1,
				'fields' => 'ID',
			) );
			$author_id = ! empty( $admin_users ) ? $admin_users[0] : 1;
		}

		// Create the post
		$post_id = wp_insert_post(
			array(
				'post_name'    => $slug,
				'post_title'   => $title,
				'post_excerpt' => $description,
				'post_type'    => 'wp_template',
				'post_status'  => 'publish',
				'post_content' => $template_content,
				'post_author'  => $author_id,
				'tax_input'    => array(
					'wp_theme' => array( $this->namespace ),
				),
			),
			true
		);

		if ( ! $post_id || is_wp_error( $post_id ) ) {
			return null;
		}

		return $this->hydrate_template( get_post( $post_id ) );
	}

	/**
	 * Ensure wp_theme taxonomy term exists
	 */
	private function ensure_taxonomy_term() {
		// Check if term exists
		$term = get_term_by( 'name', $this->namespace, 'wp_theme' );

		if ( ! $term ) {
			// Create the term
			wp_insert_term(
				$this->namespace,
				'wp_theme',
				array(
					'description' => __( 'Event Genius plugin templates', 'event-genius' ),
				)
			);
		}
	}

	/**
	 * Hydrate WP_Block_Template from WP_Post
	 *
	 * @param WP_Post $post Post object.
	 * @return WP_Block_Template|null Template object or null on failure.
	 */
	private function hydrate_template( $post ) {
		$terms = get_the_terms( $post, 'wp_theme' );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return null;
		}

		// Determine post types based on template slug
		$post_types = array( EVGE_EVENT_POST_TYPE ); // Default to event
		if ( strpos( $post->post_name, 'single-evge_organizer' ) === 0 ) {
			$post_types = array( EVGE_ORGANIZER_POST_TYPE );
		} elseif ( strpos( $post->post_name, 'single-evge_venue' ) === 0 ) {
			$post_types = array( EVGE_VENUE_POST_TYPE );
		} elseif ( strpos( $post->post_name, 'single-evge_series' ) === 0 ) {
			$post_types = defined( 'EVGE_SERIES_POST_TYPE' ) ? array( EVGE_SERIES_POST_TYPE ) : array();
		} elseif ( strpos( $post->post_name, 'archive-evge_event' ) === 0 ) {
			$post_types = array( EVGE_EVENT_POST_TYPE );
		}

		$template                 = new \WP_Block_Template();
		$template->wp_id          = $post->ID;
		$template->id             = $terms[0]->name . '//' . $post->post_name; // CRITICAL: Format is "namespace//slug"
		$template->theme          = $terms[0]->name;
		$template->content        = $post->post_content;
		$template->slug           = $post->post_name;
		$template->source         = 'custom'; // CRITICAL: Must be 'custom' for plugin templates
		$template->type           = 'wp_template';
		$template->title          = $post->post_title;
		$template->description    = $post->post_excerpt;
		$template->status         = $post->post_status;
		$template->has_theme_file = false;
		$template->is_custom      = true;
		$template->author         = (int) $post->post_author; // Use the post's author (safe user ID)
		$template->modified       = $post->post_modified;
		$template->post_types     = $post_types;

		return $template;
	}

	/**
	 * Inject theme attribute into template parts
	 *
	 * @param string $content Template content.
	 * @return string Modified template content.
	 */
	private function inject_theme_attribute( $content ) {
		// Check if content already has theme attributes - if so, skip processing
		if ( strpos( $content, '"theme":' ) !== false ) {
			return $content;
		}

		$theme_slug = wp_get_theme()->get_stylesheet();

		// Use regex to add theme attribute to template-part blocks that don't have it
		// Match: <!-- wp:template-part {"slug":"...","area":"..."} /-->
		// Pattern matches the opening comment, attributes JSON, and closing
		$pattern = '/(<!--\s*wp:template-part\s+\{)([^}]*)(\}\s*\/-->)/';
		
		$content = preg_replace_callback(
			$pattern,
			function( $matches ) use ( $theme_slug ) {
				$opening = $matches[1];
				$attrs_json = trim( $matches[2] );
				$closing = $matches[3];
				
				// Check if theme attribute already exists
				if ( strpos( $attrs_json, '"theme":' ) !== false ) {
					return $matches[0]; // Return unchanged
				}
				
				// Add theme attribute to the JSON object
				if ( ! empty( $attrs_json ) ) {
					// Remove trailing comma if present
					$attrs_json = rtrim( $attrs_json, ',' );
					$attrs_json .= ',';
				}
				$attrs_json .= ' "theme":"' . esc_attr( $theme_slug ) . '"';
				
				return $opening . $attrs_json . $closing;
			},
			$content
		);

		return $content;
	}

	/**
	 * Inject dynamic classes into wrapper blocks
	 *
	 * @param string   $block_content The block content.
	 * @param array    $block         The block array.
	 * @return string Modified block content.
	 */
	public function inject_wrapper_classes( $block_content, $block ) {
		// Early exit if not a block theme
		if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
			return $block_content;
		}

		// Only process on single event pages
		if ( ! is_singular( EVGE_EVENT_POST_TYPE ) ) {
			return $block_content;
		}

		// Only process group blocks with our wrapper classes
		if ( 'core/group' !== $block['blockName'] || empty( $block['attrs']['className'] ) ) {
			return $block_content;
		}

		$templater = new \WPEventGenius\Common\Utils\Templater();
		$className = $block['attrs']['className'];

		// Add classes to the main evge wrapper
		// This includes the color theme class (e.g., 'evge-color-theme-dark') based on
		// the 'color_theme' setting in Event Settings → Event Display (views)
		if ( strpos( $className, 'evge-block-theme-wrapper' ) !== false ) {
			$evge_classes = $templater->evge_classes();
			
			// Add dynamic classes (includes color theme: 'evge-color-theme-dark' if dark theme is selected)
			if ( ! empty( $evge_classes ) ) {
				$block_content = str_replace(
					'class="wp-block-group evge evge-block-theme-wrapper',
					'class="wp-block-group evge evge-block-theme-wrapper' . esc_attr( $evge_classes ),
					$block_content
				);
			}
			
			// Add data attribute
			if ( strpos( $block_content, 'data-evge-type' ) === false ) {
				$block_content = preg_replace(
					'/(<div[^>]*class="[^"]*evge-block-theme-wrapper[^"]*")/',
					'$1 data-evge-type="single-event"',
					$block_content
				);
			}
		}

		// Add classes to the evge-events-single-main wrapper
		if ( strpos( $className, 'evge-events-single-main' ) !== false ) {
			$classes = $templater->classes();
			if ( ! empty( $classes ) ) {
				$block_content = str_replace(
					'class="wp-block-group evge-events-single-main',
					'class="wp-block-group evge-events-single-main' . esc_attr( $classes ),
					$block_content
				);
			}
			// Add id attribute
			if ( strpos( $block_content, 'id="evge-single-main"' ) === false ) {
				$block_content = preg_replace(
					'/(class="[^"]*evge-events-single-main[^"]*")/',
					'$1 id="evge-single-main"',
					$block_content
				);
			}
		}

		return $block_content;
	}

	/**
	 * Get default template content if file doesn't exist
	 *
	 * @return string Default template content.
	 */
	private function get_default_template_content() {
		return '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->
<!-- wp:post-featured-image /-->
<!-- wp:post-title /-->
<!-- wp:post-content /-->
<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->';
	}

}
