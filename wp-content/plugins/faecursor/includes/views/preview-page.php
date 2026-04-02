<?php
/**
 * Effect Preview Page with Live Settings
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get parameters
// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Preview page for admin users only, checked in handle_preview_page
// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Simple GET parameters unslashed below
$effect_type = isset($_GET['type']) ? sanitize_text_field(wp_unslash($_GET['type'])) : 'cursor';
$effect_name = isset($_GET['effect']) ? sanitize_text_field(wp_unslash($_GET['effect'])) : 'none';
$display_name = ucwords(str_replace('-', ' ', $effect_name));

// Check for URL overrides (for live preview)
$url_color = isset($_GET['color']) ? sanitize_hex_color(wp_unslash($_GET['color'])) : null;
$url_speed = isset($_GET['speed']) ? sanitize_text_field(wp_unslash($_GET['speed'])) : null;
$url_size = isset($_GET['size']) ? sanitize_text_field(wp_unslash($_GET['size'])) : null;
$url_icon = isset($_GET['icon']) ? sanitize_file_name(wp_unslash($_GET['icon'])) : null;
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

// Get saved settings & effect config
$color = '#667eea';
$speed = 'normal';
$icon = 'star.svg';
$size = '1.5rem';
$supports_color = true;
$supports_speed = true;
$supports_icon = false;
$supports_size = false;

if ($effect_type === 'cursor') {
    $options = Fae_Cursor_Settings::get_options();
    $color = isset($options['color']) ? $options['color'] : $color;
    $speed = isset($options['speed']) ? $options['speed'] : $speed;
    $icon = isset($options['icon']) ? $options['icon'] : $icon;
    $size = isset($options['size']) ? $options['size'] : $size;
    
    $config = fae_cursor_get_effect_config($effect_name);
    $supports_color = isset($config['supports_color']) ? $config['supports_color'] : true;
    $supports_speed = isset($config['supports_speed']) ? $config['supports_speed'] : true;
    $supports_icon = isset($config['supports_icon']) ? $config['supports_icon'] : false;
    $supports_size = isset($config['supports_size']) ? $config['supports_size'] : false;
} elseif ($effect_type === 'keyboard') {
    $options = Fae_Keyboard_Settings::get_options();
    $color = isset($options['color']) ? $options['color'] : $color;
    $supports_speed = false;
} elseif ($effect_type === 'particle') {
    $options = Fae_Particle_Settings::get_options();
    $color = isset($options['color']) ? $options['color'] : $color;
    $speed = isset($options['speed']) ? $options['speed'] : $speed;
}

// Apply URL overrides
if ($url_color) $color = $url_color;
if ($url_speed) $speed = $url_speed;
if ($url_size) $size = $url_size;
if ($url_icon) $icon = $url_icon;

// Icons list
$icons_dir = FAE_CURSOR_DIR . '/assets/ionicons/';
$icon_files = glob($icons_dir . '*.svg');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview: <?php echo esc_html($display_name); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #1a1a2e; min-height: 100vh; font-family: system-ui, sans-serif; }
        
        /* Header */
        .fae-preview-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 16px;
            background: rgba(20,20,35,0.98);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            z-index: 1000;
        }
        .fae-preview-title { color: #fff; font-size: 14px; font-weight: 600; }
        .fae-preview-title span { color: #667eea; }
        .fae-header-actions { display: flex; gap: 8px; }
        .fae-btn {
            padding: 7px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .fae-btn-secondary {
            background: rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.8);
            border: 1px solid rgba(255,255,255,0.15);
        }
        .fae-btn-secondary:hover { background: rgba(255,255,255,0.15); color: #fff; }
        
        /* Preview Area */
        .fae-preview-area {
            position: fixed;
            top: 45px;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1;
        }
        
        /* Settings Panel */
        .fae-preview-panel {
            position: fixed;
            top: 55px;
            right: 12px;
            width: 260px;
            background: #252538;
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 10px;
            z-index: 1000;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
        }
        .fae-preview-panel.collapsed .fae-panel-body { display: none; }
        .fae-preview-panel.collapsed { width: auto; }
        
        .fae-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: linear-gradient(135deg, #667eea20 0%, #764ba220 100%);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            cursor: pointer;
        }
        .fae-panel-header:hover { background: linear-gradient(135deg, #667eea30 0%, #764ba230 100%); }
        .fae-panel-title { color: #fff; font-size: 13px; font-weight: 600; }
        .fae-panel-toggle { color: rgba(255,255,255,0.6); font-size: 10px; transition: transform 0.25s; }
        .fae-preview-panel.collapsed .fae-panel-toggle { transform: rotate(-90deg); }
        
        .fae-panel-body { padding: 16px; }
        
        .fae-setting-row { margin-bottom: 14px; }
        .fae-setting-row:last-child { margin-bottom: 0; }
        .fae-setting-row.hidden { display: none; }
        .fae-setting-label {
            display: block;
            color: rgba(255,255,255,0.7);
            font-size: 11px;
            margin-bottom: 6px;
            font-weight: 500;
        }
        .fae-setting-row input[type="color"] {
            width: 100%;
            height: 38px;
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 6px;
            cursor: pointer;
            background: #1e1e30;
        }
        .fae-setting-row input[type="color"]:hover { border-color: rgba(255,255,255,0.3); }
        .fae-setting-row select {
            width: 100%;
            padding: 9px 12px;
            background: #1e1e30;
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 6px;
            color: #fff;
            font-size: 13px;
            cursor: pointer;
        }
        .fae-setting-row select:hover { border-color: rgba(255,255,255,0.3); }
        .fae-setting-row select:focus { outline: none; border-color: #667eea; }
        .fae-setting-row select option { background: #1e1e30; }
        
        /* Icon Grid */
        .fae-icon-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 6px;
            max-height: 120px;
            overflow-y: auto;
            padding: 8px;
            background: #1e1e30;
            border: 2px solid rgba(255,255,255,0.15);
            border-radius: 6px;
        }
        .fae-icon-grid::-webkit-scrollbar { width: 4px; }
        .fae-icon-grid::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 2px; }
        .fae-icon-item {
            width: 34px; height: 34px;
            display: flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,0.08);
            border: 2px solid transparent;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s;
        }
        .fae-icon-item:hover { background: rgba(255,255,255,0.15); border-color: rgba(255,255,255,0.2); }
        .fae-icon-item.selected { border-color: #667eea; background: rgba(102,126,234,0.3); }
        .fae-icon-item svg { width: 18px; height: 18px; fill: #fff; }
        
        /* Panel Buttons */
        .fae-panel-buttons { margin-top: 16px; display: flex; flex-direction: column; gap: 8px; }
        .fae-btn-preview {
            width: 100%;
            padding: 10px;
            background: #1e1e30;
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 6px;
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .fae-btn-preview:hover { border-color: #667eea; background: rgba(102,126,234,0.1); }
        .fae-btn-save {
            width: 100%;
            padding: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 6px;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .fae-btn-save:hover { transform: translateY(-1px); box-shadow: 0 4px 15px rgba(102,126,234,0.4); }
        .fae-btn-save:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        
        /* Toast Notification */
        .fae-toast {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            padding: 12px 24px;
            background: #10b981;
            color: #fff;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            z-index: 2000;
            opacity: 0;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(16,185,129,0.4);
        }
        .fae-toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
        .fae-toast.error { background: #ef4444; box-shadow: 0 4px 20px rgba(239,68,68,0.4); }
        
        /* Hint */
        .fae-preview-hint {
            position: fixed;
            bottom: 12px;
            left: 50%;
            transform: translateX(-50%);
            padding: 8px 16px;
            background: rgba(20,20,35,0.95);
            border-radius: 20px;
            color: rgba(255,255,255,0.4);
            font-size: 11px;
            z-index: 1000;
            transition: opacity 0.3s ease;
        }
        .fae-preview-hint.hidden {
            opacity: 0;
            pointer-events: none;
        }
        
        /* Keyboard Input */
        .fae-preview-input {
            position: fixed;
            bottom: 50px;
            left: 50%;
            transform: translateX(-50%);
            width: 350px;
            padding: 10px 14px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 6px;
            color: #fff;
            font-size: 14px;
            outline: none;
            z-index: 500;
        }
        .fae-preview-input:focus { border-color: #667eea; }
    </style>
    <?php if (file_exists($css_path)) : ?>
    <?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- Preview page with inline styles ?>
    <link rel="stylesheet" href="<?php echo esc_url($css_url); ?>">
    <?php elseif (file_exists($css_path_alt)) : ?>
    <?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- Preview page with inline styles ?>
    <link rel="stylesheet" href="<?php echo esc_url($css_url_alt); ?>">
    <?php endif; ?>
</head>
<body>
    <!-- Header -->
    <div class="fae-preview-header" data-no-effect="true">
        <div class="fae-preview-title">Preview: <span><?php echo esc_html($display_name); ?></span></div>
        <div class="fae-header-actions">
            <a href="javascript:window.close();" class="fae-btn fae-btn-secondary">✕ Close</a>
        </div>
    </div>
    
    <!-- Preview Area -->
    <div class="fae-preview-area" id="fae-preview-area"></div>
    
    <!-- Settings Panel -->
    <div class="fae-preview-panel" id="fae-panel" data-no-effect="true">
        <div class="fae-panel-header" id="fae-panel-toggle">
            <div class="fae-panel-title">⚙ Customize</div>
            <div class="fae-panel-toggle">▼</div>
        </div>
        <div class="fae-panel-body">
            <div class="fae-setting-row <?php echo !$supports_color ? 'hidden' : ''; ?>">
                <label class="fae-setting-label">Color</label>
                <input type="color" id="fae-color" value="<?php echo esc_attr($color); ?>">
            </div>
            
            <div class="fae-setting-row <?php echo !$supports_speed ? 'hidden' : ''; ?>">
                <label class="fae-setting-label">Speed</label>
                <select id="fae-speed">
                    <option value="slow" <?php selected($speed, 'slow'); ?>>Slow</option>
                    <option value="normal" <?php selected($speed, 'normal'); ?>>Normal</option>
                    <option value="fast" <?php selected($speed, 'fast'); ?>>Fast</option>
                </select>
            </div>
            
            <div class="fae-setting-row <?php echo !$supports_size ? 'hidden' : ''; ?>">
                <label class="fae-setting-label">Size</label>
                <select id="fae-size">
                    <option value="1rem" <?php selected($size, '1rem'); ?>>Small</option>
                    <option value="1.5rem" <?php selected($size, '1.5rem'); ?>>Medium</option>
                    <option value="2rem" <?php selected($size, '2rem'); ?>>Large</option>
                    <option value="2.5rem" <?php selected($size, '2.5rem'); ?>>Extra Large</option>
                </select>
            </div>
            
            <?php if ($supports_icon) : ?>
            <div class="fae-setting-row">
                <label class="fae-setting-label">Icon</label>
                <div class="fae-icon-grid" id="fae-icon-grid">
                    <?php 
                    $priority_icons = ['star.svg', 'heart.svg', 'sparkles.svg', 'balloon.svg', 'diamond.svg'];
                    $shown_icons = array_merge($priority_icons, array_slice(array_map('basename', $icon_files), 0, 30));
                    $shown_icons = array_unique($shown_icons);
                    foreach ($shown_icons as $icon_file) :
                        $icon_path = $icons_dir . $icon_file;
                        if (!file_exists($icon_path)) continue;
                        $is_selected = ($icon_file === $icon) ? 'selected' : '';
                    ?>
                    <div class="fae-icon-item <?php echo esc_attr($is_selected); ?>" data-icon="<?php echo esc_attr($icon_file); ?>">
                        <?php echo file_get_contents($icon_path); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Local SVG file content ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="fae-panel-buttons">
                <button type="button" class="fae-btn-preview" id="fae-preview-btn">↻ Preview Changes</button>
                <button type="button" class="fae-btn-save" id="fae-save-btn">✓ Save Settings</button>
            </div>
        </div>
    </div>
    
    <?php if ($effect_type === 'keyboard') : ?>
    <input type="text" class="fae-preview-input" placeholder="Type here to see the effect..." autofocus>
    <?php endif; ?>
    
    <div class="fae-preview-hint" data-no-effect="true">
        <?php 
        if ($effect_type === 'keyboard') {
            echo 'Type to see effect';
        } elseif ($effect_name === 'fireworks-effect') {
            echo 'Click anywhere to see the fireworks effect';
        } else {
            echo 'Move mouse to see effect';
        }
        ?>
    </div>
    
    <!-- Toast -->
    <div class="fae-toast" id="fae-toast"></div>

    <script>
        var currentSettings = {
            effect: '<?php echo esc_js($effect_name); ?>',
            color: '<?php echo esc_js($color); ?>',
            speed: '<?php echo esc_js($speed); ?>',
            size: '<?php echo esc_js($size); ?>',
            icon: '<?php echo esc_js($icon); ?>',
            assetsUrl: '<?php echo esc_js(FAE_CURSOR_URL . 'assets/'); ?>',
            scope: { scope_type: 'entire_website' }
        };
        var effectType = '<?php echo esc_js($effect_type); ?>';
        var ajaxUrl = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
        var saveNonce = '<?php echo esc_js(wp_create_nonce('fae_save_preview_settings')); ?>';
        
        window.faeCursorSettings = currentSettings;
        
        // Scope functions
        var previewArea = document.getElementById('fae-preview-area');
        function isInPreviewArea(x, y, element) {
            if (element) {
                var el = element;
                while (el) {
                    if (el.hasAttribute && el.hasAttribute('data-no-effect')) return false;
                    el = el.parentElement;
                }
            }
            if (previewArea && x !== undefined && y !== undefined) {
                var rect = previewArea.getBoundingClientRect();
                return x >= rect.left && x <= rect.right && y >= rect.top && y <= rect.bottom;
            }
            return true;
        }
        window.faeCursorShouldTrigger = function(x, y, element) { return isInPreviewArea(x, y, element); };
        window.faeCursorIsInScope = function(element) { return isInPreviewArea(undefined, undefined, element); };
        window.faeCursorElementMatchesSelector = function() { return true; };
        
        // Panel toggle
        document.getElementById('fae-panel-toggle').addEventListener('click', function() {
            document.getElementById('fae-panel').classList.toggle('collapsed');
        });
        
        // Settings controls
        document.getElementById('fae-color')?.addEventListener('input', function() { currentSettings.color = this.value; });
        document.getElementById('fae-speed')?.addEventListener('change', function() { currentSettings.speed = this.value; });
        document.getElementById('fae-size')?.addEventListener('change', function() { currentSettings.size = this.value; });
        
        // Icon selection
        document.querySelectorAll('.fae-icon-item').forEach(function(item) {
            item.addEventListener('click', function() {
                document.querySelectorAll('.fae-icon-item').forEach(function(i) { i.classList.remove('selected'); });
                this.classList.add('selected');
                currentSettings.icon = this.dataset.icon;
            });
        });
        
        // Preview button - reload with new params
        document.getElementById('fae-preview-btn')?.addEventListener('click', function() {
            var params = new URLSearchParams(window.location.search);
            params.set('color', currentSettings.color);
            params.set('speed', currentSettings.speed);
            params.set('size', currentSettings.size);
            params.set('icon', currentSettings.icon);
            window.location.search = params.toString();
        });
        
        // Save button - AJAX save to database
        document.getElementById('fae-save-btn')?.addEventListener('click', function() {
            var btn = this;
            btn.disabled = true;
            btn.textContent = 'Saving...';
            
            var formData = new FormData();
            formData.append('action', 'fae_save_preview_settings');
            formData.append('nonce', saveNonce);
            formData.append('type', effectType);
            formData.append('color', currentSettings.color);
            formData.append('speed', currentSettings.speed);
            formData.append('size', currentSettings.size);
            formData.append('icon', currentSettings.icon);
            
            fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                btn.disabled = false;
                btn.textContent = '✓ Save Settings';
                if (data.success) {
                    showToast('Settings saved!', false);
                } else {
                    showToast('Error saving settings', true);
                }
            })
            .catch(function() {
                btn.disabled = false;
                btn.textContent = '✓ Save Settings';
                showToast('Error saving settings', true);
            });
        });
        
        // Toast function
        function showToast(message, isError) {
            var toast = document.getElementById('fae-toast');
            toast.textContent = message;
            toast.className = 'fae-toast' + (isError ? ' error' : '');
            toast.classList.add('show');
            setTimeout(function() { toast.classList.remove('show'); }, 3000);
        }
        
        // Hide hint on interaction
        var hint = document.querySelector('.fae-preview-hint');
        var effectName = '<?php echo esc_js($effect_name); ?>';
        
        if (hint && effectName === 'fireworks-effect') {
            // Hide hint on click for fireworks effect
            var clicked = false;
            document.addEventListener('click', function() {
                if (!clicked && hint) {
                    hint.classList.add('hidden');
                    clicked = true;
                }
            });
        } else if (hint && effectType !== 'keyboard') {
            // Hide hint on mouse move for other cursor effects
            var moved = false;
            document.addEventListener('mousemove', function() {
                if (!moved && hint) {
                    hint.classList.add('hidden');
                    moved = true;
                }
            });
        }
        
        // Escape to close
        document.addEventListener('keydown', function(e) { if (e.key === 'Escape') window.close(); });
    </script>
    <?php if (file_exists($js_path)) : ?>
    <?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Preview page with inline scripts ?>
    <script src="<?php echo esc_url($js_url); ?>"></script>
    <?php endif; ?>
</body>
</html>
