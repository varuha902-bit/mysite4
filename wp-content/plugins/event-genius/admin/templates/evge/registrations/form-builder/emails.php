<?php

use WPEventGenius\Admin\BaseAdminPage;
use WPEventGenius\Admin\Notice;
use WPEventGenius\Admin\Services\DashboardNoticeService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Initialize the notice service
$notice_service = new DashboardNoticeService();

// Get SMTP notice if it should be shown
$smtp_notice = $notice_service->dashboard_notice_smtp();
if ($smtp_notice) {
    $notices = array($smtp_notice);
    include EVGE_ADMIN_TEMPLATE_PATH . 'evge/partials/dashboard/notice.php';
}

$this->before_settings_sections();
?>

<div class="evge-settings-page evge-bump-down">
    <form id="evge-fb-emails" method="post" action="options.php">
		<?php settings_fields( 'evge_registration_settings' ); ?>

        <div class="evge-settings-section-wrap">
			<?php do_settings_sections( 'evge_registration_email_confirmation' ); ?>
        </div>

        <div class="evge-settings-section-wrap">
			<?php do_settings_sections( 'evge_registration_email_notification' ); ?>
        </div>

        <div class="evge-settings-section-wrap evge-bump-down">
			<?php do_settings_sections( 'evge_registration_email_cancel_request' ); ?>
        </div>

        <div class="evge-settings-section-wrap">
			<?php do_settings_sections( 'evge_registration_email_cancel_notification' ); ?>
        </div>

        <div class="evge-settings-section-wrap">
			<?php do_settings_sections( 'evge_registration_email_cancel_confirmation' ); ?>
        </div>
    </form>
</div>
