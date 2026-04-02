<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}
if ( ! empty( $this->header_image ) ) : ?>
<table border="0" cellpadding="0" cellspacing="0" class="btn btn-primary" style="border-collapse:separate;mso-table-lspace:0pt;mso-table-rspace:0pt;box-sizing:border-box;width:100%;">
	<tbody>
	<tr>
		<td align="center" style="font-family:sans-serif;font-size:14px;vertical-align:top;padding-bottom:15px;">
			<table border="0" cellpadding="0" cellspacing="0" style="border-collapse:separate;mso-table-lspace:0pt;mso-table-rspace:0pt;width:100%;width:auto;">
				<tbody>
				<tr>
					<td align="center" style="text-align:center;"><img src="<?php echo esc_url( $this->header_image ); ?>" style="height: 50px;margin:auto;" /></td>
				</tr>
				</tbody>
			</table>
		</td>
	</tr>
	</tbody>
</table>
<?php
endif;

if ( isset( $body_content ) ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo wpautop( $body_content );
}
