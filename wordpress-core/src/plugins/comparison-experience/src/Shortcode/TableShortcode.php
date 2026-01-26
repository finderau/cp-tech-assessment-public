<?php

declare(strict_types=1);

namespace Finder\ComparisonExperience\Shortcode;

use Finder\ComparisonExperience\Assets\AssetLoader;

/**
 * Handles the [comparison_table] shortcode registration and rendering.
 *
 * @package Finder\ComparisonExperience\Shortcode
 */
final class TableShortcode
{
    private const SHORTCODE_TAG = 'comparison_table';
    private const CONTAINER_ID = 'comparison-table-root';

    private AssetLoader $assetLoader;

    /**
     * Constructor.
     *
     * @param AssetLoader $assetLoader Asset loader instance.
     */
    public function __construct(AssetLoader $assetLoader)
    {
        $this->assetLoader = $assetLoader;
    }

    /**
     * Initialize the shortcode.
     *
     * @return void
     */
    public function init(): void
    {
        add_shortcode(self::SHORTCODE_TAG, [$this, 'render']);
    }

    /**
     * Render the shortcode output.
     *
     * @param array<string, mixed>|string $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render(array|string $atts = []): string
    {
        // Normalize attributes.
        $atts = is_array($atts) ? $atts : [];

        /**
         * Filter the shortcode attributes before rendering.
         *
         * @since 1.0.0
         * @param array<string, mixed> $atts The shortcode attributes.
         */
        $atts = apply_filters('comparison_experience/shortcode/attributes', $atts);

        // Enqueue assets when shortcode is rendered.
        $this->assetLoader->enqueue();

        // Build data attributes for the React app.
        $dataAttributes = $this->buildDataAttributes($atts);

        /**
         * Filter the container data attributes.
         *
         * @since 1.0.0
         * @param array<string, string> $dataAttributes The data attributes.
         * @param array<string, mixed> $atts The shortcode attributes.
         */
        $dataAttributes = apply_filters(
            'comparison_experience/shortcode/data_attributes',
            $dataAttributes,
            $atts
        );

        // Build the HTML output.
        $html = $this->buildHtml($dataAttributes);

        /**
         * Filter the shortcode HTML output.
         *
         * @since 1.0.0
         * @param string $html The HTML output.
         * @param array<string, mixed> $atts The shortcode attributes.
         */
        return apply_filters('comparison_experience/shortcode/html', $html, $atts);
    }

    /**
     * Build data attributes for the container element.
     *
     * @param array<string, mixed> $atts Shortcode attributes.
     * @return array<string, string> Data attributes.
     */
    private function buildDataAttributes(array $atts): array
    {
        $dataAttributes = [
            'api-base' => rest_url('api/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
        ];

        // Pass through any shortcode attributes as data attributes.
        foreach ($atts as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $dataAttributes[$key] = (string) $value;
            }
        }

        return $dataAttributes;
    }

    /**
     * Build the HTML output.
     *
     * @param array<string, string> $dataAttributes Data attributes.
     * @return string HTML output.
     */
    private function buildHtml(array $dataAttributes): string
    {
        $attributesHtml = '';

        foreach ($dataAttributes as $key => $value) {
            $attributesHtml .= sprintf(
                ' data-%s="%s"',
                esc_attr($key),
                esc_attr($value)
            );
        }

        return sprintf(
            '<div id="%s"%s></div>',
            esc_attr(self::CONTAINER_ID),
            $attributesHtml
        );
    }
}
