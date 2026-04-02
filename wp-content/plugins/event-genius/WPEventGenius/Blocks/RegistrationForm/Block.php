<?php
namespace WPEventGenius\Blocks\RegistrationForm;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class Block {
    public function init() {
        $this->register_block();
        add_action('rest_api_init', array($this, 'register_rest_routes'));

    }

    public function register_block() {
        // Register block script
        wp_register_script(
            'evge_registration_form_block',
            EVGE_PLUGIN_URL . 'blocks/registration-form/build/index.js',
            array(
                'wp-blocks',
                'wp-block-editor',
                'wp-components',
                'wp-element',
                'wp-api-fetch',
                'evge-blocks-shared'
            ),
            EVGE_VERSION,
            true // Load in footer
        );
        EVGE()->style_service()->enqueue_style( 'evge_common' );
        EVGE()->style_service()->enqueue_style( 'evge_registration_form' );

        // Register the block
        register_block_type('wp-event-genius/registration-form', array(
            'editor_script' => 'evge_registration_form_block',
            'editor_style' => 'evge_registration_form_block',
            'style' => 'evge_registration_form_block',
            'render_callback' => array($this, 'render_block'),
            'attributes' => array(
                'eventId' => array(
                    'type' => 'string',
                    'default' => 'auto',
                ),
                'showHeader' => array(
                    'type' => 'boolean',
                    'default' => true,
                ),
                'formType' => array(
                    'type' => 'string',
                    'default' => 'inline',
                ),
            ),
        ));

    }

