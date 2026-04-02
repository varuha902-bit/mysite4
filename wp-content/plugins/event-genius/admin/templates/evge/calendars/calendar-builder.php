<?php

use WPEventGenius\Common\Utils\Icon;
use WPEventGenius\Common\Utils\Settings;
if (!defined('ABSPATH')) {
    exit;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$calendar_id      = isset($_GET['calendar_id']) ? sanitize_key($_GET['calendar_id']) : 0;
$calendar_name    = '';
$default_settings = [
    'view' => 'month',
    'num' => 12,
    'filters' => [],
    'color' => '#3498db',
    'events_per_day' => 3,
    'bulk_registration_enabled' => 'disabled'
];

// If editing, get existing values
if ($calendar_id) {
    if ($calendar_id === 'default') {
        $saved_settings = Settings::get('default_calendar_settings');
		if ( ! empty( $saved_settings ) && is_string( $saved_settings ) ) {
			$saved_settings = json_decode( $saved_settings, true );
		} else {
			$saved_settings = $default_settings;
		}
        $calendar_name = __('Default (Archive)', 'event-genius');
    } else {
        $saved_settings = get_term_meta($calendar_id, 'evge_calendar_settings', true);
        $calendar = get_term($calendar_id, 'evge_calendar');
        $calendar_name = $calendar ? $calendar->name : '';
    }
    $settings = wp_parse_args($saved_settings ?: [], $default_settings);
} else {
    $settings = $default_settings;
}
?>

<div class="wrap">

    <?php $this->builder_top($settings, $calendar_name); ?>

    <div class="evge-builder-wrap">


    <form method="post" action="" class="evge-calendar-form">
        <?php wp_nonce_field('evge_save_calendar', 'evge_calendar_nonce'); ?>
        <input type="hidden" name="calendar_id" value="<?php echo esc_attr($calendar_id); ?>">

        <div class="evge-calendar-builder">
            <div class="evge-calendar-builder-sidebar">
                <div class="evge-builder-section">
                    <div class="evge-builder-field">
                        <label><?php esc_html_e('Calendar Name', 'event-genius'); ?></label>
                        <?php if ($calendar_id === 'default') : ?>
                            <p><?php esc_html_e( 'Default', 'event-genius' ); ?></p>
                        <?php else : ?>
                            <input type="text" name="calendar_name" 
                                   value="<?php echo esc_attr($calendar_name); ?>" 
                                   required>
                        <?php endif; ?>
                    </div>

                    <div class="evge-builder-field">
                        <label><?php esc_html_e('Identity Color', 'event-genius'); ?></label>
                        <input type="text" 
                               name="calendar_settings[color]" 
                               value="<?php echo esc_attr($settings['color']); ?>"
                               class="evge-color-field">
                    </div>

                    <div class="evge-field-filters">
                        <div class="evge-field-filters-top evge-builder-field">
                            <label><?php esc_html_e('Event Filters', 'event-genius'); ?></label>
                            <?php if ($calendar_id === 'default') : ?>
                                <div style="margin-top: 4px;"><?php esc_html_e( 'Default (Archive) filters are not editable.', 'event-genius' ); ?></div>
                            <?php else : ?>
                                <div class="evge-filter-list-and-add">
                                    <div class="evge-filters-list">
                                        <?php
                                        if (!empty($settings['filters']) && is_array($settings['filters'])) {
                                            foreach ($settings['filters'] as $index => $filter) {
                                                $type = $filter['type'] ?? '';
                                                $action = $filter['action'] ?? '';
                                                $terms = $filter['terms'] ?? [];
                                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                echo $calendar_builder->render_filter_summary($type, $action, $terms, $index);
                                            }
                                        }
                                        ?>
                                    </div>
                                    <button type="button" class="button evge-add-filter">
                                        <?php
                                        esc_html_e('+ Create Filter', 'event-genius');
                                        ?>
                                    </button>
                                </div>
                            <?php endif; ?>
                            
                        </div>

                        <div class="evge-filters-wrapper">
                            <div class="evge-builder-field evge-filter-builder-wrapper" style="display: none;">
                                <label class="evge-visually-hidden"><?php esc_html_e('Event Filters', 'event-genius'); ?></label>

                                <!-- Filter Builder Form (hidden by default) -->
                                <div class="evge-filters-container">
                                    <div class="evge-filter-builder">
                                        <div class="evge-filter-step-1">
                                            <label><?php esc_html_e('Filter Type', 'event-genius'); ?></label>
                                            <select class="evge-filter-type">
                                                <option value=""><?php esc_html_e('Select Type', 'event-genius'); ?></option>
                                                <option value="category"><?php esc_html_e('Category', 'event-genius'); ?></option>
                                                <option value="tag"><?php esc_html_e('Tag', 'event-genius'); ?></option>
                                            </select>
                                        </div>

                                        <div class="evge-filter-step-2" style="display: none;">
                                            <div class="evge-flex-center">
                                                <div class="evge-filter-action">
                                                    <label class="screen-reader-text" for="evge-filter-action"><?php esc_html_e('Filter Action', 'event-genius'); ?></label>
                                                    <select class="evge-filter-action" id="evge-filter-action">
                                                        <option value="include"><?php esc_html_e('Include', 'event-genius'); ?></option>
                                                        <option value="exclude"><?php esc_html_e('Exclude', 'event-genius'); ?></option>
                                                    </select>
                                                </div>

                                                <div class="evge-filter-terms">
                                                    <label class="evge-filter-terms-label screen-reader-text" for="evge-filter-terms"><?php esc_html_e('Filter Terms', 'event-genius'); ?></label>
                                                    <select class="evge-filter-terms" id="evge-filter-terms">
                                                        <!-- Terms will be loaded dynamically -->
                                                    </select>
                                                </div>

                                                <div class="evge-filter-actions">
                                                    <button type="button" class="button button-primary evge-save-filter">
                                                        <?php esc_html_e('Add', 'event-genius'); ?>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="evge-builder-field evge-filter-relationship" <?php echo empty($settings['filters']) ? 'style="display: none;"' : ''; ?>>
                            <label><?php esc_html_e('Filter Relationship', 'event-genius'); ?></label>
                            <div>
                                <select name="calendar_settings[filter_relationship]">
                                    <option value="AND" <?php selected($settings['filter_relationship'] ?? 'AND', 'AND'); ?>>
                                        <?php esc_html_e('AND (match all)', 'event-genius'); ?>
                                    </option>
                                    <option value="OR" <?php selected($settings['filter_relationship'] ?? 'AND', 'OR'); ?>>
                                        <?php esc_html_e('OR (match any)', 'event-genius'); ?>
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="evge-builder-field evge-builder-field-view-type">
                        <label><?php esc_html_e('View Type', 'event-genius'); ?></label>
                        <div>
                        <select name="calendar_settings[view]" class="evge-view-type-select">
                                <option value="month" <?php selected($settings['view'], 'month'); ?>>
                                    <?php esc_html_e('Month', 'event-genius'); ?>
                                </option>
                                <option value="grid" <?php selected($settings['view'], 'grid'); ?>>
                                    <?php esc_html_e('Grid', 'event-genius'); ?>
                                </option>
                                <option value="list" <?php selected($settings['view'], 'list'); ?>>
                                    <?php esc_html_e('List', 'event-genius'); ?>
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="evge-builder-field">
                        <label><?php esc_html_e('Events Per Page', 'event-genius'); ?>
                                <span style="display:block; font-weight: 400; font-size: 12px;">
                                    <?php esc_html_e('(grid or list)', 'event-genius'); ?>
                                </span>
                        </label>
                        <input type="number" 
                               name="calendar_settings[num]" 
                               value="<?php echo esc_attr($settings['num'] ?? 12); ?>"
                               min="1" 
                               max="100" 
                               step="1">
                    </div>

                    <div class="evge-builder-field">
                        <label><?php esc_html_e('Events Per Day', 'event-genius'); ?>
                                <span style="display:block; font-weight: 400; font-size: 12px;">
                                    <?php esc_html_e('(month view)', 'event-genius'); ?>
                                </span>
                        </label>
                        <input type="number" 
                               name="calendar_settings[events_per_day]" 
                               value="<?php echo esc_attr($settings['events_per_day'] ?? 3); ?>"
                               min="1" 
                               max="10" 
                               step="1">
                    </div>

                    <?php
                    // Filter bar options - only show when toolbar is enabled
                    $toolbar_enabled = isset($settings['show_toolbar']) && $settings['show_toolbar'] === 'enabled';
                    $filter_bar_options = isset($settings['filter_bar_options']) && is_array($settings['filter_bar_options'])
                        ? $settings['filter_bar_options']
                        : [
                            'search' => true,
                            'venue' => true,
                            'category' => false,
                            'tag' => false,
                            'time_filter' => true,
                            'display' => true
                        ];
                    ?>
                        <div class="evge-builder-field">
                            <label><?php esc_html_e('Toolbar', 'event-genius'); ?></label>
                            <div class="evge-toggle-setting">
                                <?php
                                $value = isset($settings['show_toolbar']) ? $settings['show_toolbar'] : 'enabled';
                                ?>
                                <input class="evge-toggle-setting-enabled"
                                    type="hidden"
                                    name="calendar_settings[show_toolbar]"
                                    value="<?php echo esc_attr($value); ?>">
                                <a class="evge-settings-toggle-wrap" href="">
                                    <?php if ($value === 'enabled') : ?>
                                        <span class="evge-settings-toggle evge-input-toggle--enabled"
                                            aria-label="<?php esc_attr_e('Calendar toolbar is enabled', 'event-genius'); ?>">
                                            <?php esc_html_e('Yes', 'event-genius'); ?>
                                        </span>
                                    <?php else : ?>
                                        <span class="evge-settings-toggle evge-input-toggle--disabled"
                                            aria-label="<?php esc_attr_e('Calendar toolbar is disabled', 'event-genius'); ?>">
                                            <?php esc_html_e('No', 'event-genius'); ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                            </div>
                        </div>
                        <div class="evge-builder-field evge-filter-bar-options" <?php echo $toolbar_enabled ? '' : 'style="display: none;"'; ?>>
                            <label style="visibility: hidden;"><?php esc_html_e('Filter Bar Options', 'event-genius'); ?></label>
                            <div class="evge-filter-bar-options-group">
                            <div class="evge-sub-label"><?php esc_html_e('Filter Bar Options', 'event-genius'); ?></div>
                            <div class="evge-filter-bar-options-list">
                            <label>
                                <input type="checkbox" 
                                       name="calendar_settings[filter_bar_options][search]" 
                                       value="1" 
                                       <?php checked($filter_bar_options['search'] ?? true, true); ?>>
                                <?php esc_html_e('Search', 'event-genius'); ?>
                            </label>
                            <label>
                                <input type="checkbox" 
                                       name="calendar_settings[filter_bar_options][venue]" 
                                       value="1" 
                                       <?php checked($filter_bar_options['venue'] ?? true, true); ?>>
                                <?php esc_html_e('Venue', 'event-genius'); ?>
                            </label>
                            <label>
                                <input type="checkbox" 
                                       name="calendar_settings[filter_bar_options][category]" 
                                       value="1" 
                                       <?php checked($filter_bar_options['category'] ?? false, true); ?>>
                                <?php esc_html_e('Category', 'event-genius'); ?>
                            </label>
                            <label>
                                <input type="checkbox" 
                                       name="calendar_settings[filter_bar_options][tag]" 
                                       value="1" 
                                       <?php checked($filter_bar_options['tag'] ?? false, true); ?>>
                                <?php esc_html_e('Tag', 'event-genius'); ?>
                            </label>
                            <label>
                                <input type="checkbox" 
                                       name="calendar_settings[filter_bar_options][time_filter]" 
                                       value="1" 
                                       <?php checked($filter_bar_options['time_filter'] ?? true, true); ?>>
                                <?php esc_html_e('Upcoming/Past', 'event-genius'); ?>
                            </label>
                            <label>
                                <input type="checkbox" 
                                       name="calendar_settings[filter_bar_options][display]" 
                                       value="1" 
                                       <?php checked($filter_bar_options['display'] ?? true, true); ?>>
                                <?php esc_html_e('Display', 'event-genius'); ?>
                            </label>
                            </div>
                        </div>
                    </div>

                    <?php
                    // Only show bulk registration setting for Premium users
                    if (function_exists('evge_is_premium_tier') && evge_is_premium_tier()) :
                        $bulk_registration_enabled = isset($settings['bulk_registration_enabled']) && $settings['bulk_registration_enabled'] === 'enabled';
                    ?>
                    <div class="evge-builder-field evge-builder-field-bulk-registration">
                        <label><?php esc_html_e('Bulk Registration', 'event-genius'); ?></label>
                        <div>
                            <div class="evge-toggle-setting">
                                <input class="evge-toggle-setting-enabled" 
                                   type="hidden" 
                                   name="calendar_settings[bulk_registration_enabled]" 
                                   value="<?php echo $bulk_registration_enabled ? 'enabled' : 'disabled'; ?>">
                                <a class="evge-settings-toggle-wrap" href="">
                                    <?php if ($bulk_registration_enabled) : ?>
                                        <span class="evge-settings-toggle evge-input-toggle--enabled" 
                                            aria-label="<?php esc_attr_e('Bulk registration is enabled', 'event-genius'); ?>">
                                            <?php esc_html_e('Yes', 'event-genius'); ?>
                                        </span>
                                    <?php else : ?>
                                        <span class="evge-settings-toggle evge-input-toggle--disabled" 
                                            aria-label="<?php esc_attr_e('Bulk registration is disabled', 'event-genius'); ?>">
                                            <?php esc_html_e('No', 'event-genius'); ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                            </div>
                        </div>
                        <div class="evge-bulk-registration-message" <?php echo $bulk_registration_enabled ? '' : 'style="display: none;"'; ?>>
                            <?php
                            printf(
                                /* translators: %s: Link to registration settings */
                                esc_html__('Go to %s to configure emails', 'event-genius'),
                                '<span style="font-weight: bold;">' . esc_html__('Settings->Registrations', 'event-genius') . '</span>'
                            );
                            ?>
                        </div>
                    </div>
                    <?php
                    // Allow premium features to add additional calendar settings
                    do_action('evge_calendar_settings_after_bulk_registration', $settings, $calendar_id);
                    endif;
                    ?>
                </div>

                <div class="evge-builder-actions">
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Save Calendar', 'event-genius'); ?>
                    </button>
                </div>
            </div>

            <div class="evge-calendar-builder-preview">
                <div class="evge-preview-header">
                    <h3><?php esc_html_e('Preview', 'event-genius'); ?></h3>
                    <a href="#" style="display: none;">
                        <?php esc_html_e('View Full Preview in a New Tab', 'event-genius'); ?>
                        <?php 
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo Icon::get( 'right-chevron' ); ?>
                    </a>
                </div>
                <div class="evge-preview-content">
                    <?php
                    if (!empty($calendar_id)) {
                        echo do_shortcode('[event_genius_calendar id="' . esc_attr($calendar_id) . '"]');
                    }
                    ?>
                </div>
            </div>
        </div>
    </form>
    </div>
</div> 
<?php \WPEventGenius\Common\Utils\Notices::badges(); ?>