<?php
/**
 * WordPress Playground - Configuration
 *
 * This configuration file uses environment variables for all sensitive
 * and environment-specific settings. See config/playground.defaults.env
 * for available variables.
 *
 * @package WordPress
 */

// ** Reverse Proxy / Load Balancer Support ** //
// Detect HTTPS from X-Forwarded-Proto header (set by Traefik/nginx)
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
}

// ** Database settings ** //
define('DB_NAME', getenv('DB_NAME') ?: 'wp_playground_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASS') ?: '');
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');

// ** Authentication keys and salts ** //
// Generate unique keys at: https://api.wordpress.org/secret-key/1.1/salt/
// For production, set these via environment variables
define('AUTH_KEY', getenv('AUTH_KEY') ?: 'put-your-unique-phrase-here');
define('SECURE_AUTH_KEY', getenv('SECURE_AUTH_KEY') ?: 'put-your-unique-phrase-here');
define('LOGGED_IN_KEY', getenv('LOGGED_IN_KEY') ?: 'put-your-unique-phrase-here');
define('NONCE_KEY', getenv('NONCE_KEY') ?: 'put-your-unique-phrase-here');
define('AUTH_SALT', getenv('AUTH_SALT') ?: 'put-your-unique-phrase-here');
define('SECURE_AUTH_SALT', getenv('SECURE_AUTH_SALT') ?: 'put-your-unique-phrase-here');
define('LOGGED_IN_SALT', getenv('LOGGED_IN_SALT') ?: 'put-your-unique-phrase-here');
define('NONCE_SALT', getenv('NONCE_SALT') ?: 'put-your-unique-phrase-here');

// ** Database table prefix ** //
$table_prefix = getenv('DB_TABLE_PREFIX') ?: 'wp_';

// ** Site URLs ** //
// WP_HOME: The URL users visit (e.g., https://playground.finder.dev)
// WP_SITEURL: Where WordPress core files live (e.g., https://playground.finder.dev/wordpress)
if (getenv('WP_HOME')) {
    define('WP_HOME', getenv('WP_HOME'));
}
if (getenv('WP_SITEURL')) {
    // WP_SITEURL can be relative (/wordpress) or absolute
    $siteurl = getenv('WP_SITEURL');
    if (strpos($siteurl, '/') === 0 && defined('WP_HOME')) {
        // Relative path - prepend WP_HOME
        define('WP_SITEURL', rtrim(WP_HOME, '/') . $siteurl);
    } else {
        define('WP_SITEURL', $siteurl);
    }
}

// ** wp-content directory ** //
// Uses WordPress default: /var/www/src/wordpress/wp-content
// Custom themes/plugins are mounted as subdirectories via docker-compose

// ** Debugging ** //
define('WP_DEBUG', filter_var(getenv('WP_DEBUG') ?: false, FILTER_VALIDATE_BOOLEAN));
define('WP_DEBUG_LOG', filter_var(getenv('WP_DEBUG_LOG') ?: false, FILTER_VALIDATE_BOOLEAN));
define('WP_DEBUG_DISPLAY', filter_var(getenv('WP_DEBUG_DISPLAY') ?: false, FILTER_VALIDATE_BOOLEAN));
define('SCRIPT_DEBUG', filter_var(getenv('SCRIPT_DEBUG') ?: false, FILTER_VALIDATE_BOOLEAN));

// ** Performance ** //
define('WP_CACHE', filter_var(getenv('WP_CACHE') ?: false, FILTER_VALIDATE_BOOLEAN));
define('WP_MEMORY_LIMIT', getenv('WP_MEMORY_LIMIT') ?: '256M');
define('WP_MAX_MEMORY_LIMIT', getenv('WP_MAX_MEMORY_LIMIT') ?: '512M');

// ** Cron ** //
define('DISABLE_WP_CRON', filter_var(getenv('DISABLE_WP_CRON') ?: false, FILTER_VALIDATE_BOOLEAN));

// ** Security ** //
define('FORCE_SSL_ADMIN', filter_var(getenv('FORCE_SSL_ADMIN') ?: true, FILTER_VALIDATE_BOOLEAN));
define('DISALLOW_FILE_EDIT', filter_var(getenv('DISALLOW_FILE_EDIT') ?: true, FILTER_VALIDATE_BOOLEAN));

// ** Revisions ** //
if (getenv('WP_POST_REVISIONS')) {
    define('WP_POST_REVISIONS', (int) getenv('WP_POST_REVISIONS'));
}

// ** Redis Object Cache (if using redis plugin) ** //
if (getenv('REDIS_OBJECT_CACHE_DB_HOST')) {
    define('WP_REDIS_HOST', getenv('REDIS_OBJECT_CACHE_DB_HOST'));
    define('WP_REDIS_PORT', getenv('REDIS_OBJECT_CACHE_DB_PORT') ?: 6379);
}

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory (subdirectory install). */
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/wordpress/');
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
