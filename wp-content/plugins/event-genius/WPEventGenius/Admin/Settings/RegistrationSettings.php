<?php

namespace WPEventGenius\Admin\Settings;

use WPEventGenius\Admin\Services\PaymentMethodAdmin;
use WPEventGenius\Common\Utils\Defaults;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RegistrationSettings extends BaseSettings {

	protected $page = 'evge_registration_settings';

	protected $tab = 'registration';

	protected $text_area_fields;

	protected $rich_editor_fields;

    protected $checkbox_fields;


	public function settings() {
		$this->register_setting();

		add_settings_section(
			'evge_registration_settings_general',
			'',
			array( $this, 'section_callback' ),
			'evge_registration_settings_general' // must match do_settings_section
		);

        $args = array(
            'id' => 'allow_registration',
            'label' => __( 'Allow Registration', 'event-genius' ) . $this->tooltip( __( 'Allow users to register for events.', 'event-genius' ) ),
			'overwrite' => 'event',
			'default' => Defaults::get( 'allow_registration' ), // 'enabled', 'disabled'
            'callback' => 'toggle_field',
            'page' => 'evge_registration_settings_general',
            'section' => 'evge_registration_settings_general'
        );
        $this->add_setting_field( $args );

		add_settings_section(
			'evge_registration_settings_restrictions',
			__( 'Restrictions', 'event-genius' ),
			array( $this, 'section_callback' ),
			'evge_registration_restrictions' // must match do_settings_section
		);

		$args = array(
			'id' => 'capacity',
			'label' => __( 'Event Capacity', 'event-genius' ) . $this->tooltip( __( 'The maximum number of users who can register for an event.', 'event-genius' ) ),
			'default' => Defaults::get( 'capacity' ), // 'enabled', 'disabled'
			'overwrite' => 'event',
			'callback' => 'capacity_field',
			'page' => 'evge_registration_restrictions',
			'section' => 'evge_registration_settings_restrictions'
		);
		$this->add_setting_field( $args );

		$args = array(
			'id' => 'timeline',
			'label' => __( 'Timeline', 'event-genius' ) . $this->tooltip( __( 'When event registration opens and closes - either immediately after an event is created or a certain amount of time before the event starts.', 'event-genius' ) ),
			'default' => '',
			'overwrite' => 'event',
			'callback' => 'timeline_field',
			'page' => 'evge_registration_restrictions',
			'section' => 'evge_registration_settings_restrictions'
		);
		$this->add_setting_field( $args );

		$args = array(
			'id' => 'prevent_duplicate_emails',
			'label' => __( 'Prevent Duplicate Emails', 'event-genius' ) . $this->tooltip( __( 'Prevents people from registering if the email address entered matches an existing registration.', 'event-genius' ) ),
			'default' => Defaults::get( 'prevent_duplicate_emails' ), // 'enabled', 'disabled'
			'callback' => 'toggle_field',
			'page' => 'evge_registration_restrictions',
			'section' => 'evge_registration_settings_restrictions'
		);
		$this->add_setting_field( $args );

		add_settings_section(
			'evge_registration_attendee_list_settings',
			__( 'Attendee List', 'event-genius' ),
			array( $this, 'section_callback' ),
			'evge_registration_attendee_list' // must match do_settings_section
		);

		$args = array(
			'id' => 'show_attendee_list',
			'label' => __( 'Show Attendee List', 'event-genius' ) . $this->tooltip( __( 'A list of confirmed registrants will appear below the registration form.', 'event-genius' ) ),
			'overwrite' => 'event',
			'default' => Defaults::get( 'show_attendee_list' ), // 'enabled', 'disabled'
			'callback' => 'toggle_field',
			'page' => 'evge_registration_attendee_list',
			'section' => 'evge_registration_attendee_list_settings'
		);
		$this->add_setting_field( $args );

		$options = array(
			'everyone' => __( 'Everyone', 'event-genius' ),
			'logged_in' => __( 'Logged In Users', 'event-genius' )
		);
		$args = array(
			'id' => 'who_can_see_attendee_list',
			'label' => __( 'Who Can See Attendee List?', 'event-genius' ),
			'options' => $options,
			'default' => Defaults::get( 'who_can_see_attendee_list' ),
			'callback' => 'radio_field',
			'page' => 'evge_registration_attendee_list',
			'section' => 'evge_registration_attendee_list_settings'
		);
		$this->add_setting_field( $args );


		add_settings_section(
			'evge_registration_editing_settings',
			__( 'Registration Editing', 'event-genius' ),
			array( $this, 'section_callback' ),
			'evge_registration_editing' // must match do_settings_section
		);

		$args = array(
			'id' => 'allow_cancellation',
			'label' => __( 'Allow Cancellation', 'event-genius' ) . $this->tooltip( __( 'Adds a tool for logged out users to enter an email and receive a cancellation link. With the "Pro" version, logged in users will see a button to cancel if that user has already registered.', 'event-genius' ) ),
			'default' => Defaults::get( 'allow_cancellation' ), // 'enabled', 'disabled'
			'callback' => 'toggle_field',
			'page' => 'evge_registration_editing',
			'section' => 'evge_registration_editing_settings'
		);
		$this->add_setting_field( $args );

		$args = array(
			'id' => 'cancellation_timeline',
			'label' => __( 'Cancellation Timeline', 'event-genius' ),
			'callback' => 'cancellation_timeline_field',
			'page' => 'evge_registration_editing',
			'section' => 'evge_registration_editing_settings'
		);
		$this->add_setting_field( $args );



	}

	public function surcharge( $args ) {
		// get option 'text_string' value from the database
		$options = get_option( $args['option'], array() );
		$default_flat       = 0;
		$option_string_flat = ( isset( $options[ $args['id'] . '_' . $args['gateway'] . '_flat' ] ) ) ? esc_attr( $options[ $args['id'] . '_' . $args['gateway'] . '_flat' ] ) : $default_flat;
		$type_flat          = 'type="text"';
		$step_flat          = ' step=".01"';
		$class              = isset( $args['input_class'] ) ? $args['input_class'] : $args['class'];
		?>
        <input id="evge-<?php echo esc_attr( $args['id'] . '_' . $args['gateway'] . '_flat' ); ?>" class="<?php echo esc_attr( $class ); ?>" name="<?php echo esc_attr( $args['option'] . '[' . $args['id'] . '_' . $args['gateway'] . '_flat' . ']' ); ?>" <?php echo esc_attr( $type_flat . $step_flat ); ?> value="<?php echo esc_attr( $option_string_flat ); ?>"/>
        <span>+</span>
		<?php
		// get option 'text_string' value from the database
		$default       = isset( $args['default'] ) ? esc_attr( $args['default'] ) : '';
		$option_string = ( isset( $options[ $args['id'] . '_' . $args['gateway'] ] ) ) ? esc_attr( $options[ $args['id'] . '_' . $args['gateway'] ] ) : $default;
		$type          = ( isset( $args['type'] ) ) ? 'type="' . $args['type'] . '"' : 'type="text"';
		$step          = isset( $args['type'] ) && $args['type'] === 'number' && $args['id'] . '_' . $args['gateway'] === 'payment_member_discount_amount' ? ' step=".01"' : '';
		?>
        <input id="evge-<?php echo esc_attr( $args['id'] . '_' . $args['gateway'] ); ?>" class="<?php echo esc_attr( $class ); ?>" name="<?php echo esc_attr( $args['option'] . '[' . $args['id'] . '_' . $args['gateway'] . ']' ); ?>" <?php echo esc_attr( $type . $step ); ?> value="<?php echo esc_attr( $option_string ); ?>"/>
        <span>%</span>
        <br>
        <span class="description"><?php echo wp_kses_post( $args['description'] ); ?></span>

		<?php
	}

	public function capacity_field( $args ) {
		$options = get_option( $args['option'], array() );
		$default = isset( $args['default'] ) ? $args['default'] : '';
		$value = isset( $options[ $args['id'] ] ) ? $options[ $args['id'] ] : $default;

        $unlimited_args = $args;
        $unlimited_args['id'] = $args['id'] . '_unlimited';
        $unlimited_args['label'] = __( 'Unlimited Capacity', 'event-genius' );
		$unlimited_args['default'] = 'disabled';
        $unlimited_args['value'] = isset( $options[ $args['id'] . '_unlimited' ] ) ? $options[ $args['id'] . '_unlimited' ] : 'disabled';
		?>
		<div class="evge-flex-center">
			<input id="evge_<?php echo esc_attr( $args['id'] ); ?>" class="evge-short-input" type="number" min="1" step="1" name="<?php echo esc_attr( $args['option'] ); ?>[<?php echo esc_attr( $args['id'] ); ?>]" value="<?php echo esc_attr( $value ); ?>">
            <div style="margin-left: 14px;">
                <?php $this->toggle_field( $unlimited_args ); ?>
            </div>
            <label for="evge_<?php echo esc_attr( $args['id'] ); ?>">
                <?php echo esc_html( $unlimited_args['label'] ); ?>
            </label>
		</div>
		<?php
	}

	public function timeline_field( $args ) {
		$options = get_option( $args['option'], array() );

		$open_type_default = 'immediate';
		$value_open_type = isset( $options['open_type'] ) ? $options['open_type'] : $open_type_default;
        $relative_open_offset = isset( $options['relative_open_offset'] ) ? $options['relative_open_offset'] : 0;
        $relative_open_offset_type = isset( $options['relative_open_offset_type'] ) ? $options['relative_open_offset_type'] : 'hours';

        $close_type_default = 'relative';
        $value_close_type = isset( $options['close_type'] ) ? $options['close_type'] : $close_type_default;
        $relative_close_offset = isset( $options['relative_close_offset'] ) ? $options['relative_close_offset'] : 0;
        $relative_close_offset_type = isset( $options['relative_close_offset_type'] ) ? $options['relative_close_offset_type'] : 'hours';

		?>
        <div class="evge-setting-flex-column">

            <div class="evge-flex-center">
                <div class="evge-label-right-space">
                    <label for="evge_open_type">
                        <?php esc_html_e( 'Registration opens', 'event-genius' ) ?>:
                    </label>
                    <select id="evge_open_type" name="<?php echo esc_attr( $args['option'] ); ?>[open_type]">
                        <option value="immediate" <?php selected( 'immediate', $value_open_type ); ?>><?php esc_html_e( 'Immediately', 'event-genius' ); ?></option>
                        <option value="relative" <?php selected( 'relative', $value_open_type ); ?>><?php esc_html_e( 'Relative to Event Start', 'event-genius' ); ?></option>
                    </select>
                </div>

                <div>
                    <div class="evge-open-type-sub evge-flex evge-flex-center" data-type="relative">
                        <input id="evge_relative_open_offset" class="evge-short-input" type="number" name="<?php echo esc_attr( $args['option'] ); ?>[relative_open_offset]" value="<?php echo esc_attr( $relative_open_offset ); ?>">
                        <select id="evge_relative_open_offset_type" class="evge-short-input" name="<?php echo esc_attr( $args['option'] ); ?>[relative_open_offset_type]">
                            <option value="hours" <?php selected( 'hours', $relative_open_offset_type ); ?>><?php esc_html_e( 'Hours', 'event-genius' ); ?></option>
                            <option value="days" <?php selected( 'days', $relative_open_offset_type ); ?>><?php esc_html_e( 'Days', 'event-genius' ); ?></option>
                        </select>
                        <div>
                            <?php esc_html_e( 'before event start', 'event-genius' ); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="evge-flex-center">

                <div class="evge-label-right-space">
                    <label for="evge_close_type">
                        <?php esc_html_e( 'Registration closes', 'event-genius' ) ?>:
                    </label>
                    <select id="evge_close_type" name="<?php echo esc_attr( $args['option'] ); ?>[close_type]">
                        <option value="relative" <?php selected( 'relative', $value_close_type ); ?>><?php esc_html_e( 'Relative to Event Start', 'event-genius' ); ?></option>
                        <option value="never" <?php selected( 'never', $value_close_type ); ?>><?php esc_html_e( 'Never', 'event-genius' ); ?></option>
                    </select>
                </div>
                <div>
                    <div class="evge-close-type-sub evge-flex evge-flex-center" data-type="relative">
                        <input id="evge_relative_close_offset" class="evge-short-input" type="number" name="<?php echo esc_attr( $args['option'] ); ?>[relative_close_offset]" value="<?php echo esc_attr( $relative_close_offset ); ?>">
                        <select id="evge_relative_close_offset_type" class="evge-short-input" name="<?php echo esc_attr( $args['option'] ); ?>[relative_close_offset_type]">
                            <option value="hours" <?php selected( 'hours', $relative_close_offset_type ); ?>><?php esc_html_e( 'Hours', 'event-genius' ); ?></option>
                            <option value="days" <?php selected( 'days', $relative_close_offset_type ); ?>><?php esc_html_e( 'Days', 'event-genius' ); ?></option>
                        </select>
                        <div>
                            <?php esc_html_e( 'before event start', 'event-genius' ); ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>

		<?php
	}

	public function cancellation_timeline_field( $args ) {
		$options = get_option( $args['option'], array() );

		$close_type_default = 'same';
		$value_close_type = isset( $options['cancellation_close_type'] ) ? $options['cancellation_close_type'] : $close_type_default;
		$relative_close_offset = isset( $options['cancellation_relative_close_offset'] ) ? $options['cancellation_relative_close_offset'] : 0;
		$relative_close_offset_type = isset( $options['cancellation_relative_close_offset_type'] ) ? $options['cancellation_relative_close_offset_type'] : 'hours';

		?>
        <div class="evge-setting-flex-column">

            <div class="evge-flex-center">

                <div class="evge-label-right-space">
                    <label for="evge_cancellation_close_type">
						<?php esc_html_e( 'Cancellation closes', 'event-genius' ) ?>:
                    </label>
                    <select id="evge_cancellation_close_type" name="<?php echo esc_attr( $args['option'] ); ?>[cancellation_close_type]">
                        <option value="same" <?php selected( 'same', $value_close_type ); ?>><?php esc_html_e( 'When Registration Closes', 'event-genius' ); ?></option>
                        <option value="relative" <?php selected( 'relative', $value_close_type ); ?>><?php esc_html_e( 'Relative to Event Start', 'event-genius' ); ?></option>
                    </select>
                </div>

                <div>
                    <div class="evge-cancellation-close-type-sub evge-flex evge-flex-center" data-type="relative">
                        <input id="evge_cancellation_relative_close_offset" class="evge-short-input" type="number" name="<?php echo esc_attr( $args['option'] ); ?>[cancellation_relative_close_offset]" value="<?php echo esc_attr( $relative_close_offset ); ?>">
                        <select id="evge_cancellation_relative_close_offset_type" class="evge-short-input" name="<?php echo esc_attr( $args['option'] ); ?>[cancellation_relative_close_offset_type]">
                            <option value="hours" <?php selected( 'hours', $relative_close_offset_type ); ?>><?php esc_html_e( 'Hours', 'event-genius' ); ?></option>
                            <option value="days" <?php selected( 'days', $relative_close_offset_type ); ?>><?php esc_html_e( 'Days', 'event-genius' ); ?></option>
                        </select>
                        <div>
							<?php esc_html_e( 'before event start', 'event-genius' ); ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>

		<?php
	}


}
