<?php
/**
 * Full Attendee List Template
 * 
 * This template displays a full attendee list in a table format including:
 * - Table header with form field labels
 * - Attendee rows with their registration data
 * - Load more functionality for pagination
 * 
 * @var array  $args      Template arguments including event_id, template, status, and offset
 * @var array  $attendees Array of attendee objects
 * @var object $form      Registration form object
 * @var bool   $has_more  Whether there are more attendees to load
 * 
 * @package WPEventGenius
 * @since 1.0.0
 */

use WPEventGenius\Common\Utils\Icon;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$list_class = 'evge-attendee-list evge-attendee-list-full';
?>

<div class="<?php echo esc_attr( $list_class ); ?>"
	data-event-id="<?php echo esc_attr( $args['event_id'] ); ?>"
	data-template="<?php echo esc_attr( $args['template'] ); ?>"
	data-status="<?php echo esc_attr( is_array( $args['status'] ) ? $args['status'][0] : $args['status'] ); ?>"
	data-offset="<?php echo esc_attr( $args['offset'] + count( $attendees ) ); ?>"
	data-per-load="<?php echo esc_attr( isset( $args['per_load'] ) ? absint( $args['per_load'] ) : 20 ); ?>"
	role="region"
	aria-label="<?php esc_attr_e( 'Attendee List', 'event-genius' ); ?>">

	<?php
	// Display attendee count if available
	if ( isset( $total_registrations ) ) {
		?>
		<div class="evge-attendee-count" role="status">
			<?php
			Icon::output( 'person' );
			/* translators: %s: Number of confirmed attendees */
			echo wp_kses_post( sprintf( 
				_n( '%s Attendee', '%s Attendees', $total_registrations, 'event-genius' ),
				number_format_i18n( $total_registrations )
			) );
			?>
		</div>
		<?php
	}
	?>

	<div class="evge-attendee-list-grid-wrapper">
		<div class="evge-attendee-list-grid-header">
			<a href="#" class="evge-grid-nav evge-grid-nav-prev" role="button" aria-label="<?php esc_attr_e( 'Show previous columns', 'event-genius' ); ?>" tabindex="0" style="display: none;">
				<?php Icon::output( 'left-carat' ); ?>
			</a>
			<div class="evge-attendee-list-grid-header-inner">
				<?php foreach ( $fields as $index => $field ) : ?>
					<div class="evge-grid-header-cell" data-field-slug="<?php echo esc_attr( $field->get_slug() ); ?>" data-field-index="<?php echo esc_attr( $index ); ?>">
						<a href="#" class="evge-grid-header-sort" data-field-slug="<?php echo esc_attr( $field->get_slug() ); ?>" role="button" aria-label="<?php echo esc_attr( sprintf( __( 'Sort by %s', 'event-genius' ), $field->get_label() ) ); ?>" tabindex="0">
							<span class="evge-grid-header-label"><?php echo esc_html( $field->get_label() ); ?></span>
							<span class="evge-grid-header-sort-icon" aria-hidden="true"></span>
						</a>
					</div>
				<?php endforeach; ?>
			</div>
			<a href="#" class="evge-grid-nav evge-grid-nav-next" role="button" aria-label="<?php esc_attr_e( 'Show next columns', 'event-genius' ); ?>" tabindex="0" style="display: none;">
				<?php Icon::output( 'right-carat' ); ?>
			</a>
		</div>
		
		<div class="evge-attendee-list-grid-body">
			<?php 
			$per_load = isset( $args['per_load'] ) ? absint( $args['per_load'] ) : 20;
			$index = 0;
			foreach ( $attendees as $attendee ) {
				$index++;
				// Hide attendees beyond the initial per_load number
				$is_hidden = $index > $per_load;
				
				EVGE()->template_manager()->get_template(
					'registration/attendee-lists/partials/full-attendee-item.php',
					[
						'attendee' => $attendee,
						'form'     => $form,
						'fields'   => $fields,
						'is_hidden' => $is_hidden,
						'attendee_index' => $index
					]
				);
			}
			?>
		</div>
	</div>

	<?php if ( $has_more ) : ?>
		<div class="evge-load-more evge-show-link-wrap">
			<a href="#" class="evge-load-more-link" role="button" aria-expanded="false" aria-controls="evge-attendee-list-items">
				<?php 
				esc_html_e( 'Show Full List', 'event-genius' );
				Icon::output( 'down-carat' );
				?>
			</a>
		</div>
	<?php endif; ?>
</div> 