<?php

declare(strict_types=1);

namespace Finder\ProductData\Admin;

use Finder\ProductData\Plugin;

/**
 * Admin settings page for Product Data plugin.
 *
 * @package Finder\ProductData\Admin
 */
final class SettingsPage
{
    private const PAGE_SLUG = 'product-data-settings';
    private const OPTION_GROUP = 'product_data_options';
    private const SECTION_GENERAL = 'product_data_general';

    /**
     * Initialize the settings page.
     *
     * @return void
     */
    public function init(): void
    {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    /**
     * Add the admin menu page.
     *
     * @return void
     */
    public function addMenuPage(): void
    {
        add_options_page(
            __('Product Data Settings', 'product-data'),
            __('Product Data', 'product-data'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'renderSettingsPage']
        );
    }

    /**
     * Register plugin settings.
     *
     * @return void
     */
    public function registerSettings(): void
    {
        // Register settings.
        register_setting(
            self::OPTION_GROUP,
            'product_data_enabled',
            [
                'type' => 'boolean',
                'default' => true,
                'sanitize_callback' => 'rest_sanitize_boolean',
            ]
        );

        register_setting(
            self::OPTION_GROUP,
            'product_data_cache_ttl',
            [
                'type' => 'integer',
                'default' => 3600,
                'sanitize_callback' => 'absint',
            ]
        );

        // Add settings section.
        add_settings_section(
            self::SECTION_GENERAL,
            __('General Settings', 'product-data'),
            [$this, 'renderSectionDescription'],
            self::PAGE_SLUG
        );

        // Add settings fields.
        add_settings_field(
            'product_data_enabled',
            __('Enable Plugin', 'product-data'),
            [$this, 'renderEnabledField'],
            self::PAGE_SLUG,
            self::SECTION_GENERAL
        );

        add_settings_field(
            'product_data_cache_ttl',
            __('Cache TTL (seconds)', 'product-data'),
            [$this, 'renderCacheTtlField'],
            self::PAGE_SLUG,
            self::SECTION_GENERAL
        );

        /**
         * Fires after Product Data settings are registered.
         *
         * Use this hook to add custom settings fields.
         *
         * @since 1.0.0
         * @param string $pageSlug The settings page slug.
         * @param string $optionGroup The option group name.
         */
        do_action(
            'finder/product_data/admin/register_settings',
            self::PAGE_SLUG,
            self::OPTION_GROUP
        );
    }

    /**
     * Render the settings page.
     *
     * @return void
     */
    public function renderSettingsPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Check for settings update.
        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'product_data_messages',
                'product_data_message',
                __('Settings Saved', 'product-data'),
                'updated'
            );
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <?php settings_errors('product_data_messages'); ?>
            <form action="options.php" method="post">
                <?php
                settings_fields(self::OPTION_GROUP);
                do_settings_sections(self::PAGE_SLUG);
                submit_button(__('Save Settings', 'product-data'));
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render the section description.
     *
     * @return void
     */
    public function renderSectionDescription(): void
    {
        echo '<p>' . esc_html__(
            'Configure the Product Data plugin settings.',
            'product-data'
        ) . '</p>';
    }

    /**
     * Render the enabled field.
     *
     * @return void
     */
    public function renderEnabledField(): void
    {
        $enabled = Plugin::isEnabled();
        ?>
        <label>
            <input
                type="checkbox"
                name="product_data_enabled"
                value="1"
                <?php checked($enabled); ?>
            />
            <?php esc_html_e('Enable Product Data functionality', 'product-data'); ?>
        </label>
        <p class="description">
            <?php esc_html_e(
                'When disabled, all Product Data features will be turned off.',
                'product-data'
            ); ?>
        </p>
        <?php
    }

    /**
     * Render the cache TTL field.
     *
     * @return void
     */
    public function renderCacheTtlField(): void
    {
        $cacheTtl = (int) get_option('product_data_cache_ttl', 3600);
        ?>
        <input
            type="number"
            name="product_data_cache_ttl"
            value="<?php echo esc_attr((string) $cacheTtl); ?>"
            min="0"
            step="1"
            class="small-text"
        />
        <p class="description">
            <?php esc_html_e(
                'How long to cache product data (in seconds). Set to 0 to disable caching.',
                'product-data'
            ); ?>
        </p>
        <?php
    }
}
