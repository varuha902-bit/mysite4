<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="evge-form-builder-pages" data-evge-tab="builder">
	<?php include_once( EVGE_ADMIN_TEMPLATE_PATH . 'evge/registrations/form-builder/builder.php' ); ?>
</div>

<div class="evge-form-builder-pages" data-evge-tab="emails" style="display: none">
	<?php $this->emails_content(); ?>
</div>

<div class="evge-form-builder-pages" data-evge-tab="settings" style="display: none">
	<?php include_once( EVGE_ADMIN_TEMPLATE_PATH . 'evge/registrations/form-builder/settings.php' ); ?>
</div>

<?php do_action( 'evge_admin_modal' ); ?>
