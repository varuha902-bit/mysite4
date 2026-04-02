<?php
use WPEventGenius\Admin\CustomPostTypes\EventsWPListTable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
if (isset($_GET['trashed']) && $_GET['trashed'] == 1) {
    echo '<div class="updated"><p>' . esc_html__('Event moved to trash.', 'event-genius') . '</p></div>';
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
} elseif (isset($_GET['untrashed']) && $_GET['untrashed'] == 1) {
    echo '<div class="updated"><p>' . esc_html__('Event restored from trash.', 'event-genius') . '</p></div>';
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
} elseif (isset($_GET['emptied']) && $_GET['emptied'] === 'true') {
    echo '<div class="updated"><p>' . esc_html__('Trash emptied successfully.', 'event-genius') . '</p></div>';
}

$event_list_table = $page->get_event_list_table();

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$current_page = isset($_REQUEST['page']) ? sanitize_text_field(wp_unslash($_REQUEST['page'])) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$post_status = isset($_GET['post_status']) ? sanitize_key(wp_unslash($_GET['post_status'])) : '';

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}
?>

<div class="wrap evge-management-page" style="visibility: hidden;">
    
    <ul class="subsubsub">
        <?php echo wp_kses_post(implode(' | ', $event_list_table->get_views())); ?>
    </ul>

    <form id="posts-filter" method="get" action="">
        <?php wp_nonce_field('bulk-' . $event_list_table->_args['plural']); ?>

        <input type="hidden" name="page" value="<?php echo esc_attr($current_page); ?>" />
        <?php if (!empty($post_status)): ?>
            <input type="hidden" name="post_status" value="<?php echo esc_attr($post_status); ?>" />
        <?php endif; ?>

        <div class="evge-filter-move">
            <?php include( EVGE_ADMIN_TEMPLATE_PATH . 'evge/all-events/partials/search-and-filter-bar.php' ); ?>
        </div>
    </form>
    <form class="evge-bulk-actions-form" method="post" action="">
        <?php if (!empty($event_list_table->items)): ?>
            <?php $event_list_table->display(); ?>
        <?php else: ?>
            <?php 
            $reset_url = admin_url('admin.php?page=' . $current_page . '&qtype=all&with=either');
            echo wp_kses_post($page->get_no_events_message($reset_url)); 
            ?>
        <?php endif; ?>
    </form>
</div>
<?php
do_action( 'evge_admin_modal' );
?>