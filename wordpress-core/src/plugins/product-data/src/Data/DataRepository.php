<?php

declare(strict_types=1);

namespace Finder\ProductData\Data;

use Finder\ProductData\Data\Filters\FiltersInterface;

/**
 * Repository for accessing product and provider data from JSON files.
 *
 * @package Finder\ProductData\Data
 */
final class DataRepository
{
    private const DATA_FILE = 'data.json';
    private const PROVIDERS_FILE = 'providers.json';

    private string $dataDir;

    private FiltersInterface $filters;

    /**
     * Cached product data.
     *
     * @var array<int, array<string, mixed>>|null
     */
    private ?array $products = null;

    /**
     * Cached provider data.
     *
     * @var array<int, array<string, mixed>>|null
     */
    private ?array $providers = null;

    /**
     * Constructor.
     *
     * @param FiltersInterface $filters Filters implementation.
     */
    public function __construct(FiltersInterface $filters)
    {
        $this->dataDir = PRODUCT_DATA_PLUGIN_DIR . 'data/';
        $this->filters = $filters;
    }

    /**
     * Get all products with optional filtering, sorting, and pagination.
     *
     * @param array<int, array<string, mixed>> $filters Filter configurations.
     * @param array<int, array<string, mixed>> $sort Sort configurations.
     * @param array<string, int> $pagination Pagination configuration.
     * @return array<string, mixed> Result with products, total count, and pagination info.
     */
    public function getProducts(
        array $filters = [],
        array $sort = [],
        array $pagination = []
    ): array {
        $products = $this->loadProducts();

        // Apply filters.
        if (!empty($filters)) {
            $products = $this->filters->applyFilters($products, $filters);
        }

        $totalCount = count($products);

        // Apply sorting.
        if (!empty($sort)) {
            $products = $this->applySort($products, $sort);
        }

        // Apply pagination.
        $offset = $pagination['offset'] ?? 0;
        $pageSize = $pagination['pageSize'] ?? 20;
        $products = array_slice($products, $offset, $pageSize);

        return [
            'products' => array_values($products),
            'totalCount' => $totalCount,
            'offset' => $offset,
            'pageSize' => $pageSize,
        ];
    }

    /**
     * Get a single product by ID.
     *
     * @param string $productId The product ID.
     * @return array<string, mixed>|null Product data or null if not found.
     */
    public function getProductById(string $productId): ?array
    {
        $products = $this->loadProducts();

        foreach ($products as $product) {
            if (isset($product['id']) && $product['id'] === $productId) {
                return $product;
            }
        }

        return null;
    }

    /**
     * Get all providers.
     *
     * @return array<int, array<string, mixed>> Provider data.
     */
    public function getProviders(): array
    {
        return $this->loadProviders();
    }

    /**
     * Get a single provider by ID.
     *
     * @param string $providerId The provider ID.
     * @return array<string, mixed>|null Provider data or null if not found.
     */
    public function getProviderById(string $providerId): ?array
    {
        $providers = $this->loadProviders();

        foreach ($providers as $provider) {
            if (isset($provider['id']) && $provider['id'] === $providerId) {
                return $provider;
            }
        }

        return null;
    }

    /**
     * Load products from JSON file.
     *
     * @return array<int, array<string, mixed>> Product data.
     */
    private function loadProducts(): array
    {
        if ($this->products !== null) {
            return $this->products;
        }

        $filePath = $this->dataDir . self::DATA_FILE;

        if (!file_exists($filePath)) {
            $this->products = [];
            return $this->products;
        }

        $content = file_get_contents($filePath);

        if ($content === false) {
            $this->products = [];
            return $this->products;
        }

        $data = json_decode($content, true);

        if (!is_array($data)) {
            $this->products = [];
            return $this->products;
        }

        $this->products = $data;

        return $this->products;
    }

    /**
     * Load providers from JSON file.
     *
     * @return array<int, array<string, mixed>> Provider data.
     */
    private function loadProviders(): array
    {
        if ($this->providers !== null) {
            return $this->providers;
        }

        $filePath = $this->dataDir . self::PROVIDERS_FILE;

        if (!file_exists($filePath)) {
            $this->providers = [];
            return $this->providers;
        }

        $content = file_get_contents($filePath);

        if ($content === false) {
            $this->providers = [];
            return $this->providers;
        }

        $data = json_decode($content, true);

        if (!is_array($data)) {
            $this->providers = [];
            return $this->providers;
        }

        $this->providers = $data;

        return $this->providers;
    }

    /**
     * Get a nested value from an array using dot notation.
     *
     * @param array<string, mixed> $data Source array.
     * @param string $field Field path (supports dot notation, e.g., "metadata.basePrice").
     * @return mixed The value or null if not found.
     */
    private function getNestedValue(array $data, string $field): mixed
    {
        $keys = explode('.', $field);
        $value = $data;

        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }

    /**
     * Apply sorting to products.
     *
     * @param array<int, array<string, mixed>> $products Products to sort.
     * @param array<int, array<string, mixed>> $sortConfigs Sort configurations.
     * @return array<int, array<string, mixed>> Sorted products.
     */
    private function applySort(array $products, array $sortConfigs): array
    {
        usort($products, function (array $a, array $b) use ($sortConfigs): int {
            foreach ($sortConfigs as $sortConfig) {
                if (!isset($sortConfig['field'], $sortConfig['direction'])) {
                    continue;
                }

                $field = $sortConfig['field'];
                $direction = $sortConfig['direction'];

                $valueA = $this->getNestedValue($a, $field);
                $valueB = $this->getNestedValue($b, $field);

                $comparison = $this->compareValues($valueA, $valueB);

                if ($comparison !== 0) {
                    return $direction === 'DESCENDING' ? -$comparison : $comparison;
                }
            }

            return 0;
        });

        return $products;
    }

    /**
     * Compare two values for sorting.
     *
     * @param mixed $a First value.
     * @param mixed $b Second value.
     * @return int Comparison result (-1, 0, or 1).
     */
    private function compareValues(mixed $a, mixed $b): int
    {
        // Handle nulls.
        if ($a === null && $b === null) {
            return 0;
        }
        if ($a === null) {
            return -1;
        }
        if ($b === null) {
            return 1;
        }

        // Numeric comparison.
        if (is_numeric($a) && is_numeric($b)) {
            return $a <=> $b;
        }

        // String comparison.
        if (is_string($a) && is_string($b)) {
            return strcasecmp($a, $b);
        }

        // Boolean comparison.
        if (is_bool($a) && is_bool($b)) {
            return $a <=> $b;
        }

        return 0;
    }
}
