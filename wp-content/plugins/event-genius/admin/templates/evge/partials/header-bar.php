<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="evge-admin-header">

	<div class="evge-admin-header-inner">
		<div class="evge-admin-header-identity">
			<div class="evge-logo">
				<img src="<?php echo esc_url( EVGE_PLUGIN_URL . 'assets/images/admin/eg-logo-svg.svg' ); ?>" alt="WP Event Genius">
			</div>
            <svg width="10" height="20" viewBox="0 0 10 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0.542969 18.7969L8.54297 0.796875" stroke="#666666" stroke-linecap="round"/>
            </svg>
            <h1>
				<?php if ( method_exists( $page, 'get_calendar_color' ) && $page->get_calendar_color() ) : ?>
					<span class="evge-calendar-color-dot" style="background-color: <?php echo esc_attr( $page->get_calendar_color() ); ?>"></span>
				<?php endif; ?>
				<span class="evge-current-page"><?php echo esc_html( $page->page_title() ); ?></span>
			</h1>
            <?php $page->action_button(); ?>
		</div>	
		<?php if ( $page->get_tab() !== 'support' ) : ?>
		<div class="evge-header-support">
			<a href="<?php echo esc_url( get_admin_url( null, 'admin.php?page=evge-support' ) ); ?>" class="evge-admin-button evge-help-button evge-icon-text"><svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="8" cy="8" r="7.5" stroke="#333333"/><path d="M6.79028 9.69192V9.53496C6.79336 8.99637 6.84106 8.56704 6.93339 8.24696C7.0288 7.92688 7.1673 7.66836 7.34888 7.47139C7.53046 7.27442 7.74897 7.09592 8.00442 6.93588C8.19523 6.81277 8.36604 6.68505 8.51685 6.55271C8.66765 6.42037 8.78768 6.27419 8.87693 6.11415C8.96618 5.95103 9.01081 5.76945 9.01081 5.5694C9.01081 5.35704 8.96003 5.17085 8.85847 5.01081C8.7569 4.85077 8.61995 4.72766 8.4476 4.64149C8.27833 4.55532 8.09059 4.51223 7.88439 4.51223C7.68434 4.51223 7.49507 4.55685 7.31656 4.64611C7.13806 4.73228 6.99187 4.86154 6.878 5.03389C6.76412 5.20316 6.70257 5.41398 6.69334 5.66635H4.80981C4.8252 5.05082 4.97293 4.54301 5.253 4.14291C5.53306 3.73974 5.90392 3.43967 6.36557 3.2427C6.82722 3.04265 7.33657 2.94263 7.89362 2.94263C8.50607 2.94263 9.04774 3.04419 9.51862 3.24731C9.9895 3.44736 10.3588 3.7382 10.6266 4.11983C10.8943 4.50146 11.0282 4.96157 11.0282 5.50015C11.0282 5.86024 10.9682 6.18032 10.8482 6.46038C10.7312 6.73737 10.5666 6.98358 10.3542 7.19902C10.1418 7.41138 9.89102 7.60373 9.60172 7.77608C9.35858 7.92073 9.15854 8.07153 9.00158 8.22849C8.84769 8.38545 8.73228 8.56703 8.65534 8.77324C8.58148 8.97944 8.54301 9.23335 8.53993 9.53496V9.69192H6.79028ZM7.70435 12.6465C7.39658 12.6465 7.13344 12.5387 6.91493 12.3233C6.69949 12.1048 6.59331 11.8432 6.59639 11.5385C6.59331 11.2369 6.69949 10.9784 6.91493 10.7629C7.13344 10.5475 7.39658 10.4398 7.70435 10.4398C7.99672 10.4398 8.25371 10.5475 8.4753 10.7629C8.69689 10.9784 8.80922 11.2369 8.8123 11.5385C8.80922 11.7416 8.75536 11.9278 8.65072 12.0971C8.54916 12.2633 8.41528 12.3972 8.24909 12.4987C8.0829 12.5972 7.90132 12.6465 7.70435 12.6465Z" fill="#333333"/></svg><?php esc_html_e( 'Support', 'event-genius' ); ?></a>
		</div>
		<?php endif; ?>
	</div>
</div>
