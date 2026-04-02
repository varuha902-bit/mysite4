<?php
/**
 * Admin Options Page View
 * Template for rendering the FaeCursor admin settings page
 */

if (!defined('ABSPATH')) {
    exit;
}

?>
<div class="wrap fae-cursor-dashboard">
    <!-- Header Section -->
    <div class="fae-dashboard-header">
        <div class="fae-header-content">
            <div class="fae-header-main">
                <h1 class="fae-dashboard-title">
                    <span class="fae-title-group">
                        <span class="fae-title-name">
                            FaeCursor <span class="fae-version-badge">v1.2.2</span>
                        </span>
                        <span class="fae-tagline">Move with Magic</span>
                    </span>
                </h1>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <?php
    // Determine active effects
    $active_effects = array();
    if ($selected_effect !== 'none') {
        $cursor_config = fae_cursor_get_effect_config($selected_effect);
        $active_effects[] = isset($cursor_config['display_name']) ? $cursor_config['display_name'] : ucwords(str_replace('-', ' ', $selected_effect));
    }
    if ($selected_keyboard_effect !== 'none') {
        $keyboard_config = fae_keyboard_get_effect_config($selected_keyboard_effect);
        $active_effects[] = isset($keyboard_config['display_name']) ? $keyboard_config['display_name'] : ucwords(str_replace('-', ' ', $selected_keyboard_effect));
    }
    $particle_options = Fae_Particle_Settings::get_options();
    $selected_particle = isset($particle_options['effect']) ? $particle_options['effect'] : 'none';
    if ($selected_particle !== 'none') {
        $particle_config = fae_particle_get_effect_config($selected_particle);
        $active_effects[] = isset($particle_config['display_name']) ? $particle_config['display_name'] : ucwords(str_replace('-', ' ', $selected_particle));
    }
    $is_active = !empty($active_effects);
    $active_count = count($active_effects);
    ?>
    <?php 
    // Count total effects (excluding 'none' option from each)
    $cursor_effects_config = fae_cursor_get_effects_config();
    $keyboard_effects_config = fae_keyboard_get_effects_config();
    $particle_effects_config = fae_particle_get_effects_config();
    
    // Count free and pro effects separately
    $free_count = 0;
    $pro_count = 0;
    
    // Count cursor effects
    foreach ($cursor_effects_config as $effect_id => $config) {
        if ($effect_id === 'none') continue;
        if (isset($config['is_pro']) && $config['is_pro'] === true) {
            $pro_count++;
        } else {
            $free_count++;
        }
    }
    
    // Count keyboard effects
    foreach ($keyboard_effects_config as $effect_id => $config) {
        if ($effect_id === 'none') continue;
        if (isset($config['is_pro']) && $config['is_pro'] === true) {
            $pro_count++;
        } else {
            $free_count++;
        }
    }
    
    // Count particle effects
    foreach ($particle_effects_config as $effect_id => $config) {
        if ($effect_id === 'none') continue;
        if (isset($config['is_pro']) && $config['is_pro'] === true) {
            $pro_count++;
        } else {
            $free_count++;
        }
    }
    
    $total_effects = $free_count + $pro_count;
    ?>
    <div class="fae-stats-grid">
        <div class="fae-stat-card <?php echo !$is_active ? 'fae-stat-card-inactive' : ''; ?>">
            <h3>Status</h3>
            <p class="fae-stat-value"><?php echo $is_active ? 'Active' : 'Inactive'; ?></p>
        </div>
        <div class="fae-stat-card <?php echo !$is_active ? 'fae-stat-card-inactive' : ''; ?>">
            <h3>Active Effects</h3>
            <p class="fae-stat-value <?php echo $active_count > 1 ? 'fae-stat-value-small' : ''; ?>">
                <?php 
                if (empty($active_effects)) {
                    echo 'None';
                } elseif ($active_count === 1) {
                    echo esc_html($active_effects[0]);
                } else {
                    // Multiple effects - show count
                    echo esc_html($active_count) . ' Active';
                }
                ?>
            </p>
            <?php if ($active_count > 1) : ?>
            <p class="fae-stat-detail"><?php echo esc_html(implode(', ', $active_effects)); ?></p>
            <?php endif; ?>
        </div>
        <div class="fae-stat-card">
            <h3>Effects Available</h3>
            <p class="fae-stat-value"><?php echo esc_html($total_effects); ?></p>
            <?php if ($pro_count > 0) : ?>
            <p class="fae-stat-detail"><?php echo esc_html($free_count); ?> Free<?php echo $pro_count > 0 ? ' + ' . esc_html($pro_count) . ' Pro' : ''; ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Review Request Notification -->
    <?php Fae_Cursor_Review::render_notification(); ?>

    <!-- Main Content -->
    <div class="fae-main-content">
        <!-- Tabs Navigation with Status Indicators -->
        <div class="fae-tabs-wrapper">
            <div class="fae-tabs-nav">
                <button type="button" class="fae-tab-button active" data-tab="cursor-effects">
                    <svg class="fae-icon" viewBox="0 0 32 32">
                        <path d="M5,2.5l22,9c0.5,0.2,0.7,0.8,0.4,1.2c-0.1,0.2-0.3,0.3-0.5,0.4l-8.6,2.3l3.6,8.1c0.2,0.5,0,1.1-0.5,1.3
                        c-0.4,0.2-0.9,0.1-1.1-0.2l-4.7-6l-3.2,6.9c-0.2,0.5-0.7,0.7-1.1,0.6c-0.3-0.1-0.5-0.3-0.6-0.6l-7-24C3,2.8,3.3,2.3,3.8,2.3
                        C4.1,2.3,4.4,2.4,5,2.5z" fill="currentColor"/>
                    </svg>
                    Cursor Effects
                    <span class="fae-tab-status <?php echo ($selected_effect !== 'none') ? 'active' : ''; ?>" data-effect-type="cursor" title="<?php echo ($selected_effect !== 'none') ? 'Active' : 'Inactive'; ?>">
                        <span class="fae-status-dot"></span>
                    </span>
                </button>
                <button type="button" class="fae-tab-button" data-tab="keyboard-effects">
                    
                    <svg class="fae-icon" viewBox="0 0 512 512">
                        <rect x="64" y="128" width="384" height="256" rx="32" fill="currentColor"/>
                        <rect x="96" y="160" width="48" height="48" rx="8" fill="#fff"/>
                        <rect x="168" y="160" width="48" height="48" rx="8" fill="#fff"/>
                        <rect x="240" y="160" width="48" height="48" rx="8" fill="#fff"/>
                        <rect x="312" y="160" width="48" height="48" rx="8" fill="#fff"/>
                        <rect x="384" y="160" width="32" height="48" rx="8" fill="#fff"/>
                        <rect x="96" y="224" width="48" height="48" rx="8" fill="#fff"/>
                        <rect x="168" y="224" width="48" height="48" rx="8" fill="#fff"/>
                        <rect x="240" y="224" width="48" height="48" rx="8" fill="#fff"/>
                        <rect x="312" y="224" width="48" height="48" rx="8" fill="#fff"/>
                        <rect x="384" y="224" width="32" height="48" rx="8" fill="#fff"/>
                        <rect x="96" y="288" width="80" height="48" rx="8" fill="#fff"/>
                        <rect x="192" y="288" width="80" height="48" rx="8" fill="#fff"/>
                        <rect x="288" y="288" width="80" height="48" rx="8" fill="#fff"/>
                    </svg>
                Keyboard Effects
                    <span class="fae-tab-status <?php echo ($selected_keyboard_effect !== 'none') ? 'active' : ''; ?>" data-effect-type="keyboard" title="<?php echo ($selected_keyboard_effect !== 'none') ? 'Active' : 'Inactive'; ?>">
                        <span class="fae-status-dot"></span>
                    </span>
                </button>
                <button type="button" class="fae-tab-button" data-tab="particle-effects">
                    <svg class="fae-icon" viewBox="0 0 512 512">
                        <path d="M280,0H232a24,24,0,0,0-24,24V88H152a24,24,0,0,0-24,24v80H80a24,24,0,0,0-24,24v80H0v32H56v80a24,24,0,0,0,24,24h48v80a24,24,0,0,0,24,24h48V488a24,24,0,0,0,24,24h48a24,24,0,0,0,24-24V408h48a24,24,0,0,0,24-24V304h56V272H456V192a24,24,0,0,0-24-24H384V88a24,24,0,0,0-24-24H280V0ZM256,192v80H176V192h80Zm0-112V80h48V80h48v48H304v32H256Zm80,80h80v48H336V160ZM176,352h80v80H176V352ZM80,304H160v48H80V304Zm352,48V352h48v48H432Z"/>
                    </svg>
                    Screen Effects
                    <span class="fae-tab-status <?php 
                        $particle_options = Fae_Particle_Settings::get_options();
                        $selected_particle_effect = isset($particle_options['effect']) ? $particle_options['effect'] : 'none';
                        echo ($selected_particle_effect !== 'none') ? 'active' : ''; 
                    ?>" data-effect-type="particle" title="<?php echo ($selected_particle_effect !== 'none') ? 'Active' : 'Inactive'; ?>">
                        <span class="fae-status-dot"></span>
                    </span>
                </button>
            </div>
        </div>

        <!-- Tab Content: Cursor Effects -->
        <div class="fae-tab-content active" id="cursor-effects-tab">
        <div class="fae-settings-panel">
            <form id="fae-cursor-form" action="options.php" method="post">
                <?php settings_fields('fae_cursor'); ?>
                
                <div class="fae-split-layout">
                    <!-- Main Content (Left) -->
                    <div class="fae-main-content">
                        <!-- Effects Selection Container -->
                        <div class="fae-settings-group-container">
                            <div class="fae-settings-group-header">
                                <div class="fae-settings-group-text">
                                    <h3 class="fae-settings-group-title">Effects</h3>
                                    <p class="fae-settings-group-description">Choose a cursor effect to apply</p>
                                </div>
                            </div>
                            <div class="fae-settings-group-content">
                                <div class="fae-effect-grid">
                                    <?php 
                                    $effects_config = fae_cursor_get_effects_config();
                                    $is_pro_active = Fae_Cursor_Pro::is_pro();
                                    foreach ($effects_config as $effect_id => $config) : 
                                        $abbrev = Fae_Cursor_Admin::get_effect_abbrev($effect_id, 'cursor');
                                        $is_pro_effect = isset($config['is_pro']) && $config['is_pro'] === true;
                                        $is_disabled = $is_pro_effect && !$is_pro_active;
                                    ?>
                                    <label class="fae-effect-option <?php echo $is_disabled ? 'fae-effect-pro-locked' : ''; ?>" data-effect-id="<?php echo esc_attr($effect_id); ?>">
                                        <input type="radio" name="fae_cursor_options[effect]" value="<?php echo esc_attr($effect_id); ?>" <?php checked($selected_effect, $effect_id); ?> <?php echo $is_disabled ? 'disabled' : ''; ?>>
                                        <div class="fae-effect-content">
                                            <span class="fae-effect-letter" data-effect="<?php echo esc_attr($effect_id); ?>"><?php echo esc_html($abbrev); ?></span>
                                            <span class="fae-effect-name"><?php echo esc_html($config['display_name']); ?></span>
                                            <?php if ($is_pro_effect) : ?>
                                                <?php echo Fae_Cursor_Pro::get_pro_badge(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                            <?php endif; ?>
                                        </div>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Effect-Specific Settings (Display Rules) -->
                        <div class="fae-effect-settings" style="<?php echo $selected_effect === 'none' ? 'display: none;' : ''; ?>">

                    <!-- ═══════════════════════════════════════════════════════════════ -->
                    <!-- GROUP 2: DISPLAY RULES - Where, when, and who sees the effect -->
                    <!-- ═══════════════════════════════════════════════════════════════ -->
                    <div class="fae-settings-group-container">
                        <div class="fae-settings-group-header">
                            <div class="fae-settings-group-text">
                                <h3 class="fae-settings-group-title">Display Rules</h3>
                                <p class="fae-settings-group-description">Control where and who sees the effect</p>
                        </div>
                    </div>

                            <div class="fae-settings-group-content">
                            <!-- Cursor Appearance -->
                            <div class="fae-settings-subgroup" id="fae-cursor-appearance-subgroup" style="<?php echo (fae_cursor_effect_supports($selected_effect, 'hide_cursor') && $selected_effect !== 'none') ? '' : 'display: none;'; ?>">
                                <div class="fae-settings-subgroup-header">
                                    <svg class="fae-subgroup-icon" viewBox="0 0 32 32">
                                        <path d="M5,2.5l22,9c0.5,0.2,0.7,0.8,0.4,1.2c-0.1,0.2-0.3,0.3-0.5,0.4l-8.6,2.3l3.6,8.1c0.2,0.5,0,1.1-0.5,1.3
                                        c-0.4,0.2-0.9,0.1-1.1-0.2l-4.7-6l-3.2,6.9c-0.2,0.5-0.7,0.7-1.1,0.6c-0.3-0.1-0.5-0.3-0.6-0.6l-7-24C3,2.8,3.3,2.3,3.8,2.3
                                        C4.1,2.3,4.4,2.4,5,2.5z"/>
                                    </svg>
                                    <span class="fae-subgroup-title">Cursor Appearance</span>
                                    <button type="button" class="fae-info-button fae-info-button-inline" aria-label="More information about Hide Default Cursor" style="margin-left: auto;">
                                        <svg class="fae-icon" viewBox="0 0 24 24" style="width: 18px; height: 18px;">
                                            <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                                            <line x1="12" y1="8" x2="12" y2="8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            <path d="M11 11h1a1 1 0 0 1 1 1v4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        </svg>
                                        <div class="fae-tooltip fae-tooltip-inline">
                                            <div class="fae-tooltip-content">
                                                <p>When enabled, the normal browser cursor is hidden and only the cursor effect is visible.</p>
                                            </div>
                                        </div>
                                    </button>
                                </div>
                                <div class="fae-settings-subgroup-content">
                                    <div class="fae-setting-item">
                                        <label class="fae-toggle-switch" for="fae_hide_default_cursor_toggle">
                                            <input type="checkbox" name="fae_cursor_options[hide_default_cursor]" value="1" <?php checked($hide_default_cursor, '1'); ?> id="fae_hide_default_cursor_toggle" class="fae-toggle-input">
                                            <span class="fae-toggle-slider"></span>
                                            <span class="fae-toggle-label">Hide Default Cursor</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Page Targeting -->
                            <div class="fae-settings-subgroup">
                                <div class="fae-settings-subgroup-header">
                                    <svg class="fae-subgroup-icon" viewBox="0 0 512 512">
                                        <path d="M428,224H288a48,48,0,0,1-48-48V36a4,4,0,0,0-4-4H144A64,64,0,0,0,80,96V416a64,64,0,0,0,64,64H368a64,64,0,0,0,64-64V228A4,4,0,0,0,428,224Z"/>
                                        <path d="M419.22,188.59,275.41,44.78A2,2,0,0,0,272,46.19V176a16,16,0,0,0,16,16H417.81A2,2,0,0,0,419.22,188.59Z"/>
                            </svg>
                                    <span class="fae-subgroup-title">Page Targeting</span>
                                    </div>
                                <div class="fae-settings-subgroup-content">
                                    <div class="fae-setting-item">
                                        <label for="fae_cursor_scope_type">
                                            <span class="fae-setting-label">Display Effect On</span>
                                        </label>
                            <?php 
                            $is_pro_active = Fae_Cursor_Pro::is_pro();
                            $css_selector_pro = Fae_Cursor_Pro::is_pro_feature('css_selector_scoping') && !$is_pro_active;
                            $specific_pages_pro = Fae_Cursor_Pro::is_pro_feature('specific_pages_scoping') && !$is_pro_active;
                            ?>
                            <select name="fae_cursor_options[scope_type]" id="fae_cursor_scope_type" class="fae-scope-type-select">
                                <option value="entire_website" <?php selected($scope_type, 'entire_website'); ?>>Entire Website</option>
                                <option value="specific_pages" <?php selected($scope_type, 'specific_pages'); ?> <?php echo $specific_pages_pro ? 'disabled' : ''; ?>>Specific Pages<?php echo $specific_pages_pro ? ' (Pro)' : ''; ?></option>
                                <option value="css_selector" <?php selected($scope_type, 'css_selector'); ?> <?php echo $css_selector_pro ? 'disabled' : ''; ?>>CSS Selector<?php echo $css_selector_pro ? ' (Pro)' : ''; ?></option>
                            </select>
                            <?php if ($css_selector_pro || $specific_pages_pro) : ?>
                                <!-- Hidden inputs to force Pro scoping options to defaults -->
                                <input type="hidden" name="fae_cursor_options[scope_type]" value="entire_website">
                                <input type="hidden" name="fae_cursor_options[scope_pages]" value="">
                                <input type="hidden" name="fae_cursor_options[scope_css_selector]" value="">
                                <?php echo Fae_Cursor_Pro::get_upgrade_notice('Advanced scoping options require Pro.'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Device Visibility -->
                            <div class="fae-settings-subgroup">
                                <div class="fae-settings-subgroup-header">
                                    <svg class="fae-subgroup-icon" viewBox="0 0 512 512">
                                        <rect x="128" y="16" width="256" height="480" rx="48" ry="48" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/>
                                        <path d="M176,16h24a8,8,0,0,1,8,8h0a16,16,0,0,0,16,16h64a16,16,0,0,0,16-16h0a8,8,0,0,1,8-8h24" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/>
                            </svg>
                                    <span class="fae-subgroup-title">Device Visibility</span>
                                </div>
                                <div class="fae-settings-subgroup-content">
                                    <p class="fae-setting-hint" style="margin-bottom: 15px;">Hide the effect on specific device types</p>
                                    <div class="fae-device-toggles">
                                        <label class="fae-toggle-switch" for="fae_hide_on_mobile">
                                            <input type="checkbox" name="fae_cursor_options[hide_on_mobile]" value="1" <?php checked($hide_on_mobile, '1'); ?> id="fae_hide_on_mobile" class="fae-toggle-input">
                                            <span class="fae-toggle-slider"></span>
                                            <span class="fae-toggle-label">Hide on Mobile</span>
                                        </label>
                                        
                                        <label class="fae-toggle-switch" for="fae_hide_on_tablet">
                                            <input type="checkbox" name="fae_cursor_options[hide_on_tablet]" value="1" <?php checked($hide_on_tablet, '1'); ?> id="fae_hide_on_tablet" class="fae-toggle-input">
                                            <span class="fae-toggle-slider"></span>
                                            <span class="fae-toggle-label">Hide on Tablet</span>
                                        </label>
                                        
                                        <label class="fae-toggle-switch" for="fae_hide_on_desktop">
                                            <input type="checkbox" name="fae_cursor_options[hide_on_desktop]" value="1" <?php checked($hide_on_desktop, '1'); ?> id="fae_hide_on_desktop" class="fae-toggle-input">
                                            <span class="fae-toggle-slider"></span>
                                            <span class="fae-toggle-label">Hide on Desktop</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- User Roles -->
                            <div class="fae-settings-subgroup">
                                <div class="fae-settings-subgroup-header">
                                    <svg class="fae-subgroup-icon" viewBox="0 0 512 512">
                                        <path d="M336,256c-20.56,0-40.44-9.18-56-25.84-15.13-16.25-24.37-37.92-26-61-1.74-24.62,5.77-47.26,21.14-63.76S312,80,336,80c23.83,0,45.38,9.06,60.7,25.52,15.47,16.62,23,39.22,21.26,63.63h0c-1.67,23.11-10.9,44.77-26,61C376.44,246.82,356.57,256,336,256Z"/>
                                        <path d="M467.83,432H204.18a27.71,27.71,0,0,1-22-10.67,30.22,30.22,0,0,1-5.26-25.79c8.42-33.81,29.28-61.85,60.32-81.08C264.79,297.4,299.86,288,336,288c36.85,0,71,9,98.71,26.05,31.11,19.13,52,47.33,60.38,81.55a30.27,30.27,0,0,1-5.32,25.78A27.68,27.68,0,0,1,467.83,432Z"/>
                                        <path d="M147,260c-35.19,0-66.13-32.72-69-72.93C76.58,166.47,83,147.42,96,133.45,108.86,119.62,127,112,147,112s38.16,7.62,51,21.45c13,14,19.42,33,17.88,53.62C213.13,227.28,182.19,260,147,260Z"/>
                                        <path d="M212.66,291.45c-17.59-8.6-40.42-12.9-65.65-12.9-29.46,0-58.07,7.68-80.57,21.62C40.93,316,23.77,339.05,16.84,366.88a27.39,27.39,0,0,0,4.79,23.36A25.32,25.32,0,0,0,41.72,400h111.83"/>
                                    </svg>
                                    <span class="fae-subgroup-title">User Restrictions</span>
                                </div>
                                <div class="fae-settings-subgroup-content">
                            <?php 
                            $has_role_restriction = !empty($user_roles);
                            $user_roles_pro = Fae_Cursor_Pro::is_pro_feature('user_role_restrictions') && !Fae_Cursor_Pro::is_pro();
                            ?>
                                    <div class="fae-radio-group">
                                        <label class="fae-radio-item">
                                    <input type="radio" name="fae_cursor_user_restriction_type" value="all" <?php checked(!$has_role_restriction); ?> class="fae-user-restriction-type">
                                            <span class="fae-radio-label">
                                                <strong>All Users</strong>
                                                <small>Logged-in and logged-out visitors</small>
                                            </span>
                                </label>
                                        <label class="fae-radio-item <?php echo $user_roles_pro ? 'fae-radio-item-disabled' : ''; ?>">
                                    <input type="radio" name="fae_cursor_user_restriction_type" value="specific" <?php checked($has_role_restriction); ?> <?php echo $user_roles_pro ? 'disabled' : ''; ?> class="fae-user-restriction-type">
                                            <span class="fae-radio-label">
                                                <strong>Specific User Roles<?php echo $user_roles_pro ? ' (Pro)' : ''; ?></strong>
                                                <small>Choose which roles can see the effect</small>
                                            </span>
                                </label>
                            </div>
                            <?php if ($user_roles_pro) : ?>
                                <!-- Hidden inputs to force Pro user role options to defaults -->
                                <input type="hidden" name="fae_cursor_options[user_roles]" value="">
                                <input type="hidden" name="fae_cursor_options[include_logged_out]" value="0">
                                <?php echo Fae_Cursor_Pro::get_upgrade_notice('User role restrictions require Pro.'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                        </div>

                        <!-- Action Buttons -->
                        <div class="fae-action-buttons">
                            <button type="submit" class="fae-btn fae-btn-primary">
                                <svg class="fae-icon" viewBox="0 0 512 512" style="width: 16px; height: 16px;">
                                    <path d="M173.898,439.404l-166.4-166.4c-9.997-9.997-9.997-26.206,0-36.204l36.203-36.204c9.997-9.998,26.207-9.998,36.204,0L192,312.69,432.095,72.596c9.997-9.997,26.207-9.997,36.204,0l36.203,36.204c9.997,9.997,9.997,26.206,0,36.204l-294.4,294.401C383.105,449.401,366.896,449.401,356.898,439.404z"/>
                                </svg>
                                Save Settings
                            </button>
                        </div>
                    </div><!-- /.fae-main-content -->
                    
                    <!-- Sticky Sidebar (Right) -->
                    <div class="fae-sidebar-preview">
                        <div class="fae-preview-card">
                            <!-- Preview Section -->
                            <div class="fae-preview-section">
                                <div class="fae-preview-header-inline">
                                    <span>Live Preview</span>
                                    <div class="fae-preview-header-actions">
                                        <button type="button" class="fae-preview-bg-toggle" id="fae-cursor-preview-bg" data-bg="<?php echo esc_attr($preview_bg); ?>" title="Toggle Preview Background">
                                            <span class="fae-bg-toggle-icon fae-bg-icon-moon">
                                                <svg viewBox="0 0 512 512" fill="none" stroke="currentColor" stroke-width="32" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M160 136c0-30.62 4.51-61.61 16-88C99 57.57 48 141.35 48 230c0 114.9 93.1 208 208 208 88.65 0 172.43-51 182-128-26.39 11.49-57.38 16-88 16-114.9 0-208-93.1-208-208z"/>
                                                </svg>
                                            </span>
                                            <span class="fae-bg-toggle-icon fae-bg-icon-sun">
                                                <svg viewBox="0 0 512 512" fill="none" stroke="currentColor" stroke-width="32" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="256" cy="256" r="80"/>
                                                    <path d="M256 48v80M256 384v80M448 256h-80M144 256H64M393.14 118.86l-56.57 56.57M175.43 336.57l-56.57 56.57M393.14 393.14l-56.57-56.57M175.43 175.43l-56.57-56.57"/>
                                                </svg>
                                            </span>
                                        </button>
                                        <button type="button" class="fae-expand-btn" data-preview-type="cursor">
                                            <svg viewBox="0 0 512 512"><path d="M432,320v112a16,16,0,0,1-16,16H96a16,16,0,0,1-16-16V320" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><polyline points="368 304 432 368 496 304" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><line x1="432" y1="368" x2="432" y2="144" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><path d="M80,192V80a16,16,0,0,1,16-16H416a16,16,0,0,1,16,16v112" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/></svg>
                                            Expand
                                        </button>
                                    </div>
                                </div>
                                <div class="fae-preview-iframe-wrapper" style="background: <?php echo ($preview_bg === 'light') ? 'linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%)' : 'linear-gradient(135deg, #1a1a2e 0%, #16213e 100%)'; ?>;">
                                    <iframe 
                                        id="fae-cursor-preview-iframe" 
                                        class="fae-preview-iframe"
                                        src="<?php echo esc_url(admin_url('admin.php?fae_embed_preview=1&type=cursor&effect=none&color=' . urlencode($selected_color) . '&speed=' . $selected_speed . '&size=' . urlencode($selected_size) . '&icon=' . $selected_icon . '&flag=' . urlencode($selected_flag) . '&flag_position=' . urlencode($flag_position) . '&multi_color=' . $multi_color . '&bg=' . urlencode($preview_bg) . '&_t=' . time())); ?>"
                                        onload="this.classList.add('loaded')"
                                    ></iframe>
                                </div>
                            </div>
                            
                            <!-- Appearance Settings -->
                            <div class="fae-appearance-section" style="<?php echo $selected_effect === 'none' ? 'display: none;' : ''; ?>">
                                <div class="fae-preview-header-inline">
                                    <span>Appearance</span>
                                </div>
                                <div class="fae-appearance-settings">
                                    <div class="fae-inline-setting fae-flag-setting" style="<?php echo ($selected_effect === 'flag-effect') ? '' : 'display: none;'; ?>">
                                        <label>Flag</label>
                                        <div class="fae-flag-picker-inline">
                                            <button type="button" class="fae-flag-trigger" id="fae-flag-trigger-cursor">
                                                <span class="fae-flag-preview-small" id="fae-flag-preview-cursor">
                                                    <?php
                                                    if ($selected_flag) {
                                                        $selected_flag_path = FAE_CURSOR_DIR . '/assets/flags/' . $selected_flag;
                                                        if (file_exists($selected_flag_path)) {
                                                            echo '<img src="' . esc_url(FAE_CURSOR_URL . 'assets/flags/' . $selected_flag) . '" alt="' . esc_attr($selected_flag) . '" style="width: 24px; height: 18px; object-fit: cover; border-radius: 2px;">';
                                                        } else {
                                                            echo '<span style="color: #9ca3af; font-size: 12px;">None</span>';
                                                        }
                                                    } else {
                                                        echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px; color: #9ca3af;">
                                                            <path d="M19 11H5m14 0a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2m14 0V9a2 2 0 0 0-2-2M5 11V9a2 2 0 0 1 2-2m0 0V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2M7 7h10"/>
                                                        </svg>';
                                                    }
                                                    ?>
                                                </span>
                                                <span class="fae-flag-name-small" id="fae-flag-name-cursor">
                                                    <?php 
                                                    if ($selected_flag) {
                                                        echo esc_html(strtoupper(str_replace('.svg', '', $selected_flag)));
                                                    } else {
                                                        echo 'FILL';
                                                    }
                                                    ?>
                                                </span>
                                                <svg class="fae-icon-chevron" viewBox="0 0 512 512"><path d="M233.4 406.6c12.5 12.5 32.8 12.5 45.3 0l192-192c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L256 338.7 86.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l192 192z"/></svg>
                                            </button>
                                            <input type="hidden" name="fae_cursor_options[flag]" value="<?php echo esc_attr($selected_flag); ?>" id="fae-cursor-flag">
                                            <div class="fae-flag-dropdown" id="fae-flag-dropdown-cursor">
                                                <div class="fae-flag-dropdown-header">
                                                    <input type="text" class="fae-flag-search" placeholder="Search by country name (e.g., India)..." id="fae-flag-search-cursor">
                                                </div>
                                                <div class="fae-flag-dropdown-grid" id="fae-flag-grid-cursor">
                                                    <div class="fae-flag-dropdown-item <?php echo empty($selected_flag) ? 'selected' : ''; ?>" data-flag="" data-name="FILL">
                                                        <div class="fae-flag-preview-placeholder" style="display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 12px; font-weight: 600;">
                                                            FILL
                                                        </div>
                                                        <span class="fae-flag-label">FILL</span>
                                                    </div>
                                                    <?php
                                                    $flags_dir = FAE_CURSOR_DIR . '/assets/flags/';
                                                    if (is_dir($flags_dir)) {
                                                        $flags = glob($flags_dir . '*.svg');
                                                        usort($flags, function($a, $b) {
                                                            return strcasecmp(basename($a), basename($b));
                                                        });
                                                        foreach ($flags as $flag_path) {
                                                            $flag_file = basename($flag_path);
                                                            $flag_code = strtolower(str_replace('.svg', '', $flag_file));
                                                            $flag_name = strtoupper($flag_code);
                                                            $country_name = fae_get_country_name($flag_code);
                                                            $is_selected = ($selected_flag === $flag_file) ? 'selected' : '';
                                                            $flag_url = FAE_CURSOR_URL . 'assets/flags/' . $flag_file;
                                                            echo '<div class="fae-flag-dropdown-item ' . esc_attr($is_selected) . '" data-flag="' . esc_attr($flag_file) . '" data-name="' . esc_attr($flag_name) . '" data-country-name="' . esc_attr(strtolower($country_name)) . '">';
                                                            echo '<img src="' . esc_url($flag_url) . '" alt="' . esc_attr($country_name) . '" class="fae-flag-preview-img">';
                                                            echo '</div>';
                                                        }
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="fae-inline-setting fae-flag-position-setting" style="<?php echo ($selected_effect === 'flag-effect' && !empty($selected_flag)) ? '' : 'display: none;'; ?>">
                                        <label>Flag Position</label>
                                        <select name="fae_cursor_options[flag_position]" id="fae-cursor-flag-position">
                                            <option value="center" <?php selected($flag_position, 'center'); ?>>Center</option>
                                            <option value="top" <?php selected($flag_position, 'top'); ?>>Top</option>
                                            <option value="bottom" <?php selected($flag_position, 'bottom'); ?>>Bottom</option>
                                            <option value="left" <?php selected($flag_position, 'left'); ?>>Left</option>
                                            <option value="right" <?php selected($flag_position, 'right'); ?>>Right</option>
                                            <option value="top-left" <?php selected($flag_position, 'top-left'); ?>>Top Left</option>
                                            <option value="top-right" <?php selected($flag_position, 'top-right'); ?>>Top Right</option>
                                            <option value="bottom-left" <?php selected($flag_position, 'bottom-left'); ?>>Bottom Left</option>
                                            <option value="bottom-right" <?php selected($flag_position, 'bottom-right'); ?>>Bottom Right</option>
                                        </select>
                                    </div>
                                    
                                    <?php
                                    // Check if current effect has limited customization
                                    $effect_has_limited_customization = fae_cursor_effect_has_limited_customization($selected_effect);
                                    $can_customize_color = fae_can_customize_cursor_color($selected_effect);
                                    $can_customize_speed = fae_can_customize_cursor_speed($selected_effect);
                                    ?>
                                    <div class="fae-inline-setting fae-color-setting" style="<?php 
                                        // For flag-effect, show color only if no flag is selected
                                        if ($selected_effect === 'flag-effect') {
                                            echo empty($selected_flag) ? '' : 'display: none;';
                                        } else {
                                            // For other effects, check if effect supports color
                                            echo fae_cursor_effect_supports($selected_effect, 'color') ? '' : 'display: none;';
                                        }
                                    ?>" data-limited-customization="<?php echo $effect_has_limited_customization ? '1' : '0'; ?>">
                                        <label>Color <?php if ($effect_has_limited_customization && !$can_customize_color) : ?><span class="fae-pro-badge fae-pro-badge-inline">PRO</span><?php endif; ?></label>
                                        <div class="fae-color-input-inline <?php echo ($effect_has_limited_customization && !$can_customize_color) ? 'fae-color-locked' : ''; ?>">
                                            <?php if ($effect_has_limited_customization && !$can_customize_color) : ?>
                                                <!-- Free users on limited effects: show locked color swatch -->
                                                <div class="fae-color-swatch-locked" style="background-color: <?php echo esc_attr(fae_get_free_default_color()); ?>;" title="Upgrade to Pro to customize color"></div>
                                                <input type="hidden" name="fae_cursor_options[color]" value="<?php echo esc_attr(fae_get_free_default_color()); ?>" id="fae-cursor-color">
                                                <span class="fae-color-locked-text"><?php echo esc_html(fae_get_free_default_color()); ?></span>
                                            <?php else : ?>
                                                <input type="color" class="fae-color-picker-inline" name="fae_cursor_options[color]" value="<?php echo esc_attr($selected_color); ?>" id="fae-cursor-color">
                                                <input type="text" value="<?php echo esc_attr($selected_color); ?>" id="fae-cursor-color-text">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="fae-inline-setting fae-multi-color-setting" style="<?php echo ($selected_effect === 'bubbles-effect' || $selected_effect === 'magic-trail') ? '' : 'display: none;'; ?>">
                                        <label>
                                            <input type="checkbox" name="fae_cursor_options[multi_color]" value="1" disabled id="fae-cursor-multi-color">
                                            Multi-Color <?php echo Fae_Cursor_Pro::get_pro_badge(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                        </label>
                                        <!-- Hidden input to always force multi_color to 0 (Pro feature protection) -->
                                        <input type="hidden" name="fae_cursor_options[multi_color]" value="0">
                                    </div>
                                    
                                    <div class="fae-inline-setting fae-speed-setting" style="<?php echo fae_cursor_effect_supports($selected_effect, 'speed') ? '' : 'display: none;'; ?>" data-limited-customization="<?php echo $effect_has_limited_customization ? '1' : '0'; ?>">
                                        <label>Speed</label>
                                        <select name="fae_cursor_options[speed]" id="fae-cursor-speed" <?php echo ($effect_has_limited_customization && !$can_customize_speed) ? 'class="fae-speed-locked"' : ''; ?>>
                                            <option value="slow" <?php selected($selected_speed, 'slow'); ?> <?php echo ($effect_has_limited_customization && !$can_customize_speed) ? 'disabled' : ''; ?>>Slow <?php echo ($effect_has_limited_customization && !$can_customize_speed) ? '(Pro)' : ''; ?></option>
                                            <option value="normal" <?php selected($selected_speed, 'normal'); ?>>Normal</option>
                                            <option value="fast" <?php selected($selected_speed, 'fast'); ?> <?php echo ($effect_has_limited_customization && !$can_customize_speed) ? 'disabled' : ''; ?>>Fast <?php echo ($effect_has_limited_customization && !$can_customize_speed) ? '(Pro)' : ''; ?></option>
                                        </select>
                                    </div>
                                    
                                    <div class="fae-inline-setting fae-icon-setting" style="<?php echo fae_cursor_effect_supports($selected_effect, 'icon') ? '' : 'display: none;'; ?>">
                                        <label>Size</label>
                                        <select name="fae_cursor_options[size]" id="fae-cursor-size">
                                            <option value="1rem" <?php selected($selected_size, '1rem'); ?>>Small</option>
                                            <option value="1.5rem" <?php selected($selected_size, '1.5rem'); ?>>Medium</option>
                                            <option value="2rem" <?php selected($selected_size, '2rem'); ?>>Large</option>
                                            <option value="2.5rem" <?php selected($selected_size, '2.5rem'); ?>>Extra Large</option>
                                        </select>
                                    </div>
                                    
                                    <div class="fae-inline-setting fae-icon-setting" style="<?php echo fae_cursor_effect_supports($selected_effect, 'icon') ? '' : 'display: none;'; ?>">
                                        <label>Icon</label>
                                        <div class="fae-icon-picker-inline">
                                            <button type="button" class="fae-icon-trigger" id="fae-icon-trigger-cursor">
                                                <span class="fae-icon-preview-small" id="fae-icon-preview-cursor">
                                                    <?php
                                                    $selected_icon_path = FAE_CURSOR_DIR . '/assets/ionicons/' . $selected_icon;
                                                    if (file_exists($selected_icon_path)) {
                                                        echo file_get_contents($selected_icon_path); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Local SVG file content
                                                    }
                                                    ?>
                                                </span>
                                                <span class="fae-icon-name-small" id="fae-icon-name-cursor"><?php echo esc_html(str_replace('.svg', '', $selected_icon)); ?></span>
                                                <svg class="fae-icon-chevron" viewBox="0 0 512 512"><path d="M233.4 406.6c12.5 12.5 32.8 12.5 45.3 0l192-192c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L256 338.7 86.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l192 192z"/></svg>
                                            </button>
                                            <input type="hidden" name="fae_cursor_options[icon]" value="<?php echo esc_attr($selected_icon); ?>" id="fae-cursor-icon">
                                            <div class="fae-icon-dropdown" id="fae-icon-dropdown-cursor">
                                                <div class="fae-icon-dropdown-grid">
                                                    <?php
                                                    $icons_dir = FAE_CURSOR_DIR . '/assets/ionicons/';
                                                    $icon_files = glob($icons_dir . '*.svg');
                                                    $priority_icons = ['star.svg', 'heart.svg', 'sparkles.svg', 'balloon.svg', 'diamond.svg'];
                                                    foreach ($priority_icons as $icon_file) :
                                                        $icon_path = $icons_dir . $icon_file;
                                                        if (!file_exists($icon_path)) continue;
                                                        $is_selected = ($icon_file === $selected_icon) ? 'selected' : '';
                                                    ?>
                                                    <div class="fae-icon-dropdown-item <?php echo esc_attr($is_selected); ?>" data-icon="<?php echo esc_attr($icon_file); ?>">
                                                        <?php echo file_get_contents($icon_path); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents ?>
                                                    </div>
                                                    <?php endforeach; ?>
                                                    <?php 
                                                    $count = 0;
                                                    foreach ($icon_files as $icon_path) :
                                                        $icon_file = basename($icon_path);
                                                        if (in_array($icon_file, $priority_icons)) continue;
                                                        if ($count++ > 25) break;
                                                        $is_selected = ($icon_file === $selected_icon) ? 'selected' : '';
                                                    ?>
                                                    <div class="fae-icon-dropdown-item <?php echo esc_attr($is_selected); ?>" data-icon="<?php echo esc_attr($icon_file); ?>">
                                                        <?php echo file_get_contents($icon_path); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents ?>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!-- /.fae-sidebar-preview -->
                </div><!-- /.fae-split-layout -->
            </form>
        </div>
        </div>

        <!-- Tab Content: Keyboard Effects -->
        <div class="fae-tab-content" id="keyboard-effects-tab">
        <div class="fae-settings-panel fae-keyboard-effects-panel">
            <form id="fae-keyboard-form" action="options.php" method="post">
                <?php settings_fields('fae_keyboard'); ?>
                
                <div class="fae-split-layout">
                    <!-- Main Content (Left) -->
                    <div class="fae-main-content">
                        <!-- Effects Selection Container -->
                        <div class="fae-settings-group-container">
                            <div class="fae-settings-group-header">
                                <div class="fae-settings-group-text">
                                    <h3 class="fae-settings-group-title">Effects</h3>
                                    <p class="fae-settings-group-description">Choose a keyboard effect to apply</p>
                                </div>
                            </div>
                            <div class="fae-settings-group-content">
                                <div class="fae-effect-grid fae-keyboard-effect-grid">
                                    <?php 
                                    $keyboard_effects_config = fae_keyboard_get_effects_config();
                                    $is_pro_active = Fae_Cursor_Pro::is_pro();
                                    foreach ($keyboard_effects_config as $effect_id => $config) : 
                                        $abbrev = Fae_Cursor_Admin::get_effect_abbrev($effect_id, 'keyboard');
                                        $is_pro_effect = isset($config['is_pro']) && $config['is_pro'] === true;
                                        $is_disabled = $is_pro_effect && !$is_pro_active;
                                    ?>
                                    <label class="fae-effect-option <?php echo $is_disabled ? 'fae-effect-pro-locked' : ''; ?>" data-effect-id="<?php echo esc_attr($effect_id); ?>">
                                        <input type="radio" name="fae_keyboard_options[effect]" value="<?php echo esc_attr($effect_id); ?>" <?php checked($selected_keyboard_effect, $effect_id); ?> <?php echo $is_disabled ? 'disabled' : ''; ?>>
                                        <div class="fae-effect-content">
                                            <span class="fae-effect-letter" data-effect="<?php echo esc_attr($effect_id); ?>"><?php echo esc_html($abbrev); ?></span>
                                            <span class="fae-effect-name"><?php echo esc_html($config['display_name']); ?></span>
                                            <?php if ($is_pro_effect) : ?>
                                                <?php echo Fae_Cursor_Pro::get_pro_badge(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                            <?php endif; ?>
                                        </div>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Keyboard Effect Settings -->
                        <div class="fae-effect-settings fae-keyboard-effect-settings" style="<?php echo $selected_keyboard_effect === 'none' ? 'display: none;' : ''; ?>">

                    <!-- DISPLAY RULES -->
                    <div class="fae-settings-group-container">
                        <div class="fae-settings-group-header">
                            <div class="fae-settings-group-text">
                                <h3 class="fae-settings-group-title">Display Rules</h3>
                                <p class="fae-settings-group-description">Control where and who sees the effect</p>
                        </div>
                    </div>

                        <div class="fae-settings-group-content">
                            <!-- Page Targeting -->
                            <div class="fae-settings-subgroup">
                                <div class="fae-settings-subgroup-header">
                                    <svg class="fae-subgroup-icon" viewBox="0 0 512 512">
                                        <path d="M428,224H288a48,48,0,0,1-48-48V36a4,4,0,0,0-4-4H144A64,64,0,0,0,80,96V416a64,64,0,0,0,64,64H368a64,64,0,0,0,64-64V228A4,4,0,0,0,428,224Z"/>
                                        <path d="M419.22,188.59,275.41,44.78A2,2,0,0,0,272,46.19V176a16,16,0,0,0,16,16H417.81A2,2,0,0,0,419.22,188.59Z"/>
                            </svg>
                                    <span class="fae-subgroup-title">Page Targeting</span>
                                    </div>
                                <div class="fae-settings-subgroup-content">
                                    <div class="fae-setting-item">
                                        <label for="fae_keyboard_scope_type">
                                            <span class="fae-setting-label">Display Effect On</span>
                                        </label>
                            <?php 
                            $is_pro_active = Fae_Cursor_Pro::is_pro();
                            $keyboard_css_selector_pro = Fae_Cursor_Pro::is_pro_feature('css_selector_scoping') && !$is_pro_active;
                            $keyboard_specific_pages_pro = Fae_Cursor_Pro::is_pro_feature('specific_pages_scoping') && !$is_pro_active;
                            ?>
                            <select name="fae_keyboard_options[scope_type]" id="fae_keyboard_scope_type" class="fae-scope-type-select">
                                <option value="entire_website" <?php selected($keyboard_scope_type, 'entire_website'); ?>>Entire Website</option>
                                <option value="specific_pages" <?php selected($keyboard_scope_type, 'specific_pages'); ?> <?php echo $keyboard_specific_pages_pro ? 'disabled' : ''; ?>>Specific Pages<?php echo $keyboard_specific_pages_pro ? ' (Pro)' : ''; ?></option>
                                <option value="css_selector" <?php selected($keyboard_scope_type, 'css_selector'); ?> <?php echo $keyboard_css_selector_pro ? 'disabled' : ''; ?>>CSS Selector<?php echo $keyboard_css_selector_pro ? ' (Pro)' : ''; ?></option>
                            </select>
                            <?php if ($keyboard_css_selector_pro || $keyboard_specific_pages_pro) : ?>
                                <!-- Hidden inputs to force Pro scoping options to defaults -->
                                <input type="hidden" name="fae_keyboard_options[scope_type]" value="entire_website">
                                <input type="hidden" name="fae_keyboard_options[scope_pages]" value="">
                                <input type="hidden" name="fae_keyboard_options[scope_css_selector]" value="">
                                <?php echo Fae_Cursor_Pro::get_upgrade_notice('Advanced scoping options require Pro.'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Device Visibility -->
                            <div class="fae-settings-subgroup">
                                <div class="fae-settings-subgroup-header">
                                    <svg class="fae-subgroup-icon" viewBox="0 0 512 512">
                                        <rect x="128" y="16" width="256" height="480" rx="48" ry="48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/>
                                        <path d="M176,16h24a8,8,0,0,1,8,8h0a16,16,0,0,0,16,16h64a16,16,0,0,0,16-16h0a8,8,0,0,1,8-8h24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/>
                            </svg>
                                    <span class="fae-subgroup-title">Device Visibility</span>
                                </div>
                                <div class="fae-settings-subgroup-content">
                                    <p class="fae-setting-hint" style="margin-bottom: 15px;">Hide the effect on specific device types</p>
                                    <div class="fae-device-toggles">
                                        <label class="fae-toggle-switch" for="fae_keyboard_hide_on_mobile">
                                            <input type="checkbox" name="fae_keyboard_options[hide_on_mobile]" value="1" <?php checked($keyboard_hide_on_mobile, '1'); ?> id="fae_keyboard_hide_on_mobile" class="fae-toggle-input">
                                            <span class="fae-toggle-slider"></span>
                                            <span class="fae-toggle-label">Hide on Mobile</span>
                                        </label>
                                        
                                        <label class="fae-toggle-switch" for="fae_keyboard_hide_on_tablet">
                                            <input type="checkbox" name="fae_keyboard_options[hide_on_tablet]" value="1" <?php checked($keyboard_hide_on_tablet, '1'); ?> id="fae_keyboard_hide_on_tablet" class="fae-toggle-input">
                                            <span class="fae-toggle-slider"></span>
                                            <span class="fae-toggle-label">Hide on Tablet</span>
                                        </label>
                                        
                                        <label class="fae-toggle-switch" for="fae_keyboard_hide_on_desktop">
                                            <input type="checkbox" name="fae_keyboard_options[hide_on_desktop]" value="1" <?php checked($keyboard_hide_on_desktop, '1'); ?> id="fae_keyboard_hide_on_desktop" class="fae-toggle-input">
                                            <span class="fae-toggle-slider"></span>
                                            <span class="fae-toggle-label">Hide on Desktop</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- User Roles -->
                            <div class="fae-settings-subgroup">
                                <div class="fae-settings-subgroup-header">
                                    <svg class="fae-subgroup-icon" viewBox="0 0 512 512">
                                        <path d="M336,256c-20.56,0-40.44-9.18-56-25.84-15.13-16.25-24.37-37.92-26-61-1.74-24.62,5.77-47.26,21.14-63.76S312,80,336,80c23.83,0,45.38,9.06,60.7,25.52,15.47,16.62,23,39.22,21.26,63.63h0c-1.67,23.11-10.9,44.77-26,61C376.44,246.82,356.57,256,336,256Z"/>
                                        <path d="M467.83,432H204.18a27.71,27.71,0,0,1-22-10.67,30.22,30.22,0,0,1-5.26-25.79c8.42-33.81,29.28-61.85,60.32-81.08C264.79,297.4,299.86,288,336,288c36.85,0,71,9,98.71,26.05,31.11,19.13,52,47.33,60.38,81.55a30.27,30.27,0,0,1-5.32,25.78A27.68,27.68,0,0,1,467.83,432Z"/>
                                        <path d="M147,260c-35.19,0-66.13-32.72-69-72.93C76.58,166.47,83,147.42,96,133.45,108.86,119.62,127,112,147,112s38.16,7.62,51,21.45c13,14,19.42,33,17.88,53.62C213.13,227.28,182.19,260,147,260Z"/>
                                        <path d="M212.66,291.45c-17.59-8.6-40.42-12.9-65.65-12.9-29.46,0-58.07,7.68-80.57,21.62C40.93,316,23.77,339.05,16.84,366.88a27.39,27.39,0,0,0,4.79,23.36A25.32,25.32,0,0,0,41.72,400h111.83"/>
                                    </svg>
                                    <span class="fae-subgroup-title">User Restrictions</span>
                                </div>
                                <div class="fae-settings-subgroup-content">
                            <?php 
                            $keyboard_has_role_restriction = !empty($keyboard_user_roles);
                            $keyboard_user_roles_pro = Fae_Cursor_Pro::is_pro_feature('user_role_restrictions') && !Fae_Cursor_Pro::is_pro();
                            ?>
                                    <div class="fae-radio-group">
                                        <label class="fae-radio-item">
                                    <input type="radio" name="fae_keyboard_user_restriction_type" value="all" <?php checked(!$keyboard_has_role_restriction); ?> class="fae-user-restriction-type">
                                            <span class="fae-radio-label">
                                                <strong>All Users</strong>
                                                <small>Logged-in and logged-out visitors</small>
                                            </span>
                                </label>
                                        <label class="fae-radio-item <?php echo $keyboard_user_roles_pro ? 'fae-radio-item-disabled' : ''; ?>">
                                    <input type="radio" name="fae_keyboard_user_restriction_type" value="specific" <?php checked($keyboard_has_role_restriction); ?> <?php echo $keyboard_user_roles_pro ? 'disabled' : ''; ?> class="fae-user-restriction-type">
                                            <span class="fae-radio-label">
                                                <strong>Specific User Roles<?php echo $keyboard_user_roles_pro ? ' (Pro)' : ''; ?></strong>
                                                <small>Choose which roles can see the effect</small>
                                            </span>
                                </label>
                            </div>
                            <?php if ($keyboard_user_roles_pro) : ?>
                                <!-- Hidden inputs to force Pro user role options to defaults -->
                                <input type="hidden" name="fae_keyboard_options[user_roles]" value="">
                                <input type="hidden" name="fae_keyboard_options[include_logged_out]" value="0">
                                <?php echo Fae_Cursor_Pro::get_upgrade_notice('User role restrictions require Pro.'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                        </div>

                        <!-- Action Buttons for Keyboard Effects -->
                        <div class="fae-action-buttons">
                            <button type="submit" class="fae-btn fae-btn-primary">
                                <svg class="fae-icon" viewBox="0 0 512 512" style="width: 16px; height: 16px;">
                                    <path d="M173.898,439.404l-166.4-166.4c-9.997-9.997-9.997-26.206,0-36.204l36.203-36.204c9.997-9.998,26.207-9.998,36.204,0L192,312.69,432.095,72.596c9.997-9.997,26.207-9.997,36.204,0l36.203,36.204c9.997,9.997,9.997,26.206,0,36.204l-294.4,294.401C383.105,449.401,366.896,449.401,356.898,439.404z"/>
                                </svg>
                                Save Settings
                            </button>
                        </div>
                    </div><!-- /.fae-main-content -->
                    
                    <!-- Sticky Sidebar (Right) -->
                    <div class="fae-sidebar-preview">
                        <div class="fae-preview-card">
                            <div class="fae-preview-section">
                                <div class="fae-preview-header-inline">
                                    <span>Live Preview</span>
                                    <div class="fae-preview-header-actions">
                                        <button type="button" class="fae-preview-bg-toggle" id="fae-keyboard-preview-bg" data-bg="<?php echo esc_attr($preview_bg); ?>" title="Toggle Preview Background">
                                            <span class="fae-bg-toggle-icon fae-bg-icon-moon">
                                                <svg viewBox="0 0 512 512" fill="none" stroke="currentColor" stroke-width="32" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M160 136c0-30.62 4.51-61.61 16-88C99 57.57 48 141.35 48 230c0 114.9 93.1 208 208 208 88.65 0 172.43-51 182-128-26.39 11.49-57.38 16-88 16-114.9 0-208-93.1-208-208z"/>
                                                </svg>
                                            </span>
                                            <span class="fae-bg-toggle-icon fae-bg-icon-sun">
                                                <svg viewBox="0 0 512 512" fill="none" stroke="currentColor" stroke-width="32" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="256" cy="256" r="80"/>
                                                    <path d="M256 48v80M256 384v80M448 256h-80M144 256H64M393.14 118.86l-56.57 56.57M175.43 336.57l-56.57 56.57M393.14 393.14l-56.57-56.57M175.43 175.43l-56.57-56.57"/>
                                                </svg>
                                            </span>
                                        </button>
                                        <button type="button" class="fae-expand-btn" data-preview-type="keyboard">
                                            <svg viewBox="0 0 512 512"><path d="M432,320v112a16,16,0,0,1-16,16H96a16,16,0,0,1-16-16V320" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><polyline points="368 304 432 368 496 304" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><line x1="432" y1="368" x2="432" y2="144" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><path d="M80,192V80a16,16,0,0,1,16-16H416a16,16,0,0,1,16,16v112" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/></svg>
                                            Expand
                                        </button>
                                    </div>
                                </div>
                                <div class="fae-preview-iframe-wrapper" style="background: <?php echo ($preview_bg === 'light') ? 'linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%)' : 'linear-gradient(135deg, #1a1a2e 0%, #16213e 100%)'; ?>;">
                                    <iframe 
                                        id="fae-keyboard-preview-iframe" 
                                        class="fae-preview-iframe"
                                        src="<?php echo esc_url(admin_url('admin.php?fae_embed_preview=1&type=keyboard&effect=none&color=' . urlencode($selected_keyboard_color) . '&multi_color=' . $keyboard_multi_color . '&bg=' . urlencode($preview_bg) . '&_t=' . time())); ?>"
                                        onload="this.classList.add('loaded')"
                                    ></iframe>
                                </div>
                            </div>
                            <div class="fae-appearance-section" style="<?php echo $selected_keyboard_effect === 'none' ? 'display: none;' : ''; ?>">
                                <div class="fae-preview-header-inline"><span>Appearance</span></div>
                                <?php
                                // Check if current keyboard effect has limited color customization
                                $keyboard_effect_has_limited_color = fae_keyboard_effect_has_limited_color($selected_keyboard_effect);
                                $can_customize_keyboard_color = fae_can_customize_keyboard_color($selected_keyboard_effect);
                                ?>
                                <div class="fae-appearance-settings">
                                    <div class="fae-inline-setting fae-color-setting fae-keyboard-color-setting" style="<?php echo fae_keyboard_effect_supports($selected_keyboard_effect, 'color') ? '' : 'display: none;'; ?>" data-limited-customization="<?php echo $keyboard_effect_has_limited_color ? '1' : '0'; ?>">
                                        <label>Color <?php if ($keyboard_effect_has_limited_color && !$can_customize_keyboard_color) : ?><span class="fae-pro-badge fae-pro-badge-inline">PRO</span><?php endif; ?></label>
                                        <div class="fae-color-input-inline <?php echo ($keyboard_effect_has_limited_color && !$can_customize_keyboard_color) ? 'fae-color-locked' : ''; ?>">
                                            <?php if ($keyboard_effect_has_limited_color && !$can_customize_keyboard_color) : ?>
                                                <!-- Free users on limited effects: show locked color swatch -->
                                                <div class="fae-color-swatch-locked" style="background-color: <?php echo esc_attr(fae_get_keyboard_free_default_color()); ?>;" title="Upgrade to Pro to customize color"></div>
                                                <input type="hidden" name="fae_keyboard_options[color]" value="<?php echo esc_attr(fae_get_keyboard_free_default_color()); ?>" id="fae-keyboard-color">
                                                <span class="fae-color-locked-text"><?php echo esc_html(fae_get_keyboard_free_default_color()); ?></span>
                                            <?php else : ?>
                                                <input type="color" class="fae-color-picker-inline" name="fae_keyboard_options[color]" value="<?php echo esc_attr($selected_keyboard_color); ?>" id="fae-keyboard-color">
                                                <input type="text" value="<?php echo esc_attr($selected_keyboard_color); ?>" id="fae-keyboard-color-text">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="fae-inline-setting fae-multi-color-setting" style="<?php echo ($selected_keyboard_effect === 'sparkle-keys') ? '' : 'display: none;'; ?>">
                                        <label>
                                            <input type="checkbox" name="fae_keyboard_options[multi_color]" value="1" disabled id="fae-keyboard-multi-color">
                                            Multi-Color <?php echo Fae_Cursor_Pro::get_pro_badge(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                        </label>
                                        <!-- Hidden input to always force multi_color to 0 (Pro feature protection) -->
                                        <input type="hidden" name="fae_keyboard_options[multi_color]" value="0">
                                    </div>
                                    
                                    <div class="fae-inline-setting fae-color-setting-sparkle" style="<?php echo ($selected_keyboard_effect === 'sparkle-keys' && $keyboard_multi_color !== '1') ? '' : 'display: none;'; ?>">
                                        <label>Color</label>
                                        <div class="fae-color-input-inline">
                                            <input type="color" class="fae-color-picker-inline" name="fae_keyboard_options[color]" value="<?php echo esc_attr($selected_keyboard_color); ?>" id="fae-keyboard-color-sparkle">
                                            <input type="text" value="<?php echo esc_attr($selected_keyboard_color); ?>" id="fae-keyboard-color-text-sparkle">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!-- /.fae-sidebar-preview -->
                </div><!-- /.fae-split-layout -->
            </form>
        </div>
        </div>

        <!-- Tab Content: Particle Effects -->
        <div class="fae-tab-content" id="particle-effects-tab">
        <div class="fae-settings-panel">
            <form id="fae-particle-form" action="options.php" method="post">
                <?php settings_fields('fae_particle'); ?>
                
                <div class="fae-split-layout">
                    <!-- Main Content (Left) -->
                    <div class="fae-main-content">
                        <!-- Effects Selection Container -->
                        <div class="fae-settings-group-container">
                            <div class="fae-settings-group-header">
                                <div class="fae-settings-group-text">
                                    <h3 class="fae-settings-group-title">Effects</h3>
                                    <p class="fae-settings-group-description">Choose a screen effect to apply</p>
                                </div>
                            </div>
                            <div class="fae-settings-group-content">
                                <div class="fae-effect-grid">
                                    <?php 
                                    $particle_effects_config = fae_particle_get_effects_config();
                                    $is_pro_active = Fae_Cursor_Pro::is_pro();
                                    foreach ($particle_effects_config as $effect_id => $config) : 
                                        $abbrev = Fae_Cursor_Admin::get_effect_abbrev($effect_id, 'particle');
                                        $is_pro_effect = isset($config['is_pro']) && $config['is_pro'] === true;
                                        $is_disabled = $is_pro_effect && !$is_pro_active;
                                    ?>
                                    <label class="fae-effect-option <?php echo $is_disabled ? 'fae-effect-pro-locked' : ''; ?>" data-effect-id="<?php echo esc_attr($effect_id); ?>">
                                        <input type="radio" name="fae_particle_options[effect]" value="<?php echo esc_attr($effect_id); ?>" <?php checked($selected_particle_effect, $effect_id); ?> <?php echo $is_disabled ? 'disabled' : ''; ?>>
                                        <div class="fae-effect-content">
                                            <span class="fae-effect-letter" data-effect="<?php echo esc_attr($effect_id); ?>"><?php echo esc_html($abbrev); ?></span>
                                            <span class="fae-effect-name"><?php echo esc_html($config['display_name']); ?></span>
                                            <?php if ($is_pro_effect) : ?>
                                                <?php echo Fae_Cursor_Pro::get_pro_badge(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                            <?php endif; ?>
                                        </div>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Effect-Specific Settings -->
                        <div class="fae-effect-settings" style="<?php echo $selected_particle_effect === 'none' ? 'display: none;' : ''; ?>">

                    <!-- DISPLAY RULES -->
                    <div class="fae-settings-group-container">
                        <div class="fae-settings-group-header">
                            <div class="fae-settings-group-text">
                                <h3 class="fae-settings-group-title">Display Rules</h3>
                                <p class="fae-settings-group-description">Control where and who sees the effect</p>
                        </div>
                    </div>

                        <div class="fae-settings-group-content">
                            <!-- Page Targeting -->
                            <div class="fae-settings-subgroup">
                                <div class="fae-settings-subgroup-header">
                                    <svg class="fae-subgroup-icon" viewBox="0 0 512 512">
                                        <path d="M428,224H288a48,48,0,0,1-48-48V36a4,4,0,0,0-4-4H144A64,64,0,0,0,80,96V416a64,64,0,0,0,64,64H368a64,64,0,0,0,64-64V228A4,4,0,0,0,428,224Z"/>
                                        <path d="M419.22,188.59,275.41,44.78A2,2,0,0,0,272,46.19V176a16,16,0,0,0,16,16H417.81A2,2,0,0,0,419.22,188.59Z"/>
                            </svg>
                                    <span class="fae-subgroup-title">Page Targeting</span>
                                    </div>
                                <div class="fae-settings-subgroup-content">
                                    <div class="fae-setting-item">
                                        <label for="fae_particle_scope_type">
                                            <span class="fae-setting-label">Display Effect On</span>
                                        </label>
                            <?php 
                            $is_pro_active = Fae_Cursor_Pro::is_pro();
                            $particle_specific_pages_pro = Fae_Cursor_Pro::is_pro_feature('specific_pages_scoping') && !$is_pro_active;
                            ?>
                            <select name="fae_particle_options[scope_type]" id="fae_particle_scope_type" class="fae-scope-type-select">
                                <option value="entire_website" <?php selected($particle_scope_type, 'entire_website'); ?>>Entire Website</option>
                                <option value="specific_pages" <?php selected($particle_scope_type, 'specific_pages'); ?> <?php echo $particle_specific_pages_pro ? 'disabled' : ''; ?>>Specific Pages<?php echo $particle_specific_pages_pro ? ' (Pro)' : ''; ?></option>
                            </select>
                            <?php if ($particle_specific_pages_pro) : ?>
                                <!-- Hidden inputs to force Pro scoping options to defaults -->
                                <input type="hidden" name="fae_particle_options[scope_type]" value="entire_website">
                                <input type="hidden" name="fae_particle_options[scope_pages]" value="">
                                <?php echo Fae_Cursor_Pro::get_upgrade_notice('Advanced scoping options require Pro.'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Device Visibility -->
                            <div class="fae-settings-subgroup">
                                <div class="fae-settings-subgroup-header">
                                    <svg class="fae-subgroup-icon" viewBox="0 0 512 512">
                                        <rect x="128" y="16" width="256" height="480" rx="48" ry="48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/>
                                        <path d="M176,16h24a8,8,0,0,1,8,8h0a16,16,0,0,0,16,16h64a16,16,0,0,0,16-16h0a8,8,0,0,1,8-8h24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/>
                            </svg>
                                    <span class="fae-subgroup-title">Device Visibility</span>
                                </div>
                                <div class="fae-settings-subgroup-content">
                                    <p class="fae-setting-hint" style="margin-bottom: 15px;">Hide the effect on specific device types</p>
                                    <div class="fae-device-toggles">
                                        <label class="fae-toggle-switch" for="fae_particle_hide_on_mobile">
                                            <input type="checkbox" name="fae_particle_options[hide_on_mobile]" value="1" <?php checked($particle_hide_on_mobile, '1'); ?> id="fae_particle_hide_on_mobile" class="fae-toggle-input">
                                            <span class="fae-toggle-slider"></span>
                                            <span class="fae-toggle-label">Hide on Mobile</span>
                                        </label>
                                        
                                        <label class="fae-toggle-switch" for="fae_particle_hide_on_tablet">
                                            <input type="checkbox" name="fae_particle_options[hide_on_tablet]" value="1" <?php checked($particle_hide_on_tablet, '1'); ?> id="fae_particle_hide_on_tablet" class="fae-toggle-input">
                                            <span class="fae-toggle-slider"></span>
                                            <span class="fae-toggle-label">Hide on Tablet</span>
                                        </label>
                                        
                                        <label class="fae-toggle-switch" for="fae_particle_hide_on_desktop">
                                            <input type="checkbox" name="fae_particle_options[hide_on_desktop]" value="1" <?php checked($particle_hide_on_desktop, '1'); ?> id="fae_particle_hide_on_desktop" class="fae-toggle-input">
                                            <span class="fae-toggle-slider"></span>
                                            <span class="fae-toggle-label">Hide on Desktop</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- User Roles -->
                            <div class="fae-settings-subgroup">
                                <div class="fae-settings-subgroup-header">
                                    <svg class="fae-subgroup-icon" viewBox="0 0 512 512">
                                        <path d="M336,256c-20.56,0-40.44-9.18-56-25.84-15.13-16.25-24.37-37.92-26-61-1.74-24.62,5.77-47.26,21.14-63.76S312,80,336,80c23.83,0,45.38,9.06,60.7,25.52,15.47,16.62,23,39.22,21.26,63.63h0c-1.67,23.11-10.9,44.77-26,61C376.44,246.82,356.57,256,336,256Z"/>
                                        <path d="M467.83,432H204.18a27.71,27.71,0,0,1-22-10.67,30.22,30.22,0,0,1-5.26-25.79c8.42-33.81,29.28-61.85,60.32-81.08C264.79,297.4,299.86,288,336,288c36.85,0,71,9,98.71,26.05,31.11,19.13,52,47.33,60.38,81.55a30.27,30.27,0,0,1-5.32,25.78A27.68,27.68,0,0,1,467.83,432Z"/>
                                        <path d="M147,260c-35.19,0-66.13-32.72-69-72.93C76.58,166.47,83,147.42,96,133.45,108.86,119.62,127,112,147,112s38.16,7.62,51,21.45c13,14,19.42,33,17.88,53.62C213.13,227.28,182.19,260,147,260Z"/>
                                        <path d="M212.66,291.45c-17.59-8.6-40.42-12.9-65.65-12.9-29.46,0-58.07,7.68-80.57,21.62C40.93,316,23.77,339.05,16.84,366.88a27.39,27.39,0,0,0,4.79,23.36A25.32,25.32,0,0,0,41.72,400h111.83"/>
                                    </svg>
                                    <span class="fae-subgroup-title">User Restrictions</span>
                                </div>
                                <div class="fae-settings-subgroup-content">
                            <?php 
                            $particle_has_role_restriction = !empty($particle_user_roles);
                            $particle_user_roles_pro = Fae_Cursor_Pro::is_pro_feature('user_role_restrictions') && !Fae_Cursor_Pro::is_pro();
                            ?>
                                    <div class="fae-radio-group">
                                        <label class="fae-radio-item">
                                    <input type="radio" name="fae_particle_user_restriction_type" value="all" <?php checked(!$particle_has_role_restriction); ?> class="fae-user-restriction-type">
                                            <span class="fae-radio-label">
                                                <strong>All Users</strong>
                                                <small>Logged-in and logged-out visitors</small>
                                            </span>
                                </label>
                                        <label class="fae-radio-item <?php echo $particle_user_roles_pro ? 'fae-radio-item-disabled' : ''; ?>">
                                    <input type="radio" name="fae_particle_user_restriction_type" value="specific" <?php checked($particle_has_role_restriction); ?> <?php echo $particle_user_roles_pro ? 'disabled' : ''; ?> class="fae-user-restriction-type">
                                            <span class="fae-radio-label">
                                                <strong>Specific User Roles<?php echo $particle_user_roles_pro ? ' (Pro)' : ''; ?></strong>
                                                <small>Choose which roles can see the effect</small>
                                            </span>
                                </label>
                            </div>
                            <?php if ($particle_user_roles_pro) : ?>
                                <!-- Hidden inputs to force Pro user role options to defaults -->
                                <input type="hidden" name="fae_particle_options[user_roles]" value="">
                                <input type="hidden" name="fae_particle_options[include_logged_out]" value="0">
                                <?php echo Fae_Cursor_Pro::get_upgrade_notice('User role restrictions require Pro.'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                        </div>

                        <!-- Action Buttons -->
                        <div class="fae-action-buttons">
                            <button type="submit" class="fae-btn fae-btn-primary">
                                <svg class="fae-icon" viewBox="0 0 512 512" style="width: 16px; height: 16px;">
                                    <path d="M173.898,439.404l-166.4-166.4c-9.997-9.997-9.997-26.206,0-36.204l36.203-36.204c9.997-9.998,26.207-9.998,36.204,0L192,312.69,432.095,72.596c9.997-9.997,26.207-9.997,36.204,0l36.203,36.204c9.997,9.997,9.997,26.206,0,36.204l-294.4,294.401C383.105,449.401,366.896,449.401,356.898,439.404z"/>
                                </svg>
                                Save Settings
                            </button>
                        </div>
                    </div><!-- /.fae-main-content -->
                    
                    <!-- Sticky Sidebar (Right) -->
                    <div class="fae-sidebar-preview">
                        <div class="fae-preview-card">
                            <div class="fae-preview-section">
                                <div class="fae-preview-header-inline">
                                    <span>Live Preview</span>
                                    <div class="fae-preview-header-actions">
                                        <button type="button" class="fae-preview-bg-toggle" id="fae-particle-preview-bg" data-bg="<?php echo esc_attr($preview_bg); ?>" title="Toggle Preview Background">
                                            <span class="fae-bg-toggle-icon fae-bg-icon-moon">
                                                <svg viewBox="0 0 512 512" fill="none" stroke="currentColor" stroke-width="32" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M160 136c0-30.62 4.51-61.61 16-88C99 57.57 48 141.35 48 230c0 114.9 93.1 208 208 208 88.65 0 172.43-51 182-128-26.39 11.49-57.38 16-88 16-114.9 0-208-93.1-208-208z"/>
                                                </svg>
                                            </span>
                                            <span class="fae-bg-toggle-icon fae-bg-icon-sun">
                                                <svg viewBox="0 0 512 512" fill="none" stroke="currentColor" stroke-width="32" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="256" cy="256" r="80"/>
                                                    <path d="M256 48v80M256 384v80M448 256h-80M144 256H64M393.14 118.86l-56.57 56.57M175.43 336.57l-56.57 56.57M393.14 393.14l-56.57-56.57M175.43 175.43l-56.57-56.57"/>
                                                </svg>
                                            </span>
                                        </button>
                                        <button type="button" class="fae-expand-btn" data-preview-type="particle">
                                            <svg viewBox="0 0 512 512"><path d="M432,320v112a16,16,0,0,1-16,16H96a16,16,0,0,1-16-16V320" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><polyline points="368 304 432 368 496 304" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><line x1="432" y1="368" x2="432" y2="144" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><path d="M80,192V80a16,16,0,0,1,16-16H416a16,16,0,0,1,16,16v112" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/></svg>
                                            Expand
                                        </button>
                                    </div>
                                </div>
                                <div class="fae-preview-iframe-wrapper" style="background: <?php echo ($preview_bg === 'light') ? 'linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%)' : 'linear-gradient(135deg, #1a1a2e 0%, #16213e 100%)'; ?>;">
                                    <iframe 
                                        id="fae-particle-preview-iframe" 
                                        class="fae-preview-iframe"
                                        src="<?php echo esc_url(admin_url('admin.php?fae_embed_preview=1&type=particle&effect=none&color=' . urlencode($selected_particle_color) . '&speed=' . $selected_particle_speed . '&interactive_cursor=' . urlencode($particle_interactive_cursor) . '&bg=' . urlencode($preview_bg) . '&_t=' . time())); ?>"
                                        onload="this.classList.add('loaded')"
                                    ></iframe>
                                </div>
                            </div>
                            <div class="fae-appearance-section" style="<?php echo $selected_particle_effect === 'none' ? 'display: none;' : ''; ?>">
                                <div class="fae-preview-header-inline"><span>Appearance</span></div>
                                <?php
                                // Check if current particle effect has limited customization
                                $particle_effect_has_limited_customization = fae_particle_effect_has_limited_customization($selected_particle_effect);
                                $can_customize_particle_color = fae_can_customize_particle_color($selected_particle_effect);
                                $can_customize_particle_speed = fae_can_customize_particle_speed($selected_particle_effect);
                                ?>
                                <div class="fae-appearance-settings">
                                    <div class="fae-inline-setting fae-color-setting fae-particle-color-setting" style="<?php echo fae_particle_effect_supports($selected_particle_effect, 'color') ? '' : 'display: none;'; ?>" data-limited-customization="<?php echo $particle_effect_has_limited_customization ? '1' : '0'; ?>">
                                        <label>Color <?php if ($particle_effect_has_limited_customization && !$can_customize_particle_color) : ?><span class="fae-pro-badge fae-pro-badge-inline">PRO</span><?php endif; ?></label>
                                        <div class="fae-color-input-inline <?php echo ($particle_effect_has_limited_customization && !$can_customize_particle_color) ? 'fae-color-locked' : ''; ?>">
                                            <?php if ($particle_effect_has_limited_customization && !$can_customize_particle_color) : ?>
                                                <!-- Free users on limited effects: show locked color swatch -->
                                                <div class="fae-color-swatch-locked" style="background-color: <?php echo esc_attr(fae_get_particle_free_default_color()); ?>;" title="Upgrade to Pro to customize color"></div>
                                                <input type="hidden" name="fae_particle_options[color]" value="<?php echo esc_attr(fae_get_particle_free_default_color()); ?>" id="fae-particle-color">
                                                <span class="fae-color-locked-text"><?php echo esc_html(fae_get_particle_free_default_color()); ?></span>
                                            <?php else : ?>
                                                <input type="color" class="fae-color-picker-inline" name="fae_particle_options[color]" value="<?php echo esc_attr($selected_particle_color); ?>" id="fae-particle-color">
                                                <input type="text" value="<?php echo esc_attr($selected_particle_color); ?>" id="fae-particle-color-text">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="fae-inline-setting fae-speed-setting fae-particle-speed-setting" style="<?php echo fae_particle_effect_supports($selected_particle_effect, 'speed') ? '' : 'display: none;'; ?>" data-limited-customization="<?php echo $particle_effect_has_limited_customization ? '1' : '0'; ?>">
                                        <label>Speed</label>
                                        <select name="fae_particle_options[speed]" id="fae-particle-speed" <?php echo ($particle_effect_has_limited_customization && !$can_customize_particle_speed) ? 'class="fae-speed-locked"' : ''; ?>>
                                            <option value="slow" <?php selected($selected_particle_speed, 'slow'); ?> <?php echo ($particle_effect_has_limited_customization && !$can_customize_particle_speed) ? 'disabled' : ''; ?>>Slow <?php echo ($particle_effect_has_limited_customization && !$can_customize_particle_speed) ? '(Pro)' : ''; ?></option>
                                            <option value="normal" <?php selected($selected_particle_speed, 'normal'); ?>>Normal</option>
                                            <option value="fast" <?php selected($selected_particle_speed, 'fast'); ?> <?php echo ($particle_effect_has_limited_customization && !$can_customize_particle_speed) ? 'disabled' : ''; ?>>Fast <?php echo ($particle_effect_has_limited_customization && !$can_customize_particle_speed) ? '(Pro)' : ''; ?></option>
                                        </select>
                                    </div>
                                    <?php 
                                    // Interactive Cursor is a Pro feature
                                    $particle_config = fae_particle_get_effect_config($selected_particle_effect);
                                    $show_interactive_cursor = isset($particle_config['supports_interactive_cursor']) && $particle_config['supports_interactive_cursor'];
                                    ?>
                                    <div class="fae-inline-setting fae-interactive-cursor-setting" style="<?php echo $show_interactive_cursor ? '' : 'display: none;'; ?>">
                                        <label>
                                            Interactive Cursor
                                            <span class="fae-pro-badge">PRO</span>
                                        </label>
                                        <label class="fae-toggle-switch" for="fae-particle-interactive-cursor">
                                            <input type="checkbox" name="fae_particle_options[interactive_cursor]" value="1" disabled id="fae-particle-interactive-cursor" class="fae-toggle-input">
                                            <span class="fae-toggle-slider"></span>
                                        </label>
                                        <!-- Hidden input to always force interactive_cursor to 0 (Pro feature protection) -->
                                        <input type="hidden" name="fae_particle_options[interactive_cursor]" value="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!-- /.fae-sidebar-preview -->
                </div><!-- /.fae-split-layout -->
            </form>
        </div>
        </div>
    </div>
    <p class="fae-dashboard-footer-version"><?php echo esc_html(__('FaeCursor v1.2.2', 'faecursor')); ?></p>
</div>

<!-- Fullscreen Preview Modal -->
<div class="fae-preview-modal" id="fae-preview-modal">
    <div class="fae-preview-modal-header">
        <div class="fae-preview-modal-title">Fullscreen Preview</div>
        <button type="button" class="fae-preview-modal-close" id="fae-modal-close">
            <svg viewBox="0 0 512 512"><path d="M289.94,256l95-95A24,24,0,0,0,351,127l-95,95-95-95A24,24,0,0,0,127,161l95,95-95,95a24,24,0,1,0,34,34l95-95,95,95a24,24,0,0,0,34-34Z"/></svg>
            Close (Esc)
        </button>
    </div>
    <div class="fae-preview-modal-body">
        <iframe class="fae-preview-modal-iframe" id="fae-modal-iframe"></iframe>
        <div class="fae-preview-modal-hint" id="fae-modal-hint">Move mouse around to see the effect</div>
    </div>
</div>

