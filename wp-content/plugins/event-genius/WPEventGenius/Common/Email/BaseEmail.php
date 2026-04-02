<?php
namespace WPEventGenius\Common\Email;

use WPEventGenius\Common\Utils\Defaults;
use WPEventGenius\Common\Utils\Placeholders;
use WPEventGenius\Common\Utils\Settings;
use WPEventGenius\Common\Utils\Logger\DebugLogger;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class BaseEmail implements Email {

	protected $placeholders;

	protected $type;

	protected $recipients;

	protected $from_address;

	protected $from_name;

	protected $subject;

	protected $headers;

	protected $content;

	protected $message_header;

	protected $message_footer;

	protected $message_body;

	protected $button;

	protected $reply_to;

	protected $reply_to_name;

	public function __construct( Placeholders $placeholders ){
		$this->placeholders = $placeholders;
		$this->message_header = '';

		$this->message_footer = '';

		$this->message_body = '';
		$this->from_address = '';
		$this->button = '';

		$this->headers = '';

		$this->type = 'confirmation';
	}

	public function set_recipient( $email ){
		$this->recipients[] = $email;
	}

	public function set_type( $type ){
		$this->type = $this->type_filter( $type );
	}

	public function set_reply_to( $email, $name = '' ) {
		$this->reply_to = $email;
		$this->reply_to_name = $name;
	}

	public function generate_headers( $args ){
		$headers = array();

		$from_address = ! empty( $args['from_address'] ) && is_email( $args['from_address'] ) ? $args['from_address'] : $this->from_address;
		if ( empty( $from_address ) ) {
			$from_address = Defaults::get( 'email_from_address' );
		}
		$email_from = ! empty( $args['from_name'] ) ? $args['from_name'] . ' <' . sanitize_email($from_address) . '>' : Settings::get( $this->type . '_from_name' ) .' <' . sanitize_email($from_address) . '>';

		$headers[]    = 'From: ' . $email_from;
		$headers[] = 'Content-Type: text/html; charset=utf-8';
		
		if ( ! empty( $this->reply_to ) ) {
			$reply_to_header = !empty($this->reply_to_name) 
			// sanitize the reply_to_name
				? esc_html($this->reply_to_name) . ' <' . sanitize_email($this->reply_to) . '>'
				: sanitize_email($this->reply_to);
			$headers[] = 'Reply-To: ' . $reply_to_header;
		}
		$this->headers = $headers;
	}

	public function set_from_address( $email ){
		$this->from_address = $email;
	}

	public function set_from_name( $name ){
		$this->from_name = $name;
	}

	public function set_subject( $text ){
		$this->subject = $this->placeholders->replace( $text );
	}

	public function set_content( $content ){
		$this->content = $content;
	}

	public function set_message_header( $args ){
		ob_start();
		$custom_header_template = locate_template( 'event-genius/email/header.php', false, false );
		$header_template        = $custom_header_template ? $custom_header_template : EVGE_PLUGIN_PATH . 'templates/event-genius/email/header.php';
		include $header_template;
		$this->message_header = ob_get_contents();
		ob_end_clean();
	}

	public function set_message_body() {
		ob_start();
		$body_content = $this->placeholders->replace( $this->content['body'] );
		$custom_body_template = locate_template( 'event-genius/email/body.php', false, false );
		$body_template        = $custom_body_template ? $custom_body_template : EVGE_PLUGIN_PATH . 'templates/event-genius/email/body.php';
		include $body_template;
		$this->message_body = ob_get_contents();
		ob_end_clean();
	}

	public function set_message_cta_button() {
		ob_start();

		$custom_button_template = locate_template( 'event-genius/email/partials/cta-button.php', false, false );
		$button_template        = $custom_button_template ? $custom_button_template : EVGE_PLUGIN_PATH . 'templates/event-genius/email/partials/cta-button.php';
		include $button_template;
		$this->button = ob_get_contents();
		ob_end_clean();
	}

	public function set_message_footer( $args ){
		ob_start();
		$custom_footer_template = locate_template( 'event-genius/email/footer.php', false, false );
		$footer_template        = $custom_footer_template ? $custom_footer_template : EVGE_PLUGIN_PATH . 'templates/event-genius/email/footer.php';
		include $footer_template;
		$this->message_footer = ob_get_contents();
		ob_end_clean();

	}

	public function generate_email_message_html() {
		return $this->message_header . $this->message_body . $this->button . $this->message_footer;
	}

	public function send(){
		$sent = $this->send_email();
		if (! $sent) {
			$context = array(
				'email_type' => $this->type,
				'has_message_body' => !empty($this->message_body),
				'recipients' => $this->recipients,
				'subject' => $this->subject,
				'headers' => $this->headers,
			);
			
			DebugLogger::log(
				'Email failed to send',	
				$context
			);
		}
		
		return $sent;
	}

	protected function send_email() {
		// Check if we have attachments to include
		if ( ! empty( $this->attachments ) && is_array( $this->attachments ) ) {
			// Prepare attachments for wp_mail
			$wp_attachments = array();
			foreach ( $this->attachments as $attachment ) {
				if ( ! empty( $attachment['path'] ) && file_exists( $attachment['path'] ) ) {
					$wp_attachments[] = $attachment['path'];
				}
			}
			
			// Send email with attachments
			return wp_mail( $this->recipients, html_entity_decode( $this->subject, ENT_QUOTES, 'UTF-8' ), $this->generate_email_message_html(), $this->headers, $wp_attachments );
		}
		
		// Send email without attachments (default behavior)
		return wp_mail( $this->recipients, html_entity_decode( $this->subject, ENT_QUOTES, 'UTF-8' ), $this->generate_email_message_html(), $this->headers );
	}

	protected function type_filter( $type ) {
		$acceptable_types = array(
			'confirmation',
			'receipt',
			'notification',
			'offline',
			'action',
		);

		if ( in_array( $type, $acceptable_types, true ) ) {
			return $type;
		}

		return 'confirmation';
	}
}