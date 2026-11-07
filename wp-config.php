<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('WP_CACHE', true);
define( 'WPCACHEHOME', '/var/www/thinhphattsc_usr/data/www/thinhphattsc.com/wp-content/plugins/wp-super-cache/' );
define( 'DB_NAME', 'thinhphattsc' );

/** Database username */
define( 'DB_USER', 'thinhphattsc' );

/** Database password */
define( 'DB_PASSWORD', 'EZg}NjW_Ns:/u=xa' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

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
define( 'AUTH_KEY',          'u#ZGN9Vzom|!cV _+z#|PDb6mP5cR,1SZ_l74gJfE GD*sS]n]O7UC/bF6<z~hWM' );
define( 'SECURE_AUTH_KEY',   'GVoi<M?7-mAG+$i@%ox:[ lxJZY?N)b:b9Vb/a_#ScO|sR9QSm]Yd_G]Zfa(/}}(' );
define( 'LOGGED_IN_KEY',     'M.qvJKKnCI-$g;oTF-6$4^} @{o*^JON7n&S%Q&v>o-rIu~t?J5qA#A./J[y%s+d' );
define( 'NONCE_KEY',         'a7,hKBrX:S0fIPaawVjbcauJDNiI47o`v-Lby0@UeZ/fKWCE]3vS<$:|Zrq9*Cnv' );
define( 'AUTH_SALT',         'zj<<5Or#[dW$:nj{{);aOV?Ey^!W@2TqY|r|E_z>1uMef]iVp/]U <0ha];*@:q(' );
define( 'SECURE_AUTH_SALT',  'FxmfQhgiQj`b]wU^G>hIcY9Mb-Y.7X#r[>_lP2R?Ei*/lM>a*s3|;Va`wP3.|t6$' );
define( 'LOGGED_IN_SALT',    'KqI8R>S66A4W:aspt(TR$[w5aE:m+%=&hi]fMGXOWMEqvr`wP|4p~AgghCF fj]]' );
define( 'NONCE_SALT',        '68Zg%`_3#Vbx.9&fKxxqWJgc9+Ebcu9kI||[UPvm`-pl]N;Qz5Y9>FfOEQDnUd+h' );
define( 'WP_CACHE_KEY_SALT', '0}5Xaan`Kz^F*)^)plWr0AZ2]xbhA.nBA[J$`G5#VO63Wh5UhTX~VyTgXVy50^o*' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) {
	$_SERVER['HTTPS'] = 'on';
}

/** Performance optimizations */
define( 'WP_MEMORY_LIMIT', '256M' );
define( 'WP_MAX_MEMORY_LIMIT', '512M' );
define( 'WP_POST_REVISIONS', 5 );
define( 'AUTOSAVE_INTERVAL', 180 );
define( 'EMPTY_TRASH_DAYS', 7 );
define( 'DISALLOW_FILE_EDIT', true );
define( 'DISABLE_WP_CRON', true );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
