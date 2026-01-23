<?php
/**
 * WordPress Playground - Front Controller
 *
 * This file is the entry point for WordPress. It loads the WordPress
 * environment from the subdirectory where WordPress core is installed.
 *
 * @package WordPress
 */

/**
 * Tells WordPress to load the WordPress theme and output it.
 *
 * @var bool
 */
define('WP_USE_THEMES', true);

/** Loads the WordPress Environment and Template */
require __DIR__ . '/wordpress/wp-blog-header.php';
