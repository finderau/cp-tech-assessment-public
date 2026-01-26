<?php

declare(strict_types=1);

/**
 * Plugin Name: Comparison Experience
 * Plugin URI: https://finder.com.au
 * Description: Provides comparison experience functionality for product comparisons.
 * Version: 1.0.0
 * Author: Finder
 * Author URI: https://finder.com.au
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: comparison-experience
 * Domain Path: /languages
 * Requires PHP: 8.3
 *
 * @package Finder\ComparisonExperience
 */

namespace Finder\ComparisonExperience;

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants.
define('COMPARISON_EXPERIENCE_VERSION', '1.0.0');
define('COMPARISON_EXPERIENCE_PLUGIN_FILE', __FILE__);
define('COMPARISON_EXPERIENCE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('COMPARISON_EXPERIENCE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoloader.
spl_autoload_register(function (string $class): void {
    $prefix = 'Finder\\ComparisonExperience\\';
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
