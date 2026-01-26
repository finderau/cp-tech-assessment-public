<?php

declare(strict_types=1);

namespace Finder\ProductData\Tests\Unit\Data\Filters;

use Finder\ProductData\Data\Filters\BasicFiltersImpl;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for BasicFiltersImpl.
 */
final class BasicFiltersImplTest extends TestCase
{
    private BasicFiltersImpl $filters;

    protected function setUp(): void
    {
        $this->filters = new BasicFiltersImpl();
    }

    // =========================================================================
    // Generic Filter Tests
    // =========================================================================

    #[Test]
    public function applyFiltersReturnsAllProductsWhenNoFilters(): void
    {
        $products = [
            ['id' => '1', 'name' => 'Product 1'],
            ['id' => '2', 'name' => 'Product 2'],
        ];

        $result = $this->filters->applyFilters($products, []);

        $this->assertCount(2, $result);
    }

    #[Test]
    public function eqComparatorMatchesExactValue(): void
    {
        $products = [
            ['id' => '1', 'providerName' => 'Provider A'],
            ['id' => '2', 'providerName' => 'Provider B'],
            ['id' => '3', 'providerName' => 'Provider A'],
        ];

        $filters = [
            ['field' => 'providerName', 'comparator' => 'eq', 'value' => 'Provider A'],
        ];

        $result = $this->filters->applyFilters($products, $filters);

        $this->assertCount(2, $result);
        $this->assertEquals('Provider A', array_values($result)[0]['providerName']);
        $this->assertEquals('Provider A', array_values($result)[1]['providerName']);
    }

    #[Test]
    public function noteqComparatorExcludesExactValue(): void
    {
        $products = [
            ['id' => '1', 'providerName' => 'Provider A'],
            ['id' => '2', 'providerName' => 'Provider B'],
        ];

        $filters = [
            ['field' => 'providerName', 'comparator' => 'noteq', 'value' => 'Provider A'],
        ];

        $result = $this->filters->applyFilters($products, $filters);

        $this->assertCount(1, $result);
        $this->assertEquals('Provider B', array_values($result)[0]['providerName']);
    }

    #[Test]
    public function gtComparatorFiltersNumericGreaterThan(): void
    {
        $products = [
            ['id' => '1', 'metadata' => ['basePrice' => 50]],
            ['id' => '2', 'metadata' => ['basePrice' => 100]],
            ['id' => '3', 'metadata' => ['basePrice' => 75]],
        ];

        $filters = [
            ['field' => 'metadata.basePrice', 'comparator' => 'gt', 'value' => 60],
        ];

        $result = $this->filters->applyFilters($products, $filters);

        $this->assertCount(2, $result);
    }

    #[Test]
    public function gteComparatorFiltersNumericGreaterThanOrEqual(): void
    {
        $products = [
            ['id' => '1', 'metadata' => ['basePrice' => 50]],
            ['id' => '2', 'metadata' => ['basePrice' => 100]],
            ['id' => '3', 'metadata' => ['basePrice' => 75]],
        ];

        $filters = [
            ['field' => 'metadata.basePrice', 'comparator' => 'gte', 'value' => 75],
        ];

        $result = $this->filters->applyFilters($products, $filters);

        $this->assertCount(2, $result);
    }

    #[Test]
    public function ltComparatorFiltersNumericLessThan(): void
    {
        $products = [
            ['id' => '1', 'metadata' => ['basePrice' => 50]],
            ['id' => '2', 'metadata' => ['basePrice' => 100]],
            ['id' => '3', 'metadata' => ['basePrice' => 75]],
        ];

        $filters = [
            ['field' => 'metadata.basePrice', 'comparator' => 'lt', 'value' => 80],
        ];

        $result = $this->filters->applyFilters($products, $filters);

        $this->assertCount(2, $result);
    }

    #[Test]
    public function lteComparatorFiltersNumericLessThanOrEqual(): void
    {
        $products = [
            ['id' => '1', 'metadata' => ['basePrice' => 50]],
            ['id' => '2', 'metadata' => ['basePrice' => 100]],
            ['id' => '3', 'metadata' => ['basePrice' => 75]],
        ];

        $filters = [
            ['field' => 'metadata.basePrice', 'comparator' => 'lte', 'value' => 75],
        ];

        $result = $this->filters->applyFilters($products, $filters);

        $this->assertCount(2, $result);
    }

