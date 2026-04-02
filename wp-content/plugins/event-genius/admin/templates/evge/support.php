<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get system information
$system_info = array();

global $wpdb;

// Helper function to check if a value is an email and validate its domain
function evge_check_email_domain($value) {
    if (!is_string($value) || !is_email($value)) {
        return 'Not a valid email address';
    }
    
    $email_parts = explode('@', $value);
    if (count($email_parts) !== 2) {
        return 'Not a valid email address';
    }
    
    $site_url = parse_url(get_site_url(), PHP_URL_HOST);
    $email_domain = $email_parts[1];
    
    return sprintf(
        'Valid email address | Domain %s site domain',
        $email_domain === $site_url ? 'matches' : 'does not match'
    );
}

// Helper function to process comma-separated email values
function evge_process_email_list($value) {
    if (!is_string($value)) {
        return 'Invalid format';
    }
    
    $emails = array_map('trim', explode(',', $value));
    $results = array();
    
    foreach ($emails as $email) {
        $results[] = evge_check_email_domain($email);
    }
    
    return implode("\n", $results);
}

// Site/Server Info
$system_info['## Site/Server Info ##'] = array(
    'WordPress Version' => get_bloginfo('version'),
    'PHP Version' => PHP_VERSION,
    'Server Software' => sanitize_text_field($_SERVER['SERVER_SOFTWARE']),
    'MySQL Version' => $wpdb->db_version(),
    'Active Theme' => wp_get_theme()->get('Name'),
    'Site URL' => get_site_url(),
    'Multisite' => is_multisite() ? 'Yes' : 'No'
);

// Active Plugins
$active_plugins = get_option('active_plugins');
$plugin_info = array();
foreach ($active_plugins as $plugin) {
    $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
    $plugin_info[$plugin_data['Name']] = $plugin_data['Version'];
}
$system_info['## Active Plugins ##'] = $plugin_info;

// Event Information
$system_info['## Event Information ##'] = array(
    'Next Event' => get_option('evge_next_event', 'No upcoming events scheduled'),
    'Total Events' => wp_count_posts('evge_event')->publish,
    'Total Registrations' => get_option('evge_total_registrations', 0)
);

// Settings
$settings = get_option('evge_settings', array());
$formatted_settings = array();
$email_settings = array('notification_recipients', 'email_from_address');

foreach ($settings as $key => $value) {
    if (in_array($key, $email_settings)) {
        $formatted_settings[$key] = evge_process_email_list($value);
    } else {
        $formatted_settings[$key] = $value;
    }
}
$system_info['## Settings ##'] = $formatted_settings;

// Debug Log
$debug_log_entries = \WPEventGenius\Common\Utils\Logger\DebugLogger::get_log_contents();
$formatted_log_entries = array();
if (!empty($debug_log_entries)) {
    // Get only the 10 most recent entries
    $recent_entries = array_slice($debug_log_entries, 0, 10);
    foreach ($recent_entries as $entry) {
        $formatted_log_entries[] = \WPEventGenius\Common\Utils\Logger\DebugLogger::format_log_summary($entry);
    }
}
$system_info['## Debug Log ##'] = array(
    'Log Entries' => !empty($formatted_log_entries) ? esc_textarea(implode("\n", $formatted_log_entries)) : 'No log entries found.'
);

// Cron Events
$cron_jobs = _get_cron_array();
$formatted_cron = array();
foreach ($cron_jobs as $timestamp => $crons) {
    foreach ($crons as $hook => $events) {
        foreach ($events as $key => $event) {
            if (strpos($hook, 'evge_') === 0) {
                $next_run = date('Y-m-d H:i:s', $timestamp);
                $hours_until = round(($timestamp - time()) / 3600, 1);
                $formatted_cron[$hook] = array(
                    'Next Run' => $next_run,
                    'Hours Until' => $hours_until . ' hours',
                    'Schedule' => $event['schedule'] ?? 'single'
                );
            }
        }
    }
}
$system_info['## Cron Events ##'] = $formatted_cron;

// Apply filters to allow other plugins to add their info
$system_info = apply_filters( 'evge_system_info', $system_info );

// Convert system info to string for copying
$system_info_text = '';
foreach ($system_info as $section => $content) {
    $system_info_text .= "\n" . $section . "\n";
    if (is_array($content)) {
        // Find the longest key length for padding
        $max_key_length = 0;
        foreach ($content as $key => $value) {
            $max_key_length = max($max_key_length, strlen($key));
        }
        
        foreach ($content as $key => $value) {
            if (is_array($value)) {
                if ($section === '## Settings ##') {
                    // For settings, use print_r format
                    $value = print_r($value, true);
                    $system_info_text .= "$key: $value\n";
                } else {
                    // For other sections, format each array item with padding
                    $system_info_text .= str_pad($key . ':', $max_key_length + 1) . " ";
                    if (is_array($value)) {
                        $system_info_text .= "\n";
                        foreach ($value as $subkey => $subvalue) {
                            $system_info_text .= "    " . str_pad($subkey . ':', $max_key_length + 1) . " $subvalue\n";
                        }
                    } else {
                        $system_info_text .= "$value\n";
                    }
                }
            } else {
                $system_info_text .= str_pad($key . ':', $max_key_length + 1) . " $value\n";
            }
        }
    }
    $system_info_text .= "\n";
}
?>

