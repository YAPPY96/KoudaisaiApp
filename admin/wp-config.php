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
define( 'DB_NAME', '8sw3m_77e3j35b' );

/** Database username */
define( 'DB_USER', '8sw3m_an5c68e7' );

/** Database password */
define( 'DB_PASSWORD', '4y(T5HNa4#6' );

/** Database hostname */
define( 'DB_HOST', 'mysql20.onamae.ne.jp' );

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
define( 'AUTH_KEY',          'q]?,4gI[Y%exP7&?(Gv^a|s8E@6(j*x2X! E:Ni=DOwX%%ZI2feN);!wF>0.:~l+' );
define( 'SECURE_AUTH_KEY',   'NL@kR:z!%1!!#6B4S8 VOc1b&P<-MRDig&Q</Ijw<ZIM ,7[w(M`}C3iZfE;2Lw7' );
define( 'LOGGED_IN_KEY',     'bbeqYLDrI./#abwCz9nM%rSWrjgJ9dml7#Zk8tJZQe^T`RTvpiADNfc<P6Y>I`qm' );
define( 'NONCE_KEY',         '&h}W0amAs]++XOx(KvyXN9C=aI ]srK7!zQ/sZFtVsqWA?@GXwFz-*g7zhAiKC(Q' );
define( 'AUTH_SALT',         ']z}x6v)O+>hrM%GXI>52R&9h1mssI#)+/7G4T}Ld{GU`V2$]^^MYQ#9[VgI5[w+2' );
define( 'SECURE_AUTH_SALT',  'K#e4=h $GoK7}`HN>[~0gbb2S&avEH`dTSFw@ur,u4l^?Pq-g~ZFDMY[z*y#lN. ' );
define( 'LOGGED_IN_SALT',    'U`@M#S%`}5j1Q8e_|Rf(`oR}pz,d?e%JA1mCSt|0U|0mCL)o}BEqwn5YQ35`QGAA' );
define( 'NONCE_SALT',        'aeX+sW6$AY7+*o>(|D<_[Q+&7#N&0_$K<5uz~g%/qY0c=Y-O:w457=#O$sG6]fs)' );
define( 'WP_CACHE_KEY_SALT', 'K6#UDQBroT2K892VX(}}iP])5hJgV@7Mpu!dC_!)2q]F/jifC^4fC#kjH05=r7s?' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === "https") {
    $_SERVER['HTTPS'] = 'on';
    define('FORCE_SSL_LOGIN', true);
    define('FORCE_SSL_ADMIN', true);
}


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

define( 'RS_DASHBOARD_PLUGIN_SID', 'bD-bKy9Uh6HvUe5JwOFgZeupmsGOGgyBQX2SZnvxq80JmBbtkBMZR_FZSVCpSLEbuxlxqZZHGpLRPSCx75SC2j3whEC7nbZvM2F7LCPzMJY.' );
define( 'RS_DASHBOARD_PLUGIN_DID', 'jFQJ9MLA4Z2vFHquP0ZExaesrTgYN3qy9cuu_HVrOFMeGDxceiw2rVc95Q96jXgdlGqTz8sVg9y9_aaSeLnGX525irB4CQj6c9aVU6WXgao.' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
