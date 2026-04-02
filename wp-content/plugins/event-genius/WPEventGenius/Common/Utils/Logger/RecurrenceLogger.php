<?php
namespace WPEventGenius\Common\Utils\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class RecurrenceLogger {
    const LOG_TRANSIENT_KEY = 'evge_debug_log';
    const LOG_EXPIRATION = 24 * HOUR_IN_SECONDS; // 24 hours
    const MAX_LOG_ENTRIES = 1000;
    const ENCRYPTION_KEY = 'evge_log_encryption_key';
    
    /**
     * Add a log entry
     * 
     * @param string $message The log message
     * @param array $context Additional context data
     */
    public static function log($message, $context = array()) {
        if (!self::is_debug_mode_enabled()) {
            return;
        }

        $log_entries = self::get_log_contents();
        $log_entry = array(
            'timestamp' => current_time('timestamp'),
            'message' => self::sanitize_message($message),
            'context' => self::sanitize_context($context)
        );

        array_unshift($log_entries, $log_entry);
        
        // Keep only the most recent entries
        $log_entries = array_slice($log_entries, 0, self::MAX_LOG_ENTRIES);
        
        set_transient(self::LOG_TRANSIENT_KEY, self::encrypt_logs($log_entries), self::LOG_EXPIRATION);
    }
    
    /**
     * Get all log entries
     * 
     * @return array Array of log entries
     */
    public static function get_log_contents() {
        $logs = get_transient(self::LOG_TRANSIENT_KEY);
        if (!$logs) {
            return array();
        }
        return self::decrypt_logs($logs);
    }
    
    /**
     * Clear all log entries
     */
    public static function clear_log() {
        delete_transient(self::LOG_TRANSIENT_KEY);
    }
    
    /**
     * Enable debug mode
     */
    public static function enable_debug_mode() {
        if (!current_user_can('manage_options')) {
            return;
        }
        set_transient('evge_recurrence_debug_mode', time() + HOUR_IN_SECONDS, HOUR_IN_SECONDS);
    }
    
    /**
     * Disable debug mode
     */
    public static function disable_debug_mode() {
        if (!current_user_can('manage_options')) {
            return;
        }
        delete_transient('evge_recurrence_debug_mode');
        self::clear_log();
    }
    
    /**
     * Check if debug mode is enabled
     * 
     * @return bool
     */
    public static function is_debug_mode_enabled() {
        return (bool) get_transient('evge_recurrence_debug_mode');
    }
    
    /**
     * Sanitize log message
     * 
     * @param string $message
     * @return string
     */
    private static function sanitize_message($message) {
        return wp_kses_post($message);
    }
    
    /**
     * Sanitize context data
     * 
     * @param array $context
     * @return array
     */
    private static function sanitize_context($context) {
        if (!is_array($context)) {
            return array();
        }

        $sanitized = array();
        foreach ($context as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitize_context($value);
            } else {
                $sanitized[$key] = wp_kses_post($value);
            }
        }
        return $sanitized;
    }
    
    /**
     * Encrypt log data
     * 
     * @param array $logs
     * @return string
     */
    private static function encrypt_logs($logs) {
        $key = wp_salt('auth') . self::ENCRYPTION_KEY;
        $data = json_encode($logs);
        return base64_encode(openssl_encrypt($data, 'AES-256-CBC', $key, 0, substr($key, 0, 16)));
    }
    
    /**
     * Decrypt log data
     * 
     * @param string $encrypted
     * @return array
     */
    private static function decrypt_logs($encrypted) {
        $key = wp_salt('auth') . self::ENCRYPTION_KEY;
        $data = openssl_decrypt(base64_decode($encrypted), 'AES-256-CBC', $key, 0, substr($key, 0, 16));
        return json_decode($data, true) ?: array();
    }
    
    /**
     * Format a log entry for display
     * 
     * @param array $entry The log entry
     * @return string Formatted log entry
     */
    public static function format_log_entry($entry) {
        if (!isset($entry['timestamp']) || !isset($entry['message'])) {
            return '';
        }

        $date = new \DateTime('@' . $entry['timestamp']);
        $date->setTimezone(new \DateTimeZone('America/New_York'));
        
        $output = sprintf(
            '[%s] %s',
            $date->format('Y-m-d H:i:s'),
            $entry['message']
        );

        if (!empty($entry['context'])) {
            $output .= "\nContext: " . json_encode($entry['context'], JSON_PRETTY_PRINT);
        }

        return $output;
    }
} 