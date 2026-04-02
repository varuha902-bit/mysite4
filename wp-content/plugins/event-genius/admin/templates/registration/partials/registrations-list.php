<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$table_columns = $page->registration_list_columns();

include( EVGE_ADMIN_TEMPLATE_PATH . 'registration/partials/search-and-filter-bar.php' );

?>
<div class="evge-registration-list-wrapper">


    <div class="evge-event-card-reg-table evge-reg-table">

        <table class="widefat striped evge-registrations-data">
            <thead>
            <tr>
				<?php foreach ( $table_columns as $key => $label ) :
					if ( $key === 'identity' ) : ?>
                        <th><<?php esc_html_e( 'Name', 'event-genius' ); ?></th>
					<?php else: ?>
                        <th><?php echo esc_html( $label ); ?></th>
					<?php endif; ?>
				<?php endforeach; ?>
            </tr>
            </thead>
            <tbody>
			<?php if ( ! empty( $page->registrations ) ) : ?>
				<?php foreach ( $page->registrations as $registration ) :
					?>
                    <tr class="evge-reg-row">

						<?php foreach ( $table_columns as $key => $label ) :

							if ( $key === 'identity' ) : ?>
								<?php include( EVGE_ADMIN_TEMPLATE_PATH . 'registration/partials/identity.php' ); ?>

							<?php else: ?>
                                <td>
                                <?php
                                switch ( $key ) {
	                                case 'event':
		                                echo '<a href="' . esc_url( $page->nav_link( 'evge-registrations', array( 'id' => $registration['event_id'], 'tab' => 'single' ) ) ) . '">' . esc_html( get_the_title( $registration['event_id'] ) ) . '</a>';
		                                break;
                                    default:
                                    //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                        echo $page->escaped_output( $registration, $key, null );
                                        break;
                                }
                                
                                ?>
                                </td>
							<?php endif; ?>
						<?php endforeach; ?>
                    </tr>
				<?php endforeach; ?>
			<?php else : ?>
                <tr>
                    <td colspan="<?php echo count( $table_columns ); ?>"><?php esc_html_e( 'No registrations yet!', 'event-genius' ); ?></td>
                </tr>
			<?php endif; // ! empty $event->registrations ?>
            </tbody>
        </table>
    </div>
<?php

?>

</div>

