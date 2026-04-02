<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<div id="evge-dashboard-events-list" class="evge-is-processing" <?php echo $this->ajax_atts( 'evge_upcoming_events', true ); ?>>
    <div class="evge-dynamic">
        <div class="evge-spinner-container"><div class="evge-spinner-circle"></div></div>
    </div>
</div>
