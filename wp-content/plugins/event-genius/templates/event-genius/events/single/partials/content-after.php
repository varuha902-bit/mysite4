<?php
/**
 * Event Content After Template
 * 
 * This template displays additional content sections after the main event content including:
 * - Export options (if enabled)
 * - Organizer information
 * - Categories
 * - Tags
 * - Cost and registration information
 * 
 * @var int    $event_id The ID of the current event
 * @package WPEventGenius
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\Common\Event\EventPost;
use WPEventGenius\Common\Event\OrganizerPost;
use WPEventGenius\Common\Event\RegistrationCounter;
use WPEventGenius\Common\Utils\DynamicContentHelper;

$event_post = new EventPost( $event_id );
$factory = new \WPEventGenius\Common\Services\RegistrationObjectFactory();
$event_post->set_registration_counter( $factory->create_registration_counter( $event_id, new \WPEventGenius\Common\Database() ) );
?>
                    </p>

                    <?php if ( $event_post->get_allow_registration() === 'enabled' && $event_post->get_show_export_options() === 'enabled' ) : ?>
                        <div class="evge-event-list-content">
                            <?php 
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo $event_post->list_export_html( 'evge-secondary evge-gray-button' ); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php do_action( 'evge_event_single_content_before_organizers', $event_post ); ?>

                <?php if ( ! empty( $event_post->get_organizer_ids() ) && $event_post->should_show_section( 'organizers' ) ) : ?>
                    <div class="evge-event-list-organizer evge-single-event-section">
                        <h3 class="evge-event-meta-heading"><?php echo esc_html( $event_post->organizer_heading() ); ?></h3>
                        <div class="evge-organizer-wrap">
                            <?php foreach ( $event_post->get_organizer_ids() as $organizer_id ) :
                                $organizer_post = new OrganizerPost( $organizer_id );
                            ?>
                                <div class="evge-single-organizer-wrap">
                                    <div class="evge-organizer-avatar">
                                        <?php 
                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                        echo $organizer_post->get_the_featured_image(); ?>
                                    </div>

                                    <div class="evge-organizer evge-multi-line-align">
                                        <div class="evge-organizer-title">
                                            <a href="<?php echo esc_url( $organizer_post->get_the_permalink() ); ?>" target="_blank" rel="noopener noreferrer">
                                                <?php echo esc_html( $organizer_post->get_the_title() ); ?>
                                            </a>
                                        </div>
                                        <div class="evge-organizer-summary">
                                            <?php echo wp_kses_post( $organizer_post->get_the_summary() ); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php do_action( 'evge_event_single_content_before_categories', $event_post ); ?>

                <?php if ( ! empty( $event_post->get_categories() ) && $event_post->should_show_section( 'categories' ) ) : ?>
                    <div class="evge-event-list-categories evge-single-event-section">
                        <h3 class="evge-event-meta-heading"><?php echo esc_html( $event_post->categories_heading() ); ?></h3>
                        <div class="evge-categories evge-pill-link-wrap">
                            <?php foreach ( $event_post->get_categories() as $category ) : ?>
                                <a href="<?php echo esc_url( get_term_link( $category['id'] ) ); ?>" class="evge-pill-link evge-beige">
                                    <?php echo esc_html( $category['label'] ); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php do_action( 'evge_event_single_content_before_tags', $event_post ); ?>

                <?php if ( ! empty( $event_post->get_tags() ) && $event_post->should_show_section( 'tags' ) ) : ?>
                    <div class="evge-event-list-tags evge-single-event-section">
                        <h3 class="evge-event-meta-heading"><?php echo esc_html( $event_post->tags_heading() ); ?></h3>
                        <div class="evge-tags evge-pill-link-wrap">
                            <?php foreach ( $event_post->get_tags() as $tag ) : ?>
                                <a href="<?php echo esc_url( get_tag_link( $tag['id'] ) ); ?>" class="evge-pill-link evge-beige">
                                    <?php echo esc_html( $tag['label'] ); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
            </div>
            <div class="evge-event-single-col-right evge-cta-col">
                <?php do_action( 'evge_event_single_content_before_cta', $event_post ); ?>
                
                <div class="evge-single-event-cta evge-sticky evge-dynamic-content"<?php echo DynamicContentHelper::get_data_attributes( 'event-cta', $event_post->get_the_id(), 'event-cta' ); ?>>
                    <?php
                    // Only show cost section if there's an actual amount
                    $cost_amount = $event_post->get_the_cost_amount();
                    if ( ! empty( $cost_amount ) ) : ?>
                        <div class="evge-single-event-cost">
                            <?php echo esc_html( $event_post->get_the_cost_display() ); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( $event_post->get_allow_registration() === 'enabled' && $event_post->should_show_section( 'capacity' ) && ! $event_post->registration_has_filled() && ! $event_post->registration_has_closed() ) : ?>
                        <div class="evge-single-event-capacity">
                            <span><?php echo wp_kses_post( $event_post->get_registration_capacity_text() ); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php 
                    do_action( 'evge_event_single_cta_before', $event_post );
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo $event_post->get_the_cta( 'single' );
                    
                    do_action( 'evge_event_single_cta_after', $event_post );
                    ?>
                </div>
                <?php do_action( 'evge_event_single_content_after_cta', $event_post ); ?>

            </div>
        </div>
    </div>
</div>

