<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$event = $page->events[0];
$subtab = $page->get_subtab();

?>
<div class="evge-single-view-wrapper">
	<?php
	include_once( EVGE_ADMIN_TEMPLATE_PATH . 'registration/partials/single/more.php' );

	?>

</div>