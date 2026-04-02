<?php

namespace WPEventGenius\Common\Services;

use WPEventGenius\Blocks\RegistrationForm\Block as RegistrationFormBlock;
use WPEventGenius\Blocks\AttendeeList\Block as AttendeeListBlock;
use WPEventGenius\Blocks\Calendar\Block as CalendarBlock;
use WPEventGenius\Blocks\MyRegistrations\Block as MyRegistrationsBlock;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class BlockLoaderService {
    private $blocks = [];

    public function init() {
        // Initialize blocks
        $this->blocks = [
            new RegistrationFormBlock(),
            new AttendeeListBlock(),
            new CalendarBlock()
        ];

        // Add pro tier blocks if available
        if ( function_exists( 'evge_is_pro_tier' ) && evge_is_pro_tier() ) {
            $this->blocks[] = new MyRegistrationsBlock();
        }

        // Add Standard tier blocks if available
        if ( function_exists( 'evge_is_standard_tier' ) && evge_is_standard_tier() ) {
            $this->blocks[] = new \WPEventGenius\Standard\Blocks\AdminCheckIn\Block();
        }

        // Register assets early
	    add_action('admin_enqueue_scripts', [$this, 'register_shared_assets']);
	    add_action('init', [$this, 'register_blocks']);
	}

    public function register_shared_assets() {
        // Register common block assets
        wp_register_script(
            'evge-blocks-shared',
            EVGE_PLUGIN_URL . 'assets/js/admin/evge-blocks-shared.js',
            ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-api-fetch'],
            EVGE_VERSION,
            true
        );

        wp_register_style(
            'evge-blocks-shared',
            EVGE_PLUGIN_URL . 'assets/css/admin/evge-blocks-shared.css',
            [],
            EVGE_VERSION
        );
    }

    public function register_blocks() {
        foreach ($this->blocks as $block) {
            if (method_exists($block, 'init')) {
                $block->init();
            }
        }
    }
} 