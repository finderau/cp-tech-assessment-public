<?php

declare(strict_types=1);

namespace Finder\ProductData\Data\Filters;

/**
 * Basic implementation of product filters.
 *
 * Supports special filter fields for health insurance products:
 * - hospitalTreatments: Filter by available hospital treatments
 * - extrasTreatments: Filter by available extras treatments
 * - hospitalCover: Filter by hospital cover tier
 * - coverType: Filter by cover type (Hospital, Extras, Combined)
 *
 * Also supports generic field filtering with various comparators.
 *
 * @package Finder\ProductData\Data\Filters
 */
final class BasicFiltersImpl implements FiltersInterface
{
    /**
     * Special filter field names that require custom handling.
     */
    private const SPECIAL_FIELDS = [
        'hospitalTreatments',
        'extrasTreatments',
        'hospitalCover',
        'coverType',
    ];

    /**
     * {@inheritDoc}
     */
    public function applyFilters(array $products, array $filters): array
    {
        foreach ($filters as $filter) {
            if (!$this->isValidFilter($filter)) {
                continue;
            }

            $field = $filter['field'];
            $comparator = $filter['comparator'];
            $value = $filter['value'] ?? null;

            $products = array_filter(
                $products,
                fn(array $product): bool => $this->matchesFilter(
                    $product,
                    $field,
                    $comparator,
                    $value
                )
            );
        }

        return $products;
    }

    /**
     * Check if a filter configuration is valid.
     *
     * @param array<string, mixed> $filter Filter configuration.
     * @return bool True if valid, false otherwise.
     */
    private function isValidFilter(array $filter): bool
    {
        return isset($filter['field'], $filter['comparator'])
            && is_string($filter['field'])
            && is_string($filter['comparator']);
    }

    /**
     * Check if a product matches a filter.
     *
     * @param array<string, mixed> $product Product data.
     * @param string $field Field to compare.
     * @param string $comparator Comparison operator.
     * @param mixed $value Value to compare against.
     * @return bool True if matches, false otherwise.
     */
    private function matchesFilter(
        array $product,
        string $field,
        string $comparator,
        mixed $value
    ): bool {
        // Handle special filter fields.
        if (in_array($field, self::SPECIAL_FIELDS, true)) {
            return $this->matchesSpecialFilter($product, $field, $comparator, $value);
        }

        // Handle generic field filtering.
        return $this->matchesGenericFilter($product, $field, $comparator, $value);
    }

    /**
     * Handle special filter fields that require custom logic.
     *
     * @param array<string, mixed> $product Product data.
     * @param string $field Special field name.
     * @param string $comparator Comparison operator.
     * @param mixed $value Value to compare against.
     * @return bool True if matches, false otherwise.
     */
    private function matchesSpecialFilter(
        array $product,
        string $field,
        string $comparator,
        mixed $value
    ): bool {
        return match ($field) {
            'hospitalTreatments' => $this->matchesTreatmentFilter(
                $product,
                'Hospital',
                $comparator,
                $value
            ),
            'extrasTreatments' => $this->matchesTreatmentFilter(
                $product,
                'Extras',
                $comparator,
                $value
            ),
            'hospitalCover' => $this->matchesHospitalCoverFilter(
                $product,
                $comparator,
                $value
            ),
            'coverType' => $this->matchesCoverTypeFilter(
                $product,
                $comparator,
                $value
            ),
            default => true,
        };
    }

