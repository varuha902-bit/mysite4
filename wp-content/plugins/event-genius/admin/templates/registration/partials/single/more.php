<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\Common\Utils\Formatter;
use WPEventGenius\Common\Utils\Icon;

$table_columns = $page->table_columns( $event->details['form'], array( 'include' => array( 'status', 'quantity_cost' ) ), $subtab );
?>
<div class="evge-single-event-meta-wrap">
	<?php include EVGE_ADMIN_TEMPLATE_PATH . 'registration/partials/event-meta.php'; ?>
</div>


<div class="evge-event-card evge-subtab-<?php echo esc_attr( $subtab ); ?>">
    <div class="evge-event-card-inner">
	    <?php include EVGE_ADMIN_TEMPLATE_PATH . 'registration/partials/single/sub-navigation.php'; ?>
        <form method="post" id="evge-registration-form" action="">
            <input type="hidden" name="evge_event_id" value="<?php echo absint( $event->ID ); ?>">
            <input type="hidden" name="evge_subtab" value="<?php echo esc_attr( $subtab ); ?>">
	        <?php include( EVGE_ADMIN_TEMPLATE_PATH . 'registration/partials/single/search-and-filter-bar.php' ); ?>

        </form>

        <div class="evge-event-card-reg-table evge-reg-table">

            <table class="widefat striped evge-registrations-data">
                <thead>
                <tr>
                    <td class="manage-column check-column">
                        <label class="screen-reader-text" for="evge-select-all-0"><?php esc_html_e( 'Select All', 'event-genius' ); ?></label>
                        <input type="checkbox" id="evge-select-all-0">
                    </td>
                    <?php
                    $i = 0;
                    $group = 0;
                    $num_cols = count( $table_columns ) - 1; // Subtract 1 for the identity column.
                    foreach ( $table_columns as $key => $label ) :
                        if ( $key === 'identity' ) : ?>
                            <th><?php esc_html_e( 'Name', 'event-genius' ); ?></th>

                        <?php else:
                            if ( $i % 4 === 0 ) {
                                $group ++;
                                $arrow_left = '';
                                if ( $i !== 0 ) {
                                    $arrow_left = '<div class="evge-data-nav-wrap evge-left"><div class="evge-data-nav evge-arrow-left" data-next-index="' . ( $group - 1 ) . '">' . Icon::get( 'left-carat' ) . '</div></div>';
                                }
                            } else {
                                $arrow_left = '';
                            }
                            if ( ( $i + 1 ) % 4 === 0 ) {
                                $arrow_right = '<div class="evge-data-nav-wrap evge-right"><div class="evge-data-nav evge-arrow-right" data-next-index="' . ( $group + 1 ) . '">' . Icon::get( 'right-carat' ) . '</div></div>';
                            } else {
                                $arrow_right = '';
                            }
                            $i ++;
                            if ( $i === $num_cols ) {
                                $arrow_right = '';
                            }

                            ?>
                            <th class="evge-data-cell evge-data-group-<?php echo absint( $group ); ?>">
                                <?php 
                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                echo $arrow_left . esc_html( wp_unslash( $label ) ) . $arrow_right; 
                                ?>
                            </th>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody>
                <?php if ( ! empty( $event->registrations ) ) : ?>

			<?php foreach ( $event->registrations as $index => $registration ) :
				?>
				<tr class="evge-reg-row">

                    <th class="check-column">
                        <label class="screen-reader-text" for="evge-select-<?php echo esc_attr( $registration['id'] ); ?>"><?php echo esc_html( Formatter::identity( $registration ) ) ; ?></label>
                        <input type="checkbox" name="evge-registration[]" value="<?php echo esc_attr( $registration['id'] ); ?>" id="evge-select-<?php echo esc_attr( $registration['id'] ); ?>" class="evge-row-select">
                        <div class="locked-indicator"></div>
                    </th>
					<?php
					$group = 0;
                    $ii = 0;
                    foreach ( $table_columns as $key => $label ) :
						if ( $key === 'identity' ) : ?>
							<?php include( EVGE_ADMIN_TEMPLATE_PATH . 'registration/partials/identity.php' ); ?>

						<?php else:
							if ( $ii % 4 === 0 ) {
								$group++;
							}
							$td_class = 'evge-data-group-' . $group;
							$ii++;
                            ?>
							<td class="evge-data-cell <?php echo esc_attr( $td_class ); ?>"><?php 
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            $output = $page->escaped_output( $registration , $key, $event->details['form'] ); 
                            if ( $key === 'quantity_cost' && ! empty( $registration['payment_status'] ) ) {
                                $output = '<span class="evge-icon-text">' . $output . \WPEventGenius\Common\Utils\Formatter::get_payment_status_display( $registration['payment_status'] ) . '</span>';
                            }
                            echo $output;
                            ?>
                            </td>
						<?php endif; ?>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="<?php echo count( $table_columns ) + 1; ?>"><?php esc_html_e( 'No registrations yet!', 'event-genius' ); ?></td>
                    </tr>

			    <?php
            endif; // ! empty $event->registrations
            ?>
			</tbody>
		</table>

	</div>

</div>
</div>
