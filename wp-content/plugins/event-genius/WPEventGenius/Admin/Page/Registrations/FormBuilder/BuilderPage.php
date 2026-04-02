<?php
namespace WPEventGenius\Admin\Page\Registrations\FormBuilder;

use WPEventGenius\Common\Registration\Field\FieldHandler;
use WPEventGenius\Common\Registration\Form\Form;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class BuilderPage extends BasePage {

	protected $active_subtab = 'builder';

	public function __construct() {
	}

	public function page_title() {
		return __( 'Form Builder', 'event-genius' );
	}


	public function content() {
		$form_id = $this->form_id;
		$field_handler = $this->field_handler;
		$form = $this->form;
		$current_fields = $this->form->get_fields();
		include_once( EVGE_ADMIN_TEMPLATE_PATH . 'evge/registrations/form-builder/builder.php' );
	}

	public function get_form_id() {
		return $this->form_id;
	}

	public function get_form() {
		return $this->form;
	}


}