    /**
     * Filter by treatment availability in a specific details section.
     *
     * Checks if a treatment with the given name has isAvailable = true
     * in the specified details section (Hospital or Extras).
     *
     * @param array<string, mixed> $product Product data.
     * @param string $sectionTitle The details section title (Hospital or Extras).
     * @param string $comparator Comparison operator.
     * @param mixed $treatmentName Treatment name to look for.
     * @return bool True if matches, false otherwise.
     */
    private function matchesTreatmentFilter(
        array $product,
        string $sectionTitle,
        string $comparator,
        mixed $treatmentName
    ): bool {
        if (!is_string($treatmentName)) {
            return true;
        }

        $section = $this->getDetailsSection($product, $sectionTitle);

        if ($section === null) {
            // No section found - doesn't match for 'contains', matches for 'notcontains'
            return $comparator === 'notcontains';
        }

        $attributes = $section['attributes'] ?? [];
        $hasAvailableTreatment = false;

        foreach ($attributes as $attribute) {
            if (!isset($attribute['name'])) {
                continue;
            }

            // Check if treatment name matches (case-insensitive).
            if (strcasecmp($attribute['name'], $treatmentName) === 0) {
                // Treatment is available if isAvailable is true (not false or "restricted").
                if (isset($attribute['isAvailable']) && $attribute['isAvailable'] === true) {
                    $hasAvailableTreatment = true;
                    break;
                }
            }
        }

        return match ($comparator) {
            'contains' => $hasAvailableTreatment,
            'notcontains' => !$hasAvailableTreatment,
            default => true,
        };
    }

    /**
     * Filter by hospital cover tier.
     *
     * Looks for the "Hospital tier" attribute in the Hospital details section
     * and compares its value.
     *
     * @param array<string, mixed> $product Product data.
     * @param string $comparator Comparison operator.
     * @param mixed $coverValue Cover tier value to match.
     * @return bool True if matches, false otherwise.
     */
    private function matchesHospitalCoverFilter(
        array $product,
        string $comparator,
        mixed $coverValue
    ): bool {
        if (!is_string($coverValue)) {
            return true;
        }

        $hospitalSection = $this->getDetailsSection($product, 'Hospital');

        if ($hospitalSection === null) {
            return $comparator === 'notlike';
        }

        $hospitalTier = null;
        $attributes = $hospitalSection['attributes'] ?? [];

        foreach ($attributes as $attribute) {
            if (($attribute['name'] ?? '') === 'Hospital tier' && isset($attribute['value'])) {
                $hospitalTier = $attribute['value'];
                break;
            }
        }

        if ($hospitalTier === null) {
            return $comparator === 'notlike';
        }

        return match ($comparator) {
            'eq' => strcasecmp($hospitalTier, $coverValue) === 0,
            'noteq' => strcasecmp($hospitalTier, $coverValue) !== 0,
            'like' => stripos($hospitalTier, $coverValue) !== false,
            'notlike' => stripos($hospitalTier, $coverValue) === false,
            default => true,
        };
    }

    /**
     * Filter by cover type (Hospital, Extras, or Combined).
     *
     * - Hospital: Has Hospital details with > 0 included treatments
     * - Extras: Has Extras details with > 0 included treatments
     * - Combined: Has both Hospital and Extras covers
     *
     * @param array<string, mixed> $product Product data.
     * @param string $comparator Comparison operator.
     * @param mixed $coverType Cover type value (Hospital, Extras, or Combined).
     * @return bool True if matches, false otherwise.
     */
    private function matchesCoverTypeFilter(
        array $product,
        string $comparator,
        mixed $coverType
    ): bool {
        if (!is_string($coverType)) {
            return true;
        }

        $hasHospital = $this->hasValidCover($product, 'Hospital');
        $hasExtras = $this->hasValidCover($product, 'Extras');

        $productCoverType = match (true) {
            $hasHospital && $hasExtras => 'Combined',
            $hasHospital => 'Hospital',
            $hasExtras => 'Extras',
            default => null,
        };

        $normalizedCoverType = ucfirst(strtolower($coverType));

        return match ($comparator) {
            'eq' => $productCoverType === $normalizedCoverType,
            'noteq' => $productCoverType !== $normalizedCoverType,
            'contains' => $this->coverTypeContains($productCoverType, $normalizedCoverType),
            'notcontains' => !$this->coverTypeContains($productCoverType, $normalizedCoverType),
            default => true,
        };
    }

    /**
     * Check if a product cover type "contains" the requested type.
     *
     * Combined covers contain both Hospital and Extras.
     *
     * @param string|null $productCoverType The product's cover type.
     * @param string $requestedType The requested cover type.
     * @return bool True if contains, false otherwise.
     */
    private function coverTypeContains(?string $productCoverType, string $requestedType): bool
    {
        if ($productCoverType === null) {
            return false;
        }

        if ($productCoverType === $requestedType) {
            return true;
        }

        // Combined contains both Hospital and Extras.
        if ($productCoverType === 'Combined') {
            return in_array($requestedType, ['Hospital', 'Extras'], true);
        }

        return false;
    }

