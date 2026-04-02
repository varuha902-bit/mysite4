    <?php
    use WPEventGenius\Admin\CustomPostTypes\EventsWPListTable;

    if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
    ?>
    <div class="wrap evge-management-page evge-event-card-page">
    <?php

    if (!class_exists('WP_List_Table')) {
	    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
    }

    $event_list_table = new EventsWPListTable();
    $event_list_table->prepare_items();

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $current_page = isset($_REQUEST['page']) ? sanitize_text_field(wp_unslash($_REQUEST['page'])) : '';
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $post_status = isset($_GET['post_status']) ? sanitize_key(wp_unslash($_GET['post_status'])) : '';
    ?>


    <ul class="subsubsub">
		<?php echo wp_kses_post(implode(' | ', $event_list_table->get_views())); ?>
    </ul>
    <form method="get" action="">
	    <?php if (!empty($post_status)): ?>
            <input type="hidden" name="post_status" value="<?php echo esc_attr($post_status); ?>" />
	    <?php endif; ?>
	<?php include( EVGE_ADMIN_TEMPLATE_PATH . 'evge/all-events/partials/search-and-filter-bar.php' ); ?>
</form>
	<?php
	if (!empty($page->events))  : ?>
    <?php $page->event_pagination( $page->events ); ?>
<div class="evge-card-wrapper evge-overview-display">
<?php
		foreach ($page->events as $event) {
			include(EVGE_ADMIN_TEMPLATE_PATH . 'registration/partials/overview/card.php');
		}
	?>

</div>
<?php endif;
if (empty($page->events)) : ?>
    <?php 
    $reset_url = admin_url('admin.php?page=' . $current_page . '&qtype=all&with=either&view=grid');
    echo wp_kses_post($page->get_no_events_message($reset_url)); 
    ?>
<?php endif;
$page->event_pagination( $page->events ); ?>
</div>
