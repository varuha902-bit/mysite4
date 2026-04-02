<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$submissions_active = empty( $_GET['subsubtab'] ) || 'submissions' === $_GET['subsubtab'] ?  ' nav-tab-active' : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$payments_active = ! empty( $_GET['subsubtab'] ) && 'payments' === $_GET['subsubtab'] ? ' nav-tab-active' : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$active_subtab = ! empty( $_GET['subsubtab'] ) ? sanitize_key( wp_unslash( $_GET['subsubtab'] ) ) : 'submissions';
$sub_navigation_args = array(
	'active_subtab' => $active_subtab,
	'nav_items' => array(
		array(
			'id' => 'submissions',
			'title' => __( 'Submissions', 'event-genius' ),
			'url' => $page->nav_link( 'evge-registrations', array( 'id' => $event->ID, 'tab' => 'registrations', 'subtab' => 'single', 'subsubtab' => 'submissions' ) )
		),
	)
);

if ( ! evge_is_free_version() ) {
	// Convert WP_Post to EventPost to check payment acceptance
	$event_post = new \WPEventGenius\Common\Event\EventPost( $event->ID );
	if ( $event_post->get_accept_payments() ) {
		$sub_navigation_args['nav_items'][] = array(
			'id' => 'payments',
			'title' => __( 'Payments', 'event-genius' ),
			'url' => $page->nav_link( 'evge-registrations', array( 'id' => $event->ID, 'tab' => 'registrations', 'subtab' => 'single', 'subsubtab' => 'payments' ) )
		);
	}
}

if ( evge_is_standard_tier() ) {
	$sub_navigation_args['nav_items'][] = array(
		'id' => 'attendance',
		'title' => __( 'Attendance', 'event-genius' ),
		'url' => $page->nav_link( 'evge-registrations', array( 'id' => $event->ID, 'tab' => 'registrations', 'subtab' => 'single', 'subsubtab' => 'attendance' ) )
	);
}

// Apply filter to allow free version to add upsell tabs
$sub_navigation_args = apply_filters( 'evge_single_registration_sub_navigation_args', $sub_navigation_args, $event );
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