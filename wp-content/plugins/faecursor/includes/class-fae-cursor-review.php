<?php
/**
 * FaeCursor Review Request System
 * Handles review notification display and user interactions
 */

if (!defined('ABSPATH')) {
    exit;
}

class Fae_Cursor_Review {
    
    /**
     * Initialize review system
     */
    public static function init() {
        // AJAX handler for review actions
        add_action('wp_ajax_fae_review_action', array(__CLASS__, 'ajax_review_action'));
        
        // Track plugin activation for review logic
        register_activation_hook(FAE_CURSOR_DIR . '/faecursor.php', array(__CLASS__, 'on_plugin_activation'));
    }
    
    /**
     * Track plugin activation time
     */
    public static function on_plugin_activation() {
        if (!get_option('faecursor_activated_time')) {
            update_option('faecursor_activated_time', time());
        }
    }
    
    /**
     * Check if review notification should be shown
     * 
     * Conditions:
     * - Plugin active for 7+ days
     * - User has enabled at least 1 effect
     * - Not already reviewed/dismissed
     * - If "Later" was clicked, 3+ days have passed
     * 
     * @return bool True if notification should be shown
     */
    public static function should_show_notice() {
        $review_status = get_option('faecursor_review_status', '');
        
        // Never show if already reviewed or permanently dismissed
        if ($review_status === 'reviewed' || $review_status === 'dismissed') {
            return false;
        }
        
        // Check if "Later" was clicked
        if ($review_status === 'later') {
            $later_time = get_option('faecursor_review_later_time', 0);
            $days_passed = (time() - $later_time) / DAY_IN_SECONDS;
            
            // Show again after 3 days
            if ($days_passed < 3) {
                return false;
            }
        }
        
        // Handle activation time tracking
        // For NEW installs: Set on activation hook
        // For EXISTING installs (upgrades): Set on first check (NOW), then wait 7 days
        $activated_time = get_option('faecursor_activated_time', 0);
        if (!$activated_time) {
            // This is an existing installation without activation tracking
            // Set it now so we can start the 7-day countdown
            update_option('faecursor_activated_time', time());
            return false; // Don't show yet, wait 7 days from now
        }
        
        // Check if 7 days have passed since activation
        $days_since_activation = (time() - $activated_time) / DAY_IN_SECONDS;
        if ($days_since_activation < 7) {
            return false;
        }
        
        // Check if user has enabled at least one effect (proves they're using the plugin)
        $cursor_options = Fae_Cursor_Settings::get_options();
        $keyboard_options = Fae_Keyboard_Settings::get_options();
        $particle_options = Fae_Particle_Settings::get_options();
        
        $cursor_effect = isset($cursor_options['effect']) ? $cursor_options['effect'] : 'none';
        $keyboard_effect = isset($keyboard_options['effect']) ? $keyboard_options['effect'] : 'none';
        $particle_effect = isset($particle_options['effect']) ? $particle_options['effect'] : 'none';
        
        $has_active_effect = ($cursor_effect !== 'none' || $keyboard_effect !== 'none' || $particle_effect !== 'none');
        
        // Only show review request if user is actually using the plugin
        if (!$has_active_effect) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Render review notification HTML
     */
    public static function render_notification() {
        if (!self::should_show_notice()) {
            return;
        }
        ?>
        <!-- Review Request Slide-in Notification -->
        <div class="fae-review-notification" id="fae-review-notification" data-nonce="<?php echo esc_attr(wp_create_nonce('fae_review_action')); ?>">
            <div class="fae-review-notification-bar"></div>
            <div class="fae-review-notification-content">
                <div class="fae-review-notification-icon">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                    </svg>
                </div>
                <div class="fae-review-notification-body">
                    <div class="fae-review-notification-header">
                        <h4>Enjoying FaeCursor?</h4>
                        <button type="button" class="fae-review-notification-dismiss" id="fae-review-notification-dismiss" aria-label="Dismiss">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 6L6 18M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <p class="fae-review-notification-text">Share your feedback and help us grow</p>
                    <div class="fae-review-notification-stars">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                        </svg>
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                        </svg>
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                        </svg>
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                        </svg>
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                        </svg>
                    </div>
                    <div class="fae-review-notification-actions">
                        <a href="https://wordpress.org/plugins/faecursor/#reviews" target="_blank" class="fae-review-notification-btn" id="fae-review-btn">
                            Leave a Review
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                <path d="M5 12h14M12 5l7 7-7 7"/>
                            </svg>
                        </a>
                        <button type="button" class="fae-review-notification-later" id="fae-review-notification-later">
                            Maybe Later
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX handler for review actions
     */
    public static function ajax_review_action() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'fae_review_action')) {
            wp_send_json_error(array('message' => 'Invalid security token.'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }
        
        $action = isset($_POST['review_action']) ? sanitize_text_field(wp_unslash($_POST['review_action'])) : '';
        
        switch ($action) {
            case 'reviewed':
                // User clicked "Leave a Review" - never show again
                update_option('faecursor_review_status', 'reviewed');
                wp_send_json_success(array('message' => 'Thank you for your review!'));
                break;
                
            case 'later':
                // User clicked "Maybe Later" - show again in 3 days
                update_option('faecursor_review_status', 'later');
                update_option('faecursor_review_later_time', time());
                wp_send_json_success(array('message' => 'Reminder set.'));
                break;
                
            case 'dismissed':
                // User clicked X - never show again
                update_option('faecursor_review_status', 'dismissed');
                wp_send_json_success(array('message' => 'Notification dismissed.'));
                break;
                
            default:
                wp_send_json_error(array('message' => 'Invalid action.'));
        }
    }
}
