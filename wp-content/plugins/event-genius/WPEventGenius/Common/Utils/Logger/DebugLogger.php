<?php
namespace WPEventGenius\Common\Utils\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class DebugLogger {
    const LOG_TRANSIENT_KEY = 'evge_debug_log';
    const MAX_LOG_ENTRIES = 20;
    
    /**
     * Add a log entry
     * 
     * @param string $message The log message
     * @param array $context Additional context data
     */
    public static function log($message, $context = array()) {
        $log_entries = self::get_log_contents();
        $log_entry = array(
            'timestamp' => current_time('timestamp'),
            'message' => self::sanitize_message($message),
            'context' => self::sanitize_context($context)
        );

        array_unshift($log_entries, $log_entry);
        
        // Keep only the most recent entries
        $log_entries = array_slice($log_entries, 0, self::MAX_LOG_ENTRIES);
        
        update_option(self::LOG_TRANSIENT_KEY, $log_entries, false);
    }
    
    /**
     * Get all log entries
     * 
     * @return array Array of log entries
     */
    public static function get_log_contents() {
        $logs = get_option(self::LOG_TRANSIENT_KEY);
        if (!$logs) {
            return array();
        }
        return $logs;
    }
    
    /**
     * Clear all log entries
     */
    public static function clear_log() {
        delete_option(self::LOG_TRANSIENT_KEY);
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

    /**
     * Format a log entry in summary format
     * 
     * @param array $entry The log entry
     * @return string Formatted log entry summary
     */
    public static function format_log_summary($entry) {
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

        // Add event information if available
        if (!empty($entry['context']['event'])) {
            $output .= $entry['context']['event'];
        }

        return $output;
    }
} 