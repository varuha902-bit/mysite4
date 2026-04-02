<?php
namespace WPEventGenius\Common\Series\Queue;

use WPEventGenius\Common\Database;
use WPEventGenius\Common\Series\EventSeriesRepository;
use WPEventGenius\Common\Series\RecurrenceScheduler;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class SeriesQueue {
    const QUEUE_OPTION_KEY = 'evge_series_queue';
    const BATCH_SIZE = 30; // Number of events to create per batch
    const LOCK_TIMEOUT = 300; // 5 minutes
    const TASK_TYPE_CREATE = 'create';
    const TASK_TYPE_UPDATE = 'update';

    protected $db;

    public function __construct( Database $db) {
        $this->db = $db;
    }

    /**
     * Add a series task to the queue
     */
    public function enqueue($task_data) {
        if (!isset($task_data['type'])) {
            $task_data['type'] = self::TASK_TYPE_CREATE;
        }
        
        $queue = $this->get_queue();
        $task_id = uniqid('series_', true);
        $queue[] = array(
            'id' => $task_id,
            'data' => $task_data,
            'status' => 'pending',
            'created_at' => current_time('mysql'),
            'processed_events' => 0,
            'total_events' => 0
        );
        
        $this->save_queue($queue);
        
        // Log the enqueued task
        if ($this->is_debug_mode_enabled()) {
            \WPEventGenius\Common\Utils\Logger\RecurrenceLogger::log('Task enqueued', [
                'task_id' => $task_id,
                'type' => $task_data['type'],
                'series_id' => $task_data['series_id'] ?? 'unknown',
                'template_id' => $task_data['template_id'] ?? 'unknown'
            ]);
        }
        
        // Schedule processing if not already scheduled
        if (!wp_next_scheduled('evge_process_series_queue')) {
            wp_schedule_single_event(time() + 10, 'evge_process_series_queue');
        }
    }

    /**
     * Process the next batch of items in the queue
     */
    public function process_batch() {
        if ($this->is_locked()) {
            return;
        }

        $this->set_lock();

        try {
            $queue = $this->get_queue();
            $processed_any = false;

            foreach ($queue as $key => &$task) {
                if ($task['status'] === 'pending' || $task['status'] === 'processing') {
                    $processed_any = true;
                    $this->process_task($task);
                    
                    if ($task['status'] !== 'completed') {
                        // Schedule next batch
                        wp_schedule_single_event(time() + 10, 'evge_process_series_queue');
                        break;
                    }
                }
            }

            if ($processed_any) {
                $this->save_queue($queue);
            }

        } finally {
            $this->release_lock();
        }
    }

    /**
     * Process a single task
     */
    protected function process_task(&$task) {
        $repository = new EventSeriesRepository($this->db);
        $scheduler = new RecurrenceScheduler($repository);

        try {
            if ($task['status'] === 'pending') {
                // First time processing this task
                $task['status'] = 'processing';
                
                if ($task['data']['type'] === self::TASK_TYPE_CREATE) {
                    // For create tasks, get total from recurrence pattern
                    $dates = $scheduler->get_recurrence_dates($task['data']['pattern']);
                    $task['total_events'] = count($dates);
                    
                    // Check if we're preserving an existing series
                    if (isset($task['data']['preserve_series']) && $task['data']['preserve_series']) {
                        // Use existing series ID
                        $series_id = $task['data']['series_id'];
                        
                        // Log series preservation
                        if ($this->is_debug_mode_enabled()) {
                            \WPEventGenius\Common\Utils\Logger\RecurrenceLogger::log('Series preserved', [
                                'task_id' => $task['id'],
                                'series_id' => $series_id,
                                'template_id' => $task['data']['template_id'],
                                'total_events' => $task['total_events']
                            ]);
                        }
                    } else {
                        // Create the series first
                        $series_id = $repository->create_series($task['data']['series_data']);
                        $task['data']['series_id'] = $series_id;

                        // Save the series ID to the template event
                        update_post_meta($task['data']['template_id'], 'evge_series_id', $series_id);

                        // Log series creation
                        if ($this->is_debug_mode_enabled()) {
                            \WPEventGenius\Common\Utils\Logger\RecurrenceLogger::log('Series created', [
                                'task_id' => $task['id'],
                                'series_id' => $series_id,
                                'template_id' => $task['data']['template_id'],
                                'total_events' => $task['total_events']
                            ]);
                        }
                    }
                } else {
                    // For update tasks, get total from existing events (excluding those added via settings)
                    $event_ids = $repository->get_series_events($task['data']['series_id']);
                    $series_id = $task['data']['series_id'];
                    
                    // Filter out events added via settings and template event
                    $updatable_events = array();
                    foreach ($event_ids as $event_id) {
                        if ($event_id === $task['data']['template_id']) {
                            continue; // Skip template event
                        }
                        
                        // Skip events added via settings
                        $added_via_setting = get_post_meta($event_id, 'evge_series_added_via_setting', true);
                        if ($added_via_setting && (int)$added_via_setting === (int)$series_id) {
                            continue;
                        }
                        
                        $updatable_events[] = $event_id;
                    }
                    
                    $task['total_events'] = count($updatable_events);

                    // Log update task initialization
                    if ($this->is_debug_mode_enabled()) {
                        \WPEventGenius\Common\Utils\Logger\RecurrenceLogger::log('Update task initialized', [
                            'task_id' => $task['id'],
                            'series_id' => $task['data']['series_id'],
                            'total_events' => $task['total_events']
                        ]);
                    }
                }
            }

            // Process next batch
            if ($task['data']['type'] === self::TASK_TYPE_CREATE) {
                // Create new events
                $batch_processed = $scheduler->create_batch_of_events(
                    $task,
                    $task['processed_events'],
                    self::BATCH_SIZE
                );
            } else {
                // Update existing events
                $batch_processed = $scheduler->update_batch_of_events(
                    $task,
                    $task['processed_events'],
                    self::BATCH_SIZE
                );
            }

            $task['processed_events'] += $batch_processed;

            // Log batch processing results
            if ($this->is_debug_mode_enabled()) {
                \WPEventGenius\Common\Utils\Logger\RecurrenceLogger::log('Batch processed', [
                    'task_id' => $task['id'],
                    'type' => $task['data']['type'],
                    'batch_processed' => $batch_processed,
                    'total_processed' => $task['processed_events'],
                    'total_events' => $task['total_events']
                ]);
            }

            if ($task['processed_events'] >= $task['total_events']) {
                $task['status'] = 'completed';
                
                // Log task completion
                if ($this->is_debug_mode_enabled()) {
                    \WPEventGenius\Common\Utils\Logger\RecurrenceLogger::log('Task completed', [
                        'task_id' => $task['id'],
                        'type' => $task['data']['type'],
                        'total_events' => $task['total_events']
                    ]);
                }
            }

        } catch (\Exception $e) {
            $task['status'] = 'failed';
            $task['error'] = $e->getMessage();
            
            // Log task failure
            if ($this->is_debug_mode_enabled()) {
                \WPEventGenius\Common\Utils\Logger\RecurrenceLogger::log('Task failed', [
                    'task_id' => $task['id'],
                    'type' => $task['data']['type'],
                    'error' => $e->getMessage(),
                    'processed_events' => $task['processed_events'],
                    'total_events' => $task['total_events']
                ]);
            }
        }
    }

    public function get_queue() {
        return get_option(self::QUEUE_OPTION_KEY, array());
    }

    protected function save_queue($queue) {
        // Remove completed and failed tasks immediately
        $queue = array_filter($queue, function($task) {
            return !in_array($task['status'], ['completed', 'failed']);
        });

        // Reindex array
        $queue = array_values($queue);
        
        // Delete option if queue is empty, otherwise update
        if (empty($queue)) {
            delete_option(self::QUEUE_OPTION_KEY);
        } else {
            update_option(self::QUEUE_OPTION_KEY, $queue);
        }
    }

    protected function is_locked() {
        return get_transient('evge_series_queue_lock');
    }

    protected function set_lock() {
        set_transient('evge_series_queue_lock', true, self::LOCK_TIMEOUT);
    }

    protected function release_lock() {
        delete_transient('evge_series_queue_lock');
    }

    /**
     * Check if recurrence debug mode is enabled
     * 
     * @return bool True if debug mode is enabled
     */
    public function is_debug_mode_enabled() {
        return (bool) get_transient('evge_recurrence_debug_mode');
    }

    public function enqueue_update($data) {
        $data['type'] = self::TASK_TYPE_UPDATE;
        return $this->enqueue($data);
    }

    /**
     * Clear all items from the queue
     */
    public function clear_queue() {
        delete_option(self::QUEUE_OPTION_KEY);
    }
} 