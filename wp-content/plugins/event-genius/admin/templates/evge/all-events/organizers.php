<?php
use WPEventGenius\Admin\CustomPostTypes\OrganizersWPListTable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (!class_exists('WP_List_Table')) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

$organizer_list_table = new OrganizersWPListTable();
$organizer_list_table->prepare_items();

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$post_status = isset($_GET['post_status']) ? sanitize_key( wp_unslash( $_GET['post_status'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$current_page = isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : '';
?>

<div class="wrap evge-management-page">
    <ul class="subsubsub">
		<?php echo wp_kses_post(implode(' | ', $organizer_list_table->get_views())); ?>
    </ul>

	<form id="organizers-filter" method="post">
		<?php
		$organizer_list_table->search_box('Search Organizers', 'organizer');
		wp_nonce_field('bulk-' . $organizer_list_table->_args['plural']);
		?>

		<input type="hidden" name="page" value="<?php echo esc_attr( $current_page ); ?>" />
		<?php if ( ! empty( $post_status ) ): ?>
			<input type="hidden" name="post_status" value="<?php echo esc_attr( $post_status ); ?>" />
		<?php endif; ?>

		<?php $organizer_list_table->display(); ?>
	</form>
</div>
