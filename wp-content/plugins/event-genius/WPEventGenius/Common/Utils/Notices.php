<?php

namespace WPEventGenius\Common\Utils;

use WPEventGenius\Common\Utils\Icon;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Notices {

	public static function exclamation( $message, $classes = '' ) {
		?>
            <div class="evge-flex-center">
                <div class="evge-notice-base evge-exclamation-notice<?php echo esc_attr( $classes ); ?>">
                    <div class="evge-notice-icon">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="8" cy="8" r="8"/>
                            <circle cx="8" cy="4" r="1" fill="white"/>
                            <rect x="7" y="7" width="2" height="6" fill="white"/>
                        </svg>
                    </div>
                    <p><?php echo wp_kses_post( $message ); ?></p>
                </div>
            </div>

		<?php
	}

	public static function documentation( $message, $classes = '', $link = '' ) {
		?>
            <div class="evge-flex-center">
                <div class="evge-notice-base evge-documentation-notice<?php echo esc_attr( $classes ); ?>">
                    <div class="evge-notice-icon">
                        <?php 
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo Icon::get( 'doc' ); ?>
                    </div>
                    <?php if ( ! empty( $link ) ) : ?>
                        <a href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener noreferrer" class="evge-icon-text">
                            <p><?php echo wp_kses_post( $message ); ?></p>
                            <?php 
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo Icon::get( 'right-chevron' ); ?>
                        </a>
                    <?php else : ?>
                        <p><?php echo wp_kses_post( $message ); ?></p>
                    <?php endif; ?>
                </div>
            </div>

		<?php
	}

    public static function link( $message, $classes = '', $link = '' ) {
		?>
        <div class="evge-notice-link-wrap">
            <div class="evge-flex-center">
                <div class="evge-notice-base evge-documentation-notice<?php echo esc_attr( $classes ); ?>">
                    <div class="evge-notice-icon">
                        <?php 
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo Icon::get( 'info' ); ?>
                    </div>
                    <?php if ( ! empty( $link ) ) : ?>
                        <a href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener noreferrer" class="evge-icon-text">
                            <?php echo wp_kses_post( $message ); ?>
                        </a>
                    <?php else : ?>
                        <?php echo wp_kses_post( $message ); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
		<?php
	}

    public static function alert( $message, $classes = '' ) {
		?>
            <div class="evge-flex-center">
                <div class="evge-notice-base evge-alert-notice<?php echo esc_attr( $classes ); ?>">
                    <div class="evge-notice-icon">
                        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <ellipse cx="9" cy="9" rx="9" ry="9" transform="rotate(180 9 9)" fill="#FFD24C"/>
                        <circle cx="9" cy="13" r="1" transform="rotate(180 9 13)" fill="#856008"/>
                        <rect x="10" y="10" width="2" height="6" transform="rotate(180 10 10)" fill="#856008"/>
                        </svg>
                    </div>
                    <p><?php echo wp_kses_post( $message ); ?></p>
                </div>
            </div>

		<?php
	}

    public static function badges() {
        ?>
        <div class="evge-message-badge evge-message-badge-success evge-hidden-initially"><div class="evge-message-badge-inner" style="visibility: hidden"><span class="evge-icon-text">
            <?php 
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo Icon::get('check'); ?>
            <span class="evge-badge-message"></span></span></div></div>
        <div class="evge-message-badge evge-message-badge-error evge-hidden-initially"><div class="evge-message-badge-inner" style="visibility: hidden"><span class="evge-icon-text">
            <?php 
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo Icon::get('exclamation'); ?><span class="evge-badge-message"></span></span></div></div>
        <?php
    }

}