<?php
/**
 * Filter bar template for calendar views
 * 
 * This template provides the search and filter functionality for calendar views.
 * It includes search input, venue filter, time filter, and view type selector.
 * 
 * @param string $view Current view type
 * @param array $settings Calendar settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\Common\Utils\Icon;
use WPEventGenius\Common\Utils\Templater;

$templater = new Templater();

// Get current search query and settings
$settings = $calendar_display->get_settings();
$search_query = ! empty( $settings['search'] ) ? $settings['search'] : '';
$view = $settings['view'];

// Count visible filters in the first group (search, venue, category, tag, time_filter) for "more" button.
$first_group_keys = [ 'search', 'venue', 'category', 'tag', 'time_filter' ];
$visible_filter_count = 0;
foreach ( $first_group_keys as $key ) {
	if ( $calendar_display->should_show_filter_bar_item( $key ) ) {
		++$visible_filter_count;
	}
}
$show_more_filters_button = $visible_filter_count > 3;
$filter_index = 0;
?>

<form 
	method="get" 
	class="evge-filter-bar-form" 
	action="<?php echo esc_url( get_post_type_archive_link( EVGE_EVENT_POST_TYPE ) ); ?>">
	<div class="evge-calendar-filters<?php echo esc_attr( $calendar_display->get_filter_bar_container_class() ); ?>" role="search">
		<div class="evge-filter-group">
			<?php
			$item_more_class = function () use ( &$filter_index ) {
				$class = 'evge-filter-bar-item';
				if ( $filter_index >= 3 ) {
					$class .= ' evge-filter-item-more';
				}
				++$filter_index;
				return $class;
			};
			?>
			<?php if ( $calendar_display->should_show_filter_bar_item( 'search' ) ) : ?>
				<span class="<?php echo esc_attr( $item_more_class() ); ?>">
					<input 
						type="text" 
						class="evge-search-input" 
						name="evge_search" 
						value="<?php echo esc_attr( $search_query ); ?>"
						placeholder="<?php esc_attr_e( 'Search events', 'event-genius' ); ?>" 
						aria-label="<?php esc_attr_e( 'Search events', 'event-genius' ); ?>">
				</span>
			<?php endif; ?>

			<?php if ( $calendar_display->should_show_filter_bar_item( 'venue' ) ) :
				$venue_query = new \WPEventGenius\Common\Queries\VenueQuery();
				$venue_query->add_wp_query();
				$venues = $venue_query->get_venues();
				$selected_venue = ! empty( $settings['venue_id'] ) ? $settings['venue_id'] : '';
				?>
				<span class="<?php echo esc_attr( $item_more_class() ); ?>">
					<select 
						name="evge_venue_id" 
						class="evge-venue-select" 
						aria-label="<?php esc_attr_e( 'Filter by venue', 'event-genius' ); ?>">
						<option value=""><?php esc_html_e( 'All Venues', 'event-genius' ); ?></option>
						<?php
						foreach ( $venues as $venue ) {
							printf(
								'<option value="%d" %s>%s</option>',
								esc_attr( $venue->ID ),
								selected( $selected_venue, $venue->ID, false ),
								esc_html( $venue->post_title )
							);
						}
						?>
					</select>
				</span>
			<?php endif; ?>

			<?php if ( $calendar_display->should_show_filter_bar_item( 'category' ) ) :
				$selected_category = ! empty( $settings['category_id'] ) ? $settings['category_id'] : '';
				$term_visibility_args = $calendar_display->get_filter_bar_category_terms_args();

				$categories_args = [
					'taxonomy'   => EVGE_EVENT_CATEGORY_TYPE,
					'hide_empty' => false,
				];
				if ( ! empty( $term_visibility_args ) ) {
					$categories_args = array_merge( $categories_args, $term_visibility_args );
				}

				$categories = get_terms( $categories_args );
				if ( ! is_wp_error( $categories ) ) :
					?>
					<span class="<?php echo esc_attr( $item_more_class() ); ?>">
						<select 
							name="evge_category_id" 
							class="evge-category-select" 
							aria-label="<?php esc_attr_e( 'Filter by category', 'event-genius' ); ?>">
							<option value=""><?php esc_html_e( 'All Categories', 'event-genius' ); ?></option>
							<?php
							foreach ( $categories as $category ) {
								printf(
									'<option value="%d" %s>%s</option>',
									esc_attr( $category->term_id ),
									selected( $selected_category, $category->term_id, false ),
									esc_html( $category->name )
								);
							}
							?>
						</select>
					</span>
				<?php endif;
			endif; ?>

			<?php if ( $calendar_display->should_show_filter_bar_item( 'tag' ) ) :
				$selected_tag = ! empty( $settings['tag_id'] ) ? $settings['tag_id'] : '';
				$term_visibility_args = $calendar_display->get_filter_bar_tag_terms_args();

				$tags_args = [
					'taxonomy'   => EVGE_EVENT_TAG_TYPE,
					'hide_empty' => false,
				];
				if ( ! empty( $term_visibility_args ) ) {
					$tags_args = array_merge( $tags_args, $term_visibility_args );
				}

				$tags = get_terms( $tags_args );
				if ( ! is_wp_error( $tags ) ) :
					?>
					<span class="<?php echo esc_attr( $item_more_class() ); ?>">
						<select 
							name="evge_tag_id" 
							class="evge-tag-select" 
							aria-label="<?php esc_attr_e( 'Filter by tag', 'event-genius' ); ?>">
							<option value=""><?php esc_html_e( 'All Tags', 'event-genius' ); ?></option>
							<?php
							foreach ( $tags as $tag ) {
								printf(
									'<option value="%d" %s>%s</option>',
									esc_attr( $tag->term_id ),
									selected( $selected_tag, $tag->term_id, false ),
									esc_html( $tag->name )
								);
							}
							?>
						</select>
					</span>
				<?php endif;
			endif; ?>

			<?php if ( $calendar_display->should_show_filter_bar_item( 'time_filter' ) ) : ?>
				<span class="<?php echo esc_attr( $item_more_class() ); ?>">
					<select 
						name="evge_time_filter" 
						class="evge-time-select" 
						aria-label="<?php esc_attr_e( 'Filter by time', 'event-genius' ); ?>">
						<?php
						$selected_time = ! empty( $settings['time_filter'] ) ? $settings['time_filter'] : 'upcoming';
						?>
						<option value="upcoming" <?php selected( $selected_time, 'upcoming' ); ?>>
							<?php esc_html_e( 'Upcoming', 'event-genius' ); ?>
						</option>
						<option value="past" <?php selected( $selected_time, 'past' ); ?>>
							<?php esc_html_e( 'Past', 'event-genius' ); ?>
						</option>
					</select>
				</span>
			<?php endif; ?>

			<?php if ( $show_more_filters_button ) : ?>
				<button type="button" class="evge-filter-more-button" title="<?php esc_attr_e( 'Show more filters', 'event-genius' ); ?>" aria-label="<?php esc_attr_e( 'Show more filters', 'event-genius' ); ?>">
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG from Icon helper
					echo Icon::get( 'ellipsis' );
					?>
				</button>
			<?php endif; ?>
		</div>

		<?php if ( $calendar_display->should_show_filter_bar_item( 'display' ) ) : ?>
			<div class="evge-filter-group">
				<?php
				$views = [
					'list'  => esc_html__( 'List', 'event-genius' ),
					'grid'  => esc_html__( 'Grid', 'event-genius' ),
					'month' => esc_html__( 'Month', 'event-genius' ),
				];
				?>
				<select 
					name="evge_view" 
					class="evge-view-select" 
					aria-label="<?php esc_attr_e( 'Calendar view', 'event-genius' ); ?>">
					<?php foreach ( $views as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $view, $value ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
		<?php endif; ?>
	</div>
</form>