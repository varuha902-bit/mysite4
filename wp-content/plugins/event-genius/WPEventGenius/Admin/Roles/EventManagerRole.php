<?php
namespace WPEventGenius\Admin\Roles;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class EventManagerRole {
    /**
     * The role name for event managers
     */
    const ROLE_NAME = 'evge_event_manager';

    /**
     * Initialize the event manager role
     */
    public function init() {
        // No hooks needed here as this is now a pure service class
    }

    /**
     * Get all capabilities for the event manager role
     *
     * @return array Array of capabilities
     */
    public function get_capabilities() {
        return array(
            // Event capabilities
            'edit_evge_events' => true,
            'read_evge_events' => true,
            'delete_evge_events' => true,
            'edit_others_evge_events' => true,
            'publish_evge_events' => true,
            'read_private_evge_events' => true,
            'delete_others_evge_events' => true,
            'delete_private_evge_events' => true,
            'delete_published_evge_events' => true,
            'delete_pending_evge_events' => true,
            'edit_others_evge_events' => true,
            'edit_private_evge_events' => true,
            'edit_published_evge_events' => true,
            'edit_pending_evge_events' => true,

            // Venue capabilities
            'edit_evge_venues' => true,
            'read_evge_venues' => true,
            'delete_evge_venues' => true,
            'edit_others_evge_venues' => true,
            'publish_evge_venues' => true,
            'read_private_evge_venues' => true,
            'delete_others_evge_venues' => true,
            'delete_private_evge_venues' => true,
            'delete_published_evge_venues' => true,
            'delete_pending_evge_venues' => true,
            'edit_others_evge_venues' => true,
            'edit_private_evge_venues' => true,
            'edit_published_evge_venues' => true,
            'edit_pending_evge_venues' => true,

            // Organizer capabilities
            'edit_evge_organizers' => true,
            'read_evge_organizers' => true,
            'delete_evge_organizers' => true,
            'edit_others_evge_organizers' => true,
            'publish_evge_organizers' => true,
            'read_private_evge_organizers' => true,
            'delete_others_evge_organizers' => true,
            'delete_private_evge_organizers' => true,
            'delete_published_evge_organizers' => true,
            'delete_pending_evge_organizers' => true,
            'edit_others_evge_organizers' => true,
            'edit_private_evge_organizers' => true,
            'edit_published_evge_organizers' => true,
            'edit_pending_evge_organizers' => true,

            // Series capabilities
            'edit_evge_series' => true,
            'read_evge_series' => true,
            'delete_evge_series' => true,
            'edit_others_evge_series' => true,
            'publish_evge_series' => true,
            'read_private_evge_series' => true,
            'delete_others_evge_series' => true,
            'delete_private_evge_series' => true,
            'delete_published_evge_series' => true,
            'delete_pending_evge_series' => true,
            'edit_others_evge_series' => true,
            'edit_private_evge_series' => true,
            'edit_published_evge_series' => true,
            'edit_pending_evge_series' => true,

            // Registration capabilities
            'view_evge_registrations' => true,
            'manage_evge_registrations' => true,

            // Event Category and Tag capabilities
            'manage_evge_categories' => true,
            'manage_evge_tags' => true,
        );
    }

    /**
     * Create the event manager role and assign capabilities
     */
    public function create_role() {
        $manager_capabilities = $this->get_capabilities();

        $generic_capabilities = array(
            'read' => true,
            'upload_files' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'manage_options' => false,
        );

        // Add the role if it doesn't exist
        if (!get_role(self::ROLE_NAME)) {
            add_role(self::ROLE_NAME, __('Event Manager', 'event-genius'), array_merge($generic_capabilities, $manager_capabilities));
        }

        // Add capabilities to administrator role
        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach ($manager_capabilities as $cap => $grant) {
                $admin_role->add_cap($cap);
            }
        }

