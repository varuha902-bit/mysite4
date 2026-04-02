<?php

namespace WPEventGenius\Common\Registration\Communicator;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class CommunicatorAfterError extends Communicator {

	public function get_response_html( $data = array() ) {
		$errors = $data['main'] ?? array();
		$html = '<div class="evge-error-message">';
		
		// Check for honeypot error first
		if (isset($errors['evge_user_comments'])) {
			$html .= '<p>' . esc_html__('Our spam prevention was triggered. Did you use a form autofiller? Please try completing the form again.', 'event-genius') . '</p>';
		} else {
			$html .= '<p>' . esc_html__('There were some errors with your submission. Please check the value of these fields and try again.', 'event-genius') . '</p>';
			
			// Add specific field error messages
			$html .= '<ul class="evge-field-errors" style="text-align: left;">';
			foreach ($errors as $field => $error_type) {
				if ($field === 'evge_user_comments') continue; // Skip honeypot as it's handled above
				
				$html .= sprintf(
					'<li>%s</li>',
					esc_html($error_type)
				);
			}
			$html .= '</ul>';
		}
		
		$html .= '</div>';
		return $html;
	}
}
