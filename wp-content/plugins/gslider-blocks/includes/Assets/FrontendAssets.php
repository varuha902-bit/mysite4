<?php

namespace GSlider\Assets;

use GSlider\Traits\SingletonTrait;

if (!defined('ABSPATH')) {
    exit;
}

class FrontendAssets {
    use SingletonTrait;

    public function register() {
        add_action('enqueue_block_assets', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets() {
        wp_register_style(
            'gslider-blocks-swiper-style',
            GSLIDER_PLUGIN_URL . 'assets/css/swiper-bundle.min.css',
            [],
            GSLIDER_VERSION
        );

        wp_register_script(
            'gslider-blocks-swiper-script',
            GSLIDER_PLUGIN_URL . 'assets/js/swiper-bundle.min.js',
            [],
            GSLIDER_VERSION,
            true
        );

        wp_register_script(
            'gslider-blocks-fslightbox-script',
            GSLIDER_PLUGIN_URL . 'assets/js/fslightbox.min.js',
            [],
            GSLIDER_VERSION,
            true
        );

        // Enqueue fsLightbox for admin editor only
        if (is_admin()) {
            wp_enqueue_script('gslider-blocks-fslightbox-script');
        }
    }
}
