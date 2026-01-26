<?php

declare(strict_types=1);

namespace Finder\ComparisonExperience\Admin;

use Finder\ComparisonExperience\Plugin;

/**
 * Admin settings page for Comparison Experience plugin.
 *
 * @package Finder\ComparisonExperience\Admin
 */
final class SettingsPage
{
    private const PAGE_SLUG = 'comparison-experience-settings';
    private const OPTION_GROUP = 'comparison_experience_options';
    private const SECTION_GENERAL = 'comparison_experience_general';

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
            __('Comparison Experience Settings', 'comparison-experience'),
            __('Comparison Experience', 'comparison-experience'),
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
            'comparison_experience_enabled',
            [
                'type' => 'boolean',
                'default' => true,
                'sanitize_callback' => 'rest_sanitize_boolean',
            ]
        );

        // Add settings section.
        add_settings_section(
            self::SECTION_GENERAL,
            __('General Settings', 'comparison-experience'),
            [$this, 'renderSectionDescription'],
            self::PAGE_SLUG
        );

        // Add settings fields.
        add_settings_field(
            'comparison_experience_enabled',
            __('Enable Plugin', 'comparison-experience'),
            [$this, 'renderEnabledField'],
            self::PAGE_SLUG,
            self::SECTION_GENERAL
        );

        /**
         * Fires after Comparison Experience settings are registered.
         *
         * Use this hook to add custom settings fields.
         *
         * @since 1.0.0
         * @param string $pageSlug The settings page slug.
         * @param string $optionGroup The option group name.
         */
        do_action(
            'finder/comparison_experience/admin/register_settings',
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
                'comparison_experience_messages',
                'comparison_experience_message',
                __('Settings Saved', 'comparison-experience'),
                'updated'
            );
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <?php settings_errors('comparison_experience_messages'); ?>
            <form action="options.php" method="post">
                <?php
                settings_fields(self::OPTION_GROUP);
                do_settings_sections(self::PAGE_SLUG);
                submit_button(__('Save Settings', 'comparison-experience'));
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
            'Configure the Comparison Experience plugin settings.',
            'comparison-experience'
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
                name="comparison_experience_enabled"
                value="1"
                <?php checked($enabled); ?>
            />
            <?php esc_html_e('Enable Comparison Experience functionality', 'comparison-experience'); ?>
        </label>
        <p class="description">
            <?php esc_html_e(
                'When disabled, all Comparison Experience features will be turned off.',
                'comparison-experience'
            ); ?>
        </p>
        <?php
    }
}
