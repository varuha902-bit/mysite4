<?php
use WPEventGenius\Common\Utils\Formatter;
use WPEventGenius\Common\Utils\Icon;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$subtab = ! empty( $_GET['subsubtab'] ) ? sanitize_key( $_GET['subsubtab'] ) : 'submissions';

?>

<td class="evge-identifier-column">
	<div class="evge-identifier-wrap">
		<div class="evge-identifier-top">
			<div class="evge-identifier evge-is-user"><?php echo wp_kses_post( Formatter::identity( $registration ) ); ?></div>
			<?php if ( $page->identity() === 'single' || $page->identity() === 'reglist' ) : ?>

				<?php Formatter::single_registration_record_actions( $registration, $subtab ); ?>

			<?php endif; ?>
		</div>

	</div>
</td>
