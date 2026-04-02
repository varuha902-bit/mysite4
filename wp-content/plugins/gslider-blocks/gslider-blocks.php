<?php
/**
 * Plugin Name: Slider Block for Gutenberg Gutenslider by GSlider
 * Description: Enhance your site with dynamic, customizable, and multi-functional slider blocks to create a more engaging and functional experience.
 * Author: 		Noruzzaman
 * Author URI: 	https://github.com/noruzzamans/
 * Version: 	1.1.6
 * Text Domain: gslider-blocks
 * Domain Path: /languages
 * License: 	GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package GSlider_Blocks
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Check if the autoload file exists and include it
 */
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
	require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * Final Class for GSlider Blocks
 */
if ( ! class_exists( 'GSliderBlocks' ) ) {

	final class GSliderBlocks {

		private static $instance;

		/**
		 * Get the instance
		 */
		public static function getInstance() {
			if (!isset(self::$instance)) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 */
		public function __construct() {
			$this->define_constants();
			$this->includes();
		}

		/**
		 * Define gslider constants
		 */
		public function define_constants() {
			define('GSLIDER_FILE', __FILE__);
			define('GSLIDER_SLUG', 'gslider-blocks');
			define('GSLIDER_VERSION', '1.1.6');
			define('GSLIDER_DIR_PATH', plugin_dir_path(__FILE__));
			define('GSLIDER_PLUGIN_URL', plugin_dir_url(__FILE__));
			define('GSLIDER_WP_VERSION', (float) get_bloginfo('version'));
			define('GSLIDER_PHP_VERSION', (float) phpversion());
		}

		/**
		 * Include necessary files for the plugin
		 */
		private function includes() {
			require_once trailingslashit( GSLIDER_DIR_PATH ) . 'includes/Plugin.php';
		}
	}
}

GSliderBlocks::getInstance();

/**
 * Define a global function to get the instance of the plugin class
 */
function GSliderBlocks() {
	return GSliderBlocks::getInstance();
}

/**
 * Initialize the plugin
 */
GSliderBlocks();