    #[Test]
    public function likeComparatorMatchesSubstring(): void
    {
        $products = [
            ['id' => '1', 'productName' => 'Basic Extras'],
            ['id' => '2', 'productName' => 'Premium Hospital'],
            ['id' => '3', 'productName' => 'Starter Extras'],
        ];

        $filters = [
            ['field' => 'productName', 'comparator' => 'like', 'value' => 'Extras'],
        ];

        $result = $this->filters->applyFilters($products, $filters);

        $this->assertCount(2, $result);
    }

    #[Test]
    public function likeComparatorIsCaseInsensitive(): void
    {
        $products = [
            ['id' => '1', 'productName' => 'Basic EXTRAS'],
            ['id' => '2', 'productName' => 'Premium Hospital'],
        ];

        $filters = [
            ['field' => 'productName', 'comparator' => 'like', 'value' => 'extras'],
        ];

        $result = $this->filters->applyFilters($products, $filters);

        $this->assertCount(1, $result);
    }

    #[Test]
    public function notlikeComparatorExcludesSubstring(): void
    {
        $products = [
            ['id' => '1', 'productName' => 'Basic Extras'],
            ['id' => '2', 'productName' => 'Premium Hospital'],
        ];

        $filters = [
            ['field' => 'productName', 'comparator' => 'notlike', 'value' => 'Extras'],
        ];

        $result = $this->filters->applyFilters($products, $filters);

        $this->assertCount(1, $result);
        $this->assertEquals('Premium Hospital', array_values($result)[0]['productName']);
    }

    #[Test]
    public function emptyComparatorMatchesNullEmptyStringAndEmptyArray(): void
    {
        $products = [
            ['id' => '1', 'description' => null],
            ['id' => '2', 'description' => ''],
            ['id' => '3', 'description' => []],
            ['id' => '4', 'description' => 'Has content'],
        ];

        $filters = [
            ['field' => 'description', 'comparator' => 'empty'],
        ];

        $result = $this->filters->applyFilters($products, $filters);

        $this->assertCount(3, $result);
    }

    #[Test]
    public function notemptyComparatorMatchesNonEmptyValues(): void
    {
        $products = [
            ['id' => '1', 'description' => null],
            ['id' => '2', 'description' => ''],
            ['id' => '3', 'description' => 'Has content'],
        ];

        $filters = [
            ['field' => 'description', 'comparator' => 'notempty'],
        ];

        $result = $this->filters->applyFilters($products, $filters);

        $this->assertCount(1, $result);
        $this->assertEquals('Has content', array_values($result)[0]['description']);
    }

    #[Test]
    public function inComparatorMatchesValueInArray(): void
    {
        $products = [
            ['id' => '1', 'providerName' => 'Provider A'],
            ['id' => '2', 'providerName' => 'Provider B'],
            ['id' => '3', 'providerName' => 'Provider C'],
        ];

        $filters = [
            ['field' => 'providerName', 'comparator' => 'in', 'value' => ['Provider A', 'Provider C']],
        ];

        $result = $this->filters->applyFilters($products, $filters);

        $this->assertCount(2, $result);
    }

    #[Test]
    public function notinComparatorExcludesValuesInArray(): void
    {
        $products = [
            ['id' => '1', 'providerName' => 'Provider A'],
            ['id' => '2', 'providerName' => 'Provider B'],
            ['id' => '3', 'providerName' => 'Provider C'],
        ];

        $filters = [
            ['field' => 'providerName', 'comparator' => 'notin', 'value' => ['Provider A', 'Provider C']],
        ];

        $result = $this->filters->applyFilters($products, $filters);

        $this->assertCount(1, $result);
        $this->assertEquals('Provider B', array_values($result)[0]['providerName']);
    }

    #[Test]
    public function withinComparatorMatchesValueWithinFieldArray(): void
    {
        $products = [
            ['id' => '1', 'tags' => ['dental', 'optical']],
            ['id' => '2', 'tags' => ['hospital', 'ambulance']],
            ['id' => '3', 'tags' => ['dental', 'hospital']],
        ];

        $filters = [
            ['field' => 'tags', 'comparator' => 'within', 'value' => 'dental'],
        ];

        $result = $this->filters->applyFilters($products, $filters);

        $this->assertCount(2, $result);
    }

