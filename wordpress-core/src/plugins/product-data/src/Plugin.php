<?php

declare(strict_types=1);

namespace Finder\ProductData;

use Finder\ProductData\Admin\SettingsPage;
use Finder\ProductData\Data\DataRepository;
use Finder\ProductData\Data\Filters\BasicFiltersImpl;
use Finder\ProductData\Rest\RestController;

/**
 * Main plugin class.
 *
 * @package Finder\ProductData
 */
final class Plugin
{
    private const OPTION_ENABLED = 'product_data_enabled';

    private static ?Plugin $instance = null;

    private bool $initialized = false;

    private ?DataRepository $repository = null;

    private ?RestController $restController = null;

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
         * Fires when the Product Data plugin is enabled.
         *
         * @since 1.0.0
         */
        do_action('finder/product_data/enabled');
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
         * Fires when the Product Data plugin is disabled.
         *
         * @since 1.0.0
         */
        do_action('finder/product_data/disabled');
    }

    /**
     * Get the data repository instance.
     *
     * @return DataRepository
     */
    public function getRepository(): DataRepository
    {
        if ($this->repository === null) {
            $filters = new BasicFiltersImpl();
            $this->repository = new DataRepository($filters);
        }

        return $this->repository;
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
         * Fires after the Product Data plugin has been initialized.
         *
         * @since 1.0.0
         * @param Plugin $plugin The plugin instance.
         */
        do_action('finder/product_data/initialized', $this);
    }

    /**
     * Register WordPress hooks.
     *
     * @return void
     */
    private function registerHooks(): void
    {
        // Register REST API endpoints.
        add_action('rest_api_init', [$this, 'registerRestRoutes']);

        /**
         * Filter to modify product data capabilities.
         *
         * @since 1.0.0
         * @param array $capabilities Default capabilities.
         */
        $capabilities = apply_filters('finder/product_data/capabilities', [
            'manage_product_data' => 'manage_options',
            'read_product_data' => 'read',
        ]);
    }

    /**
     * Register REST API routes.
     *
     * @return void
     */
    public function registerRestRoutes(): void
    {
        /**
         * Fires before REST routes are registered.
         *
         * @since 1.0.0
         */
        do_action('finder/product_data/before_register_rest_routes');

        // Initialize and register REST controller routes.
        if ($this->restController === null) {
            $this->restController = new RestController($this->getRepository());
        }

        $this->restController->registerRoutes();

        /**
         * Fires after REST routes are registered.
         *
         * @since 1.0.0
         */
        do_action('finder/product_data/after_register_rest_routes');
    }

    /**
     * Get product data by ID.
     *
     * @param string $productId The product ID.
     * @return array|null Product data or null if not found.
     */
    public function getProduct(string $productId): ?array
    {
        /**
         * Filter product ID before retrieval.
         *
         * @since 1.0.0
         * @param string $productId The product ID.
         */
        $productId = apply_filters('finder/product_data/get_product_id', $productId);

        // Retrieve product from repository.
        $productData = $this->getRepository()->getProductById($productId);

        /**
         * Filter product data after retrieval.
         *
         * @since 1.0.0
         * @param array|null $productData The product data.
         * @param string $productId The product ID.
         */
        return apply_filters('finder/product_data/get_product', $productData, $productId);
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
         * Fires when the Product Data plugin is activated.
         *
         * @since 1.0.0
         */
        do_action('finder/product_data/activated');

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
         * Fires when the Product Data plugin is deactivated.
         *
         * @since 1.0.0
         */
        do_action('finder/product_data/deactivated');

        // Flush rewrite rules.
        flush_rewrite_rules();
    }
}
