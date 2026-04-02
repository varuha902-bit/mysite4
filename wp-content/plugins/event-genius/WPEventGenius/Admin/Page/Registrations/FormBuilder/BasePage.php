<?php
namespace WPEventGenius\Admin\Page\Registrations\FormBuilder;

use WPEventGenius\Admin\Page\RegistrationsBasePage;
use WPEventGenius\Common\Registration\Field\FieldHandler;
use WPEventGenius\Common\Registration\Form\Form;
use WPEventGenius\Common\Services\RegistrationObjectFactory;
use WPEventGenius\Common\Utils\Icon;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class BasePage extends RegistrationsBasePage {

	protected $active_tab = 'forms';

	protected $active_subtab = 'builder';

	protected $form_id;

    protected $form;

    protected $field_handler;

    protected $email_page;

	protected $settings_page;

	protected $factory;

	public function __construct() {
		$this->factory = new RegistrationObjectFactory();
	}

	public function build() {
		$this->form_id = isset( $_GET['form_id'] ) ? intval( $_GET['form_id'] ) : 1;
        $this->field_handler = $this->factory->create_field_handler();
		$this->form = new Form( $this->form_id, $this->field_handler );
		$this->form->set_fields();
		$this->email_page = new \WPEventGenius\Admin\Page\Registrations\FormBuilder\EmailPage();

        $this->email_page->settings();

		$this->settings_page = new \WPEventGenius\Admin\Page\Registrations\FormBuilder\SettingsPage();
        $this->settings_page->settings();
	}

	public function page_title() {
		// Ensure form_id is set (in case page_title is called before build)
		if ( empty( $this->form_id ) ) {
			$this->form_id = isset( $_GET['form_id'] ) ? intval( $_GET['form_id'] ) : 1;
		}
		// Get the actual form name from the database
		$form_name = $this->get_form_name();
		return $form_name;
	}

	public function navigation( $active_tab ) {

	}

	public function before_subnav() {
		// For Standard tier and higher, go to forms tab; otherwise go to top level registrations page
		if ( function_exists( 'evge_is_standard_tier' ) && evge_is_standard_tier() ) {
			$base_url = admin_url('admin.php?page=evge-registrations&tab=forms');
			$button_text = __( 'Back to Forms', 'event-genius' );
		} else {
			$base_url = admin_url('admin.php?page=evge-registrations');
			$button_text = __( 'Back to Registrations', 'event-genius' );
		}
		?>
            <div class="evge-before-subnav evge-bump-down">
                <a href="<?php echo esc_url(remove_query_arg(['form_id', 'subtab'], $base_url)); ?>"
                   id="evge-back-to-forms" class="evge-left-icon evge-admin-secondary-button button action evge-tiny-icon">
                    <span class="evge-icon-text">
                        <?php 
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo Icon::get('left-chevron'); ?>
                        <?php echo esc_html( $button_text ); ?>
                    </span>
                </a>
                <div class="evge-button-group">
                    <button type="button" class="button evge-form-save-button evge-blue-action-button"><span class="evge-icon-text"><?php 
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo Icon::get( 'check' ); ?>
                    <?php esc_html_e('Save Form', 'event-genius'); ?></span></button>
                </div>
            </div>

	<?php
	}

	public function sub_navigation( $sub_navigation_args ) {
        echo '<div class="evge-builder-subnav-wrap">';
        $this->navigation_html( $sub_navigation_args );
        echo '</div>';
	}

	public function sub_navigation_args() {
		$sub_navigation_args = array(
			'active_tab' => $this->active_subtab,
			'nav_items' => array(
				array(
					'id' => 'builder',
					'title' => __( 'Builder', 'event-genius' ),
					'url' => $this->nav_link( 'evge-registrations', array( 'tab' => 'forms', 'subtab' => 'builder' ) )
				),
				array(
					'id' => 'emails',
					'title' => __( 'Emails', 'event-genius' ),
					'url' => $this->nav_link( 'evge-registrations', array( 'tab' => 'forms', 'subtab' => 'emails' ) )
				),
				array(
					'id' => 'settings',
					'title' => __( 'Settings', 'event-genius' ),
					'url' => $this->nav_link( 'evge-registrations', array( 'tab' => 'forms', 'subtab' => 'settings' ) )
				),
			)
		);

		return $sub_navigation_args;

	}

	public function content() {
		$field_handler = $this->field_handler;
		$form_id = $this->form_id;
		$form = $this->form;
		$form->set_fields();
		$current_fields = $form->get_fields();
		include_once( EVGE_ADMIN_TEMPLATE_PATH . 'evge/registrations/form-builder/main.php' );
	}

	public function emails_content() {
		$field_handler = $this->field_handler;
		$form_id = $this->form_id;
		$form = $this->form;
		$current_fields = $form->get_fields();
		include_once( EVGE_ADMIN_TEMPLATE_PATH . 'evge/registrations/form-builder/emails.php' );
	}

	public function get_form_id() {
		return $this->form_id;
	}

	public function get_form() {
		return $this->form;
	}

	/**
	 * Get the form name from the database
	 *
	 * @return string The form name or 'Default' as fallback
	 */
	private function get_form_name() {
		// check to make sure class exists
		if ( ! class_exists( '\WPEventGenius\Standard\Database\StandardDatabase' ) ) {
			return __( 'Default', 'event-genius' );
		}

		if ( empty( $this->form_id ) || $this->form_id === 1 ) {
			return __( 'Default', 'event-genius' );
		}

		$database = new \WPEventGenius\Standard\Database\StandardDatabase();
		$form = $database->get_form_by_id( $this->form_id );
		
		if ( $form && ! empty( $form['name'] ) ) {
			return $form['name'];
		}
		
		// Fallback to 'Default' if form not found or name is empty
		return __( 'Default', 'event-genius' );
	}

	/**
	 * Hook called before settings sections are rendered
	 * Can be overridden by child classes to add content before settings sections
	 */
	public function before_settings_sections() {
		// Base implementation does nothing
		// Child classes can override this method to add content
	}

	/**
	 * Get the action button HTML for the forms page
	 * 
	 * Applies a filter to allow other services to add buttons (e.g., upsell buttons)
	 * 
	 * @return string Action button HTML
	 */
	public function action_button() {
		$button_html = '';
		
		/**
		 * Filter the action button HTML for the form builder page
		 * 
		 * @param string $button_html The button HTML
		 * @param BasePage $this The current page instance
		 */
		$button_html = apply_filters( 'evge_form_builder_action_button', $button_html, $this );
		
		if ( ! empty( $button_html ) ) {
			echo $button_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

}
