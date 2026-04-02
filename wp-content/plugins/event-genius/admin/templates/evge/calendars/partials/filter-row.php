<?php
/**
 * Calendar Filter Row Template
 *
 * @package WPEventGenius
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$index = $index ?? 0;
$filter = $filter ?? [];
$type = $filter['type'] ?? '';
$action = $filter['action'] ?? '';
$terms = $filter['terms'] ?? [];
?>

<div class="evge-calendar-filter-row" data-index="<?php echo esc_attr($index); ?>">
    <div class="evge-calendar-filter-row-header">
        <h4><?php esc_html_e('Filter', 'event-genius'); ?> #<?php echo esc_html($index + 1); ?></h4>
        <button type="button" class="button evge-remove-filter">
            <?php esc_html_e('Remove', 'event-genius'); ?>
        </button>
    </div>

    <div class="evge-calendar-filter-row-content">
        <div class="evge-calendar-filter-type">
            <label for="calendar_filter_type_<?php echo esc_attr($index); ?>">
                <?php esc_html_e('Filter Type', 'event-genius'); ?>
            </label>
            <select name="calendar_settings[filters][<?php echo esc_attr($index); ?>][type]" 
                    id="calendar_filter_type_<?php echo esc_attr($index); ?>"
                    class="evge-filter-type-select">
                <option value=""><?php esc_html_e('Select Type', 'event-genius'); ?></option>
                <option value="category" <?php selected($type, 'category'); ?>>
                    <?php esc_html_e('Category', 'event-genius'); ?>
                </option>
                <option value="tag" <?php selected($type, 'tag'); ?>>
                    <?php esc_html_e('Tag', 'event-genius'); ?>
                </option>
            </select>
        </div>

        <div class="evge-calendar-filter-action" <?php echo empty($type) ? 'style="display: none;"' : ''; ?>>
            <label for="calendar_filter_action_<?php echo esc_attr($index); ?>">
                <?php esc_html_e('Filter Action', 'event-genius'); ?>
            </label>
            <select name="calendar_settings[filters][<?php echo esc_attr($index); ?>][action]" 
                    id="calendar_filter_action_<?php echo esc_attr($index); ?>"
                    class="evge-filter-action-select">
                <option value="include" <?php selected($action, 'include'); ?>>
                    <?php esc_html_e('Include', 'event-genius'); ?>
                </option>
                <option value="exclude" <?php selected($action, 'exclude'); ?>>
                    <?php esc_html_e('Exclude', 'event-genius'); ?>
                </option>
            </select>
        </div>

        <div class="evge-calendar-filter-terms" <?php echo empty($type) ? 'style="display: none;"' : ''; ?>>
            <label for="calendar_filter_terms_<?php echo esc_attr($index); ?>">
                <?php esc_html_e('Filter Terms', 'event-genius'); ?>
            </label>
            <select name="calendar_settings[filters][<?php echo esc_attr($index); ?>][terms][]" 
                    id="calendar_filter_terms_<?php echo esc_attr($index); ?>"
                    class="evge-filter-terms-select"
                    multiple>
                <?php
                if ($type === 'category') {
                    $categories = get_categories(['hide_empty' => false]);
                    foreach ($categories as $category) {
                        printf(
                            '<option value="%s" %s>%s</option>',
                            esc_attr($category->term_id),
                            in_array($category->term_id, $terms) ? 'selected' : '',
                            esc_html($category->name)
                        );
                    }
                } elseif ($type === 'tag') {
                    $tags = get_tags(['hide_empty' => false]);
                    foreach ($tags as $tag) {
                        printf(
                            '<option value="%s" %s>%s</option>',
                            esc_attr($tag->term_id),
                            in_array($tag->term_id, $terms) ? 'selected' : '',
                            esc_html($tag->name)
                        );
                    }
                }
                ?>
            </select>
        </div>
    </div>
</div> 