<?php
/**
 * FaeCursor Particle Effects Configuration
 */

if (!defined('ABSPATH')) {
    exit;
}

function fae_particle_get_effects_config() {
    return array(
        'none' => array(
            'display_name' => 'None',
            'supports_color' => false,
            'supports_speed' => false,
            'supports_hide_cursor' => false,
            'directory' => '',
        ),
        'particle-10' => array(
            'display_name' => 'Snowfall',
            'supports_color' => true,
            'supports_speed' => true,
            'supports_hide_cursor' => true,
            'supports_interactive_cursor' => true,
            'directory' => 'snowfall',
        ),
        'particle-1' => array(
            'display_name' => 'Morph Grid',
            'supports_color' => true,
            'supports_speed' => false,
            'supports_hide_cursor' => true,
            'supports_interactive_cursor' => true,
            'directory' => 'morph-grid',
        ),
        'particle-4' => array(
            'display_name' => 'Swirl',
            'supports_color' => true,
            'supports_speed' => false,
            'supports_hide_cursor' => true,
            'supports_interactive_cursor' => true,
            'directory' => 'swirl-cursor',
        ),
        'particle-5' => array(
            'display_name' => 'Repel',
            'supports_color' => true,
            'supports_speed' => false,
            'supports_hide_cursor' => true,
            'supports_interactive_cursor' => true,
            'directory' => 'repel-cursor',
        ),
        'particle-8' => array(
            'display_name' => 'Color Borrower',
            'supports_color' => true,
            'supports_speed' => false,
            'supports_hide_cursor' => true,
            'supports_interactive_cursor' => true,
            'directory' => 'color-borrower',
        )
    );
}

function fae_particle_get_effect_config($effect_name) {
    $configs = fae_particle_get_effects_config();
    return isset($configs[$effect_name]) ? $configs[$effect_name] : array(
        'display_name' => $effect_name,
        'supports_color' => true,
        'supports_speed' => false,
        'supports_hide_cursor' => true,
    );
}

function fae_particle_effect_supports($effect_name, $setting) {
    $config = fae_particle_get_effect_config($effect_name);
    switch($setting) {
        case 'color': return isset($config['supports_color']) ? $config['supports_color'] : false;
        case 'speed': return isset($config['supports_speed']) ? $config['supports_speed'] : false;
        case 'hide_cursor': return isset($config['supports_hide_cursor']) ? $config['supports_hide_cursor'] : true;
        case 'interactive_cursor': return isset($config['supports_interactive_cursor']) ? $config['supports_interactive_cursor'] : false;
        default: return false;
    }
}

/**
 * Get directory name for particle effect
 * 
 * @param string $effect_name Effect ID
 * @return string Directory name
 */
function fae_particle_get_effect_directory($effect_name) {
    $config = fae_particle_get_effect_config($effect_name);
    return isset($config['directory']) ? $config['directory'] : $effect_name;
}
