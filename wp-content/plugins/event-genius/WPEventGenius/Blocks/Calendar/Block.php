<?php
namespace WPEventGenius\Blocks\Calendar;

use WPEventGenius\Common\Services\CalendarActionsService;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class Block {
    public function init() {
        $this->register_block();
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    public function register_block() {
        wp_register_script(
            'evge_calendar_block',
            EVGE_PLUGIN_URL . 'blocks/calendar/build/index.js',
            ['wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-api-fetch', 'evge-blocks-shared'],
            EVGE_VERSION,
            true
        );
		EVGE()->style_service()->enqueue_style( 'evge_common' );
		EVGE()->style_service()->enqueue_style( 'evge_calendar' );
		EVGE()->style_service()->enqueue_style( 'evge_single_post' );

        register_block_type('wp-event-genius/calendar', [
            'editor_script' => 'evge_calendar_block',
            'editor_style' => 'evge_calendar_block',
            'style' => 'evge_calendar_block',
            'render_callback' => [$this, 'render_block'],
            'attributes' => [
                'calendarId' => [
                    'type' => 'string',
                    'default' => ''
                ]
            ]
        ]);
    }

    public function register_rest_routes() {
        register_rest_route('wp-event-genius/v1', '/calendar/preview', [
            'methods' => 'POST',
            'callback' => [$this, 'get_calendar_preview'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);
    }

    public function get_calendar_preview($request) {
        $calendar_id = $request->get_param('calendar_id');
        
        // Use 'default' if calendar_id is empty
        $calendar_id = ! empty( $calendar_id ) ? $calendar_id : 'default';
        
        // Call the function directly instead of using shortcode
        $calendar_service = new CalendarActionsService();
        $calendar_html = $calendar_service->render_calendar_shortcode( array( 'id' => $calendar_id ) );
        
        return [
            'html' => '<div class="evge-block-preview">' . $calendar_html . '</div>'
        ];
    }

    public function render_block($attributes) {
        // Use 'default' if calendarId is empty, otherwise use the provided ID
        $calendar_id = ! empty( $attributes['calendarId'] ) ? $attributes['calendarId'] : 'default';

        // Call the function directly instead of using shortcode
        $calendar_service = new CalendarActionsService();
        $calendar_html = $calendar_service->render_calendar_shortcode( array( 'id' => $calendar_id ) );
        
        return sprintf(
            '<div class="evge-block">%s</div>',
            $calendar_html
        );
    }
} 