<div class="wrap evge-support-page">    
    <div class="evge-support-container">
        <!-- Left Column -->
        <div class="evge-support-left">
            <!-- Helpful Links Section -->
            <div class="evge-support-section">
				<div class="evge-section-header">
					<div class="evge-section-header-icon">
						<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
						<g clip-path="url(#clip0_608_1744)">
						<path fill-rule="evenodd" clip-rule="evenodd" d="M10 0C7.34784 0 4.8043 1.05357 2.92893 2.92893C1.05357 4.8043 0 7.34784 0 10C0 12.6522 1.05357 15.1957 2.92893 17.0711C4.8043 18.9464 7.34784 20 10 20C12.6522 20 15.1957 18.9464 17.0711 17.0711C18.9464 15.1957 20 12.6522 20 10C20 7.34784 18.9464 4.8043 17.0711 2.92893C15.1957 1.05357 12.6522 0 10 0ZM1.875 10C1.875 8.09375 2.53125 6.34125 3.63 4.95625L5.86375 7.19C5.29949 8.01834 4.99844 8.99773 5 10C5 11.0425 5.31875 12.01 5.86375 12.81L3.63 15.0437C2.49096 13.6096 1.87223 11.8314 1.875 10ZM4.95625 16.37L7.19 14.1362C7.99 14.6812 8.9575 15 10 15C11.0425 15 12.01 14.6812 12.81 14.1362L15.0437 16.37C13.6096 17.509 11.8314 18.1278 10 18.125C8.16858 18.1277 6.39043 17.509 4.95625 16.37ZM16.37 15.045C17.5093 13.6105 18.1281 11.8319 18.125 10C18.1277 8.16858 17.509 6.39043 16.37 4.95625L14.1362 7.19C14.6812 7.99 15 8.9575 15 10C15 11.0425 14.6812 12.01 14.1362 12.81L16.37 15.0437V15.045ZM15.045 3.63C13.6105 2.49069 11.8319 1.87194 10 1.875C8.16857 1.87223 6.39038 2.49096 4.95625 3.63L7.19 5.86375C8.01834 5.29949 8.99773 4.99844 10 5C11.0425 5 12.01 5.31875 12.81 5.86375L15.0437 3.63H15.045ZM6.875 10C6.875 9.1712 7.20424 8.37634 7.79029 7.79029C8.37634 7.20424 9.1712 6.875 10 6.875C10.8288 6.875 11.6237 7.20424 12.2097 7.79029C12.7958 8.37634 13.125 9.1712 13.125 10C13.125 10.8288 12.7958 11.6237 12.2097 12.2097C11.6237 12.7958 10.8288 13.125 10 13.125C9.1712 13.125 8.37634 12.7958 7.79029 12.2097C7.20424 11.6237 6.875 10.8288 6.875 10Z" fill="#D37362"/>
						</g>
						<defs>
						<clipPath id="clip0_608_1744">
						<rect width="20" height="20" fill="white"/>
						</clipPath>
						</defs>
						</svg>
					</div>
					<h2><?php esc_html_e('Docs & Troubleshooting', 'event-genius'); ?></h2>
				</div>
				<?php $links = array(
					'Getting Started Guide' => 'https://wpeventgenius.com/docs/getting-started-installing-and-setting-up-the-plugin/',
					'Troubleshooting Email Issues' => 'https://wpeventgenius.com/docs/troubleshooting-email-issues-confirmation-and-notification/',
					'Fixing 404 Errors for Events, Venues, and Organizers' => 'https://wpeventgenius.com/docs/fixing-404-errors-for-events-venues-and-organizers/',
          'How to Translate or Change Date Formats and Front-End Text' => 'https://wpeventgenius.com/docs/how-to-translate-or-change-date-formats-and-front-end-text/',
          'Using and Customizing Registration Forms' => 'https://wpeventgenius.com/docs/using-and-customizing-registration-forms/',
          'Recurring Events SEO Guide' => 'https://wpeventgenius.com/docs/recurring-events-seo-guide/',
          'Working with Caching Plugins' => 'https://wpeventgenius.com/docs/working-with-caching-plugins/',
          'Block Theme and Full Site Editing Guide' => 'https://wpeventgenius.com/docs/block-theme-full-site-editing-guide/',
				); 
				
				// Allow extensions to add their own helpful links
				$links = apply_filters( 'evge_helpful_links', $links ); ?>
                <ul class="evge-helpful-links">
					<?php foreach ($links as $link => $url) : ?>
						<li>
							<a href="<?php echo esc_url($url); ?>?utm_campaign=evge-free&utm_source=support-page&utm_medium=docs-troubleshooting&utm_content=title" target="_blank"><?php echo esc_html($link); ?>
							<svg width="5" height="10" viewBox="0 0 5 10" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path fill-rule="evenodd" clip-rule="evenodd" d="M1.02051 0.198869L4.82491 4.51989C5.05836 4.78505 5.05836 5.21495 4.82491 5.48011L1.02051 9.80113C0.787058 10.0663 0.40855 10.0663 0.175093 9.80113C-0.0583633 9.53597 -0.0583633 9.10606 0.175093 8.8409L3.55678 5L0.175093 1.1591C-0.0583641 0.893936 -0.0583641 0.464029 0.175092 0.198869C0.408549 -0.0662898 0.787057 -0.0662899 1.02051 0.198869Z" fill="#D37362"/>
							</svg>
							</a>
						</li>
					<?php endforeach; ?>
                </ul>
				<div class="evge-support-section-footer">
					<a class="evge-support-page-button evge-support-page-button evge-button-link" href="https://wpeventgenius.com/docs?utm_campaign=evge-free&utm_source=support-page&utm_medium=docs-troubleshooting&utm_content=SeeAllDocs" target="_blank">
						<span><?php esc_html_e( 'See All Docs', 'event-genius' ); ?></span>
							<svg width="5" height="10" viewBox="0 0 5 10" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path fill-rule="evenodd" clip-rule="evenodd" d="M1.02051 0.198869L4.82491 4.51989C5.05836 4.78505 5.05836 5.21495 4.82491 5.48011L1.02051 9.80113C0.787058 10.0663 0.40855 10.0663 0.175093 9.80113C-0.0583633 9.53597 -0.0583633 9.10606 0.175093 8.8409L3.55678 5L0.175093 1.1591C-0.0583641 0.893936 -0.0583641 0.464029 0.175092 0.198869C0.408549 -0.0662898 0.787057 -0.0662899 1.02051 0.198869Z" fill="#D37362"/>
							</svg>
				</a>
				</div>
            </div>

            <!-- FAQ Section -->
            <div class="evge-support-section evge-support-faq">
				<div class="evge-section-header">
					<div class="evge-section-header-icon">
						<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
						<g clip-path="url(#clip0_608_1744)">
						<path fill-rule="evenodd" clip-rule="evenodd" d="M10 0C7.34784 0 4.8043 1.05357 2.92893 2.92893C1.05357 4.8043 0 7.34784 0 10C0 12.6522 1.05357 15.1957 2.92893 17.0711C4.8043 18.9464 7.34784 20 10 20C12.6522 20 15.1957 18.9464 17.0711 17.0711C18.9464 15.1957 20 12.6522 20 10C20 7.34784 18.9464 4.8043 17.0711 2.92893C15.1957 1.05357 12.6522 0 10 0ZM1.875 10C1.875 8.09375 2.53125 6.34125 3.63 4.95625L5.86375 7.19C5.29949 8.01834 4.99844 8.99773 5 10C5 11.0425 5.31875 12.01 5.86375 12.81L3.63 15.0437C2.49096 13.6096 1.87223 11.8314 1.875 10ZM4.95625 16.37L7.19 14.1362C7.99 14.6812 8.9575 15 10 15C11.0425 15 12.01 14.6812 12.81 14.1362L15.0437 16.37C13.6096 17.509 11.8314 18.1278 10 18.125C8.16858 18.1277 6.39043 17.509 4.95625 16.37ZM16.37 15.045C17.5093 13.6105 18.1281 11.8319 18.125 10C18.1277 8.16858 17.509 6.39043 16.37 4.95625L14.1362 7.19C14.6812 7.99 15 8.9575 15 10C15 11.0425 14.6812 12.01 14.1362 12.81L16.37 15.0437V15.045ZM15.045 3.63C13.6105 2.49069 11.8319 1.87194 10 1.875C8.16857 1.87223 6.39038 2.49096 4.95625 3.63L7.19 5.86375C8.01834 5.29949 8.99773 4.99844 10 5C11.0425 5 12.01 5.31875 12.81 5.86375L15.0437 3.63H15.045ZM6.875 10C6.875 9.1712 7.20424 8.37634 7.79029 7.79029C8.37634 7.20424 9.1712 6.875 10 6.875C10.8288 6.875 11.6237 7.20424 12.2097 7.79029C12.7958 8.37634 13.125 9.1712 13.125 10C13.125 10.8288 12.7958 11.6237 12.2097 12.2097C11.6237 12.7958 10.8288 13.125 10 13.125C9.1712 13.125 8.37634 12.7958 7.79029 12.2097C7.20424 11.6237 6.875 10.8288 6.875 10Z" fill="#D37362"/>
						</g>
						<defs>
						<clipPath id="clip0_608_1744">
						<rect width="20" height="20" fill="white"/>
						</clipPath>
						</defs>
						</svg>
					</div>
					<h2><?php esc_html_e('Frequently Asked Questions', 'event-genius'); ?></h2>
				</div>
				<div class="evge-faq-item">
					<h3 class="evge-faq-question"><?php esc_html_e('How does Event Genius work with caching plugins?', 'event-genius'); ?></h3>
					<div class="evge-faq-answer">
						<p><?php esc_html_e('Event Genius includes built-in support for popular WordPress caching plugins (WP Super Cache, W3 Total Cache, WP Rocket, and LiteSpeed Cache). The plugin automatically clears cache when registrations or cancellations occur, ensuring the next visitor sees up-to-date information.', 'event-genius'); ?></p>
						<p><?php esc_html_e('Additionally, a smart content refresh system detects stale cached content and automatically updates dynamic elements (registration status, attendee counts, capacity) without reloading the entire page. This ensures visitors always see accurate information while maintaining fast page load times.', 'event-genius'); ?></p>
						<p><a href="https://wpeventgenius.com/docs/working-with-caching-plugins/?utm_campaign=evge-free&utm_source=support-page&utm_medium=faq&utm_content=caching-link" target="_blank"><?php esc_html_e('Learn more about caching integration', 'event-genius'); ?></a></p>
					</div>
				</div>
			</div>

            <!-- Contact Support Section -->
            <div class="evge-support-section evge-support-contact">
				<div class="evge-section-header">
					<div class="evge-section-header-icon">
						<svg width="21" height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M18.375 17.5L17.9837 18.2826C18.255 18.4182 18.577 18.4038 18.835 18.2444C19.0929 18.0849 19.25 17.8033 19.25 17.5H18.375ZM9.85268 9.66254C9.51099 10.0042 9.51099 10.5583 9.85268 10.9C10.1944 11.2417 10.7484 11.2417 11.0901 10.9L9.85268 9.66254ZM7.92911 7.43815C7.80865 7.90614 8.09039 8.38318 8.55839 8.50364C9.02641 8.62409 9.50338 8.34235 9.62386 7.87435L7.92911 7.43815ZM10.471 12.0313C9.98769 12.0313 9.59595 12.423 9.59595 12.9063C9.59595 13.3895 9.98769 13.7813 10.471 13.7813V12.0313ZM10.4797 13.7813C10.963 13.7813 11.3547 13.3895 11.3547 12.9063C11.3547 12.423 10.963 12.0313 10.4797 12.0313V13.7813ZM5.425 4.375H15.575V2.625H5.425V4.375ZM3.5 12.95V6.3H1.75V12.95H3.5ZM14.214 14.875H5.425V16.625H14.214V14.875ZM18.7663 16.7174L15.8575 15.263L15.0749 16.8283L17.9837 18.2826L18.7663 16.7174ZM14.214 16.625C14.4839 16.625 14.5366 16.6269 14.5832 16.6335L14.8258 14.9004C14.631 14.8731 14.4372 14.875 14.214 14.875V16.625ZM15.8575 15.263C15.658 15.1632 15.4855 15.0748 15.2989 15.012L14.7409 16.6707C14.7855 16.6856 14.8335 16.7075 15.0749 16.8283L15.8575 15.263ZM14.5832 16.6335C14.6368 16.6409 14.6896 16.6534 14.7409 16.6707L15.2989 15.012C15.145 14.9602 14.9867 14.9229 14.8258 14.9004L14.5832 16.6335ZM1.75 12.95C1.75 13.4257 1.74932 13.835 1.77675 14.1706C1.80501 14.5166 1.86742 14.8607 2.03611 15.1918L3.59537 14.3973C3.57333 14.3539 3.54036 14.2658 3.52094 14.0282C3.50068 13.7802 3.5 13.4545 3.5 12.95H1.75ZM5.425 14.875C4.92052 14.875 4.59478 14.8743 4.34686 14.8541C4.10915 14.8347 4.02102 14.8017 3.97776 14.7796L3.18328 16.3389C3.51436 16.5076 3.85843 16.57 4.20435 16.5982C4.54006 16.6257 4.9494 16.625 5.425 16.625V14.875ZM2.03611 15.1918C2.28778 15.6857 2.68935 16.0872 3.18328 16.3389L3.97776 14.7796C3.81312 14.6957 3.67926 14.5619 3.59537 14.3973L2.03611 15.1918ZM15.575 4.375C16.0795 4.375 16.4052 4.37568 16.6532 4.39594C16.8908 4.41536 16.9789 4.44833 17.0223 4.47037L17.8168 2.91111C17.4857 2.74242 17.1416 2.68001 16.7956 2.65175C16.46 2.62432 16.0507 2.625 15.575 2.625V4.375ZM19.25 6.3C19.25 5.8244 19.2507 5.41506 19.2232 5.07935C19.195 4.73343 19.1326 4.38936 18.9639 4.05828L17.4046 4.85276C17.4267 4.89602 17.4597 4.98415 17.4791 5.22186C17.4993 5.46978 17.5 5.79552 17.5 6.3H19.25ZM17.0223 4.47037C17.1869 4.55426 17.3207 4.68812 17.4046 4.85276L18.9639 4.05828C18.7122 3.56435 18.3107 3.16278 17.8168 2.91111L17.0223 4.47037ZM5.425 2.625C4.9494 2.625 4.54006 2.62432 4.20435 2.65175C3.85843 2.68001 3.51436 2.74242 3.18328 2.91111L3.97776 4.47037C4.02102 4.44833 4.10915 4.41536 4.34686 4.39594C4.59478 4.37568 4.92052 4.375 5.425 4.375V2.625ZM3.5 6.3C3.5 5.79552 3.50068 5.46978 3.52094 5.22186C3.54036 4.98415 3.57333 4.89602 3.59537 4.85276L2.03611 4.05828C1.86742 4.38936 1.80501 4.73343 1.77675 5.07935C1.74932 5.41506 1.75 5.8244 1.75 6.3H3.5ZM3.18328 2.91111C2.68935 3.16278 2.28778 3.56435 2.03611 4.05828L3.59537 4.85276C3.67926 4.68812 3.81312 4.55426 3.97776 4.47037L3.18328 2.91111ZM11.3464 8.09375C11.3464 8.24528 11.3066 8.36086 11.0668 8.59222C10.9323 8.72203 10.774 8.85089 10.5544 9.0335C10.3458 9.20701 10.1013 9.41386 9.85268 9.66254L11.0901 10.9C11.2789 10.7111 11.4719 10.5466 11.6736 10.3789C11.864 10.2204 12.0885 10.0383 12.2821 9.85136C12.6987 9.44939 13.0964 8.90873 13.0964 8.09375H11.3464ZM10.4714 7.21875C10.9546 7.21875 11.3464 7.61051 11.3464 8.09375H13.0964C13.0964 6.64401 11.9211 5.46875 10.4714 5.46875V7.21875ZM9.62386 7.87435C9.72108 7.49677 10.0649 7.21875 10.4714 7.21875V5.46875C9.247 5.46875 8.22046 6.30616 7.92911 7.43815L9.62386 7.87435ZM10.471 13.7813H10.4797V12.0313H10.471V13.7813ZM17.5 6.3V17.5H19.25V6.3H17.5Z" fill="#D37362"/>
						</svg>
					</div>
					<h2><?php esc_html_e('Contact Support', 'event-genius'); ?></h2>
				</div>
                <p><?php esc_html_e("Have a problem you can't solve? We're here to help! Submit a ticket on the WordPress forums and we'll get back to you as soon as possible.", 'event-genius'); ?></p>
                <div class="evge-contact-options">
                    <a href="https://wordpress.org/support/plugin/event-genius/" class="evge-support-page-button evge-solid-button evge-button-link" target="_blank">
						<span><?php esc_html_e('Post in Support Forums', 'event-genius'); ?></span>
						<svg width="5" height="10" viewBox="0 0 5 10" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path fill-rule="evenodd" clip-rule="evenodd" d="M1.02051 0.198869L4.82491 4.51989C5.05836 4.78505 5.05836 5.21495 4.82491 5.48011L1.02051 9.80113C0.787058 10.0663 0.40855 10.0663 0.175093 9.80113C-0.0583633 9.53597 -0.0583633 9.10606 0.175093 8.8409L3.55678 5L0.175093 1.1591C-0.0583641 0.893936 -0.0583641 0.464029 0.175092 0.198869C0.408549 -0.0662898 0.787057 -0.0662899 1.02051 0.198869Z" fill="#D37362"/>
						</svg>
					</a>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="evge-support-section evge-support-faq">
				<div class="evge-section-header">
					<div class="evge-section-header-icon">
						<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M10 0C4.47715 0 0 4.47715 0 10C0 15.5228 4.47715 20 10 20C15.5228 20 20 15.5228 20 10C20 4.47715 15.5228 0 10 0ZM10 18C5.58172 18 2 14.4183 2 10C2 5.58172 5.58172 2 10 2C14.4183 2 18 5.58172 18 10C18 14.4183 14.4183 18 10 18Z" fill="#D37362"/>
						<path d="M10 4C9.44772 4 9 4.44772 9 5V11C9 11.5523 9.44772 12 10 12C10.5523 12 11 11.5523 11 11V5C11 4.44772 10.5523 4 10 4Z" fill="#D37362"/>
						<path d="M10 14C10.5523 14 11 14.4477 11 15C11 15.5523 10.5523 16 10 16C9.44772 16 9 15.5523 9 15C9 14.4477 9.44772 14 10 14Z" fill="#D37362"/>
						</svg>
					</div>
					<h2><?php esc_html_e('Frequently Asked Questions', 'event-genius'); ?></h2>
				</div>
				<div class="evge-faq-item">
					<h3><?php esc_html_e('How does SEO work with recurring events?', 'event-genius'); ?></h3>
					<p><?php esc_html_e('Event Genius automatically handles SEO for recurring events to prevent duplicate content issues. Each recurring event instance gets its own unique canonical URL, invalid instances are automatically redirected, and sitemaps are filtered to prevent bloat. The plugin works seamlessly with Yoast SEO, Rank Math, and other popular SEO plugins.', 'event-genius'); ?></p>
					<a href="https://wpeventgenius.com/docs/recurring-events-seo-guide/?utm_campaign=evge-free&utm_source=support-page&utm_medium=faq&utm_content=seo-question" class="evge-support-page-button evge-button-link" target="_blank">
						<span><?php esc_html_e('Learn More About Recurring Events SEO', 'event-genius'); ?></span>
						<svg width="5" height="10" viewBox="0 0 5 10" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path fill-rule="evenodd" clip-rule="evenodd" d="M1.02051 0.198869L4.82491 4.51989C5.05836 4.78505 5.05836 5.21495 4.82491 5.48011L1.02051 9.80113C0.787058 10.0663 0.40855 10.0663 0.175093 9.80113C-0.0583633 9.53597 -0.0583633 9.10606 0.175093 8.8409L3.55678 5L0.175093 1.1591C-0.0583641 0.893936 -0.0583641 0.464029 0.175092 0.198869C0.408549 -0.0662898 0.787057 -0.0662899 1.02051 0.198869Z" fill="#D37362"/>
						</svg>
					</a>
				</div>
            </div>

            <!-- Recurrence Debug Section -->
            <div class="evge-support-section evge-support-recurrence-debug">
                <div class="evge-section-header">
                    <div class="evge-section-header-icon">
                        <svg width="21" height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M10.5 0C4.70101 0 0 4.70101 0 10.5C0 16.299 4.70101 21 10.5 21C16.299 21 21 16.299 21 10.5C21 4.70101 16.299 0 10.5 0ZM10.5 19.25C5.66751 19.25 1.75 15.3325 1.75 10.5C1.75 5.66751 5.66751 1.75 10.5 1.75C15.3325 1.75 19.25 5.66751 19.25 10.5C19.25 15.3325 15.3325 19.25 10.5 19.25Z" fill="#D37362"/>
                            <path d="M10.5 5.25C10.9142 5.25 11.25 5.58579 11.25 6V10.5C11.25 10.9142 10.9142 11.25 10.5 11.25C10.0858 11.25 9.75 10.9142 9.75 10.5V6C9.75 5.58579 10.0858 5.25 10.5 5.25Z" fill="#D37362"/>
                            <path d="M10.5 13.125C10.9142 13.125 11.25 13.4608 11.25 13.875V14.875C11.25 15.2892 10.9142 15.625 10.5 15.625C10.0858 15.625 9.75 15.2892 9.75 14.875V13.875C9.75 13.4608 10.0858 13.125 10.5 13.125Z" fill="#D37362"/>
                        </svg>
                    </div>
                    <h2><?php esc_html_e('Recurrence Debug Tools', 'event-genius'); ?></h2>
                </div>
                <p><?php esc_html_e('This will stop the automatic scheduling of recurrences and allow a support person to troubleshoot issues.', 'event-genius'); ?></p>

                <?php
                $debug_mode = get_transient('evge_recurrence_debug_mode');
                if ($debug_mode) {
                    $expires_in = human_time_diff(time(), $debug_mode);
                    echo '<div class="evge-debug-status">';
                    printf(
                        /* translators: %s: Time until debug mode expires */
                        esc_html__('Debug mode is active and will expire in %s.', 'event-genius'),
                        esc_html($expires_in)
                    );
                    echo '</div>';
                    ?>
                    <div class="evge-contact-options">
                        <a href="<?php echo esc_url(wp_nonce_url(add_query_arg('evge_debug_action', 'disable'), 'evge_toggle_debug_mode')); ?>" class="evge-support-page-button evge-solid-button evge-button-link evge-primary-button">
                            <span><?php esc_html_e('Disable Recurrence Debug Mode', 'event-genius'); ?></span>
                            <svg width="5" height="10" viewBox="0 0 5 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M1.02051 0.198869L4.82491 4.51989C5.05836 4.78505 5.05836 5.21495 4.82491 5.48011L1.02051 9.80113C0.787058 10.0663 0.40855 10.0663 0.175093 9.80113C-0.0583633 9.53597 -0.0583633 9.10606 0.175093 8.8409L3.55678 5L0.175093 1.1591C-0.0583641 0.893936 -0.0583641 0.464029 0.175092 0.198869C0.408549 -0.0662898 0.787057 -0.0662899 1.02051 0.198869Z" fill="#D37362"/>
                            </svg>
                        </a>
                    </div>
                    <div class="evge-debug-tools">
                        <a href="<?php echo esc_url(wp_nonce_url(add_query_arg('evge_debug_action', 'process'), 'evge_toggle_debug_mode')); ?>" class="evge-support-page-button evge-solid-button evge-button-link">
                            <span><?php esc_html_e('Process Queue', 'event-genius'); ?></span>
                            <svg width="5" height="10" viewBox="0 0 5 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M1.02051 0.198869L4.82491 4.51989C5.05836 4.78505 5.05836 5.21495 4.82491 5.48011L1.02051 9.80113C0.787058 10.0663 0.40855 10.0663 0.175093 9.80113C-0.0583633 9.53597 -0.0583633 9.10606 0.175093 8.8409L3.55678 5L0.175093 1.1591C-0.0583641 0.893936 -0.0583641 0.464029 0.175092 0.198869C0.408549 -0.0662898 0.787057 -0.0662899 1.02051 0.198869Z" fill="#D37362"/>
                            </svg>
                        </a>
                        <a href="<?php echo esc_url(wp_nonce_url(add_query_arg('evge_debug_action', 'delete'), 'evge_toggle_debug_mode')); ?>" class="evge-support-page-button evge-solid-button evge-button-link">
                            <span><?php esc_html_e('Delete Queue', 'event-genius'); ?></span>
                            <svg width="5" height="10" viewBox="0 0 5 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M1.02051 0.198869L4.82491 4.51989C5.05836 4.78505 5.05836 5.21495 4.82491 5.48011L1.02051 9.80113C0.787058 10.0663 0.40855 10.0663 0.175093 9.80113C-0.0583633 9.53597 -0.0583633 9.10606 0.175093 8.8409L3.55678 5L0.175093 1.1591C-0.0583641 0.893936 -0.0583641 0.464029 0.175092 0.198869C0.408549 -0.0662898 0.787057 -0.0662899 1.02051 0.198869Z" fill="#D37362"/>
                            </svg>
                        </a>
                        <a href="<?php echo esc_url(wp_nonce_url(add_query_arg('evge_debug_action', 'delete_orphaned'), 'evge_toggle_debug_mode')); ?>" class="evge-support-page-button evge-solid-button evge-button-link">
                            <span><?php esc_html_e('Delete Orphaned Events and Series', 'event-genius'); ?></span>
                            <svg width="5" height="10" viewBox="0 0 5 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M1.02051 0.198869L4.82491 4.51989C5.05836 4.78505 5.05836 5.21495 4.82491 5.48011L1.02051 9.80113C0.787058 10.0663 0.40855 10.0663 0.175093 9.80113C-0.0583633 9.53597 -0.0583633 9.10606 0.175093 8.8409L3.55678 5L0.175093 1.1591C-0.0583641 0.893936 -0.0583641 0.464029 0.175092 0.198869C0.408549 -0.0662898 0.787057 -0.0662899 1.02051 0.198869Z" fill="#D37362"/>
                            </svg>
                        </a>
                    </div>
                    <?php
                } else {
                    ?>
                    <div class="evge-contact-options">
                        <a href="<?php echo esc_url(wp_nonce_url(add_query_arg('evge_debug_action', 'enable'), 'evge_toggle_debug_mode')); ?>" class="evge-support-page-button evge-button-link">
                            <span><?php esc_html_e('Enable Recurrence Debug Mode', 'event-genius'); ?></span>
                            <svg width="5" height="10" viewBox="0 0 5 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M1.02051 0.198869L4.82491 4.51989C5.05836 4.78505 5.05836 5.21495 4.82491 5.48011L1.02051 9.80113C0.787058 10.0663 0.40855 10.0663 0.175093 9.80113C-0.0583633 9.53597 -0.0583633 9.10606 0.175093 8.8409L3.55678 5L0.175093 1.1591C-0.0583641 0.893936 -0.0583641 0.464029 0.175092 0.198869C0.408549 -0.0662898 0.787057 -0.0662899 1.02051 0.198869Z" fill="#D37362"/>
                            </svg>
                        </a>
                    </div>
                    <?php
                }
                ?>

                <!-- Debug Log Section -->
                <?php if ($debug_mode) : ?>
                <div class="evge-debug-log-section">
                    <div class="evge-section-header">
                        <div class="evge-section-header-icon">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M10 0C4.47715 0 0 4.47715 0 10C0 15.5228 4.47715 20 10 20C15.5228 20 20 15.5228 20 10C20 4.47715 15.5228 0 10 0ZM10 18C5.58172 18 2 14.4183 2 10C2 5.58172 5.58172 2 10 2C14.4183 2 18 5.58172 18 10C18 14.4183 14.4183 18 10 18Z" fill="#D37362"/>
                                <path d="M10 4C9.44772 4 9 4.44772 9 5V11C9 11.5523 9.44772 12 10 12C10.5523 12 11 11.5523 11 11V5C11 4.44772 10.5523 4 10 4Z" fill="#D37362"/>
                                <path d="M10 14C10.5523 14 11 14.4477 11 15C11 15.5523 10.5523 16 10 16C9.44772 16 9 15.5523 9 15C9 14.4477 9.44772 14 10 14Z" fill="#D37362"/>
                            </svg>
                        </div>
                        <h2><?php esc_html_e('Recurrence Debug Log', 'event-genius'); ?></h2>
                        <a href="<?php echo esc_url(wp_nonce_url(add_query_arg('evge_debug_action', 'clear_log'), 'evge_toggle_debug_mode')); ?>" class="button button-secondary">
                            <?php esc_html_e('Clear Log', 'event-genius'); ?>
                        </a>
                    </div>
                    <div class="evge-debug-log">
                        <pre><?php 
                            $log_entries = \WPEventGenius\Common\Utils\Logger\RecurrenceLogger::get_log_contents();
                            if (empty($log_entries)) {
                                echo esc_html__('No log entries found.', 'event-genius');
                            } else {
                                foreach ($log_entries as $entry) {
                                    echo esc_html(\WPEventGenius\Common\Utils\Logger\RecurrenceLogger::format_log_entry($entry)) . "\n\n";
                                }
                            }
                        ?></pre>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php
            // Allow Standard tier to add Scheduled Email Debug Tools section
            do_action( 'evge_support_page_before_right_column' );
            ?>

        </div>

        <!-- Right Column - System Info -->
        <div class="evge-support-right">
            <div class="evge-support-section evge-support-section-system-info">
                <div class="evge-section-header">
                    <div class="evge-section-header-icon">
                        <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12.1 7C12.1 6.39249 11.6075 5.9 11 5.9C10.3925 5.9 9.9 6.39249 9.9 7C9.9 7.60751 10.3925 8.1 11 8.1C11.6075 8.1 12.1 7.60751 12.1 7Z" fill="#D37362" stroke="#D37362" stroke-width="0.2"/>
                        <path d="M11 16C11.5523 16 12 15.6866 12 15.3V9.7C12 9.31341 11.5523 9 11 9C10.4477 9 10 9.31341 10 9.7V15.3C10 15.6866 10.4477 16 11 16Z" fill="#D37362"/>
                        <path d="M15.6969 2.0102L15.6769 2.15883L15.6969 2.0102C14.5052 1.84998 12.9826 1.84999 11.0593 1.85L11.0481 1.85H10.9519L10.9407 1.85C9.01745 1.84999 7.49477 1.84998 6.30312 2.0102C5.07697 2.17505 4.08559 2.52223 3.30391 3.30391C2.52223 4.08559 2.17505 5.07697 2.0102 6.30312C1.84998 7.49477 1.84999 9.01744 1.85 10.9407L1.85 10.9519V11.0481L1.85 11.0593C1.84999 12.9826 1.84998 14.5052 2.0102 15.6969L2.15876 15.6769L2.0102 15.6969C2.17505 16.923 2.52223 17.9145 3.30391 18.6961C4.08559 19.4778 5.07698 19.8249 6.30311 19.9898C7.49478 20.15 9.01746 20.15 10.9407 20.15H10.9519H11.0481H11.0593C12.9826 20.15 14.5052 20.15 15.6969 19.9898C16.923 19.8249 17.9145 19.4778 18.6961 18.6961C19.4778 17.9145 19.8249 16.923 19.9898 15.6969C20.15 14.5052 20.15 12.9826 20.15 11.0593V11.0481V10.9519V10.9407C20.15 9.01746 20.15 7.49478 19.9898 6.30313C19.8249 5.07698 19.4778 4.08559 18.6961 3.30391C17.9145 2.52223 16.923 2.17505 15.6969 2.0102ZM4.40404 4.40404L4.40404 4.40404C4.84846 3.9596 5.45682 3.69379 6.51043 3.55213C7.58645 3.40747 9.00463 3.40581 11 3.40581C12.9953 3.40581 14.4135 3.40747 15.4896 3.55213C16.5432 3.69379 17.1515 3.9596 17.596 4.40404C18.0404 4.84847 18.3062 5.45681 18.4478 6.51042L18.5965 6.49044L18.4478 6.51043C18.5925 7.58645 18.5942 9.00463 18.5942 11C18.5942 12.9953 18.5925 14.4135 18.4478 15.4896L18.5965 15.5095L18.4478 15.4896C18.3062 16.5432 18.0404 17.1515 17.596 17.596C17.1515 18.0404 16.5432 18.3062 15.4896 18.4478L15.5095 18.5965L15.4896 18.4478C14.4135 18.5925 12.9953 18.5942 11 18.5942C9.00463 18.5942 7.58645 18.5925 6.51043 18.4478L6.49044 18.5965L6.51042 18.4478C5.45681 18.3062 4.84847 18.0404 4.40404 17.596C3.9596 17.1515 3.69379 16.5432 3.55213 15.4896C3.40747 14.4135 3.40581 12.9953 3.40581 11C3.40581 9.00463 3.40747 7.58645 3.55213 6.51043C3.69379 5.45682 3.9596 4.84846 4.40404 4.40404Z" fill="#D37362" stroke="#D37362" stroke-width="0.3"/>
                        </svg>
                    </div>
                    <h2><?php esc_html_e('System Info', 'event-genius'); ?></h2>
                    <button id="evge-copy-system-info" class="button button-secondary evge-copy-button">
                        <svg width="13" height="13" viewBox="0 0 13 13" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8 1H2C1.44772 1 1 1.44772 1 2V8C1 8.55229 1.44772 9 2 9H3C3 2.5 3 3 9 3V2C9 1.44772 8.55229 1 8 1ZM2 0C0.895431 0 0 0.89543 0 2V8C0 9.10457 0.89543 10 2 10H4C3.5 2.5 1.5 3 10 3V2C10 0.895431 9.10457 0 8 0H2Z" fill="#D37362"/><path fill-rule="evenodd" clip-rule="evenodd" d="M11 4H5C4.44772 4 4 4.44772 4 5V11C4 11.5523 4.44772 12 5 12H11C11.5523 12 12 11.5523 12 11V5C12 4.44772 11.5523 4 11 4ZM5 3C3.89543 3 3 3.89543 3 5V11C3 12.1046 3.89543 13 5 13H11C12.1046 13 13 12.1046 13 11V5C13 3.89543 12.1046 3 11 3H5Z" fill="#D37362"/></svg>
                        <?php esc_html_e('Copy System Info', 'event-genius'); ?>
                    </button>
                </div>	
                <p><?php 
                    printf(
                        /* translators: %s: Link to support ticket page with the text Open a ticket*/
                        esc_html__('Do you need to contact support? %s and include your system information below.', 'event-genius'),
                        '<a href="https://wordpress.org/support/plugin/event-genius/" target="_blank">' . esc_html__('Open a ticket', 'event-genius') . '</a>'
                    );
                ?></p>
                <div class="evge-system-info evge-copyable">
                    <pre id="evge-system-info-content" class="evge-copyable-content"><?php echo esc_html($system_info_text); ?></pre>
                </div>
            </div>

            <!-- Debug Logs Section -->
            <div class="evge-support-section evge-support-section-logs">
                <div class="evge-section-header">
                    <div class="evge-section-header-icon">
                        <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11 0C4.925 0 0 4.925 0 11C0 17.075 4.925 22 11 22C17.075 22 22 17.075 22 11C22 4.925 17.075 0 11 0ZM11 20C6.037 20 2 15.963 2 11C2 6.037 6.037 2 11 2C15.963 2 20 6.037 20 11C20 15.963 15.963 20 11 20Z" fill="#D37362"/>
                            <path d="M11 5C10.448 5 10 5.448 10 6V11C10 11.265 10.105 11.52 10.293 11.707L14.293 15.707C14.683 16.098 15.317 16.098 15.707 15.707C16.098 15.317 16.098 14.683 15.707 14.293L12 10.586V6C12 5.448 11.552 5 11 5Z" fill="#D37362"/>
                        </svg>
                    </div>
                    <h2><?php esc_html_e('Debug Logs', 'event-genius'); ?></h2>
                    <button id="evge-copy-logs" class="button button-secondary evge-copy-button">
                        <svg width="13" height="13" viewBox="0 0 13 13" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8 1H2C1.44772 1 1 1.44772 1 2V8C1 8.55229 1.44772 9 2 9H3C3 2.5 3 3 9 3V2C9 1.44772 8.55229 1 8 1ZM2 0C0.895431 0 0 0.89543 0 2V8C0 9.10457 0.89543 10 2 10H4C3.5 2.5 1.5 3 10 3V2C10 0.895431 9.10457 0 8 0H2Z" fill="#D37362"/><path fill-rule="evenodd" clip-rule="evenodd" d="M11 4H5C4.44772 4 4 4.44772 4 5V11C4 11.5523 4.44772 12 5 12H11C11.5523 12 12 11.5523 12 11V5C12 4.44772 11.5523 4 11 4ZM5 3C3.89543 3 3 3.89543 3 5V11C3 12.1046 3.89543 13 5 13H11C12.1046 13 13 12.1046 13 11V5C13 3.89543 12.1046 3 11 3H5Z" fill="#D37362"/></svg>
                        <?php esc_html_e('Copy Logs', 'event-genius'); ?>
                    </button>
                </div>
                <div class="evge-logs evge-copyable">
                    <pre id="evge-logs-content" class="evge-copyable-content"><?php 
                        $debug_log_entries = \WPEventGenius\Common\Utils\Logger\DebugLogger::get_log_contents();
                        if (empty($debug_log_entries)) {
                            echo esc_html__('No log entries found.', 'event-genius');
                        } else {
                            foreach ($debug_log_entries as $entry) {
                                echo esc_html(\WPEventGenius\Common\Utils\Logger\DebugLogger::format_log_entry($entry)) . "\n\n";
                            }
                        }
                    ?></pre>
                </div>
            </div>

            <!-- Queue Status Section -->
            <?php if ($debug_mode) : ?>
            <div class="evge-support-section evge-support-section-queue">
                <div class="evge-section-header">
                    <div class="evge-section-header-icon">
                        <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11 0C4.925 0 0 4.925 0 11C0 17.075 4.925 22 11 22C17.075 22 22 17.075 22 11C22 4.925 17.075 0 11 0ZM11 20C6.037 20 2 15.963 2 11C2 6.037 6.037 2 11 2C15.963 2 20 6.037 20 11C20 15.963 15.963 20 11 20Z" fill="#D37362"/>
                            <path d="M11 5C10.448 5 10 5.448 10 6V11C10 11.265 10.105 11.52 10.293 11.707L14.293 15.707C14.683 16.098 15.317 16.098 15.707 15.707C16.098 15.317 16.098 14.683 15.707 14.293L12 10.586V6C12 5.448 11.552 5 11 5Z" fill="#D37362"/>
                        </svg>
                    </div>
                    <h2><?php esc_html_e('Series Queue Status', 'event-genius'); ?></h2>
                </div>
                <?php
                $queue = new \WPEventGenius\Common\Series\Queue\SeriesQueue(new \WPEventGenius\Common\Database());
                $queue_items = $queue->get_queue();
                
                if (empty($queue_items)) {
                    echo '<p>' . esc_html__('No items in the queue.', 'event-genius') . '</p>';
                } else {
                    echo '<pre class="evge-queue-dump">';
                    print_r($queue_items);
                    echo '</pre>';
                }
                ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>