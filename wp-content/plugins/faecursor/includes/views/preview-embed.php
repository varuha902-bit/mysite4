<?php
/**
 * Embeddable Effect Preview (for iframe in dashboard)
 * Simple version - just shows the effect, no settings panel
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get parameters
// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Preview embed for admin users only, checked in handle_preview_page
// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Simple GET parameters unslashed below
$effect_type = isset($_GET['type']) ? sanitize_text_field(wp_unslash($_GET['type'])) : 'cursor';
$effect_name = isset($_GET['effect']) ? sanitize_text_field(wp_unslash($_GET['effect'])) : 'none';
$color = isset($_GET['color']) ? sanitize_hex_color(wp_unslash($_GET['color'])) : '#667eea';
$speed = isset($_GET['speed']) ? sanitize_text_field(wp_unslash($_GET['speed'])) : 'normal';
$size = isset($_GET['size']) ? sanitize_text_field(wp_unslash($_GET['size'])) : '1.5rem';
$icon = isset($_GET['icon']) ? sanitize_file_name(wp_unslash($_GET['icon'])) : 'star.svg';
$flag = isset($_GET['flag']) ? sanitize_file_name(wp_unslash($_GET['flag'])) : '';
$flag_position = isset($_GET['flag_position']) ? sanitize_text_field(wp_unslash($_GET['flag_position'])) : 'center';
$multi_color = isset($_GET['multi_color']) ? sanitize_text_field(wp_unslash($_GET['multi_color'])) : '0';
$bg = isset($_GET['bg']) ? sanitize_text_field(wp_unslash($_GET['bg'])) : 'dark';
// phpcs:enable WordPress.Security.NonceVerification.Recommended
// phpcs:enable WordPress.Security.ValidatedSanitizedInput.MissingUnslash

// Effect paths
$paths = array(
    'cursor'   => 'assets/effects/cursor/',
    'keyboard' => 'assets/effects/keyboard/',
    'particle' => 'assets/effects/particles/',
);
$base_path = isset($paths[$effect_type]) ? $paths[$effect_type] : $paths['cursor'];

// Particle folder mapping
$particle_map = array(
    'particle-1' => 'morph-grid',
    'particle-4' => 'swirl-cursor',
    'particle-5' => 'repel-cursor',
    'particle-8' => 'color-borrower',
    'particle-10' => 'snowfall',
);

$effect_folder = ($effect_type === 'particle' && isset($particle_map[$effect_name])) 
    ? $particle_map[$effect_name] 
    : $effect_name;

// File paths
$css_path = FAE_CURSOR_DIR . '/' . $base_path . $effect_folder . '/style.css';
$css_path_alt = FAE_CURSOR_DIR . '/' . $base_path . $effect_folder . '/styles.css';
$js_path = FAE_CURSOR_DIR . '/' . $base_path . $effect_folder . '/script.js';

$css_url = FAE_CURSOR_URL . $base_path . $effect_folder . '/style.css';
$css_url_alt = FAE_CURSOR_URL . $base_path . $effect_folder . '/styles.css';
$js_url = FAE_CURSOR_URL . $base_path . $effect_folder . '/script.js';
// Add cache-busting parameter to ensure updated scripts are loaded
if (file_exists($js_path)) {
    $js_url = add_query_arg('v', FAE_CURSOR_VERSION . '-' . filemtime($js_path), $js_url);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { 
            <?php if ($bg === 'light') : ?>
            background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%) !important;
            <?php else : ?>
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%) !important;
            <?php endif; ?>
            min-height: 100vh;
            font-family: system-ui, sans-serif;
            overflow: hidden;
        }
        .fae-embed-hint {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            <?php if ($bg === 'light') : ?>
            color: rgba(0,0,0,0.4);
            <?php else : ?>
            color: rgba(255,255,255,0.3);
            <?php endif; ?>
            pointer-events: none;
            transition: opacity 0.3s;
        }
        .fae-embed-hint.hidden { opacity: 0; }
        .fae-embed-hint svg {
            width: 48px;
            height: 48px;
            <?php if ($bg === 'light') : ?>
            fill: rgba(0,0,0,0.2);
            <?php else : ?>
            fill: rgba(255,255,255,0.2);
            <?php endif; ?>
            margin-bottom: 12px;
        }
        .fae-embed-hint p {
            font-size: 13px;
        }
        .fae-embed-none {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            <?php if ($bg === 'light') : ?>
            color: rgba(0,0,0,0.5);
            <?php else : ?>
            color: rgba(255,255,255,0.4);
            <?php endif; ?>
        }
        .fae-embed-none svg {
            width: 40px;
            height: 40px;
            <?php if ($bg === 'light') : ?>
            fill: rgba(0,0,0,0.2);
            <?php else : ?>
            fill: rgba(255,255,255,0.2);
            <?php endif; ?>
            margin-bottom: 10px;
        }
        .fae-embed-none p {
            font-size: 12px;
        }
        /* Keyboard input for keyboard effects */
        .fae-embed-input {
            position: absolute;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 80%;
            max-width: 300px;
            padding: 10px 14px;
            <?php if ($bg === 'light') : ?>
            background: rgba(255,255,255,0.9);
            border: 1px solid rgba(0,0,0,0.15);
            color: #333;
            <?php else : ?>
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.15);
            color: #fff;
            <?php endif; ?>
            border-radius: 6px;
            font-size: 13px;
            outline: none;
        }
        .fae-embed-input:focus { border-color: #667eea; }
        .fae-embed-input::placeholder { 
            <?php if ($bg === 'light') : ?>
            color: rgba(0,0,0,0.4);
            <?php else : ?>
            color: rgba(255,255,255,0.3);
            <?php endif; ?>
        }
    </style>
    <?php if ($effect_name !== 'none') : ?>
        <?php if (file_exists($css_path)) : ?>
        <?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- Preview embed page with inline styles ?>
        <link rel="stylesheet" href="<?php echo esc_url($css_url); ?>">
        <?php elseif (file_exists($css_path_alt)) : ?>
        <?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- Preview embed page with inline styles ?>
        <link rel="stylesheet" href="<?php echo esc_url($css_url_alt); ?>">
        <?php endif; ?>
    <?php endif; ?>
</head>
<body style="<?php echo ($bg === 'light') ? 'background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);' : 'background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);'; ?>">
    <?php if ($effect_name === 'none') : ?>
    <div class="fae-embed-none">
        <svg viewBox="0 0 512 512"><path d="M256,48C141.31,48,48,141.31,48,256s93.31,208,208,208,208-93.31,208-208S370.69,48,256,48Zm0,384C159.38,432,80,352.62,80,256S159.38,80,256,80s176,79.38,176,176S352.62,432,256,432Z"/><path d="M336,256H176a16,16,0,0,1,0-32H336a16,16,0,0,1,0,32Z"/></svg>
        <p>Select an effect to preview</p>
    </div>
    <?php else : ?>
    <?php if ($effect_type === 'keyboard') : ?>
    <input type="text" class="fae-embed-input" placeholder="Type here to see effect..." id="keyboard-input" autofocus>
    <?php else : ?>
    <div class="fae-embed-hint" id="hint">
        <svg viewBox="0 0 32 32"><path d="M5,2.5l22,9c0.5,0.2,0.7,0.8,0.4,1.2c-0.1,0.2-0.3,0.3-0.5,0.4l-8.6,2.3l3.6,8.1c0.2,0.5,0,1.1-0.5,1.3c-0.4,0.2-0.9,0.1-1.1-0.2l-4.7-6l-3.2,6.9c-0.2,0.5-0.7,0.7-1.1,0.6c-0.3-0.1-0.5-0.3-0.6-0.6l-7-24C3,2.8,3.3,2.3,3.8,2.3C4.1,2.3,4.4,2.4,5,2.5z"/></svg>
        <p><?php echo ($effect_name === 'fireworks-effect') ? 'Click anywhere to see the fireworks effect' : 'Move mouse here to preview'; ?></p>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <?php if ($effect_name !== 'none') : ?>
    <script>
        window.faeCursorSettings = {
            effect: '<?php echo esc_js($effect_name); ?>',
            color: '<?php echo esc_js($color); ?>',
            speed: '<?php echo esc_js($speed); ?>',
            size: '<?php echo esc_js($size); ?>',
            icon: '<?php echo esc_js($icon); ?>',
            flag: '<?php echo esc_js($flag); ?>',
            flagPosition: '<?php echo esc_js($flag_position); ?>',
            multiColor: <?php echo ($multi_color === '1') ? 'true' : 'false'; ?>,
            assetsUrl: '<?php echo esc_js(FAE_CURSOR_URL . 'assets/'); ?>',
            scope: { scope_type: 'entire_website' }
        };
        window.faeCursorShouldTrigger = function() { return true; };
        window.faeCursorIsInScope = function() { return true; };
        window.faeCursorElementMatchesSelector = function() { return true; };
        
        // Hide hint on interaction
        var hint = document.getElementById('hint');
        var effectName = '<?php echo esc_js($effect_name); ?>';
        var interacted = false;
        
        if (effectName === 'fireworks-effect') {
            // Hide hint on click for fireworks effect
            document.addEventListener('click', function() {
                if (!interacted && hint) {
                    hint.classList.add('hidden');
                    interacted = true;
                }
            });
        } else {
            // Hide hint on mouse move for other effects
            document.addEventListener('mousemove', function() {
                if (!interacted && hint) {
                    hint.classList.add('hidden');
                    interacted = true;
                }
            });
        }
    </script>
    <?php if (file_exists($js_path)) : ?>
    <?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Preview embed page with inline scripts ?>
    <script src="<?php echo esc_url($js_url); ?>"></script>
    <?php endif; ?>
    <?php endif; ?>
</body>
</html>

