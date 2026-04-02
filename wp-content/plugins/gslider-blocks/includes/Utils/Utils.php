<?php

namespace GSlider\Utils;
use GSlider\Traits\SingletonTrait;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Utils')) {

    class Utils {
        
        use SingletonTrait;

        /**
         * Register the hooks
         */
        public function register() {
            $this->gslider_init_tracker_gslider_blocks();
        }

        /**
         * Initialize the plugin tracker
         */
        public function gslider_init_tracker_gslider_blocks() {

            $client = new \Appsero\Client(
                '3b9e5a5d-b238-4acf-b1e2-3c1faee503bb',
                'Slider Block for Gutenberg Gutenslider by GSlider',
                GSLIDER_DIR_PATH . 'gslider-blocks.php'
            );

            // Active insights
            $client->insights()->init();

        }
        
    }
}