        // Add capabilities to editor role
        $editor_role = get_role('editor');
        if ($editor_role) {
            $editor_capabilities = array(
                'edit_evge_events' => true,
                'read_evge_events' => true,
                'delete_evge_events' => true,
                'edit_others_evge_events' => true,
                'publish_evge_events' => true,
                'read_private_evge_events' => true,
                'delete_others_evge_events' => true,
                'delete_private_evge_events' => true,
                'delete_published_evge_events' => true,
                'delete_pending_evge_events' => true,
                'edit_others_evge_events' => true,
                'edit_private_evge_events' => true,
                'edit_published_evge_events' => true,
                'edit_pending_evge_events' => true,
                'edit_evge_venues' => true,
                'read_evge_venues' => true,
                'delete_evge_venues' => true,
                'edit_others_evge_venues' => true,
                'publish_evge_venues' => true,
                'read_private_evge_venues' => true,
                'delete_others_evge_venues' => true,
                'delete_private_evge_venues' => true,
                'delete_published_evge_venues' => true,
                'delete_pending_evge_venues' => true,
                'edit_others_evge_venues' => true,
                'edit_private_evge_venues' => true,
                'edit_published_evge_venues' => true,
                'edit_pending_evge_venues' => true,
                'edit_evge_organizers' => true,
                'read_evge_organizers' => true,
                'delete_evge_organizers' => true,
                'edit_others_evge_organizers' => true,
                'publish_evge_organizers' => true,
                'read_private_evge_organizers' => true,
                'delete_others_evge_organizers' => true,
                'delete_private_evge_organizers' => true,
                'delete_published_evge_organizers' => true,
                'delete_pending_evge_organizers' => true,
                'edit_others_evge_organizers' => true,
                'edit_private_evge_organizers' => true,
                'edit_published_evge_organizers' => true,
                'edit_pending_evge_organizers' => true,
                'view_evge_registrations' => true,
                'export_evge_registrations' => true,
                'manage_evge_registrations' => true,
            );
            foreach ($editor_capabilities as $cap => $grant) {
                $editor_role->add_cap($cap);
            }
        }

        // Add capabilities to author role
        $author_role = get_role('author');
        if ($author_role) {
            $author_capabilities = array(
                'edit_evge_events' => true,
                'read_evge_events' => true,
                'delete_evge_events' => true,
                'publish_evge_events' => true,
                'delete_published_evge_events' => true,
                'edit_published_evge_events' => true,
                'edit_evge_venues' => true,
                'read_evge_venues' => true,
                'delete_evge_venues' => true,
                'publish_evge_venues' => true,
                'delete_published_evge_venues' => true,
                'edit_published_evge_venues' => true,
                'edit_evge_organizers' => true,
                'read_evge_organizers' => true,
                'delete_evge_organizers' => true,
                'publish_evge_organizers' => true,
                'delete_published_evge_organizers' => true,
                'edit_published_evge_organizers' => true,
                'view_evge_registrations' => true,
            );
            foreach ($author_capabilities as $cap => $grant) {
                $author_role->add_cap($cap);
            }
        }

        // Add capabilities to contributor role
        $contributor_role = get_role('contributor');
        if ($contributor_role) {
            $contributor_capabilities = array(
                'edit_evge_events' => true,
                'read_evge_events' => true,
                'delete_evge_events' => true,
                'edit_evge_venues' => true,
                'read_evge_venues' => true,
                'delete_evge_venues' => true,
                'edit_evge_organizers' => true,
                'read_evge_organizers' => true,
                'delete_evge_organizers' => true,
                'view_evge_registrations' => true,
            );
            foreach ($contributor_capabilities as $cap => $grant) {
                $contributor_role->add_cap($cap);
            }
        }
    }

    /**
     * Assign the event manager role to a user
     *
     * @param int $user_id The user ID to assign the role to
     * @return bool Whether the role was successfully assigned
     */
    public function assign_role($user_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }

        $user->add_role(self::ROLE_NAME);
        return true;
    }

    /**
     * Remove the event manager role from a user
     *
     * @param int $user_id The user ID to remove the role from
     * @return bool Whether the role was successfully removed
     */
    public function remove_role($user_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }

        $user->remove_role(self::ROLE_NAME);
        return true;
    }
} 