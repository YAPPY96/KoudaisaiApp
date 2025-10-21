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
define( 'DB_NAME', '8sw3m_37n45aj6' );

/** Database username */
define( 'DB_USER', '8sw3m_y57m5ra4' );

/** Database password */
define( 'DB_PASSWORD', 'w8376&E#53' );

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
define( 'AUTH_KEY',          '{s 6Ft6)|L* CNmmTKf)UZ342X^oQ?E?p!hzvwO[`#5.)>+QPq}nKW3_Xa8%ibQ%' );
define( 'SECURE_AUTH_KEY',   ',0}@iTn&XE.*@>{w{]KATfD,D~}>VCTN$q:sZzIkmx{_,7C)jA6R,q_@1SJ9U65Y' );
define( 'LOGGED_IN_KEY',     'Nx<j)*B ZbQ(@$AIuaP#z;{8=+b-EKj=LM$rNtyV*rFF1KV%qF&C[@(OIYoMFK)c' );
define( 'NONCE_KEY',         's_sWLvzLheX [gM)WaJdqt#W=C8[t[</CTdjNoxHwxYS9w@o+.L|>8I*Y|10Z%V#' );
define( 'AUTH_SALT',         '=Vc_n?^WbM(x5;Sb cIF+^3^KxF~IC@?a|QG}Ww1+1wr@y)L&P!a@dP5hiM Xd3P' );
define( 'SECURE_AUTH_SALT',  'N)^`cv&JjfBFP[(.u8w@WpOOpcFB=pz/ksfAq?Dj`hzX=M<g%`L%FXZ)N~Qjg^pd' );
define( 'LOGGED_IN_SALT',    'xI:,tis=,;[m,;q?xSD=Cg{_{j_;4nY-S@NmQI28}v.Vo](K22_j_1#a3-;cWIIv' );
define( 'NONCE_SALT',        '[lk0>9O5}<u]Qu8Z$E_WWV<@hC~+]EJ.Ll=*G-AyR.;#%>Sk!awY_hjJ+Ve=Y8b6' );
define( 'WP_CACHE_KEY_SALT', 'VA[M vPC]^k%y>fg%30Ldzz{ZC1_3%M: LTqLsarmS+nXabzp4R/$zZwr~/xvLHz' );


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

define( 'RS_DASHBOARD_PLUGIN_SID', 'JaJiwEi3NAxpmYNo0bhAU5cMJITFJTQ_t4mnY-FRF1uiv9cjDUBbzTOQ3rW9ZSdt85HcQ_lr5kuIh2UucBRxgrJSw_ezyR8NfHDd6SYbJow.' );
define( 'RS_DASHBOARD_PLUGIN_DID', 'P3C2RPKpCK4Ee2PzwD6yFn16HiDhRvHYwxaTiGfMtoxIYqije-dWwV7dSFozKpOrdBSyg9kmCUWroF9dIbJQSjFC2Gx-5nIKhI7DtfaFhNY.' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
