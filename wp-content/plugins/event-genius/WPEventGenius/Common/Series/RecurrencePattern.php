<?php
namespace WPEventGenius\Common\Series;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class RecurrencePattern {
    protected $type;
    protected $interval;
    protected $start_date;  // Local time
    protected $end_date;    // Local time
    protected $timezone;
    protected $days = array();
    protected $exceptions = array();
    
    public function __construct($data) {
        $this->type = $data['type'] ?? 'daily';
        $this->interval = $data['interval'] ?? 1;
        $this->start_date = $data['start_date'] ?? null;
        $this->end_date = $data['end_date'] ?? null;
        $this->timezone = $data['timezone'] ?? wp_timezone_string();
    }
    
    public function get_type() {
        return $this->type;
    }
    
    public function get_interval() {
        return $this->interval;
    }
    
    public function get_start_date() {
        return $this->start_date;
    }
    
    public function get_end_date() {
        return $this->end_date;
    }
    
    public function get_timezone() {
        return $this->timezone;
    }
    
    public function validate() {
        if (empty($this->start_date) || empty($this->end_date)) {
            throw new \Exception('Start date and end date are required');
        }
        
        if (!in_array($this->type, array('daily', 'weekly', 'monthly'))) {
            throw new \Exception('Invalid recurrence type');
        }
        
        if ($this->interval < 1) {
            throw new \Exception('Interval must be at least 1');
        }
        
        return true;
    }
    
    // Add other getters and validation methods
} 