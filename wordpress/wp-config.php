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
define( 'DB_NAME', 'avocat_client_db' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'SYSCOA90' );

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
define( 'AUTH_KEY',         'iGT&]1-,oP $y80+XXeEV^q4Y1wk]%|Egv]=k7`%P(]%-j|#;U<ekbseyUcOPC6K' );
define( 'SECURE_AUTH_KEY',  '=M`6}<0@e6_WMoFv7#Pla&SD2aMb*Q(Q~%q`#?y.+G}4x]l`FvUk?40+9uXObl{;' );
define( 'LOGGED_IN_KEY',    '3fJuTJi:5[0S?rG__j!zQMajlRKGx>7kHD6%g;,ALe3DMay`z/w1AW4`rvRx9A?z' );
define( 'NONCE_KEY',        '=T,LjCF-c]qZ%Md_^_DAs!H/^ lev9]fwh)+/EkX]I83_&o?i&$qOyD/3EXkh9n%' );
define( 'AUTH_SALT',        'MmV%Db=(B2q~Bo3vs&WWXv(?}qZlw/Y&TTJ$t*%jke{-ruk$XQBoPx 5xa+a:F$&' );
define( 'SECURE_AUTH_SALT', ']`1#I1~.1<8FjWJfKv6HWl8^hTRDx:MG7k*hp?[_8~KG{-x8D2P}TCh$Ox%)%S>!' );
define( 'LOGGED_IN_SALT',   '_iGV|#N%.c8_!$61J6QFyCn`51;D:7*6TdZbM|fX83&CUvyUq6-pwmO4r-vQa0:_' );
define( 'NONCE_SALT',       'COhO;MaU/unVlAPbwbB=mlChJLGJnV8,V>quu)A_90Y}r0RA7DNBMmGTcl?4>Opc' );

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
