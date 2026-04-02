<?php
use WPEventGenius\Admin\CustomPostTypes\VenuesWPListTable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

$venue_list_table = new VenuesWPListTable();
$venue_list_table->prepare_items();

// phpcs:ignore WordPress.Security.NonceVerification.Recommended    
$post_status = isset($_GET['post_status']) ? sanitize_key( wp_unslash( $_GET['post_status'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$current_page = isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : '';
?>

<div class="wrap evge-management-page">

    <ul class="subsubsub">
		<?php echo wp_kses_post(implode(' | ', $venue_list_table->get_views())); ?>
    </ul>

    <form id="venues-filter" method="post">
        <?php
        $venue_list_table->search_box('Search Venues', 'venue');
        wp_nonce_field('bulk-' . $venue_list_table->_args['plural']);
        ?>

        <input type="hidden" name="page" value="<?php echo esc_attr( $current_page ); ?>" />
        <?php if ( ! empty( $post_status ) ): ?>
            <input type="hidden" name="post_status" value="<?php echo esc_attr( $post_status ); ?>" />
        <?php endif; ?>

        <?php $venue_list_table->display(); ?>
    </form>
</div>
