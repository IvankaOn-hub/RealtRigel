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
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

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
define( 'AUTH_KEY',          'kGVH d#=;ko7}c2jpJV%3LylUjz:R@F}{AhX%lXTs^uBPch;D/,gv*wAv`?^k//(' );
define( 'SECURE_AUTH_KEY',   ',.M9f@,o`G)9vJ|+c~lAEXE0rEgEZ@^^9-psS>i2cHFA_)bEnLEro4+-(:R5rfn%' );
define( 'LOGGED_IN_KEY',     '?m6,BIU*|{<D**-jtA^P/O`SYBQ2^p~&y6/1@kIP2Hj0,hPWk>nz!bEVbGqpfOF]' );
define( 'NONCE_KEY',         '@a?i:6hM_Muj-NW~h/OLCu^B]}VU#W#+^5J{W_)>U7V?F|44q{~;%J][9:M .F]`' );
define( 'AUTH_SALT',         'oWS[!9HXxY%OCKk*US6*{^u@t_I~zBA.Z#AycH08h->t{8o8MC4p1;r$_HR7&P7s' );
define( 'SECURE_AUTH_SALT',  'J$n>C,c631e9cZ]Ni>siM{TG4e;]d]|D69C>yJX,34Aaj/4 *s`_;MX{C&EF( 6X' );
define( 'LOGGED_IN_SALT',    'wgl9pd@&Q`<|O<9ojbE%oxW]p1f(-E#!UZ2{W]*}>);NAU|WnZiQe1f&,!XuG*43' );
define( 'NONCE_SALT',        '/{xCzW1~L-O]oTM`^)(j_Cy=1J>7~u~UU:q+w(tEP:[[FpJ<aYFl1t@:RWWm^mdU' );
define( 'WP_CACHE_KEY_SALT', '<*G-)ivme(6UY>=(c[Aq4naOKFzdAN?l^>W*[tH=?UA#]@@IC|l5d@NO)4xvpJ,(' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'realtrg_';


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

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
