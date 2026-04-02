<?php
namespace WPEventGenius\Admin\Page;

use WPEventGenius\Admin\BaseAdminPage;
use WPEventGenius\Common\Utils\DateFormatter;
use WPEventGenius\Common\Utils\Formatter;
use WPEventGenius\Common\Utils\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BasePage extends BaseAdminPage {

    protected $active_tab = '';

	public function __construct() {
        // Hook to ensure editor scripts are loaded on specific pages
        add_action('admin_enqueue_scripts', array($this, 'maybe_enqueue_editor_scripts'));
	}

	public function build() {
	}

	public function render( $active_tab ) {
        $this->header();
		$this->navigation( $active_tab );
		$this->before_subnav();
		$this->sub_navigation( $this->sub_navigation_args() );
		$this->content();
        $this->footer();
	}

    public function page_title() {
		return '';
	}

	public function action_button() {
    }

    public function before_subnav() {

    }

	public function ajax_atts( $action, $autotrigger = false ) {
		$atts = array();

		$atts['data-evge-ajax'] = wp_json_encode( array(
			'action' => $action
		) );

		if ( $autotrigger ) {
			$atts['data-autotrigger'] = 'true';
		}

		$atts_string = '';
		foreach ( $atts as $key => $value ) {
			$atts_string .= $key . '="' . esc_attr( $value ) . '" ';
		}

		return $atts_string;
	}


	private function generate_nav_link_html($activeTab, $tabName, $pageTitle) {
		$class = $activeTab === $tabName ? 'nav-tab nav-tab-active' : 'nav-tab';
		$url = esc_url(get_admin_url(null, "admin.php?page=evge-$tabName"));
		return "<a href=\"$url\" class=\"$class\">$pageTitle</a>";
	}

	public function navigation( $active_tab ) {
		if ( str_contains( $active_tab, 'registration' ) ) {
			$this->registration_navigation( $active_tab );
		} else {
			$this->event_navigation( $active_tab );
		}
	}

	public function navigation_html( $navigation_args ) {
        ?>
        <div class="evge-main-admin-nav">
            <div class="evge-main-admin-nav-inner">
                <?php
                foreach ( $navigation_args['nav_items'] as $nav_item ) :
                    $active_tab_class = $navigation_args['active_tab'] === $nav_item['id'] ? ' evge-nav-tab-active' : '';
                    
                    // Check if this nav item should trigger a modal
                    $is_modal_trigger = ! empty( $nav_item['modal_trigger'] ) && ! empty( $nav_item['modal_ajax_data'] );
                    
                    if ( $is_modal_trigger ) :
                        $ajax_data = $nav_item['modal_ajax_data'];
                        $modal_settings = ! empty( $nav_item['modal_settings'] ) ? $nav_item['modal_settings'] : array();
                        ?>
                        <div class="evge-main-admin-nav-item">
                            <a 
                                data-evge-id="<?php echo esc_attr( $nav_item['id'] ); ?>" 
                                href="#" 
                                class="evge-nav-tab evge-modal-trigger<?php echo esc_attr( $active_tab_class ); ?>"
                                data-evge-modal-content="ajax"
                                data-evge-ajax="<?php echo esc_attr( wp_json_encode( $ajax_data ) ); ?>"
                                data-evge-modal-settings="<?php echo esc_attr( wp_json_encode( $modal_settings ) ); ?>"
                            >
                                <?php echo esc_html( $nav_item['title'] ); ?> <span class="evge-upsell-pro-badge"><?php esc_html_e( 'Pro', 'event-genius' ); ?></span>
                            </a>
                        </div>
                    <?php else : ?>
                        <div class="evge-main-admin-nav-item">
                            <a data-evge-id="<?php echo esc_attr( $nav_item['id'] ); ?>" href="<?php echo esc_url( $nav_item['url'] ); ?>" class="evge-nav-tab<?php echo esc_attr( $active_tab_class ); ?>"><?php echo esc_html( $nav_item['title'] ); ?></a>
                        </div>
                    <?php endif; ?>
                <?php
                endforeach;
                ?>
            </div>
        </div>
        <?php
	}

    /**
     * Filter navigation items based on user capabilities
     * 
     * @param array $navigation_args The navigation arguments array
     * @return array Filtered navigation arguments
     */
    protected function filter_navigation_by_capability($navigation_args) {
        if (!isset($navigation_args['nav_items']) || !is_array($navigation_args['nav_items'])) {
            return $navigation_args;
        }

        $navigation_args['nav_items'] = array_filter($navigation_args['nav_items'], function($item) {
            // If no capability is specified, show the item
            if (!isset($item['capability'])) {
                return true;
            }
            return current_user_can($item['capability']);
        });

        return $navigation_args;
    }

    public function sub_navigation_args() {
    }

    public function sub_navigation( $sub_navigation_args ) {
        if ( empty( $sub_navigation_args ) ) {
            return;
        }
	    ?>
        <div class="evge-main-admin-subnav">
            <div class="evge-main-admin-subnav-inner">

			    <?php
			    foreach ( $sub_navigation_args['nav_items'] as $nav_item ) :
				    $active_tab_class = $sub_navigation_args['active_subtab'] === $nav_item['id'] ? ' evge-subnav-tab-active' : '';
				    
				    // Check if this nav item should trigger a modal
				    $is_modal_trigger = ! empty( $nav_item['modal_trigger'] ) && ! empty( $nav_item['modal_ajax_data'] );
				    
				    if ( $is_modal_trigger ) :
					    $ajax_data = $nav_item['modal_ajax_data'];
					    $modal_settings = ! empty( $nav_item['modal_settings'] ) ? $nav_item['modal_settings'] : array();
					    ?>
					    <div class="evge-main-admin-subnav-item">
						    <a href="#" 
						       class="evge-subnav-tab evge-modal-trigger<?php echo esc_attr( $active_tab_class ); ?>"
						       data-evge-modal-content="ajax"
						       data-evge-ajax="<?php echo esc_attr( wp_json_encode( $ajax_data ) ); ?>"
						       data-evge-modal-settings="<?php echo esc_attr( wp_json_encode( $modal_settings ) ); ?>">
						       <?php echo esc_html( $nav_item['title'] ); ?>
						       <?php if ( ! empty( $nav_item['pro_badge'] ) ) : ?>
							       <span class="evge-upsell-pro-badge"><?php esc_html_e( 'Pro', 'event-genius' ); ?></span>
						       <?php endif; ?>
					       </a>
					    </div>
				    <?php else : ?>
					    <div class="evge-main-admin-subnav-item">
						    <a href="<?php echo esc_url( $nav_item['url'] ); ?>" class="evge-subnav-tab<?php echo esc_attr( $active_tab_class ); ?>"><?php echo esc_html( $nav_item['title'] ); ?></a>
					    </div>
				    <?php endif; ?>
			    <?php
			    endforeach;
			    ?>
            </div>
        </div>
	    <?php
    }

	public function registration_navigation( $active_tab ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$active_tab = ! empty( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview';
		if ( $active_tab === 'single' ) {
			return;
		}
		$nav_items = array(
			array(
				'tab_name' => 'overview',
				'title' => __( 'Registrations', 'event-genius' )
			),
            array(
				'tab_name' => 'forms',
				'title' => __( 'Registration Forms', 'event-genius' )
			),
		)
		?>
		<h2 class="nav-tab-wrapper">
			<?php foreach( $nav_items as $nav_item ) :
				$active_tab_class = $active_tab === $nav_item['tab_name'] ? ' nav-tab-active' : '';
				?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=evge-registrations&tab=' . $nav_item['tab_name'] ) ); ?>" class="nav-tab<?php echo esc_attr( $active_tab_class ); ?>"><?php echo esc_html( $nav_item['title'] ); ?></a>
			<?php endforeach; ?>
		</h2>
		<?php
	}

	public function event_navigation( $active_tab ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$active_tab = ! empty( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$active_tab_class = ! empty( $_GET['page'] ) && 'evge-events' === $_GET['page'] ? ' nav-tab-active' : '';

		$nav_items = array(
			array(
				'tab_name' => 'general',
				'title' => __( 'Settings', 'event-genius' )
			),
			array(
				'tab_name' => 'email',
				'title' => __( 'More Event', 'event-genius' )
			),
			array(
				'tab_name' => 'payments',
				'title' => __( 'Payments', 'event-genius' )
			),
			array(
				'tab_name' => 'text',
				'title' => __( 'Text & Translation', 'event-genius' )
			)
		)
		?>
		<h2 class="nav-tab-wrapper">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=evge-events' ) ); ?>" class="nav-tab<?php echo esc_attr( $active_tab_class ); ?>"><?php echo esc_html( __( 'Events', 'event-genius' ) ); ?></a>

			<?php foreach( $nav_items as $nav_item ) :
				$active_tab_class = $active_tab === $nav_item['tab_name'] ? ' nav-tab-active' : '';
				?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=evge-event-settings&tab=' . $nav_item['tab_name'] ) ); ?>" class="nav-tab<?php echo esc_attr( $active_tab_class ); ?>"><?php echo esc_html( $nav_item['title'] ); ?></a>
			<?php endforeach; ?>
		</h2>
		<?php
	}

    public function nav_link( $page, $additional_params = array() ) {
        $params = array_merge( $this->base_params(), $additional_params );

        $params['page'] = $page;
        return add_query_arg( $params, admin_url( 'admin.php' ) );
    }

    public function base_params() {
        $sanitized_params = array();

        foreach ( $this->allowed_query_params_and_sanitization() as $param => $type ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if ( isset( $_REQUEST[ $param ] ) ) {
				// phpcs:ignore 
                $sanitized_params[ $param ] = $this->sanitize_param( $_REQUEST[ $param ], $type );
            }

        }

        return $sanitized_params;
    }

    public function exclamation_notice( $message ) {
        ?>
        <div class="evge-exclamation-notice">
            <div class="evge-notice-icon">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="8" cy="8" r="8" fill="#AAAAAA"/>
                    <circle cx="8" cy="4" r="1" fill="white"/>
                    <rect x="7" y="7" width="2" height="6" fill="white"/>
                </svg>
            </div>
            <p><?php echo wp_kses_post( $message ); ?></p>
        </div>
        <?php
    }

	public function content() {
		return '';
	}

	public function escaped_output( $registration, $key, $event_form, $context = 'table' ) {
        return Formatter::escaped_output( $registration, $key, $event_form, $context );
	}

    public function registration_list_columns() {

        $return_fields = array(
            'identity' => __( 'Identity', 'event-genius' ),
            'registration_date' => __( 'Date', 'event-genius' ),
            'status' => __( 'Status', 'event-genius' ),
            'email' => __( 'Email', 'event-genius' ),
            'event' => __( 'Event', 'event-genius' ),
        );


        return apply_filters( 'evge_registration_list_fields', $return_fields );
    }

	public function event_list_columns() {

		$return_fields = array(
			'title' => __( 'Title', 'event-genius' ),
			'date' => __( 'Date', 'event-genius' ),
			'venue' => __( 'Venue', 'event-genius' ),
			'attendance' => __( 'Attendance', 'event-genius' ),
			'tools' => __( 'Tools', 'event-genius' ),
		);


		return apply_filters( 'evge_event_list_fields', $return_fields );
	}


	public function table_columns( $event_form, $args = array(), $subtab = '' ) {
		$fields = ( $event_form !== null && is_object( $event_form ) ) ? $event_form->get_fields() : array();
		$return_fields = array();

		$number = isset( $args['number'] ) ? absint( $args['number'] ) : 100;
		$offset = isset( $args['offset'] ) ? absint( $args['offset'] ) : 0;

		if ( empty( $args['no_identity'] ) ) {
			$return_fields = array(
				'identity' => __( 'Identity', 'event-genius' ),
			);
		} else {
			$return_fields = [];
		}
		if ( $subtab !== 'payments' && $subtab !== 'attendance' ) {
			$return_fields['registration_date'] = __( 'Date', 'event-genius' );
		}

		if ( $subtab === 'payments' ) {
			$payment_fields = Utils::get_payment_columns();

			foreach ( $payment_fields as $key => $label ) {
				if ( $key === 'currency_code' ) {
					continue;
				}
				$return_fields[ $key ] = $label;
			}
		} elseif ( $subtab === 'attendance' ) {
			$attendance_fields = Utils::get_attendance_columns();

			foreach ( $attendance_fields as $key => $label ) {
				$return_fields[ $key ] = $label;
			}
		} else {
			if ( ! empty( $args['include'] ) ) {
				$include = $args['include'];
				$include = is_array( $include ) ? $include : array( $include );

				foreach ( $include as $key ) {
					if ( $key === 'status' ) {
						$return_fields[ $key ] = __( 'Status', 'event-genius' );
					}
					if ( $key === 'quantity_cost' ) {
						$return_fields['quantity_cost'] = __( 'Quantity & Cost', 'event-genius' );
					}
				}
			}

			foreach ( $fields as $field ) {
				if ( in_array( $field->get_slug(), array( 'first', 'last' ), true ) && empty( $args['no_identity'] ) ) {
					continue;
				}
				if ( count( $return_fields ) >= $number ) {
					continue;
				}

				$return_fields[ $field->get_slug() ] = $field->get_label();
			}



		}


		return $return_fields;
	}

    protected function allowed_query_params_and_sanitization() {
        return array(
           'tab' => 'key',
           'view' => 'key',
           'off' => 'key',
           'start' => 'date',
           'startt' => 'int',
           'page' => 'key',
           'paged' => 'int',
           'cat' => 'int',
           'tag' => 'int',
           'event' => 'int',
           'id' => 'int',
           'rtype' => 'key',
           'qtype' => 'key',
           'with' => 'key',
            's' => 'text',
            'stype' => 'key',
            'post_status' => 'key',
            'registration_status' => 'key',
            'registration_id' => 'int',
            'group' => 'int',
        );
    }

    protected function sanitize_param( $param, $type ) {
        switch ( $type ) {
            case 'key':
                return sanitize_key( $param );
	        case 'text':
		        return sanitize_text_field( wp_unslash( $param ) );
            case 'int':
                return absint( $param );
            case 'date':
                return gmdate( 'Y-m-d H:i:s', strtotime( $param ) );
        }
    }

    protected function build_event_query_args() {
        $args = array();
        if ( ! empty( $this->sanitized_params['s'] ) && ! empty( $this->sanitized_params['stype'] ) && $this->sanitized_params['stype'] === 'events' ) {
	        $args['s'] = $this->sanitized_params['s'];
        }

	    if ( ! empty( $this->sanitized_params['qtype'] ) ) {
		    $args['qtype'] = $this->sanitized_params['qtype'];
	    }

	    if ( ! empty( $this->sanitized_params['paged'] ) ) {
		    $args['paged'] = $this->sanitized_params['paged'];
	    }

		if ( ! empty( $this->sanitized_params['with'] ) ) {
		    $args['with'] = $this->sanitized_params['with'];
	    }

	    if ( empty( $this->sanitized_params['post_status'] ) ) {
		    $args['post_status'] = 'publish';
	    } else {
            $args['post_status'] = $this->sanitized_params['post_status'];
	    }

	    if ( ! empty( $this->sanitized_params['tag'] ) ) {
		    $args['tag'] = $this->sanitized_params['tag'];
	    }

	    if ( ! empty( $this->sanitized_params['cat'] ) ) {
		    $args['cat'] = $this->sanitized_params['cat'];
	    }

        return $args;
    }

	protected function build_registration_query_args() {
		$args = array();
		if ( ! empty( $this->sanitized_params['s'] ) && ! empty( $this->sanitized_params['stype'] ) && $this->sanitized_params['stype'] === 'registrations' ) {
			$args['s'] = $this->sanitized_params['s'];
		}

		return $args;
	}

    public function header() {
        include_once( trailingslashit( EVGE_ADMIN_TEMPLATE_PATH ) . 'evge/partials/header.php');
    }

    protected function footer() {
	    include_once( trailingslashit( EVGE_ADMIN_TEMPLATE_PATH ) . 'evge/partials/footer.php');
    }

    
    /**
     * Enqueue editor scripts if on a page that uses the hidden rich editor.
     */
    public function maybe_enqueue_editor_scripts() {
        // Only enqueue on specific pages
        $current_page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        $allowed_pages = ['evge-all-events', 'evge-registrations'];
        
        if (!in_array($current_page, $allowed_pages)) {
            return;
        }

        // Ensure WordPress editor scripts are loaded
        wp_enqueue_editor();
        
        // Enqueue media scripts for media buttons and image functionality
        wp_enqueue_media();
        
        // Ensure TinyMCE plugins are available
        wp_enqueue_script('editor');
    }
}
