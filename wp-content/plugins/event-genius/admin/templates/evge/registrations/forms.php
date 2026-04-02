<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( $active_tab === 'email' ) {
	include_once( EVGE_ADMIN_TEMPLATE_PATH . 'evge/registrations/form-builder/emails.php' );
} elseif ( $active_tab === 'settings' ) {
	include_once( EVGE_ADMIN_TEMPLATE_PATH . 'evge/registrations/form-builder/settings.php' );
} else {
	include_once( EVGE_ADMIN_TEMPLATE_PATH . 'evge/registrations/form-builder/builder.php' );
}
?>