    #[Test]
    public function multipleFiltersAreCombinedWithAnd(): void
    {
        $products = [
            ['id' => '1', 'providerName' => 'Provider A', 'metadata' => ['basePrice' => 50]],
            ['id' => '2', 'providerName' => 'Provider A', 'metadata' => ['basePrice' => 100]],
            ['id' => '3', 'providerName' => 'Provider B', 'metadata' => ['basePrice' => 50]],
        ];

        $filters = [
            ['field' => 'providerName', 'comparator' => 'eq', 'value' => 'Provider A'],
            ['field' => 'metadata.basePrice', 'comparator' => 'lt', 'value' => 80],
        ];

        $result = $this->filters->applyFilters($products, $filters);

        $this->assertCount(1, $result);
        $this->assertEquals('1', array_values($result)[0]['id']);
    }

    #[Test]
    public function nestedFieldAccessWithDotNotation(): void
    {
        $products = [
            ['id' => '1', 'metadata' => ['nested' => ['value' => 'deep']]],
            ['id' => '2', 'metadata' => ['nested' => ['value' => 'shallow']]],
        ];

        $filters = [
            ['field' => 'metadata.nested.value', 'comparator' => 'eq', 'value' => 'deep'],
        ];

        $result = $this->filters->applyFilters($products, $filters);

        $this->assertCount(1, $result);
    }

    #[Test]
    public function invalidFilterIsSkipped(): void
    {
        $products = [
            ['id' => '1', 'name' => 'Product 1'],
            ['id' => '2', 'name' => 'Product 2'],
        ];

        $filters = [
            ['field' => 'name'], // Missing comparator
            ['comparator' => 'eq', 'value' => 'test'], // Missing field
        ];

        $result = $this->filters->applyFilters($products, $filters);

        $this->assertCount(2, $result);
    }

    // =========================================================================
    // Hospital Treatments Filter Tests
    // =========================================================================

    #[Test]
    public function hospitalTreatmentsContainsMatchesAvailableTreatment(): void
    {
        $products = [
            $this->createProductWithHospitalTreatment('1', 'Heart and vascular system', true),
            $this->createProductWithHospitalTreatment('2', 'Heart and vascular system', false),
            $this->createProductWithHospitalTreatment('3', 'Cancer', true),
        ];

        $filters = [
            ['field' => 'hospitalTreatments', 'comparator' => 'contains', 'value' => 'Heart and vascular system'],
        ];

        $result = $this->filters->applyFilters($products, $filters);

        $this->assertCount(1, $result);
        $this->assertEquals('1', array_values($result)[0]['id']);
    }

    #[Test]
    public function hospitalTreatmentsContainsDoesNotMatchRestrictedTreatment(): void
    {
        $products = [
            [
                'id' => '1',
                'details' => [
                    [
                        'title' => 'Hospital',
                        'subtitle' => '1/38 included',
                        'attributes' => [
                            ['name' => 'Rehabilitation', 'isAvailable' => 'restricted'],
                        ],
                    ],
                ],
            ],
        ];

        $filters = [
            ['field' => 'hospitalTreatments', 'comparator' => 'contains', 'value' => 'Rehabilitation'],
        ];

        $result = $this->filters->applyFilters($products, $filters);

        $this->assertCount(0, $result);
    }

    #[Test]
    public function hospitalTreatmentsNotcontainsExcludesAvailableTreatment(): void
    {
        $products = [
            $this->createProductWithHospitalTreatment('1', 'Cancer', true),
            $this->createProductWithHospitalTreatment('2', 'Cancer', false),
        ];

        $filters = [
            ['field' => 'hospitalTreatments', 'comparator' => 'notcontains', 'value' => 'Cancer'],
        ];

        $result = $this->filters->applyFilters($products, $filters);

        $this->assertCount(1, $result);
        $this->assertEquals('2', array_values($result)[0]['id']);
    }

    #[Test]
    public function hospitalTreatmentsMatchesProductWithoutHospitalSection(): void
    {
        $products = [
            [
                'id' => '1',
                'details' => [
                    ['title' => 'Extras', 'subtitle' => '5/14 included', 'attributes' => []],
                ],
            ],
        ];

        $filters = [
            ['field' => 'hospitalTreatments', 'comparator' => 'notcontains', 'value' => 'Cancer'],
        ];

        $result = $this->filters->applyFilters($products, $filters);

        $this->assertCount(1, $result);
    }

