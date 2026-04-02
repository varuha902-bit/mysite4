<?php
/**
 * Event Meta Template
 * 
 * This template displays event metadata including:
 * - Date and recurrence information
 * - Venue details with address
 * - Interactive map display
 * 
 * @var object $event_post The event object
 * @var bool   $show_map   Whether to display the map
 * @package WPEventGenius
 * @since 1.0.0
 */

use WPEventGenius\Common\Event\VenuePost;
use WPEventGenius\Common\Utils\Icon;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

$event_id = $event_post->get_the_id();
?>
<div class="evge-meta">
    <?php if ( $event_post->should_show_section('date') ) : ?>
	<p class="evge-meta-date-summary evge-event-meta-row">
		<?php Icon::output( 'calendar' ); ?>
        <span>
            <?php
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $event_post->recurrence_display() . esc_html( $event_post->get_the_full_date() ); ?>
        </span>
    </p>
    <?php endif; ?>
    <?php if ( $event_post->should_show_section('locations') ) : ?>
<?php 
foreach ( $event_post->get_venue_ids() as $venue_id ) :
    $venue_post = new VenuePost( $venue_id );
    ?>
    <div class="evge-meta-venue-summary evge-event-meta-row evge-has-hidden-content">
        <p class="evge-event-meta-item">
	        <?php Icon::output( 'location' ); ?>
            <span class="evge-meta-venue-title">
                <?php 
                if ( empty( $venue_unlink ) ) {
                    echo '<a href="' . esc_url( $venue_post->get_the_permalink() ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $venue_post->get_the_title() ) . '</a>';
                } else {
                    echo esc_html( $venue_post->get_the_title() );
                }
                $full_address = $venue_post->get_the_full_address();    
                if ( ! empty( $full_address ) ) {
                    echo ',';
                }
                ?>
            </span>
            <span class="evge-meta-venue-address">
                <?php echo esc_html( $full_address ); ?>
            </span>

            <?php if ( ! empty( $show_map ) && $event_post->should_show_section('map') && ! empty( $venue_post->get_the_map_url() ) ) : ?>
            <span class="evge-event-meta-item">
                <span class="evge-show-link-wrap evge-map-link">
                    <a href="javascript:void(0);">
                        <span class="evge-reveal-text"><?php echo esc_html( $event_post->see_map_text() ); ?></span>
                        <span class="evge-hide-text" style="display: none"><?php echo esc_html( $event_post->see_map_text() ); ?></span>
                        <svg class="evge-down-carat evge-reveal-icon" xmlns="http://www.w3.org/2000/svg" height="16" width="16" viewBox="0 0 512 512" fill="currentColor"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2023 Fonticons, Inc.--><path d="M233.4 406.6c12.5 12.5 32.8 12.5 45.3 0l192-192c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L256 338.7 86.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l192 192z"/></svg>
                        <svg class="evge-up-carat evge-hide-icon" xmlns="http://www.w3.org/2000/svg" height="16" width="16" viewBox="0 0 512 512" fill="currentColor" style="display: none"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2023 Fonticons, Inc.--><path d="M233.4 105.4c12.5-12.5 32.8-12.5 45.3 0l192 192c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L256 173.3 86.6 342.6c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3l192-192z"/></svg>
                    </a>
                </span>
            </span>
        </p>
	    

        <div class="evge-map-wrap evge-hidden-content" style="display: none;">
            <div class="evge-map-placeholder" data-src="<?php echo esc_url( $venue_post->get_the_map_url() ); ?>" data-id="<?php echo absint( $event_id ); ?>">
                <div class="evge-spinner-container evge-is-processing"><div class="evge-spinner-circle"></div></div>
            </div>
        </div>

	    <?php endif; ?>

    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
