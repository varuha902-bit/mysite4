<?php
namespace WPEventGenius\Common\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class Text {
	public static function maybe_shorten_text( $string, $settings, $include_more = false ) {
		$limit = is_array( $settings ) ? $settings['textlength'] : $settings;

		if ( mb_strlen( $string, 'UTF-8' ) <= $limit ) {
			return $string;
		}

		$parts       = preg_split( '/([\s\n\r]+)/', $string, -1, PREG_SPLIT_DELIM_CAPTURE );
		$parts_count = count( $parts );

		$length      = 0;
		$last_part   = 0;
		$first_parts = array();
		$end_parts   = array();
		for ( ; $last_part < $parts_count; $last_part++ ) {
			$length += mb_strlen( $parts[ $last_part ], 'UTF-8' );
			if ( $length < $limit ) {
				$first_parts[] = $parts[ $last_part ];
			} else {
				$end_parts[] = $parts[ $last_part ];
			}
		}
		$return = implode( ' ', $first_parts );

		if ( $include_more ) {
			$return .= '<span class="evge-hidden" aria-hidden="true">' . implode( ' ', $end_parts ) . '</span><a href="#" class="evge-more" aria-label="' . esc_attr__( 'Show more text', 'event-genius' ) . '">...</a>';
		} else {
			$return .= '<span class="evge-more">...</span>';
		}

		return $return;
	}
}