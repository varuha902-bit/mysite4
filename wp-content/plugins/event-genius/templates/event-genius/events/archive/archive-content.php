<?php
/**
 * Events Archive Content Template
 * 
 * This template displays the events archive content without header/footer.
 * Used with the_content filter for block themes.
 * 
 * @package WPEventGenius
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="evge-archive-wrap">
    <?php
    // Output the shortcode
    echo do_shortcode( '[event_genius_calendar id="default"]' ); 
    ?>
</div> 