    // =========================================================================
    // Extras Treatments Filter Tests
    // =========================================================================

    #[Test]
    public function extrasTreatmentsContainsMatchesAvailableTreatment(): void
    {
        $products = [
            $this->createProductWithExtrasTreatment('1', 'General dental', true),
            $this->createProductWithExtrasTreatment('2', 'General dental', false),
            $this->createProductWithExtrasTreatment('3', 'Optical', true),
        ];

        $filters = [
            ['field' => 'extrasTreatments', 'comparator' => 'contains', 'value' => 'General dental'],
        ];

        $result = $this->filters->applyFilters($products, $filters);

        $this->assertCount(1, $result);
        $this->assertEquals('1', array_values($result)[0]['id']);
    }

    #[Test]
    public function extrasTreatmentsIsCaseInsensitive(): void
    {
        $products = [
            $this->createProductWithExtrasTreatment('1', 'General Dental', true),
        ];

        $filters = [
            ['field' => 'extrasTreatments', 'comparator' => 'contains', 'value' => 'general dental'],
        ];

        $result = $this->filters->applyFilters($products, $filters);

        $this->assertCount(1, $result);
    }

    // =========================================================================
    // Hospital Cover Filter Tests
    // =========================================================================

    #[Test]
    public function hospitalCoverLikeMatchesTierValue(): void
    {
        $products = [
            $this->createProductWithHospitalTier('1', 'Basic'),
            $this->createProductWithHospitalTier('2', 'Bronze'),
            $this->createProductWithHospitalTier('3', 'Basic Plus'),
        ];

        $filters = [
            ['field' => 'hospitalCover', 'comparator' => 'like', 'value' => 'Basic'],
        ];

        $result = $this->filters->applyFilters($products, $filters);

        $this->assertCount(2, $result);
    }

    #[Test]
    public function hospitalCoverEqMatchesExactTierValue(): void
    {
        $products = [
            $this->createProductWithHospitalTier('1', 'Basic'),
            $this->createProductWithHospitalTier('2', 'Basic Plus'),
        ];

        $filters = [
            ['field' => 'hospitalCover', 'comparator' => 'eq', 'value' => 'Basic'],
        ];

        $result = $this->filters->applyFilters($products, $filters);

        $this->assertCount(1, $result);
        $this->assertEquals('1', array_values($result)[0]['id']);
    }

    #[Test]
    public function hospitalCoverNotlikeExcludesTierValue(): void
    {
        $products = [
            $this->createProductWithHospitalTier('1', 'Basic'),
            $this->createProductWithHospitalTier('2', 'Gold'),
        ];

        $filters = [
            ['field' => 'hospitalCover', 'comparator' => 'notlike', 'value' => 'Basic'],
        ];

        $result = $this->filters->applyFilters($products, $filters);

        $this->assertCount(1, $result);
        $this->assertEquals('2', array_values($result)[0]['id']);
    }

    // =========================================================================
    // Cover Type Filter Tests
    // =========================================================================

    #[Test]
    public function coverTypeContainsHospitalMatchesHospitalOnlyProducts(): void
    {
        $products = [
            $this->createHospitalOnlyProduct('1'),
            $this->createExtrasOnlyProduct('2'),
            $this->createCombinedProduct('3'),
        ];

        $filters = [
            ['field' => 'coverType', 'comparator' => 'contains', 'value' => 'Hospital'],
        ];

        $result = $this->filters->applyFilters($products, $filters);

        // Should match Hospital only and Combined (which contains Hospital)
        $this->assertCount(2, $result);
    }

    #[Test]
    public function coverTypeContainsExtrasMatchesExtrasOnlyProducts(): void
    {
        $products = [
            $this->createHospitalOnlyProduct('1'),
            $this->createExtrasOnlyProduct('2'),
            $this->createCombinedProduct('3'),
        ];

        $filters = [
            ['field' => 'coverType', 'comparator' => 'contains', 'value' => 'Extras'],
        ];

        $result = $this->filters->applyFilters($products, $filters);

        // Should match Extras only and Combined (which contains Extras)
        $this->assertCount(2, $result);
    }

