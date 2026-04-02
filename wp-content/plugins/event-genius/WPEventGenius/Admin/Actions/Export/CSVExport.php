<?php
namespace WPEventGenius\Admin\Actions\Export;
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

use WPEventGenius\Common\Event\EventPost;

class CSVExport implements Export {

	protected $exportable;

	public function __construct( Exportable $exportable ){
		$this->exportable = $exportable;
	}

	public function generate( $args = array() ) {
		// Initialize WP_Filesystem
		global $wp_filesystem;
		
		if ( empty( $wp_filesystem ) ) {
			require_once( ABSPATH . '/wp-admin/includes/file.php' );
			require_once( ABSPATH . '/wp-admin/includes/class-wp-filesystem-base.php' );
			require_once( ABSPATH . '/wp-admin/includes/class-wp-filesystem-direct.php' );
			
			// Initialize the WP filesystem
			if ( ! WP_Filesystem() ) {
				// If WP_Filesystem fails, fallback to direct method
				$wp_filesystem = new \WP_Filesystem_Direct( null );
			}
		}

		// Generate unique file name
		$file_name = 'export.csv';
		if ( ! empty( $args['event_id'] ) ) {
			$event_post = new EventPost( $args['event_id'] );
			$file_name = $this->file_name( $event_post, '', '.csv' );
		}

		// Create a temporary file in the WordPress uploads directory
		$upload_dir = wp_upload_dir();
		$temp_dir = $upload_dir['basedir'] . '/wp-event-genius-exports';
		
		// Make sure the export directory exists
		if ( ! $wp_filesystem->is_dir( $temp_dir ) ) {
			$wp_filesystem->mkdir( $temp_dir );
		}
		
		// Create a unique temp file path with proper security
		$temp_file = $temp_dir . '/' . wp_unique_filename( $temp_dir, $file_name );
		
		// Create CSV content in memory
		$csv_content = '';
		
		// Create array to store CSV rows
		$csv_rows = array();
		
		// Add custom header if present
		$header = $this->exportable->get_header();
		foreach ( $header as $header_item ) {
			$csv_rows[] = $this->format_csv_row($header_item);
		}
		
		// Add column headers
		$csv_rows[] = $this->format_csv_row($this->exportable->get_columns());
		
		// Add data rows
		foreach ( $this->exportable->get_rows() as $row ) {
			$csv_rows[] = $this->format_csv_row($row);
		}
		
		// Combine all rows into CSV content
		$csv_content = implode("\n", $csv_rows);
		
		// Save the CSV content to the temporary file
		$wp_filesystem->put_contents( $temp_file, $csv_content, FS_CHMOD_FILE );
		
		// Set headers for download
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . basename( $file_name ) );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		
		// Output the file content
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $wp_filesystem->get_contents( $temp_file );
		
		// Delete the temporary file
		$wp_filesystem->delete( $temp_file );
		
		exit;
	}

	public function file_name( EventPost $event_post, $prefix = '', $suffix = '' ) {

		$file_name = str_replace( array( ' ', ',' ), '-', substr( $event_post->get_the_title(), 0, 10 ) ) . '_' . str_replace( ' ', '-', substr( $event_post->get_the_venue_title(), 0, 10 ) ) . '_' . date_i18n( 'm.d', strtotime( $event_post->get_the_date_summary() ) );
		return $prefix . $file_name . $suffix;
	}

	/**
	 * Format an array as a CSV row
	 *
	 * @param array $fields Array of fields to format as CSV.
	 * @return string Formatted CSV row
	 */
	protected function format_csv_row($fields) {
		$escaped_fields = array();
		foreach ($fields as $field) {
			// OPTIMIZATION: Ensure field is a string before processing
			$field = (string) $field;
			// Escape quotes by doubling them
			$field = str_replace('"', '""', $field);
			// Wrap in quotes if the field contains comma, newline or quotes
			if (preg_match('/[,"\r\n]/', $field)) {
				$field = '"' . $field . '"';
			}
			$escaped_fields[] = $field;
		}
		return implode(',', $escaped_fields);
	}
}