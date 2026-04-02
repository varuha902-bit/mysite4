<?php
/**
 * FaeCursor Effects Configuration
 */

if (!defined('ABSPATH')) {
    exit;
}

function fae_cursor_get_effects_config() {
    return array(
        'none' => array(
            'display_name' => 'None',
            'supports_color' => false,
            'supports_size' => false,
            'supports_icon' => false,
            'supports_speed' => false,
            'supports_hide_cursor' => false,
            'effect_type' => 'none'
        ),
        'drop-effect' => array(
            'display_name' => 'Drop Effect',
            'supports_color' => true,
            'supports_size' => true,
            'supports_icon' => true,
            'supports_speed' => true,
            'supports_hide_cursor' => true,
            'effect_type' => 'icon',
            'animation_direction' => 'down'
        ),
        'rise-effect' => array(
            'display_name' => 'Rise Effect',
            'supports_color' => true,
            'supports_size' => true,
            'supports_icon' => true,
            'supports_speed' => true,
            'supports_hide_cursor' => true,
            'effect_type' => 'icon',
            'animation_direction' => 'up'
        ),
        'line-effect' => array(
            'display_name' => 'Line Effect',
            'supports_color' => true,
            'supports_size' => false,
            'supports_icon' => false,
            'supports_speed' => true,
            'supports_hide_cursor' => true,
            'effect_type' => 'line'
        ),
        'duo-circle' => array(
            'display_name' => 'Duo Circle',
            'supports_color' => true,
            'supports_size' => false,
            'supports_icon' => false,
            'supports_speed' => true,
            'supports_hide_cursor' => true,
            'effect_type' => 'circle',
            'circle_style' => 'border'
        ),
        'duo-circle-2' => array(
            'display_name' => 'Duo Circle 2',
            'supports_color' => true,
            'supports_size' => false,
            'supports_icon' => false,
            'supports_speed' => true,
            'supports_hide_cursor' => true,
            'effect_type' => 'circle',
            'circle_style' => 'fill'
        ),
        'bubbles-effect' => array(
            'display_name' => 'Bubbles',
            'supports_color' => true,
            'supports_size' => false,
            'supports_icon' => false,
            'supports_speed' => true,
            'supports_hide_cursor' => true,
            'effect_type' => 'custom'
        ),
        'fireworks-effect' => array(
            'display_name' => 'Fireworks',
            'supports_color' => true,
            'supports_size' => false,
            'supports_icon' => false,
            'supports_speed' => false,
            'supports_hide_cursor' => true,
            'effect_type' => 'custom'
        ),
        'spark-effect' => array(
            'display_name' => 'Spark',
            'supports_color' => true,
            'supports_size' => false,
            'supports_icon' => false,
            'supports_speed' => true,
            'supports_hide_cursor' => true,
            'effect_type' => 'custom'
        ),
        'flag-effect' => array(
            'display_name' => 'Flag Cursor',
            'supports_color' => true,
            'supports_size' => true,
            'supports_icon' => false,
            'supports_speed' => true,
            'supports_hide_cursor' => true, // Always hides cursor (cursor replacement)
            'effect_type' => 'custom'
        ),
        'genuine-effect' => array(
            'display_name' => 'Genuine',
            'supports_color' => true,
            'supports_size' => false,
            'supports_icon' => false,
            'supports_speed' => true,
            'supports_hide_cursor' => true, // Always hides cursor (cursor replacement)
            'effect_type' => 'custom'
        ),
        'gradient-trail' => array(
            'display_name' => 'Gradient Trail',
            'supports_color' => true,
            'supports_size' => false,
            'supports_icon' => false,
            'supports_speed' => true,
            'supports_hide_cursor' => true,
            'effect_type' => 'custom'
        ),
        'leaf-effect' => array(
            'display_name' => 'Leaf Effect',
            'supports_color' => true,
            'supports_size' => false,
            'supports_icon' => false,
            'supports_speed' => true,
            'supports_hide_cursor' => true,
            'effect_type' => 'custom'
        ),
        'magic-trail' => array(
            'display_name' => 'Magic Trail',
            'supports_color' => true,
            'supports_size' => false,
            'supports_icon' => false,
            'supports_speed' => true,
            'supports_hide_cursor' => true,
            'effect_type' => 'custom'
        )
    );
}

function fae_cursor_get_effect_config($effect_name) {
    $configs = fae_cursor_get_effects_config();
    return isset($configs[$effect_name]) ? $configs[$effect_name] : array(
        'display_name' => $effect_name,
        'supports_color' => true,
        'supports_size' => false,
        'supports_icon' => false,
        'supports_speed' => true,
        'supports_hide_cursor' => true,
        'effect_type' => 'custom'
    );
}

function fae_cursor_effect_supports($effect_name, $setting) {
    $config = fae_cursor_get_effect_config($effect_name);
    switch($setting) {
        case 'color': return isset($config['supports_color']) ? $config['supports_color'] : false;
        case 'size': return isset($config['supports_size']) ? $config['supports_size'] : false;
        case 'icon': return isset($config['supports_icon']) ? $config['supports_icon'] : false;
        case 'speed': return isset($config['supports_speed']) ? $config['supports_speed'] : false;
        case 'hide_cursor': return isset($config['supports_hide_cursor']) ? $config['supports_hide_cursor'] : true;
        default: return false;
    }
}

