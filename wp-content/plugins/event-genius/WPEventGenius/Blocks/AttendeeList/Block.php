<?php
namespace WPEventGenius\Blocks\AttendeeList;

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
            'evge_attendee_list_block',
            EVGE_PLUGIN_URL . 'blocks/attendee-list/build/index.js',
            ['wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-api-fetch', 'evge-blocks-shared'],
            EVGE_VERSION,
            true
        );
        
		EVGE()->style_service()->enqueue_style( 'evge_common' );
        EVGE()->style_service()->enqueue_style( 'evge_attendee_list' );

        register_block_type('wp-event-genius/attendee-list', [
            'editor_script' => 'evge_attendee_list_block',
            'editor_style' => 'evge_attendee_list_block',
            'style' => 'evge_attendee_list_block',
            'render_callback' => [$this, 'render_block'],
            'attributes' => [
                'eventId' => [
                    'type' => 'string',
                    'default' => 'auto'
                ],
                'template' => [
                    'type' => 'string',
                    'default' => 'auto'
                ],
                'status' => [
                    'type' => 'string',
                    'default' => 'confirmed'
                ],
                'perLoad' => [
                    'type' => 'number',
                    'default' => 20
                ],
                'orderby' => [
                    'type' => 'string',
                    'default' => 'registration_date'
                ],
                'order' => [
                    'type' => 'string',
                    'default' => 'DESC'
                ],
                'selectedFields' => [
                    'type' => 'array',
                    'default' => []
                ],
                'simpleFormat' => [
                    'type' => 'string',
                    'default' => ''
                ]
            ]
        ]);
    }

    public function register_rest_routes() {
        register_rest_route('wp-event-genius/v1', '/attendee-list/preview', [
            'methods' => 'POST',
            'callback' => [$this, 'get_list_preview'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);
        
        register_rest_route('wp-event-genius/v1', '/attendee-list/event-fields', [
            'methods' => 'GET',
            'callback' => [$this, 'get_event_fields'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
            'args' => [
                'event_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ],
                'include_all' => [
                    'required' => false,
                    'type' => 'boolean',
                    'default' => false,
                ],
            ],
        ]);
    }

    public function get_list_preview($request) {
        $params = $request->get_params();
        
        // Handle auto-detection for preview
        $event_id = !empty($params['eventId']) ? $params['eventId'] : 'auto';
        if ($event_id === 'auto' || empty($event_id)) {
            $event_id = $this->get_auto_event_id(true);
        }
        
        $fields_param = '';
        if (!empty($params['selectedFields']) && is_array($params['selectedFields'])) {
            $fields_param = ' fields="' . esc_attr(implode(',', $params['selectedFields'])) . '"';
        } elseif (isset($params['template']) && $params['template'] === 'full') {
            $fields_param = ' fields="all"';
        }
        
        $format_param = '';
        if (!empty($params['simpleFormat']) && ($params['template'] ?? 'auto') === 'simple') {
            $format_param = ' format="' . esc_attr($params['simpleFormat']) . '"';
        }

        return array(
            'html' => '<div class="evge-block-preview">' . do_shortcode(sprintf(
                '[event_genius_attendees event="%s" template="%s" status="%s" per_load="%s" orderby="%s" order="%s"%s%s]',
                esc_attr($event_id),
                esc_attr($params['template'] ?? 'auto'),
                esc_attr($params['status'] ?? 'confirmed'),
                esc_attr($params['perLoad'] ?? '20'),
                esc_attr($params['orderby'] ?? 'registration_date'),
                esc_attr($params['order'] ?? 'DESC'),
                $fields_param,
                $format_param
            )) . '</div>'
        );
    }

    public function get_event_fields($request) {
        $event_id = $request->get_param('event_id');
        $include_all = $request->get_param('include_all') === true || $request->get_param('include_all') === 'true';
        
        if (empty($event_id)) {
            return new \WP_Error('missing_event_id', 'Event ID is required', array('status' => 400));
        }
        
        try {
            $event = new \WPEventGenius\Common\Event\Event($event_id);
            $form = $event->get_form();
            $all_fields = $form->get_fields();
            
            // Filter fields based on include_all parameter
            $available_fields = array();
            foreach ($all_fields as $field) {
                // Skip hidden fields
                if ($field->get_type() === 'hidden') {
                    continue;
                }
                
                // Skip admin-only fields
                if (method_exists($field, 'get_admin_only') && $field->get_admin_only()) {
                    continue;
                }
                

                $available_fields[] = array(
                    'id' => $field->get_id(),
                    'slug' => method_exists($field, 'get_slug') ? $field->get_slug() : '',
                    'label' => method_exists($field, 'get_label') ? $field->get_label() : '',
                    'type' => $field->get_type(),
                );
            }
            
            return rest_ensure_response($available_fields);
        } catch (\Exception $e) {
            return new \WP_Error('error_getting_fields', $e->getMessage(), array('status' => 500));
        }
    }

    public function render_block($attributes) {
        // Determine event ID - use provided eventId or detect from current page
        $event_id = !empty($attributes['eventId']) ? $attributes['eventId'] : 'auto';
        
        // Handle auto-detection
        if ($event_id === 'auto' || empty($event_id)) {
            $event_id = $this->get_auto_event_id(false);
        } else {
            $event_id = absint($event_id);
        }
        
        // If still no event ID after auto-detection, show error message
        if (empty($event_id)) {
            return '<div class="evge-block evge-error">No event found. Please select an event or ensure there is an upcoming event that allows registration.</div>';
        }

        // Build fields parameter if selected
        $fields_param = '';
        if (!empty($attributes['selectedFields']) && is_array($attributes['selectedFields'])) {
            $fields_param = ' fields="' . esc_attr(implode(',', $attributes['selectedFields'])) . '"';
        } elseif (isset($attributes['template']) && $attributes['template'] === 'full') {
            $fields_param = ' fields="all"';
        }
        
        // Build format parameter for simple template
        $format_param = '';
        if (!empty($attributes['simpleFormat']) && ($attributes['template'] ?? 'auto') === 'simple') {
            $format_param = ' format="' . esc_attr($attributes['simpleFormat']) . '"';
        }

        // Generate the shortcode output
        return sprintf(
            '<div class="evge-block">%s</div>',
            do_shortcode(sprintf(
                '[event_genius_attendees event="%s" template="%s" status="%s" per_load="%s" orderby="%s" order="%s"%s%s]',
                esc_attr($event_id),
                esc_attr($attributes['template'] ?? 'auto'),
                esc_attr($attributes['status'] ?? 'confirmed'),
                esc_attr($attributes['perLoad'] ?? '20'),
                esc_attr($attributes['orderby'] ?? 'registration_date'),
                esc_attr($attributes['order'] ?? 'DESC'),
                $fields_param,
                $format_param
            ))
        );
    }

    /**
     * Get event ID using auto-detection logic
     * 
     * @param bool $is_preview Whether this is for editor preview
     * @return int|string Event ID or empty string if not found
     */
    private function get_auto_event_id($is_preview = false) {
        // First, check if we're on a single event page
        if (!$is_preview && defined('EVGE_EVENT_POST_TYPE') && is_singular(EVGE_EVENT_POST_TYPE)) {
            $current_event_id = get_the_ID();
            if ($current_event_id) {
                return $current_event_id;
            }
        }

        // Otherwise, find the next upcoming event that allows registration
        $query_params = array(
            'posts_per_page' => 1,
            'qtype' => 'upcoming',
            'with' => 'with', // Only events with registration enabled
        );

        $event_query = new \WPEventGenius\Common\Queries\EventQuery($query_params);
        $event_query->add_wp_query();
        $event_query->add_all();
        $event_query->hydrate();

        $events = $event_query->get_events();

        if (!empty($events) && isset($events[0]) && is_object($events[0])) {
            $event_id = $events[0]->ID;
            // For preview, skip visibility check; for frontend, verify visibility
            if ($is_preview || \WPEventGenius\Common\Utils\Utils::is_event_visible_to_user($event_id)) {
                return $event_id;
            }
        }

        return '';
    }
} 