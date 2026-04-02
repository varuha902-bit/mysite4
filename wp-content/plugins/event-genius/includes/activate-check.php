<?php
/**
 * Event Genius Plugin Conflict Check
 * 
 * Automatically deactivates other tier versions when activating a new tier.
 * Prevents loading if another version is already loaded and shows admin notice.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Prevent activation if another Event Genius plugin tier is active
 * 
 * Shows a wp_die() message telling the user to deactivate other tiers first.
 * 
 * @param string $plugin_file The plugin file being activated
 */
if ( ! function_exists( 'evge_prevent_activation_on_conflict' ) ) {
	function evge_prevent_activation_on_conflict( $plugin_file ) {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins = array(
			'event-genius/event-genius.php',
			'event-genius-pro/event-genius-pro.php',
			'event-genius-standard/event-genius-standard.php',
			'event-genius-premium/event-genius-premium.php',
			'event-genius-advanced/event-genius-advanced.php',
			'wp-event-genius/event-genius.php',
			'wp-event-genius/event-genius-pro.php',
			'wp-event-genius/event-genius-standard.php',
			'wp-event-genius/event-genius-premium.php',
			'wp-event-genius/event-genius-advanced.php',
		);

		$current_plugin = plugin_basename( $plugin_file );
		$active_plugins = array();

		// Find all other active Event Genius plugins
		foreach ( $all_plugins as $plugin ) {
			if ( $plugin !== $current_plugin && is_plugin_active( $plugin ) ) {
				$active_plugins[] = $plugin;
			}
		}

		// If other tiers are active, show error message and prevent activation
		if ( ! empty( $active_plugins ) ) {
			$plugins_url = admin_url( 'plugins.php' );
			$message = '<h1>' . esc_html__( 'Cannot Activate Plugin', 'event-genius' ) . '</h1>';
			$message .= '<p>' . esc_html__( 'Another tier of Event Genius is currently active. Please deactivate it before activating this tier.', 'event-genius' ) . '</p>';
			$message .= '<p><a href="' . esc_url( $plugins_url ) . '" class="button button-primary">' . esc_html__( 'Go to Plugins Page', 'event-genius' ) . '</a></p>';
			
			wp_die( $message, esc_html__( 'Plugin Activation Error', 'event-genius' ), array( 'back_link' => true ) );
		}
	}
}

/**
 * Check if another Event Genius plugin is already loaded and prevent loading
 * 
 * @return bool True if should continue loading, false if should exit
 */
if ( ! function_exists( 'evge_check_conflict_before_load' ) ) {
	function evge_check_conflict_before_load() {
		// Check if evge_init_main function is already declared (means another version is loaded)
		if ( function_exists( 'evge_init_main' ) ) {
			// Register admin notice
			add_action( 'admin_notices', function() {
				$plugins_url = admin_url( 'plugins.php' );
				?>
				<div class="notice notice-error">
					<p>You have more than one tier version of Event Genius active. Deactivate other tier versions. <a href="<?php echo esc_url( $plugins_url ); ?>">Go to Plugins page</a></p>
				</div>
				<?php
			} );
			return false; // Conflict detected, don't load
		}

		return true; // No conflict, safe to load
	}
}

