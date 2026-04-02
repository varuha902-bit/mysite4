<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$analytics = new \WPEventGenius\Admin\Analytics( new \WPEventGenius\Common\Database() );
$example_event = new \WPEventGenius\Common\Event\ExampleEventPost(0, 1 );
?>
<div id="evge-dashboard-analytics">
	<div class="evge-analytics-col">
		<div class="evge-analytics-item-heading">
			<?php esc_html_e( 'Registrations', 'event-genius' ); ?>
		</div>
        <?php
        $registration_trend = $analytics->get_registration_trend();
        $class = $registration_trend['is_increase'] ? 'evge-trend-up' : 'evge-trend-down';
        $plus_minus = $registration_trend['is_increase'] ? '+' : '';
        ?>
		<div class="evge-analytics-item-content">
			<?php echo esc_html( $analytics->get_total_registration_quantity( false ) ); ?>
		</div>
		<div class="evge-analytics-item-footer">
			<div class="evge-analytics-trend">
				<span class="<?php echo esc_attr( $class ); ?>"><?php echo esc_html( $plus_minus ); ?><?php echo esc_html( $registration_trend['percent_change'] ); ?>%</span><span class="evge-trend-duration"><?php esc_html_e( '30 days', 'event-genius' ); ?></span>
			</div>
		</div>
	</div>
	<div class="evge-analytics-col">
		<div class="evge-analytics-item-heading">
			<?php esc_html_e( 'Potential Revenue', 'event-genius' ); ?>
		</div>
		<div class="evge-analytics-item-content">
			<?php echo esc_html( $example_event->currency_symbol_before() . number_format( $analytics->get_total_revenue( array( 'completed', 'offline' ) ), 0 ) . $example_event->currency_symbol_after() ); ?>
		</div>
		<div class="evge-analytics-item-footer">
			<div class="evge-analytics-trend">
				<?php esc_html_e( 'if selling tickets', 'event-genius' ); ?></span>
			</div>
		</div>
	</div>
	<?php if ( ! function_exists( 'evge_is_pro_tier' ) || ! evge_is_pro_tier() ) : ?>
    <div class="evge-analytics-blurred">
        <div class="evge-analytics-col">
            <div class="evge-analytics-item-heading">
			    Lorem ipsum
            </div>
            <div class="evge-analytics-item-content">
                77,456
            </div>
            <div class="evge-analytics-item-footer">
                <div class="evge-analytics-trend">
                    <span class="evge-trend-up">+5%</span><span class="evge-trend-duration">dolar site</span>
                </div>
            </div>
        </div>
        <div class="evge-analytics-col">
            <div class="evge-analytics-item-heading">
			    Lorem ipsum
            </div>
            <div class="evge-analytics-item-content">
                77,456
            </div>
            <div class="evge-analytics-item-footer">
                <div class="evge-analytics-trend">
                    <span class="evge-trend-up">+5%</span><span class="evge-trend-duration">dolar site</span>
                </div>
            </div>
        </div>
        <div class="evge-analytics-upsell-overlay">
            <div class="evge-analytics-upsell-heading">
                <?php esc_html_e( 'Upgrade for More Analytics', 'event-genius' ); ?>
            </div>
            <div class="evge-analytics-upsell-cta">
                <a href="https://wpeventgenius.com/pricing/?utm_campaign=evge-free&utm_source=dashboard-page&utm_medium=analytics&utm_content=upgrading-for-analytics" class="evge-button-link"><span><?php esc_html_e( 'Upgrade Now', 'event-genius' ); ?></span><svg width="22" height="26" viewBox="0 0 22 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9.70162 17.9876L7.36991 16.2295C6.91254 16.4782 5.93059 17.1199 4.92939 18.803C4.20381 20.0228 4.35765 22.3125 4.62881 22.4438C4.89996 22.5753 6.82379 22.3063 7.91267 21.1788C8.63387 20.504 9.63261 18.608 9.70162 17.9876Z" fill="#F3C826"/>
                        <path d="M5.26947 14.9571L11.2993 19.5096C12.4827 18.8647 15.0235 17.2012 17.6153 12.8392C19.4935 9.67797 19.0981 3.74591 18.3967 3.40581C17.6954 3.06572 12.7186 3.7647 9.90062 6.6872C8.03421 8.43635 5.44855 13.3498 5.26947 14.9571Z" fill="white"/>
                        <path d="M14.007 11.2399C15.2739 11.5681 16.5615 10.786 16.8831 9.49295C17.2046 8.19995 16.4383 6.8857 15.1715 6.55751C13.9047 6.22931 12.617 7.01144 12.2955 8.30445C11.9739 9.59745 12.7402 10.9117 14.007 11.2399Z" fill="white"/>
                        <path opacity="0.9" d="M14.1165 10.5607C15.1316 10.8236 16.1634 10.1969 16.421 9.16089C16.6787 8.12484 16.0646 7.07178 15.0496 6.8088C14.0345 6.54582 13.0027 7.17253 12.7451 8.20858C12.4874 9.24463 13.1015 10.2977 14.1165 10.5607Z" fill="#555555"/>
                        <path opacity="0.8" d="M14.0764 21.5922C14.3162 21.6975 14.8517 21.3197 15.0645 20.3978C15.2774 19.4759 15.3299 17.4014 15.1731 16.4533C15.0043 15.7113 14.9791 14.9748 14.3342 13.285C13.8316 12.4549 13.4921 11.8278 12.7202 11.2422C11.6621 10.4807 10.4468 10.1304 9.84538 10.0762C8.38411 9.94471 7.48489 10.1086 6.86125 10.1588C5.92642 10.2787 4.53218 10.6917 3.72853 11.1653C2.92489 11.639 2.12061 12.3497 2.32096 12.6723C2.52132 12.9949 8.19868 17.1322 8.19868 17.1322C8.19868 17.1322 13.8366 21.4869 14.0764 21.5922Z" fill="white"/>
                    </svg>
                </a>
            </div>

        </div>

    </div>
	<?php endif; ?>

</div>
