<?php

declare(strict_types=1);

/**
 * Plugin Name: Product Data
 * Plugin URI: https://finder.com.au
 * Description: Manages product data and provides data access interfaces.
 * Version: 1.0.0
 * Author: Finder
 * Author URI: https://finder.com.au
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: product-data
 * Domain Path: /languages
 * Requires PHP: 8.3
 *
 * @package Finder\ProductData
 */

namespace Finder\ProductData;

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants.
define('PRODUCT_DATA_VERSION', '1.0.0');
define('PRODUCT_DATA_PLUGIN_FILE', __FILE__);
define('PRODUCT_DATA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PRODUCT_DATA_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoloader.
spl_autoload_register(function (string $class): void {
    $prefix = 'Finder\\ProductData\\';
    $baseDir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

/**
 * Initialize the plugin.
 *
 * @return void
 */
function init(): void
{
    // Check if plugin is enabled.
    if (!Plugin::isEnabled()) {
        return;
    }

    // Initialize the plugin.
    $plugin = Plugin::getInstance();
    $plugin->init();
}

// Hook into WordPress.
add_action('plugins_loaded', __NAMESPACE__ . '\\init');

// Register activation/deactivation hooks.
register_activation_hook(__FILE__, [Plugin::class, 'activate']);
register_deactivation_hook(__FILE__, [Plugin::class, 'deactivate']);
