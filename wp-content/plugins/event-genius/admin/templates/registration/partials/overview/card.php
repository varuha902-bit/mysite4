<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEventGenius\Common\Utils\Formatter;
use WPEventGenius\Common\Utils\Icon;

$table_columns = $page->table_columns( $event->details['form'], array( 'include' => array( 'registration_date', 'status' ), 'number' => 3 ) );
?>

<div class="evge-event-card">
    <div class="evge-event-card-inner">
        <?php include EVGE_ADMIN_TEMPLATE_PATH . 'registration/partials/event-meta.php'; ?>
        <div class="evge-event-card-reg-table evge-reg-table">

            <table class="widefat striped evge-registrations-data">
                <thead>
                <tr>
                    <?php foreach ( $table_columns as $key => $label ) :
                        if ( $key === 'identity' ) : ?>
                        <th><?php esc_html_e( 'Name', 'event-genius' ); ?></th>

                        <?php else: ?>
                        <th><?php echo esc_html( $label ); ?></th>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody>
                <?php if ( ! empty( $event->registrations ) ) : ?>
                    <?php foreach ( $event->registrations as $registration ) :
                        ?>
                        <tr class="evge-reg-row">

                        <?php foreach ( $table_columns as $key => $label ) :

                            if ( $key === 'identity' ) : ?>
                                <?php include( EVGE_ADMIN_TEMPLATE_PATH . 'registration/partials/identity.php' ); ?>

                            <?php else: ?>
                                <td><?php
                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                    echo $page->escaped_output( $registration, $key, $event->details['form'], 'card' ); ?></td>
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
            <div class="evge-overview-bottom evge-event-details-actions">
                <a class="evge-secondary-button evge-standard-button button action" href="<?php echo esc_url( $page->nav_link( 'evge-all-events', array( 'id' => $event->ID, 'tab' => 'registrations', 'subtab' => 'single' ) ) ); ?>"><span class="evge-icon-text">
                    <?php
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo Icon::get( 'plus' ); ?><?php esc_html_e( 'Manage Registrations', 'event-genius' ); ?></span></a>
            </div>

        </div>
    </div>
</div>
