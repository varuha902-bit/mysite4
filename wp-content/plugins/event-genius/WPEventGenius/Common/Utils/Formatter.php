<?php


namespace WPEventGenius\Common\Utils;

use WPEventGenius\Common\Database;
use WPEventGenius\Common\Utils\Icon;
use WPEventGenius\Common\Utils\DateFormatter;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class Formatter {

	public static function identity( $registration ) {
		$identity_array = array();
		if ( ! empty( $registration['first'] ) ) {
			$identity_array[] = $registration['first'];
		}
		if ( ! empty( $registration['last'] ) ) {
			$identity_array[] = $registration['last'];
		}
		if ( empty( $identity_array ) && ! empty( $registration['email'] ) ) {
			$identity_array[] = $registration['email'];
		}

		$identity_array = apply_filters( 'evge_registration_identity_array', $identity_array, $registration );

		return implode( ' ', $identity_array );
	}

	public static function single_registration_record_actions( $item, $subtab = 'submissions', $back_page = 'evge-registrations' ) {
		$actions = self::row_actions( $item, $subtab, $back_page );
		?>
		<div class="row-actions">
            <span class="manage"><?php echo wp_kses_post( implode( ' | ', $actions ) ); ?></span>
		</div>
	<?php
	}

    public static function row_actions( $item, $subtab = 'submissions', $back_page = 'evge-registrations' ) {
	    $base_json_array = array(
		    'registration_id' => $item['id'],
		    'transaction_id' => ! empty( $item['transaction_id'] ) ? $item['transaction_id'] : 0,
		    'subtab' => ! empty( $subtab ) ? $subtab : 'submissions',
		    'action' => 'evge_identifier_tools_modal_content',
	    );
	    $edit_json_array = array_merge( $base_json_array, array( 'selected' => 'edit' ) );
	    $email_json_array = array_merge( $base_json_array, array( 'selected' => 'email' ) );
	    $delete_json_array = array_merge( $base_json_array, array( 'selected' => 'delete' ) );

	    $edit_json_settings =  array( 'width' => 'max' );

	    $actions = array(
		    'manage' => '<a href="' . esc_url( add_query_arg( array( 'page' => 'evge-registrations', 'tab' => 'registrations', 'back_page' => $back_page, 'registration_id' => absint( $item['id'] ) ), admin_url( 'admin.php' ) ) ) . '" data-evge-modal-content="ajax" data-evge-modal-settings="' . esc_attr( wp_json_encode( $edit_json_settings ) ) . '" data-evge-ajax="' . esc_attr( wp_json_encode( $edit_json_array ) ). '" class="evge-modal-trigger">' . esc_html__('Manage', 'event-genius') . '</a>',
	    );

		$actions = apply_filters( 'evge_single_registration_record_actions', $actions, $item, $subtab, $back_page );

        return $actions;
    }

	public static function output_params( $registration, $key, $event_form, $context = 'table' ) {
		// If the value is an array, implode it early as we can only display strings
		if ( isset( $registration[ $key ] ) && is_array( $registration[ $key ] ) ) {
			$registration[ $key ] = implode( ', ', $registration[ $key ] );
		}
		
		// If the value is a boolean, convert it to a string
		if ( isset( $registration[ $key ] ) && is_bool( $registration[ $key ] ) && $registration[ $key ] === true ) {
			$registration[ $key ] = '✅ true';
		}

        $return = array(
			'before'=> '',
			'after' => '',
			'value' => isset ( $registration[ $key ] ) ? (string)$registration[ $key ] : '',
		);

		if ( $key === 'quantity_cost' ) {
			if ($return['value'] === '-') {
				$return['value'] = '';
				
				$return['before'] = self::tooltip( __( 'This guest is part of a registration group with a single payment.', 'event-genius' ) );
				$return['after'] = '';
			}
		} elseif ( empty( $return['value'] ) ) {
			return $return;
		} elseif ( $key === 'registration_date' || $key === 'payment_date' || $key === 'checked_in_at' ) {
            $timezone = get_option( 'timezone_string' );
			if ( $context === 'card' ) {
				$return['value'] = DateFormatter::date_string( 'registration_admin_card', $registration[ $key ], '', $timezone );
			} else {
				$return['value'] = DateFormatter::date_string( 'registration_admin', $registration[ $key ], '', $timezone );
			}
		} elseif ( $key === 'payment_gross' && isset( $registration[ 'currency_code' ] ) ) {
			$return['before'] = '<div class="evge-flex-center">';
			$return['after'] = '<span class="evge-currentcy-code">(' . esc_html( $registration[ 'currency_code' ] ) . ')</span></div>';

		} elseif ( $key === 'status' || $key === 'payment_status' ) {
			$return = self::filter_raw_status( $return );
		} elseif ( $key === 'attendance_status' ) {
			// Handle attendance status separately from registration status
			$status = isset( $registration[ $key ] ) ? $registration[ $key ] : 'unknown';
			$return['value'] = self::get_attendance_status_label( $status );
			// Use evge-status-{status} class to match payment/submission status styling
			$return['before'] = '<div class="evge-status-' . esc_attr( sanitize_key( $status ) ) . ' evge-registration-column-status">';
			$return['after'] = '</div>';
		} elseif ( $key === 'checked_in_by' ) {
			// Get the user display name if we have a user ID
			if ( ! empty( $registration[ $key ] ) ) {
				$user = get_user_by( 'ID', absint( $registration[ $key ] ) );
				if ( $user ) {
					$return['value'] = $user->display_name;
				} else {
					$return['value'] = __( 'Unknown', 'event-genius' );
				}
			}
		}

		return apply_filters( 'evge_data_output_params', $return, $registration, $key, $event_form );
	}

    public static function get_quantity_cost_display( $registration ) {
		// If we are using the free version, return quantity, then a space, then the cost
		$payment_gross = isset( $registration['payment_gross'] ) ? $registration['payment_gross'] : 0;
		if ( evge_is_free_tier() ) {
			return self::quantity_cost_format( $registration['quantity'], $payment_gross );
		}

		$parent_id = isset( $registration['parent'] ) ? intval( $registration['parent'] ) : 0;
		
		// If this is a guest registration (parent > 0), return a dash
		if ( $parent_id > 0 ) {
			return '-';
		}

		$quantity = isset( $registration['quantity'] ) ? intval( $registration['quantity'] ) : 1;

		// Fallback: return quantity, then a space, then the cost
		return self::quantity_cost_format( $quantity, $payment_gross );
    }

	public static function quantity_cost_format( $quantity, $cost ) {
		return sprintf('%1$s (%2$s)', $quantity, number_format_i18n((float)$cost, 2 ) );
	}



	public static function escaped_output( $registration, $key, $event_form, $context = 'table' ) {
		$output_params = self::output_params( $registration, $key, $event_form, $context );

		return $output_params['before'] . esc_html( wp_unslash( $output_params['value'] ) ) . $output_params['after'];
	}

	public static function filter_raw_status( $output_params ) {
		$status = $output_params['value'];
		
		// Get all payment statuses to check if this is a payment status
		$payment_statuses = self::get_all_payment_statuses();
		
		if ( array_key_exists( $status, $payment_statuses ) ) {
			// This is a payment status, use the payment status CSS class
			$output_params['before'] = '<div class="evge-status-' . sanitize_key( $status ) . ' evge-registration-column-status">';
			$output_params['after'] = '</div>';
		} else {
			// Handle non-payment statuses
			switch ( $status ) {
				case 'confirmed':
					$output_params['before'] = '<div class="evge-status-confirmed evge-registration-column-status">';
					$output_params['after'] = '</div>';
					break;
				case 'pending':
					$output_params['before'] = '<div class="evge-status-pending evge-registration-column-status">';
					$output_params['after'] = '</div>';
					break;
				case 'canceled':
					$output_params['before'] = '<div class="evge-status-canceled evge-registration-column-status">';
					$output_params['after'] = '</div>';
					break;
				default:
					$output_params['before'] = '<div class="evge-status-unknown evge-registration-column-status">';
					$output_params['after'] = '</div>';
			}
		}

		$output_params['value'] = self::raw_status_value( $status );

		return $output_params;
	}

	protected static function raw_status_value( $raw_value ) {
		// Get all payment statuses to check if this is a payment status
		$payment_statuses = self::get_all_payment_statuses();
		
		if ( array_key_exists( $raw_value, $payment_statuses ) ) {
			// This is a payment status, return the localized text
			return $payment_statuses[ $raw_value ];
		}
		
		// Handle non-payment statuses
		switch ( $raw_value ) {
			case 'confirmed':
				return __( 'Confirmed', 'event-genius' );
			case 'pending':
				return __( 'Pending', 'event-genius' );
			case 'canceled':
				return __( 'Canceled', 'event-genius' );
			default:
				return __( 'Unknown', 'event-genius' );
		}
	}

	/**
	 * Get attendance status label
	 *
	 * @param string $status The attendance status
	 * @return string Localized status label
	 */
	protected static function get_attendance_status_label( $status ) {
		switch ( $status ) {
			case 'attended':
				return __( 'Attended', 'event-genius' );
			case 'noshow':
				return __( 'No Show', 'event-genius' );
			case 'excused':
				return __( 'Excused', 'event-genius' );
			case 'unknown':
			default:
				return __( 'Unknown', 'event-genius' );
		}
	}

	public static function manage_event_link( $event_id ) {
		return add_query_arg( array( 'id' => $event_id, 'tab' => 'registrations', 'subtab' => 'single' ), admin_url( 'admin.php?page=evge-all-events' ) );
	}

	/**
	 * Get the appropriate icon for payment status
	 * 
	 * @param string $payment_status
	 * @return string HTML for the icon
	 */
	public static function get_payment_status_icon( $payment_status ) {
		switch ( $payment_status ) {
			case 'complete':
				return Icon::get( 'check-bold' );
			case 'pending':
				return Icon::get( 'exclmation-bold' );
			case 'processing':
				return Icon::get( 'exclmation-bold' );
			case 'offline':
				return Icon::get( 'check-bold' );
			case 'abandoned':
				return Icon::get( 'close-bold' );
			case 'error':
				return Icon::get( 'exclmation-bold' );
			case 'refunded':
				return Icon::get( 'check-bold' );
			default:
				return Icon::get( 'check-bold' );
		}
	}

	/**
	 * Get the human-readable text for payment status
	 * 
	 * @param string $payment_status
	 * @return string Human-readable status text
	 */
	public static function get_payment_status_text( $payment_status ) {
		switch ( $payment_status ) {
			case 'complete':
				return __( 'Complete', 'event-genius' );
			case 'pending':
				return __( 'Pending', 'event-genius' );
			case 'processing':
				return __( 'Processing', 'event-genius' );
			case 'offline':
				return __( 'Offline Pending', 'event-genius' );
			case 'abandoned':
				return __( 'Abandoned', 'event-genius' );
			case 'error':
				return __( 'Error', 'event-genius' );
			case 'refunded':
				return __( 'Refunded', 'event-genius' );
			default:
				return ucfirst( $payment_status );
		}
	}

	/**
	 * Get a complete payment status display with icon and tooltip
	 * 
	 * @param string $payment_status
	 * @param string $status_class Optional custom CSS class (defaults to evge-status-{status})
	 * @param bool $include_tooltip Whether to show detailed explanation or just status (defaults to true)
	 * @return string HTML for the complete payment status display
	 */
	public static function get_payment_status_display( $payment_status, $status_class = '', $include_tooltip = true ) {
		if ( evge_is_free_tier() ) {
			return '';
		}
		if ( empty( $status_class ) ) {
			$status_class = 'evge-status-' . sanitize_key( $payment_status );
		}
		
		$icon = self::get_payment_status_icon( $payment_status );
		
		$output = '<div class="evge-tooltip-wrap">';
		$output .= '<span class="evge-icon-circle ' . esc_attr( $status_class ) . '">' . $icon . '</span>';
		
		if ( $include_tooltip ) {
			$output .= '<div class="evge-tooltip evge-shadow">';
			$output .= '<p>' . esc_html( self::get_payment_status_explanation( $payment_status ) ) . '</p>';
			$status_text = self::get_payment_status_text( $payment_status );
			$output .= '</div>';
		}
		

		$output .= '</div>';
		
		return $output;
	}

	/**
	 * Get all possible payment statuses as an associative array
	 * 
	 * @return array Array of payment statuses with key => display_text
	 */
	public static function get_all_payment_statuses() {
		return array(
			'complete'   => __( 'Complete', 'event-genius' ),
			'pending'    => __( 'Pending', 'event-genius' ),
			'processing' => __( 'Processing', 'event-genius' ),
			'offline'    => __( 'Offline Pending', 'event-genius' ),
			'abandoned'  => __( 'Abandoned', 'event-genius' ),
			'error'      => __( 'Error', 'event-genius' ),
			'refunded'   => __( 'Refunded', 'event-genius' ),
		);
	}

	/**
	 * Get active payment statuses (those that are not abandoned, error, or refunded)
	 * 
	 * @return array Array of active payment statuses
	 */
	public static function get_active_payment_statuses() {
		return array( 'complete', 'pending', 'processing', 'offline' );
	}

	/**
	 * Get inactive payment statuses (those that are abandoned, error, or refunded)
	 * 
	 * @return array Array of inactive payment statuses
	 */
	public static function get_inactive_payment_statuses() {
		return array( 'abandoned', 'error', 'refunded' );
	}

	/**
	 * Get a detailed explanation of what a payment status means
	 * 
	 * @param string $payment_status
	 * @return string Detailed explanation of the payment status
	 */
	public static function get_payment_status_explanation( $payment_status ) {
		$status_label = self::get_payment_status_text( $payment_status );
		
		switch ( $payment_status ) {
			case 'complete':
				return sprintf( __( '%s: Payment was successfully processed.', 'event-genius' ), $status_label );
			case 'pending':
				return sprintf( __( '%s: The person submitted a registration form but did not complete the payment step.', 'event-genius' ), $status_label );
			case 'processing':
				return sprintf( __( '%s: Payment is being actively processed by the payment processor. If the payment record shows a different status in your payment gateway dashboard, there may be an issue with payment notifications from the gateway.', 'event-genius' ), $status_label );
			case 'offline':
				return sprintf( __( '%s: The registrant chose the offline payment option when registering. You will need to manually confirm receipt of payment and update the status.', 'event-genius' ), $status_label );
			case 'abandoned':
				return sprintf( __( '%s: A payment was "pending" and never completed. The registrant started the payment process but abandoned it before completion.', 'event-genius' ), $status_label );
			case 'error':
				return sprintf( __( "%s: An error occurred. Please check the payment gateway's record of the transaction for more details about what went wrong.", 'event-genius' ), $status_label );
			case 'refunded':
				return sprintf( __( '%s: Payment was successfully refunded.', 'event-genius' ), $status_label );
			default:
				return sprintf( __( '%s: Unknown payment status. Please check the payment gateway records for more information.', 'event-genius' ), $status_label );
		}
	}

	public static function tooltip( $text = '', $position = 'left' ) {
		$tooltip_class = 'evge-tooltip evge-shadow';
		if ( $position === 'right' ) {
			$tooltip_class .= ' evge-tooltip-right';
		}
		
		$tooltip = '<div class="evge-tooltip-wrap">';
		$tooltip .= '<a href="javascript:void(0);" class="evge-tooltip-link">';
		$tooltip .= Icon::get( 'tooltip' );
		$tooltip .= '</a>';
		$tooltip .= '<div class="' . $tooltip_class . '">';
		$tooltip .= '<p>' . esc_html( $text ). '</p>';
		$tooltip .= '</div>';
		$tooltip .= '</div>';

		return $tooltip;
	}
}