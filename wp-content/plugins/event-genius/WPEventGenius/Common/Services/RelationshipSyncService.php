<?php
namespace WPEventGenius\Common\Services;

use WPEventGenius\Common\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class RelationshipSyncService {
    protected $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function init_hooks() {
        // Hook into post meta changes for venues
        add_action('added_post_meta', array($this, 'sync_venue_meta'), 10, 4);
        add_action('updated_post_meta', array($this, 'sync_venue_meta'), 10, 4);
        add_action('deleted_post_meta', array($this, 'handle_deleted_venue_meta'), 10, 4);

        // Hook into post meta changes for organizers
        add_action('added_post_meta', array($this, 'sync_organizer_meta'), 10, 4);
        add_action('updated_post_meta', array($this, 'sync_organizer_meta'), 10, 4);
        add_action('deleted_post_meta', array($this, 'handle_deleted_organizer_meta'), 10, 4);

        // Hook into post deletion to clean up relationships
        add_action('before_delete_post', array($this, 'cleanup_relationships'), 10, 1);
    }

    /**
     * Sync venue relationships when venue meta is added or updated
     */
    public function sync_venue_meta($meta_id, $post_id, $meta_key, $meta_value) {
        // Only process venue meta
        if ($meta_key !== 'evge_venue') {
            return;
        }

        // Get post type
        $post_type = get_post_type($post_id);
        if ($post_type !== EVGE_EVENT_POST_TYPE) {
            return;
        }

        // Sync the relationship
        $this->db->sync_event_venue_relationship($post_id, $meta_value);
    }

    /**
     * Handle deletion of venue meta
     */
    public function handle_deleted_venue_meta($meta_ids, $post_id, $meta_key, $meta_value) {
        // Only process venue meta
        if ($meta_key !== 'evge_venue') {
            return;
        }

        // Get post type
        $post_type = get_post_type($post_id);
        if ($post_type !== EVGE_EVENT_POST_TYPE) {
            return;
        }

        // Delete the relationship by passing null as venue_id
        $this->db->sync_event_venue_relationship($post_id, null);
    }

    /**
     * Sync organizer relationships when organizer meta is added or updated
     */
    public function sync_organizer_meta($meta_id, $post_id, $meta_key, $meta_value) {
        // Only process organizer meta
        if ($meta_key !== 'evge_organizer') {
            return;
        }

        // Get post type
        $post_type = get_post_type($post_id);
        if ($post_type !== EVGE_EVENT_POST_TYPE) {
            return;
        }

        // Get all organizer IDs for this event
        $organizer_ids = get_post_meta($post_id, 'evge_organizer');
        
        // Sync the relationships
        $this->db->sync_event_organizer_relationship($post_id, $organizer_ids);
    }

    /**
     * Handle deletion of organizer meta
     */
    public function handle_deleted_organizer_meta($meta_ids, $post_id, $meta_key, $meta_value) {
        // Only process organizer meta
        if ($meta_key !== 'evge_organizer') {
            return;
        }

        // Get post type
        $post_type = get_post_type($post_id);
        if ($post_type !== EVGE_EVENT_POST_TYPE) {
            return;
        }

        // Get remaining organizer IDs for this event
        $organizer_ids = get_post_meta($post_id, 'evge_organizer');
        
        // Sync the relationships (will delete all if $organizer_ids is empty)
        $this->db->sync_event_organizer_relationship($post_id, $organizer_ids);
    }

    /**
     * Clean up relationships when a post is deleted
     */
    public function cleanup_relationships($post_id) {
        $post_type = get_post_type($post_id);
        
        switch ($post_type) {
            case EVGE_EVENT_POST_TYPE:
                // Event is being deleted - remove all its relationships
                $this->db->sync_event_venue_relationship($post_id, null);
                $this->db->sync_event_organizer_relationship($post_id, array());
                break;
                
            case EVGE_VENUE_POST_TYPE:
                // Remove venue from all associated events' post meta
                $this->db->remove_venue_from_events_meta($post_id);
                break;
                
            case EVGE_ORGANIZER_POST_TYPE:
                // Remove organizer from all associated events' post meta
                $this->db->remove_organizer_from_events_meta($post_id);
                break;
        }
    }
} 