<?php

declare(strict_types=1);

namespace Finder\ProductData\Data\Filters;

/**
 * Interface for applying filters to product data.
 *
 * @package Finder\ProductData\Data\Filters
 */
interface FiltersInterface
{
    /**
     * Apply filters to a collection of products.
     *
     * Filters are combined with AND logic - all filters must match for a product
     * to be included in the result.
     *
     * @param array<int, array<string, mixed>> $products Products to filter.
     * @param array<int, array<string, mixed>> $filters Filter configurations.
     *        Each filter should contain:
     *        - field: string - The field to filter on
     *        - comparator: string - The comparison operator
     *        - value: mixed - The value to compare against (optional for some comparators)
     * @return array<int, array<string, mixed>> Filtered products.
     */
    public function applyFilters(array $products, array $filters): array;
}