    public function register_rest_routes() {
        register_rest_route('wp-event-genius/v1', '/registration-form/preview', array(
            'methods' => 'POST',
            'callback' => array($this, 'get_form_preview'),
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            }
        ));
    }

    public function get_form_preview($request) {
        $event_id = $request->get_param('event_id');
        $show_header = $request->get_param('show_header') ? 'true' : 'false';
        $form_type = $request->get_param('form_type') ?: 'inline';

        // Handle auto-detection for preview
        if (empty($event_id) || $event_id === 'auto') {
            $event_id = $this->get_auto_event_id(true);
        }

        return array(
            'html' => '<div class="evge-block-preview">' . $this->render_form_by_type($event_id, $show_header, $form_type, true) . '</div>'
        );
    }

    public function render_block($attributes) {
        $event_id = ! empty( $attributes['eventId'] ) ? $attributes['eventId'] : 'auto';
        
        // Handle auto-detection
        if ($event_id === 'auto' || empty($event_id)) {
            $event_id = $this->get_auto_event_id(false);
        }

        // If still no event ID after auto-detection, show error
        if (empty($event_id)) {
            return '<div class="evge-block evge-registration-form-block evge-error">No event found. Please select an event or ensure there is an upcoming event that allows registration.</div>';
        }

        $show_header = ! empty( $attributes['showHeader'] ) ? 'true' : 'false';
        $form_type = ! empty( $attributes['formType'] ) ? $attributes['formType'] : 'inline';

        // Generate the output based on form type
        return sprintf(
            '<div class="evge-block evge-registration-form-block">%s</div>',
            $this->render_form_by_type($event_id, $show_header, $form_type, false)
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

    /**
     * Render form based on type (inline, full, or modal)
     *
     * @param string $event_id Event ID
     * @param string $show_header Whether to show header ('true' or 'false')
     * @param string $form_type Form type: 'inline', 'full', or 'modal'
     * @param bool $is_preview Whether this is for editor preview
     * @return string HTML output
     */
    private function render_form_by_type($event_id, $show_header, $form_type, $is_preview = false) {
        // Validate event ID and visibility
        $event_id_int = absint($event_id);
        if (!$event_id_int) {
            return '<div class="evge-error">Invalid event ID.</div>';
        }

        // Check if event is visible (only for non-preview)
        if (!$is_preview) {
            if (!\WPEventGenius\Common\Utils\Utils::is_event_visible_to_user($event_id_int)) {
                return '<div class="evge-error">' . \WPEventGenius\Common\Utils\Utils::get_event_visibility_error_message('form_display') . '</div>';
            }
        }

        // Create event post object
        $factory = new \WPEventGenius\Common\Services\RegistrationObjectFactory();
        $event_post = $factory->create_event_post($event_id_int);

        if (!$event_post) {
            return '<div class="evge-error">Event not found.</div>';
        }


        // Check registration status and show appropriate message if needed
        $status_message = $this->get_registration_status_message($event_post);
        if ($status_message) {
            // For modal type, we still want to show the message instead of button
            // For inline/full types, the shortcode will handle it, but we can also show it here for consistency
            if ($form_type === 'modal') {
                $output = '';
                $wrapper_class = '';
                
                // Show event info if enabled
                if ($show_header === 'true') {
                    $output = $this->render_event_info_simple($event_post);
                    $wrapper_class = 'evge-registration-form-block-with-info';
                }
                
                // Add status message
                $output .= $status_message;
                
                // Wrap in class if event info is shown
                if ($wrapper_class) {
                    $output = '<div class="' . $wrapper_class . '">' . $output . '</div>';
                }
                
                return $output;
            }
            // For inline/full, wrap the message in the container
            $output = '<div class="evge-registration-form-container evge evge-standalone-registration-form">';
            if ($show_header === 'true') {
                $output .= $this->render_event_header($event_post);
            }
            $output .= '<div class="evge-form-wrapper">' . $status_message . '</div>';
            $output .= '</div>';
            
            if ($form_type === 'full') {
                $output = '<div class="evge-registration-form-full-width">' . $output . '</div>';
            }
            return $output;
        }

        // Handle modal type
        if ($form_type === 'modal') {
            return $this->render_modal_button($event_post, $show_header, $is_preview);
        }

        // Use shortcode for inline and full types
        $shortcode = sprintf(
            '[event_genius_registration_form event="%s" header="%s"]',
            esc_attr($event_id),
            $show_header
        );

        $output = do_shortcode($shortcode);

        // Wrap in additional container for full-width type
        if ($form_type === 'full') {
            $output = '<div class="evge-registration-form-full-width">' . $output . '</div>';
        }

        return $output;
    }

    /**
     * Render modal button for registration form
     *
     * @param \WPEventGenius\Event\Post $event_post Event post object
     * @param string $show_header Whether to show event info ('true' or 'false')
     * @param bool $is_preview Whether this is for editor preview
     * @return string HTML output
     */
    private function render_modal_button($event_post, $show_header = 'false', $is_preview = false) {
        // Enqueue necessary scripts and styles
        EVGE()->style_service()->enqueue_style('evge_common');
        EVGE()->style_service()->enqueue_style('evge_registration_form');
        EVGE()->script_service()->enqueue_script('evge_common');
        EVGE()->script_service()->enqueue_script('evge_registration_form');
        EVGE()->modal_service()->request_modal();

        // Get form and button settings
        $form = $event_post->get_form();
        $event_id = $event_post->get_the_id();
        $modal_settings = array('width' => 'full');

        // Check if user is already registered
        $is_already_registered = false;
        if (is_user_logged_in() 
            && $event_post->get_allow_registration() === 'enabled' 
            && !$event_post->registration_has_closed()
            && !$event_post->cancellation_has_closed()) {
            
            $event_goer = EVGE()->event_goer();
            $event_goer->set_event($event_post);
            $event_goer->init($event_post);
            
            if ($event_goer->has_made_submission_for_event()) {
                $is_already_registered = true;
            }
        }

        // Get form-specific button styles
        $button_color = $form->get_register_button_text_color();
        $button_background = $form->get_register_button_background_color();
        $button_border = $form->get_register_button_border_color();

        $styles_array = array();
        if (!empty($button_color)) {
            $styles_array[] = 'color: ' . esc_attr($button_color);
        }
        if (!empty($button_background)) {
            $styles_array[] = 'background-color: ' . esc_attr($button_background);
        }
        if (!empty($button_border)) {
            $styles_array[] = 'border: 1px solid ' . esc_attr($button_border);
        }
        
        $style_att = '';
        if (!empty($styles_array)) {
            $styles = implode(';', $styles_array);
            $style_att = ' style="' . $styles . '"';
        }

        // Build button HTML based on registration status
        $show_manage_registration = $is_already_registered
            && function_exists( 'evge_is_pro_tier' )
            && evge_is_pro_tier();
        if ( $show_manage_registration ) {
            // User is already registered (Pro only) - show "Manage Registration" button using setting
            $manage_button_text = \WPEventGenius\Common\Utils\Settings::get( 'manage_registration_button_text' );
            $button_class = 'evge-modal-trigger evge-manage-registration-' . esc_attr( $event_id );
            $button_attrs = array(
                'class' => $button_class,
                'data-evge-modal-settings' => esc_attr( wp_json_encode( $modal_settings ) ),
                'data-evge-modal-content' => 'ajax',
                'data-evge-ajax' => esc_attr( wp_json_encode( array(
                    'action' => 'evge_get_already_registered_content',
                    'event_id' => $event_id
                ) ) )
            );
            $button_html = '<button ' . $this->build_attributes_string( $button_attrs ) . $style_att . '>' . esc_html( $manage_button_text ) . '</button>';
        } else {
            // User is not registered - show regular registration button
            $register_button_text = apply_filters('evge_form_register_button_text', $form->get_register_button_text(), $event_post);
            $button_class = 'evge-modal-trigger evge-checkout-cache-' . esc_attr($event_id);
            $button_attrs = array(
                'class' => $button_class,
                'data-evge-modal-settings' => esc_attr(wp_json_encode($modal_settings)),
                'data-evge-modal-content' => 'ajax',
                'data-evge-ajax' => esc_attr($event_post->get_event_json('evge_get_registration_content'))
            );
            $button_html = '<button ' . $this->build_attributes_string($button_attrs) . $style_att . '>' . esc_html($register_button_text) . '</button>';
        }

        // Wrap button with dynamic content helper (always add attributes, JS controls usage)
        $button_html = '<div class="evge-dynamic-content evge-registration-button-wrapper"' . 
            \WPEventGenius\Common\Utils\DynamicContentHelper::get_data_attributes( 'registration-button', $event_id, 'registration-button' ) . 
            '>' . $button_html . '</div>';

        // Show event info if enabled
        $show_event_info = ($show_header === 'true');
        $wrapper_class = '';
        if ($show_event_info) {
            $event_info = $this->render_event_info_simple($event_post);
            $button_html = $event_info . $button_html;
            $wrapper_class = 'evge-registration-form-block-with-info';
        }

        // Wrap content
        $wrapper = '';
        if ($is_preview) {
            $wrapper = '<div class="evge-block-modal-preview' . ($wrapper_class ? ' ' . $wrapper_class : '') . '">';
        } elseif ($wrapper_class) {
            $wrapper = '<div class="' . $wrapper_class . '">';
        }

        if ($wrapper) {
            return $wrapper . $button_html . '</div>';
        }

        return $button_html;
    }

    /**
     * Render simple event info (for modal button display)
     * No additional wrapping styling - just the event info
     *
     * @param \WPEventGenius\Event\Post $event_post Event post object
     * @return string HTML output
     */
    private function render_event_info_simple($event_post) {
        ob_start();
        ?>
        <div class="evge-single-event-title">
            <h3><?php echo esc_html($event_post->get_the_title()); ?></h3>
        </div>
        <?php
        EVGE()->template_manager()->get_template('events/common/event-meta.php', [
            'event_post' => $event_post,
            'show_map' => true
        ]);
        ?>
        <?php
        return ob_get_clean();
    }

    /**
     * Get registration status message if registration is closed, full, or not open
     *
     * @param \WPEventGenius\Event\Post $event_post Event post object
     * @return string|false Status message HTML or false if registration is open
     */
    private function get_registration_status_message($event_post) {
        // Check if registration is allowed
        if ($event_post->get_allow_registration() !== 'enabled') {
            return false; // No registration allowed, let shortcode handle it
        }

        $message_html = '';
        
        // Check if registration has closed
        if ($event_post->registration_has_closed()) {
            $message_html = wp_kses_post($event_post->closed_message());
        } elseif ($event_post->registration_has_filled()) {
            // Check if event is full
            $message_html = wp_kses_post($event_post->filled_message());
        } elseif (!$event_post->registration_is_open()) {
            // Check if registration is not yet open
            $message_html = wp_kses_post($event_post->not_open_until_message());
        } else {
            return false; // Registration is open
        }

        // Wrap with dynamic content helper (always add attributes, JS controls usage)
        if (!empty($message_html)) {
            return '<div class="evge-dynamic-content evge-registration-status-message"' . 
                \WPEventGenius\Common\Utils\DynamicContentHelper::get_data_attributes( 'registration-status-message', $event_post->get_the_id(), 'registration-status-message' ) . 
                '>' . $message_html . '</div>';
        }

        return $message_html;
    }

    /**
     * Render event header for the form
     *
     * @param \WPEventGenius\Event\Post $event_post Event post object
     * @return string HTML output
     */
    private function render_event_header($event_post) {
        ob_start();
        ?>
        <div class="evge-registration-header">
            <div class="evge-single-event-title">
                <h3><?php echo esc_html($event_post->get_the_title()); ?></h3>
            </div>
            <div class="evge-single-event-meta evge-single-event-section">
                <?php
                EVGE()->template_manager()->get_template('events/common/event-meta.php', [
                    'event_post' => $event_post,
                    'show_map' => true
                ]);
                ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Build HTML attributes string from array
     *
     * @param array $attrs Attributes array
     * @return string HTML attributes string
     */
    private function build_attributes_string($attrs) {
        $output = array();
        foreach ($attrs as $key => $value) {
            $output[] = esc_attr($key) . '="' . esc_attr($value) . '"';
        }
        return implode(' ', $output);
    }
} 