<?php

declare(strict_types=1);

/**
 * PHPUnit bootstrap file.
 */

// Composer autoloader.
require_once __DIR__ . '/../vendor/autoload.php';

// Define WordPress constants needed for tests.
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/../wordpress/');
}

if (!defined('PRODUCT_DATA_PLUGIN_DIR')) {
    define('PRODUCT_DATA_PLUGIN_DIR', __DIR__ . '/../src/plugins/product-data/');
}
