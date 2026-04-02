<?php
namespace WPEventGenius\Admin\Actions\Export;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}
class Exportable {

	protected $columns;

	protected $rows;

	protected $header;

	protected $footer;

	public function __construct() {

	}

	public function set_columns( $columns ) {
		$this->columns = $columns;
	}

	public function get_columns() {
		return $this->columns;
	}

	public function set_rows( $rows ) {
		$this->rows = $rows;
	}

	public function get_rows() {
		return $this->rows;
	}

	public function set_header( $header ) {
		$this->header = $header;
	}

	public function get_header() {
		return $this->header;
	}

	public function set_footer( $footer ) {
		$this->footer = $footer;
	}

	public function get_footer() {
		return $this->footer;
	}

}