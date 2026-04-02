<?php
namespace WPEventGenius\Common\Email;

use WPEventGenius\Common\Utils\Placeholders;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

interface Email {
	public function __construct( Placeholders $placeholders );

	public function set_recipient( $email );

	public function set_from_address( $email );

	public function set_from_name( $name );

	public function set_subject( $text );

	public function set_content( $content );

	public function set_message_header( $args );

	public function set_message_footer( $args );

	public function set_reply_to( $email );

	public function send();
}