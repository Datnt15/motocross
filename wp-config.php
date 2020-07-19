<?php
/** Enable W3 Total Cache */
define('WP_CACHE', true); // Added by W3 Total Cache

define('WP_AUTO_UPDATE_CORE', 'minor');// This setting is required to make sure that WordPress updates can be properly managed in WordPress Toolkit. Remove this line if this WordPress website is not managed by WordPress Toolkit anymore.
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'wordpress_2');

/** MySQL database username */
define('DB_USER', 'wordpress_a');

/** MySQL database password */
define('DB_PASSWORD', 'Qc06zSv9V#');

/** MySQL hostname */
define('DB_HOST', 'localhost:3306');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'Uqmz6MVX&*1Y@0rX%*SsOc1Y7v1)!0*#X72SVICG2OxO16XU7)pkg%fxZM24O6qN');
define('SECURE_AUTH_KEY',  'WKrGVK@XFVC%ncGOgrj3Pm5MrKu0%IETHLeTzABNDJvyGyGv5TzLHiw!0t2X(*Ko');
define('LOGGED_IN_KEY',    'Bd(mzBfvDMbP^yRJI!1FuIKAsqOE1sV3E@w6YgIyMfMW*JvvGb1R#OHQ%jvuPzUR');
define('NONCE_KEY',        'y(^m4Iq9DMONTPFww#hNy1ESNSvBGw*Vj#HV(LkYwMMJY8wGxJ1HVFA)fexkBAEA');
define('AUTH_SALT',        'FANvsx)RB^8Zn&fn^5Aeauq*TPqtJeiQ@uf&Gyr(n&#aQZrwmKr0l^tlC!O)ZF)9');
define('SECURE_AUTH_SALT', 'K&d6OFkg3sp8qJ2#i7!IY!&SD#JxJn#7j#(x#*6Annj^OfcnL#hp1PNYogrs9u*Z');
define('LOGGED_IN_SALT',   'twzTE%EdFU0DQcdWGS%Qi^C4g3TSlsiV*Jn7V@qJpTmrCS%CI474R*9fJp9I6hoL');
define('NONCE_SALT',       'rAe4DZsheXazOR2l%O6rW^Gdg@GvnCi3zE%U^tDt0G6theTLX75MOrQuvfG#a3SI');
/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);
define('WP_DEBUG_DISPLAY', false);
/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');

define( 'WP_ALLOW_MULTISITE', true );

define ('FS_METHOD', 'direct');
