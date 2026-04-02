<?php

namespace GSlider\Classes;

use GSlider\Traits\SingletonTrait;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('RegistrationBlocks')) {

    class RegistrationBlocks {
        use SingletonTrait;

        /**
         * Attach hooks for block registration.
         */
        public function register() {
            add_action('init', [$this, 'register_blocks']);
        }

        /**
         * Register all blocks dynamically from build/blocks directory.
         */
        public function register_blocks() {
            $plugin_dir = defined('GSLIDER_DIR_PATH') ? GSLIDER_DIR_PATH : plugin_dir_path(__FILE__);
            $build_dir  = trailingslashit($plugin_dir) . 'build/blocks/';

            if (!is_readable($build_dir)) {
                error_log('GSlider Blocks: Build directory is not readable: ' . $build_dir);
                return;
            }

            // Get block folders dynamically
            $blocks = apply_filters('gslider_registered_blocks', $this->get_available_blocks($build_dir));

            foreach ($blocks as $block) {
                try {
                    $this->register_single_block($build_dir, $block);
                } catch (\Exception $e) {
                    error_log('GSlider Blocks: Error registering block "' . $block . '": ' . $e->getMessage());
                }
            }
        }

        /**
         * Register a single block if valid.
         *
         * @param string $build_dir
         * @param string $block
         * @throws \Exception
         */
        private function register_single_block(string $build_dir, string $block) {
            $block_dir = trailingslashit($build_dir . sanitize_file_name($block));

            if (!is_readable($block_dir)) {
                throw new \Exception("Build directory not readable: " . esc_html($block_dir));
            }

            if (!file_exists($block_dir . 'block.json')) {
                throw new \Exception("block.json not found for " . esc_html($block));
            }

            if (false === register_block_type($block_dir)) {
                throw new \Exception("Block registration failed for " . esc_html($block));
            }
        }

        /**
         * Dynamically scan block folders inside build/blocks directory.
         *
         * @param string $build_dir
         * @return array
         */
        private function get_available_blocks(string $build_dir) {
            if (!is_dir($build_dir)) {
                return [];
            }

            $blocks = [];
            $dirs = scandir($build_dir);

            foreach ($dirs as $dir) {
                if ($dir === '.' || $dir === '..') {
                    continue;
                }

                $block_path = trailingslashit($build_dir . $dir);

                if (is_dir($block_path) && file_exists($block_path . 'block.json')) {
                    $blocks[] = $dir;
                }
            }

            return $blocks;
        }
    }
}
