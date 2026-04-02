<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<div id="evge-dashboard-registrations-list" class="evge-is-processing" <?php echo $this->ajax_atts( 'evge_latest_registrations', true ); ?>>
    <div class="evge-dynamic">
        <div class="evge-spinner-container"><div class="evge-spinner-circle"></div></div>
    </div>
</div>
