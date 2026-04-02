<?php
namespace WPEventGenius\Common\CustomPostTypes\Organizer;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

use WPEventGenius\Common\Utils\Templater;

class OrganizerSingle {

	public function __construct(){

	}

	public function init_custom_hooks() {
		add_filter( 'single_template', array( $this, 'maybe_alter_template' ), 10, 1 );
		add_filter( 'the_content', array( $this, 'maybe_alter_content' ), 10, 1 );

		add_action('wp_enqueue_scripts', array($this, 'enqueue'));
	}

	public function is_cpt_page() {
		return is_singular( EVGE_ORGANIZER_POST_TYPE );
	}

	public function get_template_type() {
		return 'default';
	}

	public function maybe_alter_template( $template ) {
		if ( ! $this->is_cpt_page() ) {
			return $template;
		}

		// For block themes, always return the default template
		if ( wp_is_block_theme() ) {
			return $template;
		}

		$template_type = $this->get_template_type();

		// For custom templates, check theme directory first
		if ( $template_type === 'custom' ) {
			$theme_template = locate_template( array(
				'event-genius/single-organizer.php',
				'event-genius/organizers/single/single-organizer.php'
			));
			if ( $theme_template ) {
				return $theme_template;
			}
		}

		// For default template or if custom template not found, use plugin template
		if ( $template_type === 'default' || ($template_type === 'custom' && !$theme_template) ) {
			$template_path = EVGE()->template_manager()->locate_template('organizers/single/single-organizer.php');
			if ($template_path) {
				return $template_path;
			}
			// Fallback to plugin template
			return trailingslashit( EVGE_PLUGIN_PATH ) . 'templates/event-genius/organizers/single/single-organizer.php';
		}

		// For theme template or fallback, use default template
		return $template;
	}

	public function maybe_alter_content( $content ) {
		if ( ! $this->is_cpt_page() ) {
			return $content;
		}

		// For block themes, always return the default content
		if ( wp_is_block_theme() ) {
			return $content;
		}

		// Request modal since organizer single pages use modal triggers
		EVGE()->modal_service()->request_modal();

		$template_type = $this->get_template_type();
		
		// For traditional themes, only modify content for theme template or when custom template isn't found
		if ( $template_type === 'theme' || 
			($template_type === 'custom' && !locate_template(array('event-genius/single-organizer.php', 'event-genius/organizers/single/single-organizer.php'))) ) {
			
			ob_start();
			
			$post_id = get_the_ID();
			$this->before_organizer_content($post_id);
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $content;
			$this->after_organizer_content($post_id);
			
			return ob_get_clean();
		}

		return $content;
	}

	public function before_organizer_content( $organizer_id = 0 ) {
		$template_type = $this->get_template_type();
		
		$template_path = EVGE()->template_manager()->locate_template('organizers/single/partials/content-before.php');
		if ($template_path) {
			include $template_path;
			return;
		}
		$templater = new Templater();
		?>
<div class="evge<?php echo esc_attr( $templater->evge_classes() ); ?>" data-evge-type="single-organizer">
	<div class="evge-content">
		<div id="evge-single-main" class="evge-organizers-single-main<?php echo esc_attr( $templater->classes() ); ?>">
		<?php
	}

	public function after_organizer_content( $organizer_id = 0 ) {
		$template_path = EVGE()->template_manager()->locate_template('organizers/single/partials/content-after.php');
		if ($template_path) {
			include $template_path;
			return;
		}
		?>
		</div>
	</div>
</div>
<?php
	}

	public function maybe_alter_query( $query ) {
		if ( ! $this->is_cpt_page() ) {
			return $query;
		}
		return $query;
	}

	public function enqueue( $screen ) {

	}

	public function maybe_add_modal() {
		if ( $this->is_cpt_page() ) {
			$template_path = EVGE()->template_manager()->locate_template('common/modal.php');
			if ($template_path) {
				include $template_path;
			}
		}
	}

}