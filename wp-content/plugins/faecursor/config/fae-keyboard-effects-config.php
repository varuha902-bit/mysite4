<?php
/**
 * FaeCursor Keyboard Effects Configuration
 */

if (!defined('ABSPATH')) {
    exit;
}

function fae_keyboard_get_effects_config() {
    return array(
        'none' => array(
            'display_name' => 'None',
            'supports_color' => false,
            'supports_speed' => false,
        ),
        'sparkle-keys' => array(
            'display_name' => 'Sparkle Keys',
            'supports_color' => false,
            'supports_speed' => false,
        ),
        'bubble-keys' => array(
            'display_name' => 'Bubble Keys',
            'supports_color' => true,
            'supports_speed' => false,
        ),
        'ink-keys' => array(
            'display_name' => 'Ink Keys',
            'supports_color' => true,
            'supports_speed' => false,
        ),
        'matrix-keys' => array(
            'display_name' => 'Matrix Keys',
            'supports_color' => true,
            'supports_speed' => false,
        )
    );
}

function fae_keyboard_get_effect_config($effect_name) {
    $configs = fae_keyboard_get_effects_config();
    return isset($configs[$effect_name]) ? $configs[$effect_name] : array(
        'display_name' => $effect_name,
        'supports_color' => true,
        'supports_speed' => false,
    );
}

function fae_keyboard_effect_supports($effect_name, $setting) {
    $config = fae_keyboard_get_effect_config($effect_name);
    switch($setting) {
        case 'color': return isset($config['supports_color']) ? $config['supports_color'] : false;
        case 'speed': return isset($config['supports_speed']) ? $config['supports_speed'] : false;
        default: return false;
    }
}

