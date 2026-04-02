<?php
/**
 * Pagination Template
 * 
 * This template displays pagination controls for event listings including:
 * - Previous/Next navigation
 * - Numbered page links
 * - Ellipsis for skipped pages
 * 
 * 
 * @var object $calendar_display The calendar display object
 * @var array  $pagination      Pagination settings including total pages, current page, and items per page
 * @package WPEventGenius
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = $calendar_display->get_settings();
$pagination = $settings['pagination'];

// If there is no pagination or only one page, return.
if ( empty( $pagination ) || $pagination['total'] <= 1 ) {
	return;
}

$current    = max( 1, min( $pagination['total'], $pagination['paged'] ) );
$prev_page  = $current - 1;
$next_page  = $current + 1;
?>

<nav class="evge-pagination">
	<?php if ( $prev_page > 0 ) : ?>
		<a href="#" class="evge-pagination-prev" data-page="<?php echo esc_attr( $prev_page ); ?>">
			<?php echo esc_html__( 'Previous', 'event-genius' ); ?>
		</a>
	<?php endif; ?>

	<div class="evge-pagination-numbers">
		<?php
		$start = max( 1, $current - 2 );
		$end   = min( $pagination['total'], $current + 2 );
		
		if ( $start > 1 ) {
			echo '<a href="#" data-page="1">1</a>';
			if ( $start > 2 ) {
				echo '<span class="evge-pagination-dots">...</span>';
			}
		}
		
		for ( $i = $start; $i <= $end; $i++ ) {
			printf(
				'<a href="#" class="%s" data-page="%d">%d</a>',
				$i === $current ? 'evge-pagination-current' : '',
				esc_attr( $i ),
				esc_html( $i )
			);
		}
		
		if ( $end < $pagination['total'] ) {
			if ( $end < $pagination['total'] - 1 ) {
				echo '<span class="evge-pagination-dots">...</span>';
			}
			echo '<a href="#" data-page="' . esc_attr( $pagination['total'] ) . '">' . esc_html( $pagination['total'] ) . '</a>';
		}
		?>
	</div>

	<?php if ( $next_page <= $pagination['total'] ) : ?>
		<a href="#" class="evge-pagination-next" data-page="<?php echo esc_attr( $next_page ); ?>">
			<?php echo esc_html__( 'Next', 'event-genius' ); ?>
		</a>
	<?php endif; ?>
</nav> 