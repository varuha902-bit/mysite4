<?php
/**
 * Event Genius Free Version Initialization
 * 
 * This file contains all the shared initialization code for the free version.
 * It's loaded by the main event-genius.php file.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initialize the main plugin functionality
 */
function evge_init_main() {
	do_action( 'evge_core_loaded' );

	// Use Main class for free version
	$wp_event_genius_main = evge_get_main_instance();
	$wp_event_genius_main->init();
}
add_action( 'plugins_loaded', 'evge_init_main' );

/**
 * Initialize components that depend on WordPress being fully loaded
 */
function evge_init_components() {
	$wp_event_genius_main = evge_get_main_instance();
	$wp_event_genius_main->init_components();
}
add_action( 'init', 'evge_init_components' );

/**
 * Handle plugin activation
 */
function evge_activate($network_wide) {
	$activation_service = new \WPEventGenius\Common\Services\ActivationService();
	$activation_service->activate($network_wide);
}
register_activation_hook(EVGE_PLUGIN_FILE, 'evge_activate');

/**
 * Handle plugin deactivation
 */
function evge_deactivate() {
	$deactivation_service = new \WPEventGenius\Common\Services\DeactivationService();
	$deactivation_service->deactivate();
}
register_deactivation_hook(EVGE_PLUGIN_FILE, 'evge_deactivate');


/**
 * Get the main plugin instance
 */
function EVGE() {
	return evge_get_main_instance();
}

/**
 * Show license upsell for free version
 */
function evge_free_license_section() {
	?>
	<div class="evge-settings-section-wrap evge-bump-down">
		<div class="evge-license-field-wrap">
			<strong><?php esc_html_e( 'License Key', 'event-genius' ); ?></strong>
			<p><?php esc_html_e( 'You\'re using the free version of EventGenius - no license required! To unlock more advanced features, consider upgrading to a Pro license.', 'event-genius' ); ?></p>
			<a class="evge-license-pro-upsell" href="https://wpeventgenius.com/pricing/?utm_campaign=evge-free&utm_source=settings-page&utm_medium=license-key-field&utm_content=upgrading-to-pro" target="_blank">
				<div>
					<span><?php echo sprintf( esc_html__( 'Upgrade to %sPro%s', 'event-genius' ), '<strong>', '</strong>' ); ?></span>
					<svg width="5" height="10" viewBox="0 0 5 10" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M1.02051 0.198869L4.82491 4.51989C5.05836 4.78505 5.05836 5.21495 4.82491 5.48011L1.02051 9.80113C0.787058 10.0663 0.40855 10.0663 0.175093 9.80113C-0.0583633 9.53597 -0.0583633 9.10606 0.175093 8.8409L3.55678 5L0.175093 1.1591C-0.0583641 0.893936 -0.0583641 0.464029 0.175092 0.198869C0.408549 -0.0662898 0.787057 -0.0662899 1.02051 0.198869Z" fill="#1A79C1"/></svg>
				</div>
			</a>
		</div>
	</div>
	<?php
}
add_action( 'evge_general_settings_license_section', 'evge_free_license_section' ); 