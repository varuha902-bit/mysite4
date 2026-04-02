<?php

namespace WPEventGenius\Common\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

/**
 * Centralized Modal Service
 * 
 * Manages modal inclusion to ensure it's only added once and only when needed.
 * This service tracks modal state and provides a single point of control for
 * modal inclusion across the plugin.
 */
class ModalService {
    
    /**
     * Track if modal has been added to the page
     * @var bool
     */
    private $modal_added = false;
    
    /**
     * Track if modal is needed (has triggers on the page)
     * @var bool
     */
    private $modal_needed = false;
    
    /**
     * Track if we've scanned the page for triggers
     * @var bool
     */
    private $page_scanned = false;
    
    /**
     * Constructor
     */
    public function __construct() {}
    
    /**
     * Initialize the service hooks
     */
    public function init_hooks() {
        add_action( 'admin_footer', array( $this, 'maybe_add_modal' ), 999 );
        add_action( 'wp_footer', array( $this, 'maybe_add_modal' ), 999 );
    }
    
    /**
     * Request modal inclusion from another service
     * 
     * Other services can call this to indicate they need the modal.
     * This is useful when a service knows it will add modal triggers.
     * 
     * @return void
     */
    public function request_modal() {
        $this->modal_needed = true;
    }
    
    /**
     * Check if modal has been added
     * 
     * @return bool
     */
    public function is_modal_added() {
        return $this->modal_added;
    }
    
    /**
     * Check if modal is needed on the current page
     * 
     * @return bool
     */
    public function is_modal_needed() {
        // If already requested, return true
        if ( $this->modal_needed ) {
            return true;
        }
        
        // Scan the page for modal triggers if we haven't already
        if ( ! $this->page_scanned ) {
            $this->scan_page_for_triggers();
        }
        
        return $this->modal_needed;
    }
    
    /**
     * Scan the page content for modal triggers
     * 
     * Uses WordPress filters to check if modal triggers exist in the content.
     * This is a lightweight check that doesn't require full page parsing.
     * 
     * @return void
     */
    private function scan_page_for_triggers() {
        $this->page_scanned = true;
        
        // Check if we're in admin (admin has different modal handling)
        if ( is_admin() ) {
            // Admin modals are handled separately
            return;
        }
        
        // Check if any service has explicitly requested the modal via filter
        $modal_requested = apply_filters( 'evge_modal_needed', false );
        if ( $modal_requested ) {
            $this->modal_needed = true;
            return;
        }
        
        // For front-end, we'll rely on services explicitly requesting the modal
        // This is more reliable than trying to parse content
        // Services should call request_modal() when they know they need it
    }
    
    /**
     * Add modal template to footer if needed
     * 
     * This is the main method that ensures the modal is only added once
     * and only when it's actually needed on the page.
     * 
     * @return void
     */
    public function maybe_add_modal() {
        // Don't add if already added
        if ( $this->modal_added ) {
            return;
        }
        
        // Check if modal is needed
        if ( ! $this->is_modal_needed() ) {
            return;
        }
        
        // Include the modal template
        $modal_path = trailingslashit( EVGE_PLUGIN_PATH ) . 'templates/event-genius/common/modal.php';
        if ( file_exists( $modal_path ) ) {
            include_once $modal_path;
            $this->modal_added = true;
            
            // Fire action to let other code know modal was added
            do_action( 'evge_modal_added' );
        }
    }
}

