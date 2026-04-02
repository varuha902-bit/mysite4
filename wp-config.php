<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'mysite4' );

/** Database username */
define( 'DB_USER', 'admin' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'T,^1%E?TIh-P7AOiK uV!7l?cw3_F6P3sOM)0/5@[+seE<ygC?T^+(@AgX$VP-os' );
define( 'SECURE_AUTH_KEY',  'dr;fMp(wPg:Vkhy29zlZX.e>S9|f1R?-~CGo&mA&4I4@jFj?/STR YoX}tj>DSU|' );
define( 'LOGGED_IN_KEY',    's0SlI{9l?zD;}Qu4pG=zFj%Me~(.GEznKfqVVje;5c10_3Ax?|y`Tmqks(T_f=NX' );
define( 'NONCE_KEY',        'c@{{M}^E-a7)R/~b@]LG<4||/gcVfJ.Ey=#ITRH^^,|JD!h7;B9>+C*yt>DjuTG0' );
define( 'AUTH_SALT',        's|@FK{EQl|J:{/plsYQd7fSF<Xby`.HbO*(s.B5r-GMZ<_4~(=*zrF05:sugzQSV' );
define( 'SECURE_AUTH_SALT', '3JX!,%qO.E%9j^+06^P#CyAj>/,ykl55N,xdAoX]oPU7AnF=[`QxphjPJ|f71}F?' );
define( 'LOGGED_IN_SALT',   '68_lO>FzDSn<d/]r[aQ/~8Fii n_i}Y-g[0dX@@,=nWBd TnEqrYq,OU?a9ILHhD' );
define( 'NONCE_SALT',       'lH%u {=OR(5x 8URn)}r?DfF2Q{6ibOjL`]iU?i?*m$O<<*}AT98oti9xna~<svP' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
