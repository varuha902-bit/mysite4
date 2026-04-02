<?php
use WPEventGenius\Admin\CustomPostTypes\RegistrationsWPListTable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (!class_exists('WP_List_Table')) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

$registration_list_table = new RegistrationsWPListTable();
$registration_list_table->prepare_items();

// phpcs:ignore WordPress.Security.NonceVerification.Recommended    
$current_page = isset($_REQUEST['page']) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : '';
?>

<div class="wrap evge-admin-registrations-page evge-management-page" style="visibility: hidden;">
    <h1 class="wp-heading-inline"><?php esc_html_e('Registrations', 'event-genius'); ?></h1>
    <div>
	    <?php $registration_list_table->views(); ?>
    </div>

    <hr class="wp-header-end">

    <form id="registrations-filter" method="get">
        <?php
        wp_nonce_field('bulk-' . $registration_list_table->_args['plural']);
        ?>
        <input type="hidden" name="page" value="<?php echo esc_attr($current_page); ?>" />
        <div class="evge-filter-move">
	        <?php include( EVGE_ADMIN_TEMPLATE_PATH . 'evge/registrations/partials/search-and-filter-bar.php' ); ?>

        </div>
    </form>
    <form method="post" action="">
        <?php $registration_list_table->display(); ?>
    </form>
</div>
<?php
include_once trailingslashit( EVGE_PLUGIN_PATH ) . 'templates/event-genius/common/modal.php';
