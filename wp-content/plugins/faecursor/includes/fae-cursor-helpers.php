<?php
/**
 * FaeCursor Helper Functions
 * Utility functions for the plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get country name from country code
 * 
 * @param string $code Country code (e.g., 'us', 'gb', 'fr')
 * @return string Country name or uppercase code if not found
 */
function fae_get_country_name($code) {
    $countries = array(
        'ad' => 'Andorra', 'ae' => 'United Arab Emirates', 'af' => 'Afghanistan', 'ag' => 'Antigua and Barbuda',
        'ai' => 'Anguilla', 'al' => 'Albania', 'am' => 'Armenia', 'ao' => 'Angola', 'aq' => 'Antarctica',
        'ar' => 'Argentina', 'as' => 'American Samoa', 'at' => 'Austria', 'au' => 'Australia', 'aw' => 'Aruba',
        'ax' => 'Åland Islands', 'az' => 'Azerbaijan', 'ba' => 'Bosnia and Herzegovina', 'bb' => 'Barbados',
        'bd' => 'Bangladesh', 'be' => 'Belgium', 'bf' => 'Burkina Faso', 'bg' => 'Bulgaria', 'bh' => 'Bahrain',
        'bi' => 'Burundi', 'bj' => 'Benin', 'bl' => 'Saint Barthélemy', 'bm' => 'Bermuda', 'bn' => 'Brunei',
        'bo' => 'Bolivia', 'bq' => 'Caribbean Netherlands', 'br' => 'Brazil', 'bs' => 'Bahamas', 'bt' => 'Bhutan',
        'bv' => 'Bouvet Island', 'bw' => 'Botswana', 'by' => 'Belarus', 'bz' => 'Belize', 'ca' => 'Canada',
        'cc' => 'Cocos Islands', 'cd' => 'Congo', 'cf' => 'Central African Republic', 'cg' => 'Congo',
        'ch' => 'Switzerland', 'ci' => 'Côte d\'Ivoire', 'ck' => 'Cook Islands', 'cl' => 'Chile', 'cm' => 'Cameroon',
        'cn' => 'China', 'co' => 'Colombia', 'cr' => 'Costa Rica', 'cu' => 'Cuba', 'cv' => 'Cape Verde',
        'cw' => 'Curaçao', 'cx' => 'Christmas Island', 'cy' => 'Cyprus', 'cz' => 'Czech Republic', 'de' => 'Germany',
        'dj' => 'Djibouti', 'dk' => 'Denmark', 'dm' => 'Dominica', 'do' => 'Dominican Republic', 'dz' => 'Algeria',
        'ec' => 'Ecuador', 'ee' => 'Estonia', 'eg' => 'Egypt', 'eh' => 'Western Sahara', 'er' => 'Eritrea',
        'es' => 'Spain', 'et' => 'Ethiopia', 'fi' => 'Finland', 'fj' => 'Fiji', 'fk' => 'Falkland Islands',
        'fm' => 'Micronesia', 'fo' => 'Faroe Islands', 'fr' => 'France', 'ga' => 'Gabon', 'gb' => 'United Kingdom',
        'gd' => 'Grenada', 'ge' => 'Georgia', 'gf' => 'French Guiana', 'gg' => 'Guernsey', 'gh' => 'Ghana',
        'gi' => 'Gibraltar', 'gl' => 'Greenland', 'gm' => 'Gambia', 'gn' => 'Guinea', 'gp' => 'Guadeloupe',
        'gq' => 'Equatorial Guinea', 'gr' => 'Greece', 'gs' => 'South Georgia', 'gt' => 'Guatemala', 'gu' => 'Guam',
        'gw' => 'Guinea-Bissau', 'gy' => 'Guyana', 'hk' => 'Hong Kong', 'hm' => 'Heard Island', 'hn' => 'Honduras',
        'hr' => 'Croatia', 'ht' => 'Haiti', 'hu' => 'Hungary', 'id' => 'Indonesia', 'ie' => 'Ireland',
        'il' => 'Israel', 'im' => 'Isle of Man', 'in' => 'India', 'io' => 'British Indian Ocean Territory',
        'iq' => 'Iraq', 'ir' => 'Iran', 'is' => 'Iceland', 'it' => 'Italy', 'je' => 'Jersey', 'jm' => 'Jamaica',
        'jo' => 'Jordan', 'jp' => 'Japan', 'ke' => 'Kenya', 'kg' => 'Kyrgyzstan', 'kh' => 'Cambodia',
        'ki' => 'Kiribati', 'km' => 'Comoros', 'kn' => 'Saint Kitts and Nevis', 'kp' => 'North Korea',
        'kr' => 'South Korea', 'kw' => 'Kuwait', 'ky' => 'Cayman Islands', 'kz' => 'Kazakhstan', 'la' => 'Laos',
        'lb' => 'Lebanon', 'lc' => 'Saint Lucia', 'li' => 'Liechtenstein', 'lk' => 'Sri Lanka', 'lr' => 'Liberia',
        'ls' => 'Lesotho', 'lt' => 'Lithuania', 'lu' => 'Luxembourg', 'lv' => 'Latvia', 'ly' => 'Libya',
        'ma' => 'Morocco', 'mc' => 'Monaco', 'md' => 'Moldova', 'me' => 'Montenegro', 'mf' => 'Saint Martin',
        'mg' => 'Madagascar', 'mh' => 'Marshall Islands', 'mk' => 'North Macedonia', 'ml' => 'Mali',
        'mm' => 'Myanmar', 'mn' => 'Mongolia', 'mo' => 'Macao', 'mp' => 'Northern Mariana Islands',
        'mq' => 'Martinique', 'mr' => 'Mauritania', 'ms' => 'Montserrat', 'mt' => 'Malta', 'mu' => 'Mauritius',
        'mv' => 'Maldives', 'mw' => 'Malawi', 'mx' => 'Mexico', 'my' => 'Malaysia', 'mz' => 'Mozambique',
        'na' => 'Namibia', 'nc' => 'New Caledonia', 'ne' => 'Niger', 'nf' => 'Norfolk Island', 'ng' => 'Nigeria',
        'ni' => 'Nicaragua', 'nl' => 'Netherlands', 'no' => 'Norway', 'np' => 'Nepal', 'nr' => 'Nauru',
        'nu' => 'Niue', 'nz' => 'New Zealand', 'om' => 'Oman', 'pa' => 'Panama', 'pe' => 'Peru',
        'pf' => 'French Polynesia', 'pg' => 'Papua New Guinea', 'ph' => 'Philippines', 'pk' => 'Pakistan',
        'pl' => 'Poland', 'pm' => 'Saint Pierre and Miquelon', 'pn' => 'Pitcairn', 'pr' => 'Puerto Rico',
        'ps' => 'Palestine', 'pt' => 'Portugal', 'pw' => 'Palau', 'py' => 'Paraguay', 'qa' => 'Qatar',
        're' => 'Réunion', 'ro' => 'Romania', 'rs' => 'Serbia', 'ru' => 'Russia', 'rw' => 'Rwanda',
        'sa' => 'Saudi Arabia', 'sb' => 'Solomon Islands', 'sc' => 'Seychelles', 'sd' => 'Sudan',
        'se' => 'Sweden', 'sg' => 'Singapore', 'sh' => 'Saint Helena', 'si' => 'Slovenia', 'sj' => 'Svalbard',
        'sk' => 'Slovakia', 'sl' => 'Sierra Leone', 'sm' => 'San Marino', 'sn' => 'Senegal', 'so' => 'Somalia',
        'sr' => 'Suriname', 'ss' => 'South Sudan', 'st' => 'São Tomé and Príncipe', 'sv' => 'El Salvador',
        'sx' => 'Sint Maarten', 'sy' => 'Syria', 'sz' => 'Eswatini', 'tc' => 'Turks and Caicos',
        'td' => 'Chad', 'tf' => 'French Southern Territories', 'tg' => 'Togo', 'th' => 'Thailand',
        'tj' => 'Tajikistan', 'tk' => 'Tokelau', 'tl' => 'Timor-Leste', 'tm' => 'Turkmenistan', 'tn' => 'Tunisia',
        'to' => 'Tonga', 'tr' => 'Turkey', 'tt' => 'Trinidad and Tobago', 'tv' => 'Tuvalu', 'tw' => 'Taiwan',
        'tz' => 'Tanzania', 'ua' => 'Ukraine', 'ug' => 'Uganda', 'um' => 'United States Minor Outlying Islands',
        'us' => 'United States', 'uy' => 'Uruguay', 'uz' => 'Uzbekistan', 'va' => 'Vatican City',
        'vc' => 'Saint Vincent and the Grenadines', 've' => 'Venezuela', 'vg' => 'British Virgin Islands',
        'vi' => 'United States Virgin Islands', 'vn' => 'Vietnam', 'vu' => 'Vanuatu', 'wf' => 'Wallis and Futuna',
        'ws' => 'Samoa', 'xk' => 'Kosovo', 'ye' => 'Yemen', 'yt' => 'Mayotte', 'za' => 'South Africa',
        'zm' => 'Zambia', 'zw' => 'Zimbabwe'
    );
    
    $code_lower = strtolower($code);
    return isset($countries[$code_lower]) ? $countries[$code_lower] : strtoupper($code);
}

