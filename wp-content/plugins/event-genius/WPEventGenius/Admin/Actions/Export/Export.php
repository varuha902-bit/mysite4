<?php
namespace WPEventGenius\Admin\Actions\Export;
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

interface Export {

	public function __construct( Exportable $exportable );

	public function generate( $args = array() );
}