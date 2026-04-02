<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
?>
<div class="evge-admin-page">
<?php do_action( 'evge_admin_page_content' ); ?>
</div>
<?php
do_action( 'evge_admin_modal' );