/**
 * =====================================================
 * LIMITED CUSTOMIZATION FUNCTIONS
 * =====================================================
 * 
 * Strategy: All effects are FREE to use, but some effects
 * have limited customization options for free users.
 * Pro users get full color/speed customization.
 * 
 * This encourages users to try all effects while
 * incentivizing Pro upgrade for full customization.
 */

/**
 * Check if a cursor effect has limited customization for free users
 * 
 * Effects with full customization (no limits):
 * - drop-effect, rise-effect, line-effect, duo-circle, duo-circle-2
 * 
 * All other effects have limited customization:
 * - Color: Free users get default color only, Pro users get hex picker
 * - Speed: Free users get "normal" only, Pro users get all speeds
 * 
 * @param string $effect_id Effect ID
 * @return bool True if effect has limited customization
 */
function fae_cursor_effect_has_limited_customization($effect_id) {
    // Effects with FULL customization (no limits)
    $full_customization_effects = array(
        'none',
        'drop-effect',
        'rise-effect',
        'line-effect',
        'duo-circle',
        'duo-circle-2',
    );
    
    // If effect is in the full customization list, it's NOT limited
    return !in_array($effect_id, $full_customization_effects);
}

/**
 * Get the default color for free users on limited effects
 * 
 * @return string Default hex color
 */
