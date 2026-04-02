<?php
/**
 * Event Content Before Template
 * 
 * This template displays the initial content sections of an event including:
 * - Featured image
 * - Event title
 * - Event meta information
 * - About details
 * - Event summary
 * 
 * @var int    $event_id The ID of the current event
 * @package WPEventGenius
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\Common\Event\EventPost;
use WPEventGenius\Common\Event\RegistrationCounter;
use WPEventGenius\Common\Event\VenuePost;
use WPEventGenius\Common\Utils\DynamicContentHelper;
use WPEventGenius\Common\Utils\Icon;
use WPEventGenius\Common\Utils\Templater;

$event_post = new EventPost( $event_id );
$factory = new \WPEventGenius\Common\Services\RegistrationObjectFactory();
$event_post->set_registration_counter( $factory->create_registration_counter( $event_id, new \WPEventGenius\Common\Database() ) );
$templater = new Templater();
?>
    <div class="evge-single-event-content-wrapper">
        <div class="evge-single-event-content evge-event-<?php echo esc_attr( $event_id ); ?>">
            <?php if ( $event_post->should_show_section( 'featured_image' ) && $event_post->has_featured_image() ) : ?>
                <div class="evge-single-event-featured-image">
                    <?php
                    $featured_image = $event_post->get_the_featured_image( 'full' );
                    $image_url = $event_post->get_featured_image_url();
                    ?>
                    <div class="evge-featured-image-wrapper">
                        <div class="evge-featured-image-blur" style="background-image: url('<?php echo esc_url( $image_url ); ?>')"></div>
                        <div class="evge-featured-image-main">
                            <?php 
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo $featured_image; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="evge-cols">
                <div class="evge-event-single-col-left evge-details-col">
                    <?php if ( $event_post->should_show_section( 'title' ) ) : ?>
                        <div class="evge-single-event-title">
                            <h1><?php echo esc_html( $event_post->get_the_title() ); ?></h1>
                        </div>
                    <?php endif; ?>

                    <?php do_action( 'evge_event_single_content_before_meta', $event_post ); ?>

                    <div class="evge-single-event-meta evge-single-event-section">
                        <?php
                        EVGE()->template_manager()->get_template( 'events/common/event-meta.php', [
                            'event_post' => $event_post,
                            'show_map'   => true
                        ] );
                        ?>

                        <?php $about_items = $event_post->get_the_about_items( 'single' ); ?>
                        <?php if ( ! empty( $about_items ) && $event_post->should_show_section( 'about_details' ) ) : ?>
                            <div class="evge-single-about-details">
                                <?php foreach ( $about_items as $about_item ) : 
                                    $atts = '';
                                    if ( ! empty( $about_item['atts'] ) ) {
                                        foreach ( $about_item['atts'] as $att => $value ) {
                                            $atts .= ' ' . esc_attr( $att ) . '="' . esc_attr( $value ) . '"';
                                        }
                                    }
                                    if ( ! empty( $about_item['link'] ) ) : 
                                        if ( ! empty( $about_item['link'] ) ) {
                                            $atts .= ' href="' . esc_url( $about_item['link'] ) . '"';
                                        }
                                    ?>
                                        <a class="evge-modal-trigger" 
                                           href="<?php echo esc_url( $about_item['link'] ); ?>"
                                           role="button"
                                           aria-haspopup="dialog"
                                           aria-expanded="false"
                                           aria-controls="evge-modal"
                                           <?php 
                                           // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                           echo $atts; ?>>
                                    <?php endif; ?>
                                        <div id="evge-about-detail-<?php echo esc_attr( $about_item['slug'] ); ?>" 
                                             class="evge-about-detail-<?php echo esc_attr( $about_item['slug'] ); ?> evge-about-detail">
                                            <?php if ( ! empty( $about_item['icon'] ) ) {
                                                Icon::output( $about_item['icon'] );
                                            }
                                            ?>
                                            <?php if ( ! empty( $about_item['is_dynamic'] ) ) : ?>
                                                <span class="evge-dynamic-content"<?php echo DynamicContentHelper::get_data_attributes( $about_item['content_type'], $about_item['event_id'], $about_item['update_endpoint'] ); ?>><?php echo esc_html( $about_item['text'] ); ?></span>
                                            <?php else : ?>
                                                <span><?php echo esc_html( $about_item['text'] ); ?></span>
                                            <?php endif; ?>
                                            <?php if ( ! empty( $about_item['down_caret'] ) ) : ?>
                                                <?php Icon::output( 'down-carat' ); ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php if ( ! empty( $about_item['link'] ) ) : ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php do_action( 'evge_event_single_content_before_content', $event_post ); ?>

                    <div class="evge-event-list-content evge-single-event-section">
                        <?php 
                        $summary = $event_post->get_the_summary();
                        if ( $event_post->should_show_section( 'about_details' ) && ! empty( $summary ) ) : ?>
                            <h3 class="evge-event-meta-heading"><?php echo esc_html( $event_post->about_event_heading() ); ?></h3>
                        <?php endif; ?>
