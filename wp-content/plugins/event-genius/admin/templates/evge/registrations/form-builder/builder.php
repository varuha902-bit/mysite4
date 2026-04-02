<?php
use WPEventGenius\Common\Utils\Templater;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Use the field handler passed from the BasePage class
$all_fields = new \WPEventGenius\Admin\FormBuilder\AllFields( $field_handler );

$template = new Templater();
?>

<div id="evge-form-builder" data-form-id="<?php echo esc_attr( $form_id ); ?>">
	<div class="evge-form-builder-page-content-wrap">
		<div class="evge-form-builder">
			<div class="evge-form-fields-section">
                <div class="evge-all-fields-header">
                    <div class="evge-flex-center">
                        <strong><?php esc_html_e('All Fields', 'event-genius'); ?></strong>
                        <div class="evge-save-needed-alert evge-icon-text"><div class="evge-icon-circle"><?php 
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo \WPEventGenius\Common\Utils\Icon::get( 'exclamation') . '</div>'; esc_html_e('save your changes', 'event-genius'); ?></div>
                    </div>
                </div>
				<div id="evge-available-fields">
					<?php foreach ( $all_fields->get_fields() as $field_id => $field ) {
						include( EVGE_ADMIN_TEMPLATE_PATH . 'evge/registrations/form-builder/single-field-edit.php' );
					}

                    $field = $all_fields->get_submit_button();
                    include( EVGE_ADMIN_TEMPLATE_PATH . 'evge/registrations/form-builder/submit-edit.php' );
					?>

				</div>
                <button type="button" id="evge-add-new-field" class="button"><span class="evge-icon-text">+ <?php esc_html_e('Add New Field', 'event-genius'); ?></span></button>
			</div>


			<?php
			// Get guest registration type for the form
			$guest_registration_type = isset( $form ) && method_exists( $form, 'get_guest_registration_type' ) 
				? $form->get_guest_registration_type() 
				: 'none';
			?>
			<div class="evge-form-preview-section" data-guest-registration-type="<?php echo esc_attr( $guest_registration_type ); ?>">
                <div class="evge-preview-header">
                    <div class="evge-flex-center">
                        <strong><?php esc_html_e('Preview', 'event-genius'); ?></strong>
                        <div class="evge-save-needed-alert evge-icon-text"><div class="evge-icon-circle"><?php 
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo \WPEventGenius\Common\Utils\Icon::get( 'exclamation') . '</div>'; esc_html_e('save your changes', 'event-genius'); ?></div>

                    </div>

                </div>

				<div class="evge-left-dynamic">
                    <div class="evge-form-preview-wrap">
	                    <?php
	                    $templater = new Templater();
	                    $event_post = new \WPEventGenius\Common\Event\ExampleEventPost( 0, $form_id );
	                    include $templater->get_registration_template_part( 'form' );
	                    ?>
                    </div>

					<div class="evge-in-form-options-bar-wrap evge-hidden-initially">
						<div class="evge-form-field-in-form-top">
							<div class="evge-field-options-bar evge-hidden-initially">
								<div class="evge-in-form-field-option evge-field-option-edit-button">
									<button class="evge-in-form-field-edit-button evge-in-form-field-action-button">
										<?php 
                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                        echo \WPEventGenius\Common\Utils\Icon::get( 'gear' ); ?>
									</button>
								</div>
							</div>
						</div>

						<div class="evge-form-field-in-form-bottom">
							<div class="evge-form-field-in-form-options evge-hidden-initially">
                                <div class="evge-flex evge-form-field-in-form-options-inner">
                                    <div class="evge-left-options">
                                        <div class="evge-single-option">
                                            <div class="evge-toggle-setting">
                                                <input class="evge-toggle-setting-enabled" type="hidden" name="required" value="disabled">
                                                <a class="evge-settings-toggle-wrap evge-toggle-setting-required" href="">
                                                    <span class="evge-settings-toggle evge-input-toggle--disabled" aria-label="<?php echo sprintf ( esc_html__( 'The setting is currently disabled', 'event-genius' ), '' ); ?>"><?php echo esc_html_e( 'No', 'event-genius' ); ?></span>
                                                </a>
                                                <label for="evge-required"><?php esc_html_e( 'Required field', 'event-genius' ); ?></label>
                                            </div>


                                        </div>
                                        <div class="evge-single-option">
                                            <div class="evge-toggle-setting evge-toggle-setting-show-in-attendee-list">
                                                <input class="evge-toggle-setting-enabled" type="hidden" name="show_in_attendee_list" value="disabled">
                                                <a class="evge-settings-toggle-wrap" href="">
                                                    <span class="evge-settings-toggle evge-input-toggle--disabled" aria-label="<?php echo sprintf ( esc_html__( 'The setting is currently disabled', 'event-genius' ), '' ); ?>"><?php echo esc_html_e( 'No', 'event-genius' ); ?></span>
                                                </a>
                                                <label for="evge-show-in-attendee-list"><?php esc_html_e( 'Show in attendee lists', 'event-genius' ); ?></label>
                                            </div>
                                        </div>
                                        <?php do_action( 'evge_form_field_in_form_options_bottom', $field ); ?>
                                         <input name="id" type="hidden" value="">
                                    </div>

                                    <div class="evge-in-form-field-option evge-field-option-remove">
                                        <a href="#" class="evge-in-form-field-remove-button evge-in-form-field-action-button"><?php esc_html_e( 'Remove', 'event-genius' ); ?></a>
                                    </div>
                                </div>

							</div>
						</div>

					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php \WPEventGenius\Common\Utils\Notices::badges(); ?>