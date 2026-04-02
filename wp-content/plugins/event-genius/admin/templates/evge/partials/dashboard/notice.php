<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="evge-dashboard-notices-wrap">
	<?php foreach ( $notices as $notice ) : ?>
		<div class="evge-dashboard-notice" data-id="<?php echo esc_attr( $notice->id() ); ?>">
			<?php
			$classes = '';
			if ( ! $notice->content() ) {
				$classes .= ' evge-notice-no-text';
			}
			?>
			<div class="evge-notice-image">
				<?php if ( $notice->image_url() ) : ?>
					<img src="<?php echo esc_url( $notice->image_url() ); ?>" alt="">
				<?php endif; ?>
			</div>

			
			<div class="evge-notice-content<?php echo esc_attr( $classes ) ; ?>">
				<?php if ( $notice->heading() ) : ?>
					<h3 class="evge-notice-heading"><?php echo esc_html( $notice->heading() ); ?></h3>
				<?php endif; ?>

				<?php if ( $notice->content() ) : ?>
					<div class="evge-notice-text"><?php echo wp_kses_post( $notice->content() ); ?></div>
				<?php endif; ?>

				<div class="evge-notice-actions">
					<?php if ( $notice->primary_cta() ) : 
						$primary = $notice->primary_cta();
						?>
						<a href="<?php echo esc_url( $primary['url'] ); ?>" 
						   class="button evge-button-primary evge-notice-action"
						   <?php if ( empty( $primary['url'] ) && ! empty( $primary['action'] ) ) : ?>
						   data-action="<?php echo esc_attr( $primary['action'] ); ?>"
						   <?php endif; ?>>
                            <span class="evge-icon-text evge-small-icon">
                                <?php echo esc_html( $primary['text'] ); ?>
                                <svg width="6" height="11" viewBox="0 0 6 11" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M1.22462 0.218756L5.78989 4.97188C6.07004 5.26355 6.07004 5.73645 5.78989 6.02812L1.22462 10.7812C0.944469 11.0729 0.49026 11.0729 0.210112 10.7812C-0.070036 10.4896 -0.0700361 10.0167 0.210112 9.725L4.26813 5.5L0.210111 1.27501C-0.0700369 0.98333 -0.0700369 0.510431 0.210111 0.218756C0.490259 -0.0729188 0.944468 -0.0729189 1.22462 0.218756Z" fill="white"/>
                                </svg>
                            </span>

						</a>
					<?php endif; ?>

					<?php if ( $notice->other_ctas() ) : 
						foreach ( $notice->other_ctas() as $cta ) : ?>
							<a href="<?php echo esc_url( $cta['url'] ); ?>" 
							   class="button evge-button evge-button-secondary evge-notice-action"
							   <?php if ( ! empty( $cta['action'] ) ) : ?>
							   data-action="<?php echo esc_attr( $cta['action'] ); ?>"
							   <?php endif; ?>
                                <?php if ( !empty( $cta['url'] ) && ! empty( $cta['target'] ) ) : ?>
                                    target="<?php echo esc_attr( $cta['target'] ); ?>" rel="noopener noreferrer"
                                <?php endif; ?>
                                <?php if ( !empty( $cta['url'] ) && ! empty( $cta['redirect'] ) ) : ?>
                                data-redirect="1"
							<?php endif; ?>>
								<?php echo esc_html( $cta['text'] ); ?>
							</a>
						<?php endforeach;
					endif; ?>
				</div>
			</div>
            <?php if ( $notice->is_two_step_notice() ) : ?>
                <div class="evge-notice-content evge-notice-step-2" style="display: none;">
		            <?php if ( $notice->heading_step_2() ) : ?>
                        <h3 class="evge-notice-heading"><?php echo esc_html( $notice->heading_step_2() ); ?></h3>
		            <?php endif; ?>

		            <?php if ( $notice->content_step_2() ) : ?>
                        <div class="evge-notice-text"><?php echo wp_kses_post( $notice->content_step_2() ); ?></div>
		            <?php endif; ?>

                    <div class="evge-notice-actions">
			            <?php if ( $notice->primary_cta_step_2() ) :
				            $primary = $notice->primary_cta_step_2();
				            ?>
                            <a href="<?php echo esc_url( $primary['url'] ); ?>"
                               class="button evge-button-primary evge-notice-action"
					            <?php if ( ! empty( $primary['action'] ) ) : ?>
                                    data-action="<?php echo esc_attr( $primary['action'] ); ?>"
					            <?php endif; ?>
	                            <?php if ( !empty( $primary['url'] ) && ! empty( $primary['redirect'] ) ) : ?>
                                    data-redirect="1"
	                            <?php endif; ?>>
					            <span class="evge-icon-text evge-small-icon">
                                <?php echo esc_html( $primary['text'] ); ?>
                                    <svg width="6" height="11" viewBox="0 0 6 11" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M1.22462 0.218756L5.78989 4.97188C6.07004 5.26355 6.07004 5.73645 5.78989 6.02812L1.22462 10.7812C0.944469 11.0729 0.49026 11.0729 0.210112 10.7812C-0.070036 10.4896 -0.0700361 10.0167 0.210112 9.725L4.26813 5.5L0.210111 1.27501C-0.0700369 0.98333 -0.0700369 0.510431 0.210111 0.218756C0.490259 -0.0729188 0.944468 -0.0729189 1.22462 0.218756Z" fill="white"/>
                                    </svg>
                                </span>
                            </a>
			            <?php endif; ?>

			            <?php if ( $notice->other_ctas_step_2() ) :
				            foreach ( $notice->other_ctas_step_2() as $cta ) : ?>
                                <a href="<?php echo esc_url( $cta['url'] ); ?>"
                                   class="button evge-button-secondary evge-notice-action"
						            <?php if ( empty( $cta['url'] ) && ! empty( $cta['action'] ) ) : ?>
                                        data-action="<?php echo esc_attr( $cta['action'] ); ?>"
						            <?php endif; ?>>
						            <?php echo esc_html( $cta['text'] ); ?>
                                </a>
				            <?php endforeach;
			            endif; ?>
                    </div>
                </div>
            <?php endif;
            $dismiss_url = add_query_arg( array(
                'evge_notice_dismiss' => $notice->id(),
                'evge_notice_nonce' => wp_create_nonce( 'evge_notice_dismiss' ),
            ) );
            ?>
            <a href="<?php echo esc_url( $dismiss_url ); ?>" data-action="close" class="evge-notice-close">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="#AAAAAA" xmlns="http://www.w3.org/2000/svg">
                    <g clip-path="url(#clip0_158_272)">
                        <mask id="mask0_158_272" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="0" y="0" width="14" height="14">
                            <path d="M14 0H0V14H14V0Z" fill="white"/>
                        </mask>
                        <g mask="url(#mask0_158_272)">
                            <path d="M4.08331 4.08337L9.91665 9.91673M4.08331 9.91673L9.91665 4.08337" stroke="#AAAAAA" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </g>
                    </g>
                    <defs>
                        <clipPath id="clip0_158_272">
                            <rect width="14" height="14" fill="white"/>
                        </clipPath>
                    </defs>
                </svg>
            </a>
		</div>
	<?php endforeach; ?>
</div>
