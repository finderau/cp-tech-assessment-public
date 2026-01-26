<?php

declare(strict_types=1);

namespace Finder\ComparisonExperience;

use Finder\ComparisonExperience\Admin\SettingsPage;
use Finder\ComparisonExperience\Assets\AssetLoader;
use Finder\ComparisonExperience\Shortcode\TableShortcode;

/**
 * Main plugin class.
 *
 * @package Finder\ComparisonExperience
 */
final class Plugin
{
    private const OPTION_ENABLED = 'comparison_experience_enabled';

    private static ?Plugin $instance = null;

    private bool $initialized = false;

    private ?AssetLoader $assetLoader = null;

    private ?TableShortcode $tableShortcode = null;

    /**
     * Private constructor to enforce singleton pattern.
     */
    private function __construct()
    {
    }

    /**
     * Get singleton instance.
     *
     * @return Plugin
     */
    public static function getInstance(): Plugin
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Check if the plugin is enabled.
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return (bool) get_option(self::OPTION_ENABLED, true);
    }

    /**
     * Enable the plugin.
     *
     * @return void
     */
    public static function enable(): void
    {
        update_option(self::OPTION_ENABLED, true);

        /**
         * Fires when the Comparison Experience plugin is enabled.
         *
         * @since 1.0.0
         */
        do_action('finder/comparison_experience/enabled');
    }

    /**
     * Disable the plugin.
     *
     * @return void
     */
    public static function disable(): void
    {
        update_option(self::OPTION_ENABLED, false);

        /**
         * Fires when the Comparison Experience plugin is disabled.
         *
         * @since 1.0.0
         */
        do_action('finder/comparison_experience/disabled');
    }

    /**
     * Initialize the plugin.
     *
     * @return void
     */
    public function init(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->initialized = true;

        // Initialize admin settings.
        if (is_admin()) {
            $settingsPage = new SettingsPage();
            $settingsPage->init();
        }

        // Register hooks.
        $this->registerHooks();

        /**
         * Fires after the Comparison Experience plugin has been initialized.
         *
         * @since 1.0.0
         * @param Plugin $plugin The plugin instance.
         */
        do_action('finder/comparison_experience/initialized', $this);
    }

    /**
     * Register WordPress hooks.
     *
     * @return void
     */
    private function registerHooks(): void
    {
        // Register custom hooks for extensibility.
        add_action('init', [$this, 'registerPostTypes']);
        add_action('init', [$this, 'registerTaxonomies']);
        add_action('init', [$this, 'registerShortcodes']);

        /**
         * Filter to modify comparison experience capabilities.
         *
         * @since 1.0.0
         * @param array $capabilities Default capabilities.
         */
        $capabilities = apply_filters('finder/comparison_experience/capabilities', [
            'manage_comparisons' => 'manage_options',
        ]);
    }

    /**
     * Get the asset loader instance.
     *
     * @return AssetLoader
     */
    public function getAssetLoader(): AssetLoader
    {
        if ($this->assetLoader === null) {
            $this->assetLoader = new AssetLoader();
        }

        return $this->assetLoader;
    }

    /**
     * Register shortcodes.
     *
     * @return void
     */
    public function registerShortcodes(): void
    {
        /**
         * Fires before shortcodes are registered.
         *
         * @since 1.0.0
         */
        do_action('finder/comparison_experience/before_register_shortcodes');

        if ($this->tableShortcode === null) {
            $this->tableShortcode = new TableShortcode($this->getAssetLoader());
        }

        $this->tableShortcode->init();

        /**
         * Fires after shortcodes are registered.
         *
         * @since 1.0.0
         */
        do_action('finder/comparison_experience/after_register_shortcodes');
    }

    /**
     * Register custom post types.
     *
     * @return void
     */
    public function registerPostTypes(): void
    {
        /**
         * Fires before custom post types are registered.
         *
         * @since 1.0.0
         */
        do_action('finder/comparison_experience/before_register_post_types');

        // Post type registration would go here.

        /**
         * Fires after custom post types are registered.
         *
         * @since 1.0.0
         */
        do_action('finder/comparison_experience/after_register_post_types');
    }

    /**
     * Register custom taxonomies.
     *
     * @return void
     */
    public function registerTaxonomies(): void
    {
        /**
         * Fires before custom taxonomies are registered.
         *
         * @since 1.0.0
         */
        do_action('finder/comparison_experience/before_register_taxonomies');

        // Taxonomy registration would go here.

        /**
         * Fires after custom taxonomies are registered.
         *
         * @since 1.0.0
         */
        do_action('finder/comparison_experience/after_register_taxonomies');
    }

    /**
     * Plugin activation hook.
     *
     * @return void
     */
    public static function activate(): void
    {
        // Set default options.
        add_option(self::OPTION_ENABLED, true);

        /**
         * Fires when the Comparison Experience plugin is activated.
         *
         * @since 1.0.0
         */
        do_action('finder/comparison_experience/activated');

        // Flush rewrite rules.
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation hook.
     *
     * @return void
     */
    public static function deactivate(): void
    {
        /**
         * Fires when the Comparison Experience plugin is deactivated.
         *
         * @since 1.0.0
         */
        do_action('finder/comparison_experience/deactivated');

        // Flush rewrite rules.
        flush_rewrite_rules();
    }
}