    /**
     * Check if a product has valid cover for a section type.
     *
     * A cover is valid if the section exists and has > 0 included treatments
     * (determined by subtitle format "X/Y included" where X > 0).
     *
     * @param array<string, mixed> $product Product data.
     * @param string $sectionTitle Section title to check.
     * @return bool True if has valid cover, false otherwise.
     */
    private function hasValidCover(array $product, string $sectionTitle): bool
    {
        $section = $this->getDetailsSection($product, $sectionTitle);

        if ($section === null) {
            return false;
        }

        $subtitle = $section['subtitle'] ?? '';

        // Parse subtitle format: "X/Y included".
        if (preg_match('/^(\d+)\/\d+\s+included$/i', $subtitle, $matches)) {
            return (int) $matches[1] > 0;
        }

        return false;
    }

    /**
     * Get a specific details section by title.
     *
     * @param array<string, mixed> $product Product data.
     * @param string $title Section title to find.
     * @return array<string, mixed>|null Section data or null if not found.
     */
    private function getDetailsSection(array $product, string $title): ?array
    {
        $details = $product['details'] ?? [];

        foreach ($details as $section) {
            if (($section['title'] ?? '') === $title) {
                return $section;
            }
        }

        return null;
    }

    /**
     * Handle generic field filtering with standard comparators.
     *
     * @param array<string, mixed> $product Product data.
     * @param string $field Field path (supports dot notation).
     * @param string $comparator Comparison operator.
     * @param mixed $value Value to compare against.
     * @return bool True if matches, false otherwise.
     */
    private function matchesGenericFilter(
        array $product,
        string $field,
        string $comparator,
        mixed $value
    ): bool {
        $fieldValue = $this->getNestedValue($product, $field);

        return match ($comparator) {
            'eq' => $fieldValue === $value,
            'noteq' => $fieldValue !== $value,
            'gt' => is_numeric($fieldValue) && is_numeric($value) && $fieldValue > $value,
            'gte' => is_numeric($fieldValue) && is_numeric($value) && $fieldValue >= $value,
            'lt' => is_numeric($fieldValue) && is_numeric($value) && $fieldValue < $value,
            'lte' => is_numeric($fieldValue) && is_numeric($value) && $fieldValue <= $value,
            'like' => is_string($fieldValue) && is_string($value)
                && stripos($fieldValue, $value) !== false,
            'notlike' => is_string($fieldValue) && is_string($value)
                && stripos($fieldValue, $value) === false,
            'contains' => $this->arrayContains($fieldValue, $value),
            'notcontains' => !$this->arrayContains($fieldValue, $value),
            'empty' => $this->isEmpty($fieldValue),
            'notempty' => !$this->isEmpty($fieldValue),
            'in' => is_array($value) && in_array($fieldValue, $value, true),
            'notin' => is_array($value) && !in_array($fieldValue, $value, true),
            'within' => is_array($fieldValue) && in_array($value, $fieldValue, true),
            default => true,
        };
    }

    /**
     * Get a nested value from an array using dot notation.
     *
     * @param array<string, mixed> $data Source array.
     * @param string $field Field path (e.g., "metadata.basePrice").
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
     * Check if an array contains a value.
     *
     * @param mixed $haystack The array to search.
     * @param mixed $needle The value to search for.
     * @return bool True if found, false otherwise.
     */
    private function arrayContains(mixed $haystack, mixed $needle): bool
    {
        if (is_array($haystack)) {
            return in_array($needle, $haystack, true);
        }

        if (is_string($haystack) && is_string($needle)) {
            return stripos($haystack, $needle) !== false;
        }

        return false;
    }

    /**
     * Check if a value is empty.
     *
     * @param mixed $value Value to check.
     * @return bool True if empty, false otherwise.
     */
    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }
}