    #[Test]
    public function coverTypeEqCombinedMatchesOnlyCombinedProducts(): void
    {
        $products = [
            $this->createHospitalOnlyProduct('1'),
            $this->createExtrasOnlyProduct('2'),
            $this->createCombinedProduct('3'),
        ];

        $filters = [
            ['field' => 'coverType', 'comparator' => 'eq', 'value' => 'Combined'],
        ];

        $result = $this->filters->applyFilters($products, $filters);

        $this->assertCount(1, $result);
        $this->assertEquals('3', array_values($result)[0]['id']);
    }

    #[Test]
    public function coverTypeRequiresPositiveIncludedCount(): void
    {
        $products = [
            [
                'id' => '1',
                'details' => [
                    ['title' => 'Hospital', 'subtitle' => '0/38 included', 'attributes' => []],
                ],
            ],
            [
                'id' => '2',
                'details' => [
                    ['title' => 'Hospital', 'subtitle' => '5/38 included', 'attributes' => []],
                ],
            ],
        ];

        $filters = [
            ['field' => 'coverType', 'comparator' => 'eq', 'value' => 'Hospital'],
        ];

        $result = $this->filters->applyFilters($products, $filters);

        $this->assertCount(1, $result);
        $this->assertEquals('2', array_values($result)[0]['id']);
    }

    #[Test]
    public function coverTypeIsCaseInsensitive(): void
    {
        $products = [
            $this->createHospitalOnlyProduct('1'),
        ];

        $filters = [
            ['field' => 'coverType', 'comparator' => 'eq', 'value' => 'hospital'],
        ];

        $result = $this->filters->applyFilters($products, $filters);

        $this->assertCount(1, $result);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * @param bool|string $isAvailable
     */
    private function createProductWithHospitalTreatment(
        string $id,
        string $treatmentName,
        bool|string $isAvailable
    ): array {
        return [
            'id' => $id,
            'details' => [
                [
                    'title' => 'Hospital',
                    'subtitle' => '1/38 included',
                    'attributes' => [
                        ['name' => $treatmentName, 'isAvailable' => $isAvailable],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param bool|string $isAvailable
     */
    private function createProductWithExtrasTreatment(
        string $id,
        string $treatmentName,
        bool|string $isAvailable
    ): array {
        return [
            'id' => $id,
            'details' => [
                [
                    'title' => 'Extras',
                    'subtitle' => '1/14 included',
                    'attributes' => [
                        ['name' => $treatmentName, 'isAvailable' => $isAvailable],
                    ],
                ],
            ],
        ];
    }

    private function createProductWithHospitalTier(string $id, string $tier): array
    {
        return [
            'id' => $id,
            'details' => [
                [
                    'title' => 'Hospital',
                    'subtitle' => '5/38 included',
                    'attributes' => [
                        ['name' => 'Hospital tier', 'value' => $tier],
                    ],
                ],
            ],
        ];
    }

    private function createHospitalOnlyProduct(string $id): array
    {
        return [
            'id' => $id,
            'details' => [
                [
                    'title' => 'Hospital',
                    'subtitle' => '5/38 included',
                    'attributes' => [],
                ],
                [
                    'title' => 'Ambulance',
                    'subtitle' => '1/1 included',
                    'attributes' => [],
                ],
            ],
        ];
    }

    private function createExtrasOnlyProduct(string $id): array
    {
        return [
            'id' => $id,
            'details' => [
                [
                    'title' => 'Extras',
                    'subtitle' => '5/14 included',
                    'attributes' => [],
                ],
                [
                    'title' => 'Ambulance',
                    'subtitle' => '1/1 included',
                    'attributes' => [],
                ],
            ],
        ];
    }

    private function createCombinedProduct(string $id): array
    {
        return [
            'id' => $id,
            'details' => [
                [
                    'title' => 'Hospital',
                    'subtitle' => '10/38 included',
                    'attributes' => [],
                ],
                [
                    'title' => 'Extras',
                    'subtitle' => '8/14 included',
                    'attributes' => [],
                ],
                [
                    'title' => 'Ambulance',
                    'subtitle' => '1/1 included',
                    'attributes' => [],
                ],
            ],
        ];
    }
}
