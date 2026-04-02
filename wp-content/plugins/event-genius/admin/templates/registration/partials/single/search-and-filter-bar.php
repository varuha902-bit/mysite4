<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\Common\Utils\Icon;

$params     = $page->get_sanitized_params();
$page_param = ! empty( $params['page'] ) ? $params['page'] : 'evge-all-events';

$registration_status = ! empty( $params['registration_status'] ) ? $params['registration_status'] : '';
$search_term = ! empty( $params['evge_single_search'] ) ? $params['evge_single_search'] : '';
if ( empty( $search_term ) ) {
	$search_term = ! empty( $_POST['evge_single_search'] ) ? sanitize_text_field( $_POST['evge_single_search'] ) : '';
}
?>
<div class="evge-toolbar wp-filter evge-single-toolbar">
    <div class="evge-toolbar-inner evge-no-view-select">
        <button id="evge-filter-go" type="submit" class="button evge-toolbar-button" style="display: none;"><?php esc_html_e( 'Go', 'event-genius' ); ?></button>
        <div class="evge-toolbar-secondary evge-flex-center evge-toolbar-section">
            <input type="hidden" name="page" value="<?php echo esc_attr( $page_param ); ?>">
	        <?php
	        $base_json_array = array(
		        'registration_id' => 0,
		        'event_id' => $event->ID,
		        'action' => 'evge_event_actions_modal_content',
	        );
	        $add_json_array = array_merge( $base_json_array, array( 'selected' => 'add' ) );
	        $delete_json_array = array_merge( $base_json_array, array( 'selected' => 'bulk_delete' ) );
	        ?>
            <div class="evge-actions-left">
                <button class="evge-action-button evge-admin-button button evge-admin-secondary-button evge-left-icon evge-modal-trigger" data-evge-modal-content="ajax" data-evge-ajax="<?php echo esc_attr( wp_json_encode( $add_json_array ) ); ?>"><span class="evge-icon-text">
                    <?php 
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo Icon::get( 'plus' ); 
                    ?>
                    <?php esc_html_e( 'Add New', 'event-genius' ); ?></span></button>
                <form method="post" id="evge_csv_export_form" action="">
                    <?php wp_nonce_field( 'evge_csv_export', 'evge_csv_export_nonce' ); ?>
                    <input type="hidden" name="evge_event_id" value="<?php echo absint( $event->ID ); ?>">
                    <input type="hidden" name="evge_subtab" value="<?php echo esc_attr( $subtab ); ?>">
                    <button type="submit" name="evge_action" value="export_csv" class="evge-action-button evge-admin-button button evge-admin-secondary-button evge-left-icon"><span class="evge-icon-text">
                        <?php 
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo Icon::get( 'export' ); ?><?php esc_html_e( 'Export (.csv)', 'event-genius' ); ?></span></button>
                </form>
                <?php do_action( 'evge_bulk_registration_management_actions', $event, $params ); ?>
                <?php 
                // Only show delete button when not on payments tab
                $current_subtab = ! empty( $_GET['subsubtab'] ) ? sanitize_key( wp_unslash( $_GET['subsubtab'] ) ) : 'submissions';
                if ( 'payments' !== $current_subtab ) : 
                ?>
                <button class="evge-action-button evge-admin-button button evge-admin-secondary-button evge-left-icon evge-modal-trigger evge-toggleable-button evge-disabled" data-evge-modal-content="ajax" data-evge-ajax="<?php echo esc_attr( wp_json_encode( $delete_json_array ) ); ?>" disabled><span class="evge-icon-text">
                    <?php 
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo Icon::get( 'trash' ); 
                    ?>
                    <?php esc_html_e( 'Delete Selected', 'event-genius' ); ?></span></button>
                <?php endif; ?>
                
                <button class="evge-more-actions-button evge-admin-button button evge-admin-secondary-button evge-left-icon" style="display: none;" title="<?php esc_attr_e( 'More actions', 'event-genius' ); ?>">
                    <span class="evge-icon-text">
                        <?php 
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo Icon::get( 'ellipsis' ); 
                        ?>
                    </span>
                </button>
            </div>
        </div>
        <div class="evge-toolbar-primary search-form evge-toolbar-section">
            <div class="evge-toolbar-icon">
                <svg width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g opacity="0.7">
                        <path d="M12.432 7.24711C12.432 10.404 9.87283 12.9631 6.71598 12.9631C3.55913 12.9631 1 10.404 1 7.24711C1 4.09026 3.55913 1.53113 6.71598 1.53113C9.87283 1.53113 12.432 4.09026 12.432 7.24711Z" stroke="black" stroke-width="2"/>
                        <line x1="10.2374" y1="11.7251" x2="14.7069" y2="16.1947" stroke="black" stroke-width="2"/>
                    </g>
                </svg>

            </div>
            <label for="evge-search-input" class="screen-reader-text"><?php esc_html_e( 'Search Registrations', 'event-genius' ); ?></label>
            <input type="search" name="evge_single_search" placeholder="<?php esc_html_e( 'Search', 'event-genius' ); ?>" id="evge-search-input" class="search" value="<?php echo esc_attr( $search_term ); ?>">
            <input name="stype" value="registrants" type="hidden">
        </div>

    </div>

</div>