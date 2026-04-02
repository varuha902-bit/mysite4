<?php

namespace WPEventGenius\Common\Services;

use WPEventGenius\Common\Email\BaseEmail;
use WPEventGenius\Common\Email\ConfirmationEmail;
use WPEventGenius\Common\Email\NotificationEmail;
use WPEventGenius\Common\Email\ActionEmail;
use WPEventGenius\Common\Utils\Placeholders;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

/**
 * Factory class for creating email objects
 * Handles creation of free vs pro versions of email objects
 */
class EmailObjectFactory {

	/**
	 * Check if pro version is active
	 * 
	 * @return bool
	 */
	private function is_pro() {
		return ! evge_is_free_version();
	}

	/**
	 * Create a ConfirmationEmail object
	 * 
	 * @param Placeholders $placeholders
	 * @return BaseEmail
	 */
	public function create_confirmation_email( Placeholders $placeholders ) {
		if ( $this->is_pro() && class_exists( 'WPEventGenius\Pro\Email\ProConfirmationEmail' ) ) {
			return new \WPEventGenius\Pro\Email\ProConfirmationEmail( $placeholders );
		}
		return new ConfirmationEmail( $placeholders );
	}

	/**
	 * Create a NotificationEmail object
	 * 
	 * @param Placeholders $placeholders
	 * @return BaseEmail
	 */
	public function create_notification_email( Placeholders $placeholders ) {
		if ( $this->is_pro() && class_exists( 'WPEventGenius\Pro\Email\ProNotificationEmail' ) ) {
			return new \WPEventGenius\Pro\Email\ProNotificationEmail( $placeholders );
		}
		return new NotificationEmail( $placeholders );
	}

	/**
	 * Create an ActionEmail object
	 * 
	 * @param Placeholders $placeholders
	 * @return BaseEmail
	 */
	public function create_action_email( Placeholders $placeholders ) {
		if ( $this->is_pro() && class_exists( 'WPEventGenius\Pro\Email\ProActionEmail' ) ) {
			return new \WPEventGenius\Pro\Email\ProActionEmail( $placeholders );
		}
		return new ActionEmail( $placeholders );
	}

	/**
	 * Create an AbandonedPaymentFollowupEmail object (Pro only)
	 * 
	 * @param Placeholders $placeholders
	 * @return BaseEmail|null
	 */
	public function create_abandoned_payment_followup_email( Placeholders $placeholders ) {
		if ( $this->is_pro() && class_exists( 'WPEventGenius\Pro\Email\AbandonedPaymentFollowupEmail' ) ) {
			return new \WPEventGenius\Pro\Email\AbandonedPaymentFollowupEmail( $placeholders );
		}
		return null;
	}

	/**
	 * Create a ReceiptEmail object (Pro only)
	 * 
	 * @param Placeholders $placeholders
	 * @return BaseEmail|null
	 */
	public function create_receipt_email( Placeholders $placeholders ) {
		if ( $this->is_pro() && class_exists( 'WPEventGenius\Pro\Email\ReceiptEmail' ) ) {
			return new \WPEventGenius\Pro\Email\ReceiptEmail( $placeholders );
		}
		return null;
	}

	/**
	 * Create a CustomEmail object (Pro only)
	 * 
	 * @param Placeholders $placeholders
	 * @return BaseEmail|null
	 */
	public function create_custom_email( Placeholders $placeholders ) {
		if ( $this->is_pro() && class_exists( 'WPEventGenius\Pro\Email\CustomEmail' ) ) {
			return new \WPEventGenius\Pro\Email\CustomEmail( $placeholders );
		}
		return null;
	}

	/**
	 * Create an OfflinePaymentInstructionEmail object (Pro only)
	 * 
	 * @param Placeholders $placeholders
	 * @return BaseEmail|null
	 */
	public function create_offline_payment_instruction_email( Placeholders $placeholders ) {
		if ( $this->is_pro() && class_exists( 'WPEventGenius\Pro\Email\OfflinePaymentInstructionEmail' ) ) {
			return new \WPEventGenius\Pro\Email\OfflinePaymentInstructionEmail( $placeholders );
		}
		return null;
	}

	/**
	 * Create an OfflinePaymentPendingNotificationEmail object (Pro only)
	 * 
	 * @param Placeholders $placeholders
	 * @return BaseEmail|null
	 */
	public function create_offline_payment_pending_notification_email( Placeholders $placeholders ) {
		if ( $this->is_pro() && class_exists( 'WPEventGenius\Pro\Email\OfflinePaymentPendingNotificationEmail' ) ) {
			return new \WPEventGenius\Pro\Email\OfflinePaymentPendingNotificationEmail( $placeholders );
		}
		return null;
	}

	/**
	 * Create an email object based on type
	 * 
	 * @param string $type The type of email ('confirmation', 'notification', 'action', 'receipt', 'custom', etc.)
	 * @param Placeholders $placeholders
	 * @return BaseEmail|null The appropriate email instance or null if type not supported
	 */
	public function create_email( $type, Placeholders $placeholders ) {
		$type = strtolower( $type );
		
		switch ( $type ) {
			case 'confirmation':
				return $this->create_confirmation_email( $placeholders );
				
			case 'notification':
				return $this->create_notification_email( $placeholders );
				
			case 'action':
				return $this->create_action_email( $placeholders );
				
			case 'receipt':
				return $this->create_receipt_email( $placeholders );
				
			case 'custom':
				return $this->create_custom_email( $placeholders );
				
			case 'offline_payment_instruction':
				return $this->create_offline_payment_instruction_email( $placeholders );
				
			case 'offline_payment_pending_notification':
				return $this->create_offline_payment_pending_notification_email( $placeholders );
				
			case 'abandoned_payment_followup':
				return $this->create_abandoned_payment_followup_email( $placeholders );
				
			default:
				// Allow for custom email types in pro version
				if ( $this->is_pro() ) {
					$pro_class_name = 'WPEventGenius\Pro\Email\Pro' . ucfirst( $type ) . 'Email';
					if ( class_exists( $pro_class_name ) ) {
						return new $pro_class_name( $placeholders );
					}
				}
				
				// Fallback to action email for unknown types
				return $this->create_action_email( $placeholders );
		}
	}
}
