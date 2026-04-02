<?php

namespace WPEventGenius\Common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\Common\Utils\Settings;

/**
 * Template Manager Class
 *
 * Handles template loading and overrides for the Event Genius plugin.
 * Provides a flexible system for template customization while maintaining
 * a clean architecture and following WordPress best practices.
 */
class TemplateManager {
    /**
     * Instance of this class.
     *
     * @var TemplateManager
     */
    private static $instance = null;

    /**
     * Template paths to search in order of priority.
     *
     * @var array
     */
    protected $template_paths = [];

    /**
     * Get the singleton instance of this class.
     *
     * @return TemplateManager
     */
    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    protected function __construct() {
        $this->init_template_paths();
    }

    /**
     * Initialize template paths with proper priority order.
     */
    protected function init_template_paths(): void {

        if ( Settings::get( 'event_template' ) === 'custom' ) {
            // Theme override path
            $this->template_paths[] = get_stylesheet_directory() . '/event-genius/';
            $this->template_paths[] = get_template_directory() . '/event-genius/';
        }
        // Plugin template path
        $this->template_paths[] = EVGE_TEMPLATE_PATH . 'event-genius/';
    }

    /**
     * Locate a template file.
     *
     * @param string $template_name Template name to locate.
     * @return string|false The template path if found, false otherwise.
     */
    public function locate_template(string $template_name) {
        // Remove any leading slashes
        $template_name = ltrim($template_name, '/');

        // Check each template path
        foreach ($this->template_paths as $path) {
            $template_path = $path . $template_name;
            if (file_exists($template_path)) {
                return $template_path;
            }
        }

        return false;
    }

    /**
     * Get template content.
     *
     * @param string $template_name Template name to load.
     * @param array $args Arguments to pass to the template.
     * @param bool $echo Whether to echo the template or return it.
     * @return string|null Template content if $echo is false, null if $echo is true.
     */
    public function get_template(string $template_name, array $args = [], bool $echo = true) {
        // Allow filtering of template name before locating
        $template_name = apply_filters( 'evge_template_name', $template_name, $args );
        
        $template_path = $this->locate_template($template_name);
        
        // Allow filtering of template path after locating
        $template_path = apply_filters( 'evge_template_path', $template_path, $template_name, $args );

        if (!$template_path) {
            return '';
        }

        // Extract args to make them available in template
        if (!empty($args)) {
            extract($args);
        }

        // Start output buffering
        ob_start();

        // Include the template file
        include $template_path;

        // Get the template content
        $content = ob_get_clean();

        if ($echo) {
            echo $content;
            return null;
        }

        return $content;
    }

    /**
     * Add a custom template path.
     *
     * @param string $path Path to add.
     * @param int $priority Priority of the path (lower number = higher priority).
     */
    public function add_template_path(string $path, int $priority = 10): void {
        $path = trailingslashit($path);
        
        // Insert the path at the specified priority
        array_splice($this->template_paths, $priority, 0, [$path]);
    }

    /**
     * Get all registered template paths.
     *
     * @return array
     */
    public function get_template_paths(): array {
        return $this->template_paths;
    }
} 