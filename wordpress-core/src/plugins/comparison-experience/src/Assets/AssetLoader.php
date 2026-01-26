<?php

declare(strict_types=1);

namespace Finder\ComparisonExperience\Assets;

/**
 * Handles loading React application assets from the manifest file.
 *
 * @package Finder\ComparisonExperience\Assets
 */
final class AssetLoader
{
    private const MANIFEST_FILE = 'asset-manifest.json';
    private const SCRIPT_HANDLE = 'comparison-experience-app';
    private const STYLE_HANDLE = 'comparison-experience-styles';

    private string $assetsDir;
    private string $assetsUrl;

    /**
     * Cached manifest data.
     *
     * @var array<string, mixed>|null
     */
    private ?array $manifest = null;

    private bool $enqueued = false;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->assetsDir = COMPARISON_EXPERIENCE_PLUGIN_DIR . 'assets/';
        $this->assetsUrl = COMPARISON_EXPERIENCE_PLUGIN_URL . 'assets/';
    }

    /**
     * Enqueue the React application assets.
     *
     * @return void
     */
    public function enqueue(): void
    {
        // Only enqueue once per page load.
        if ($this->enqueued) {
            return;
        }

        $this->enqueued = true;

        // Check if we're in development mode.
        if ($this->isDevelopmentMode()) {
            $this->enqueueDevelopmentAssets();
            return;
        }

        // Load production assets from manifest.
        $this->enqueueProductionAssets();
    }

    /**
     * Check if we're in development mode.
     *
     * @return bool
     */
    private function isDevelopmentMode(): bool
    {
        /**
         * Filter to enable/disable development mode.
         *
         * @since 1.0.0
         * @param bool $isDev Whether development mode is enabled.
         */
        return apply_filters(
            'comparison_experience/assets/dev_mode',
            defined('COMPARISON_EXPERIENCE_DEV_MODE') && COMPARISON_EXPERIENCE_DEV_MODE
        );
    }

    /**
     * Enqueue development assets (Vite dev server).
     *
     * @return void
     */
    private function enqueueDevelopmentAssets(): void
    {
        $devServerUrl = $this->getDevServerUrl();

        // Enqueue Vite client for HMR.
        wp_enqueue_script(
            self::SCRIPT_HANDLE . '-vite-client',
            $devServerUrl . '/@vite/client',
            [],
            null,
            true
        );

        // Enqueue main entry point.
        wp_enqueue_script(
            self::SCRIPT_HANDLE,
            $devServerUrl . '/src/main.tsx',
            [self::SCRIPT_HANDLE . '-vite-client'],
            null,
            true
        );

        // Add type="module" to script tags.
        add_filter('script_loader_tag', [$this, 'addModuleType'], 10, 2);

        // Localize script settings.
        $this->localizeSettings();
    }

    /**
     * Get the Vite dev server URL.
     *
     * @return string
     */
    private function getDevServerUrl(): string
    {
        /**
         * Filter the Vite dev server URL.
         *
         * @since 1.0.0
         * @param string $url The dev server URL.
         */
        return apply_filters(
            'comparison_experience/assets/dev_server_url',
            'http://localhost:5173'
        );
    }

    /**
     * Enqueue production assets from manifest.
     *
     * @return void
     */
    private function enqueueProductionAssets(): void
    {
        $manifest = $this->loadManifest();

        if ($manifest === null) {
            $this->enqueueProductionFallback();
            return;
        }

        // Find the main entry point in manifest.
        $mainEntry = $this->findMainEntry($manifest);

        if ($mainEntry === null) {
            $this->enqueueProductionFallback();
            return;
        }

        // Enqueue main JavaScript.
        if (isset($mainEntry['file'])) {
            wp_enqueue_script(
                self::SCRIPT_HANDLE,
                $this->assetsUrl . $mainEntry['file'],
                ['wp-element'],
                COMPARISON_EXPERIENCE_VERSION,
                true
            );

            // Add type="module" for ES modules.
            add_filter('script_loader_tag', [$this, 'addModuleType'], 10, 2);
        }

        // Enqueue CSS files.
        if (isset($mainEntry['css']) && is_array($mainEntry['css'])) {
            foreach ($mainEntry['css'] as $index => $cssFile) {
                wp_enqueue_style(
                    self::STYLE_HANDLE . ($index > 0 ? "-{$index}" : ''),
                    $this->assetsUrl . $cssFile,
                    [],
                    COMPARISON_EXPERIENCE_VERSION
                );
            }
        }

        // Localize script settings.
        $this->localizeSettings();
    }

    /**
     * Fallback for production when manifest is not available.
     *
     * @return void
     */
    private function enqueueProductionFallback(): void
    {
        // Try to load assets without manifest (legacy support).
        $jsFile = $this->assetsDir . 'main.js';
        $cssFile = $this->assetsDir . 'main.css';

        if (file_exists($jsFile)) {
            wp_enqueue_script(
                self::SCRIPT_HANDLE,
                $this->assetsUrl . 'main.js',
                ['wp-element'],
                COMPARISON_EXPERIENCE_VERSION,
                true
            );

            add_filter('script_loader_tag', [$this, 'addModuleType'], 10, 2);
        }

        if (file_exists($cssFile)) {
            wp_enqueue_style(
                self::STYLE_HANDLE,
                $this->assetsUrl . 'main.css',
                [],
                COMPARISON_EXPERIENCE_VERSION
            );
        }

        $this->localizeSettings();
    }

    /**
     * Load the manifest file.
     *
     * @return array<string, mixed>|null
     */
    private function loadManifest(): ?array
    {
        if ($this->manifest !== null) {
            return $this->manifest;
        }

        $manifestPath = $this->assetsDir . self::MANIFEST_FILE;

        if (!file_exists($manifestPath)) {
            return null;
        }

        $content = file_get_contents($manifestPath);

        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);

        if (!is_array($data)) {
            return null;
        }

        $this->manifest = $data;

        return $this->manifest;
    }

    /**
     * Find the main entry point in the manifest.
     *
     * @param array<string, mixed> $manifest The manifest data.
     * @return array<string, mixed>|null
     */
    private function findMainEntry(array $manifest): ?array
    {
        // Vite manifest format: look for src/main.tsx or index.html.
        $possibleEntries = ['src/main.tsx', 'src/main.ts', 'src/main.jsx', 'src/main.js', 'index.html'];

        foreach ($possibleEntries as $entry) {
            if (isset($manifest[$entry]) && is_array($manifest[$entry])) {
                return $manifest[$entry];
            }
        }

        return null;
    }

    /**
     * Localize settings for the React app.
     *
     * @return void
     */
    private function localizeSettings(): void
    {
        $settings = [
            'apiBase' => rest_url('api/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
            'version' => COMPARISON_EXPERIENCE_VERSION,
        ];

        /**
         * Filter the localized settings passed to the React app.
         *
         * @since 1.0.0
         * @param array<string, mixed> $settings The settings array.
         */
        $settings = apply_filters('comparison_experience/assets/settings', $settings);

        wp_localize_script(self::SCRIPT_HANDLE, 'comparisonTableSettings', $settings);
    }

    /**
     * Add type="module" attribute to script tags.
     *
     * @param string $tag The script tag HTML.
     * @param string $handle The script handle.
     * @return string Modified script tag.
     */
    public function addModuleType(string $tag, string $handle): string
    {
        $handles = [
            self::SCRIPT_HANDLE,
            self::SCRIPT_HANDLE . '-vite-client',
        ];

        if (in_array($handle, $handles, true)) {
            $tag = str_replace(' src', ' type="module" src', $tag);
        }

        return $tag;
    }
}
