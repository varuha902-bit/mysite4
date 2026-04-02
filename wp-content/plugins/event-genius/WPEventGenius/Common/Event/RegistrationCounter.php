<?php
namespace WPEventGenius\Common\Event;

use WPEventGenius\Common\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class RegistrationCounter {
    /**
     * @var int The event ID
     */
    protected $event_id;

    /**
     * @var array Cached counts by status
     */
    protected $counts;

    protected $database;

    /**
     * Constructor
     * 
     * @param int $event_id The event ID to get counts for
     */
    public function __construct($event_id, Database $database) {
        $this->event_id = $event_id;
        $this->database = $database;
        $this->load_counts();
    }

    /**
     * Load registration counts from database
     */
    protected function load_counts() {
        $this->counts = $this->database->get_registration_status_counts($this->event_id);
    }

    /**
     * Get total confirmed registrations
     * 
     * @return int Number of confirmed registrations
     */
    public function get_confirmed_count() {
        return isset($this->counts['confirmed']) ? $this->counts['confirmed'] : 0;
    }

    /**
     * Get total pending registrations
     * 
     * @return int Number of pending registrations
     */
    public function get_pending_count() {
        return isset($this->counts['pending']) ? $this->counts['pending'] : 0;
    }

    /**
     * Get total canceled registrations
     * 
     * @return int Number of canceled registrations
     */
    public function get_canceled_count() {
        return isset($this->counts['canceled']) ? $this->counts['canceled'] : 0;
    }

    /**
     * Get total active registrations (confirmed + pending)
     * 
     * @return int Number of active registrations
     */
    public function get_active_count() {
        return $this->get_confirmed_count() + $this->get_pending_count();
    }

    /**
     * Get all registration counts
     * 
     * @return array Array of counts by status
     */
    public function get_all_counts() {
        return $this->counts;
    }

    /**
     * Get remaining registration spots
     * 
     * @param mixed $capacity The event capacity
     * @return int Number of remaining spots
     */
    public function get_remaining_count($capacity) {
        // Convert capacity to integer
        $capacity = (int) $capacity;
        
        // Get total active registrations
        $active_registrations = $this->get_active_count();
        
        // Calculate remaining spots
        $remaining = $capacity - $active_registrations;
        
        // Ensure we don't return negative numbers
        return max(0, $remaining);
    }
} 