function fae_get_free_default_color() {
    return '#fcba03'; // Default golden/amber color
}

/**
 * Get the default speed for free users on limited effects
 * 
 * @return string Default speed value
 */
function fae_get_free_default_speed() {
    return 'normal';
}

/**
 * Check if user can customize color for this cursor effect
 * FREE VERSION: Always returns false for limited effects
 * 
 * @param string $effect_id Effect ID
 * @return bool True if user can customize color
 */
function fae_can_customize_cursor_color($effect_id) {
    // If effect doesn't have limited customization, always allow
    if (!fae_cursor_effect_has_limited_customization($effect_id)) {
        return true;
    }
    
    // Free version: never allow customization for limited effects
    return false;
}

/**
 * Check if user can customize speed for this cursor effect
 * FREE VERSION: Always returns false for limited effects
 * 
 * @param string $effect_id Effect ID
 * @return bool True if user can customize speed
 */
function fae_can_customize_cursor_speed($effect_id) {
    // If effect doesn't have limited customization, always allow
    if (!fae_cursor_effect_has_limited_customization($effect_id)) {
        return true;
    }
    
    // Free version: never allow customization for limited effects
    return false;
}

/**
 * Check if a keyboard effect has limited color customization for free users
 * 
 * Effects with FULL color customization (no limits):
 * - sparkle-keys (uses multi-color instead of regular color)
 * 
 * All other effects have limited color customization:
 * - Color: Free users get default color only, Pro users get hex picker
 * 
 * @param string $effect_id Effect ID
 * @return bool True if effect has limited color customization
 */
function fae_keyboard_effect_has_limited_color($effect_id) {
    // Effects with FULL color customization (no limits)
    $full_customization_effects = array(
        'none',
        'sparkle-keys', // Uses multi-color feature instead
    );
    
    // If effect is in the full customization list, it's NOT limited
    return !in_array($effect_id, $full_customization_effects);
}

/**
 * Get the default color for free users on keyboard effects
 * 
 * @return string Default hex color
 */
function fae_get_keyboard_free_default_color() {
    return '#667eea'; // Default purple/blue color for keyboard
}

/**
 * Check if user can customize color for this keyboard effect
 * FREE VERSION: Always returns false for limited effects
 * 
 * @param string $effect_id Effect ID
 * @return bool True if user can customize color
 */
function fae_can_customize_keyboard_color($effect_id) {
    // If effect doesn't have limited color, always allow
    if (!fae_keyboard_effect_has_limited_color($effect_id)) {
        return true;
    }
    
    // Free version: never allow customization for limited effects
    return false;
}

/**
 * Check if a particle/screen effect has limited customization for free users
 * 
 * Effects with FULL customization (no limits):
 * - Snowfall (particle-10) - fully customizable
 * 
 * All other particle effects have limited customization:
 * - Color: Free users get default color only, Pro users get hex picker
 * - Speed: Free users get normal only (for effects that support speed), Pro users get all speeds
 * 
 * @param string $effect_id Effect ID
 * @return bool True if effect has limited customization
 */
function fae_particle_effect_has_limited_customization($effect_id) {
    // Effects with FULL customization (no limits)
    $full_customization_effects = array(
        'none',
        'particle-10', // Snowfall - fully customizable
    );
    
    // If effect is in the full customization list, it's NOT limited
    return !in_array($effect_id, $full_customization_effects);
}

/**
 * Get the default color for free users on particle effects
 * 
 * @return string Default hex color
 */
function fae_get_particle_free_default_color() {
    return '#a855f7'; // Default purple color for particle effects
}

/**
 * Get the default speed for free users on particle effects
 * 
 * @return string Default speed
 */
function fae_get_particle_free_default_speed() {
    return 'normal';
}

/**
 * Check if user can customize color for this particle effect
 * FREE VERSION: Always returns false for limited effects
 * 
 * @param string $effect_id Effect ID
 * @return bool True if user can customize color
 */
function fae_can_customize_particle_color($effect_id) {
    // If effect doesn't have limited customization, always allow
    if (!fae_particle_effect_has_limited_customization($effect_id)) {
        return true;
    }
    
    // Free version: never allow customization for limited effects
    return false;
}

/**
 * Check if user can customize speed for this particle effect
 * FREE VERSION: Always returns false for limited effects
 * 
 * @param string $effect_id Effect ID
 * @return bool True if user can customize speed
 */
function fae_can_customize_particle_speed($effect_id) {
    // If effect doesn't have limited customization, always allow
    if (!fae_particle_effect_has_limited_customization($effect_id)) {
        return true;
    }
    
    // Free version: never allow customization for limited effects
    return false;
}


