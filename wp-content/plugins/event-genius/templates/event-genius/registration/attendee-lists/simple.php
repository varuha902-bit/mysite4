<?php
/**
 * Simple Attendee List Template
 * 
 * This template displays a simplified list of attendees in a grid layout.
 * It shows the total number of attendees and provides a "Show Full List" option
 * when there are more attendees to display.
 * 
 * The template handles:
 * - Attendee count display with icon
 * - Grid layout for attendee names
 * - Load more functionality
 * 
 * @package WPEventGenius
 * @since 1.0.0
 * 
 * @var array $args Template arguments including event_id, template, status, and offset
 * @var array $attendees Array of attendee data
 * @var int $total_registrations Total number of registrations
 * @var bool $has_more Whether there are more attendees to load
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="evge-attendee-list evge-attendee-list-simple" 
	data-event-id="<?php echo esc_attr( $args['event_id'] ); ?>"
	data-template="<?php echo esc_attr( $args['template'] ); ?>"
	data-status="<?php echo esc_attr( is_array( $args['status'] ) ? $args['status'][0] : $args['status'] ); ?>"
	data-per-load="<?php echo esc_attr( isset( $args['per_load'] ) ? absint( $args['per_load'] ) : 20 ); ?>"
	data-simple-format="<?php echo esc_attr( isset( $args['simple_format'] ) ? $args['simple_format'] : '' ); ?>"
	role="region"
	aria-label="<?php esc_attr_e( 'Attendee List', 'event-genius' ); ?>">

	<div class="evge-attendee-count" role="status">
		<?php
		\WPEventGenius\Common\Utils\Icon::output( 'person' );
		/* translators: %s: Number of confirmed attendees */
		echo wp_kses_post( sprintf( 
			_n( '%s Attendee', '%s Attendees', $total_registrations, 'event-genius' ),
			number_format_i18n( $total_registrations )
		) );
		?>
	</div>

	<div class="evge-attendee-grid" role="list">
		<ul class="evge-attendee-list-items evge-attendee-list-items-grid" role="list">
			<?php 
			$per_load = isset( $args['per_load'] ) ? absint( $args['per_load'] ) : 20;
			$index = 0;
			foreach ( $attendees as $attendee ) {
				$index++;
				// Hide attendees beyond the initial per_load number
				$is_hidden = $index > $per_load;
				$hidden_class = $is_hidden ? ' evge-attendee-hidden' : '';
				$hidden_attr = $is_hidden ? ' style="display: none;"' : '';
				?>
				<li class="evge-attendee evge-attendee-grid-item<?php echo esc_attr( $hidden_class ); ?>"<?php echo $hidden_attr; ?>>
					<?php 
					// Use custom format if provided in args, otherwise use filter/default
					$default_format = '{first} {last}';
					$custom_format = isset( $args['simple_format'] ) && ! empty( $args['simple_format'] ) ? $args['simple_format'] : null;
					$name_format = $custom_format ? $custom_format : apply_filters( 'evge_simple_attendee_name_format', $default_format, $attendee );
					echo esc_html(
						$attendee['placeholders']->replace( $name_format )
					); 
					?>
				</li>
				<?php
			}
			?>
		</ul>
	</div>

	<?php if ( $has_more ) : ?>
		<div class="evge-load-more evge-show-link-wrap">
			<a href="#" class="evge-load-more-link" role="button" aria-expanded="false" aria-controls="evge-attendee-list-items">
				<?php 
				esc_html_e( 'Show Full List', 'event-genius' );
				\WPEventGenius\Common\Utils\Icon::output( 'down-carat' );
				?>
			</a>
		</div>
	<?php endif; ?>
</div> 