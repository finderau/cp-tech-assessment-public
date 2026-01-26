<?php

declare(strict_types=1);

namespace Finder\ProductData\Tests\Integration\Data;

use Finder\ProductData\Data\DataRepository;
use Finder\ProductData\Data\Filters\BasicFiltersImpl;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for DataRepository.
 *
 * These tests use the actual JSON data files to verify the full integration
 * between DataRepository and BasicFiltersImpl.
 */
final class DataRepositoryTest extends TestCase
{
    private DataRepository $repository;

    protected function setUp(): void
    {
        $filters = new BasicFiltersImpl();
        $this->repository = new DataRepository($filters);
    }

    // =========================================================================
    // Basic Query Tests
    // =========================================================================

    #[Test]
    public function getProductsReturnsAllProductsWithDefaultPagination(): void
    {
        $result = $this->repository->getProducts();

        $this->assertArrayHasKey('products', $result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('offset', $result);
        $this->assertArrayHasKey('pageSize', $result);

        $this->assertEquals(0, $result['offset']);
        $this->assertEquals(20, $result['pageSize']);
        $this->assertCount(20, $result['products']);
        $this->assertGreaterThan(0, $result['totalCount']);
    }

    #[Test]
    public function getProductsRespectsPaginationOffset(): void
    {
        $firstPage = $this->repository->getProducts([], [], ['offset' => 0, 'pageSize' => 5]);
        $secondPage = $this->repository->getProducts([], [], ['offset' => 5, 'pageSize' => 5]);

        $this->assertNotEquals(
            $firstPage['products'][0]['id'],
            $secondPage['products'][0]['id']
        );
    }

    #[Test]
    public function getProductsRespectsPaginationPageSize(): void
    {
        $result = $this->repository->getProducts([], [], ['offset' => 0, 'pageSize' => 10]);

        $this->assertCount(10, $result['products']);
        $this->assertEquals(10, $result['pageSize']);
    }

    #[Test]
    public function getProductsReturnsTotalCountBeforePagination(): void
    {
        $result = $this->repository->getProducts([], [], ['offset' => 0, 'pageSize' => 5]);

        $this->assertGreaterThan(5, $result['totalCount']);
    }

    // =========================================================================
    // Filter Integration Tests
    // =========================================================================

    #[Test]
    public function filterByProviderNameReturnsMatchingProducts(): void
    {
        // First, get all products to find a valid provider name
        $allProducts = $this->repository->getProducts([], [], ['offset' => 0, 'pageSize' => 1]);
        $providerName = $allProducts['products'][0]['providerName'];

        $result = $this->repository->getProducts(
            [['field' => 'providerName', 'comparator' => 'eq', 'value' => $providerName]],
            [],
            ['offset' => 0, 'pageSize' => 100]
        );

        $this->assertGreaterThan(0, count($result['products']));

        foreach ($result['products'] as $product) {
            $this->assertEquals($providerName, $product['providerName']);
        }
    }

    #[Test]
    public function filterByPriceRangeReturnsMatchingProducts(): void
    {
        $result = $this->repository->getProducts(
            [
                ['field' => 'metadata.basePrice', 'comparator' => 'gte', 'value' => 50],
                ['field' => 'metadata.basePrice', 'comparator' => 'lte', 'value' => 100],
            ],
            [],
            ['offset' => 0, 'pageSize' => 100]
        );

        foreach ($result['products'] as $product) {
            $this->assertGreaterThanOrEqual(50, $product['metadata']['basePrice']);
            $this->assertLessThanOrEqual(100, $product['metadata']['basePrice']);
        }
    }

    #[Test]
    public function filterByCoverTypeExtrasReturnsOnlyExtrasProducts(): void
    {
        $result = $this->repository->getProducts(
            [['field' => 'coverType', 'comparator' => 'eq', 'value' => 'Extras']],
            [],
            ['offset' => 0, 'pageSize' => 50]
        );

        foreach ($result['products'] as $product) {
            $hasExtras = false;
            $hasHospital = false;

            foreach ($product['details'] as $detail) {
                if ($detail['title'] === 'Extras') {
                    $hasExtras = $this->hasPositiveIncludedCount($detail['subtitle']);
                }
                if ($detail['title'] === 'Hospital') {
                    $hasHospital = $this->hasPositiveIncludedCount($detail['subtitle']);
                }
            }

            $this->assertTrue($hasExtras, 'Product should have Extras cover');
            $this->assertFalse($hasHospital, 'Extras-only product should not have Hospital cover');
        }
    }

    #[Test]
    public function filterByCoverTypeHospitalReturnsOnlyHospitalProducts(): void
    {
        $result = $this->repository->getProducts(
            [['field' => 'coverType', 'comparator' => 'eq', 'value' => 'Hospital']],
            [],
            ['offset' => 0, 'pageSize' => 50]
        );

        foreach ($result['products'] as $product) {
            $hasExtras = false;
            $hasHospital = false;

            foreach ($product['details'] as $detail) {
                if ($detail['title'] === 'Extras') {
                    $hasExtras = $this->hasPositiveIncludedCount($detail['subtitle']);
                }
                if ($detail['title'] === 'Hospital') {
                    $hasHospital = $this->hasPositiveIncludedCount($detail['subtitle']);
                }
            }

            $this->assertTrue($hasHospital, 'Product should have Hospital cover');
            $this->assertFalse($hasExtras, 'Hospital-only product should not have Extras cover');
        }
    }

    #[Test]
    public function filterByCoverTypeCombinedReturnsBothCovers(): void
    {
        $result = $this->repository->getProducts(
            [['field' => 'coverType', 'comparator' => 'eq', 'value' => 'Combined']],
            [],
            ['offset' => 0, 'pageSize' => 50]
        );

        foreach ($result['products'] as $product) {
            $hasExtras = false;
            $hasHospital = false;

            foreach ($product['details'] as $detail) {
                if ($detail['title'] === 'Extras') {
                    $hasExtras = $this->hasPositiveIncludedCount($detail['subtitle']);
                }
                if ($detail['title'] === 'Hospital') {
                    $hasHospital = $this->hasPositiveIncludedCount($detail['subtitle']);
                }
            }

            $this->assertTrue($hasHospital, 'Combined product should have Hospital cover');
            $this->assertTrue($hasExtras, 'Combined product should have Extras cover');
        }
    }

    #[Test]
    public function filterByExtrasTreatmentReturnsProductsWithAvailableTreatment(): void
    {
        $result = $this->repository->getProducts(
            [['field' => 'extrasTreatments', 'comparator' => 'contains', 'value' => 'General dental']],
            [],
            ['offset' => 0, 'pageSize' => 50]
        );

        $this->assertGreaterThan(0, count($result['products']));

        foreach ($result['products'] as $product) {
            $hasGeneralDental = false;

            foreach ($product['details'] as $detail) {
                if ($detail['title'] !== 'Extras') {
                    continue;
                }

                foreach ($detail['attributes'] as $attribute) {
                    if (
                        strcasecmp($attribute['name'], 'General dental') === 0
                        && ($attribute['isAvailable'] ?? false) === true
                    ) {
                        $hasGeneralDental = true;
                        break 2;
                    }
                }
            }

            $this->assertTrue($hasGeneralDental, 'Product should have General dental available');
        }
    }

    // =========================================================================
    // Sort Integration Tests
    // =========================================================================

    #[Test]
    public function sortByPriceAscendingOrdersCorrectly(): void
    {
        $result = $this->repository->getProducts(
            [],
            [['field' => 'metadata.basePrice', 'direction' => 'ASCENDING']],
            ['offset' => 0, 'pageSize' => 50]
        );

        $previousPrice = null;

        foreach ($result['products'] as $product) {
            $currentPrice = $product['metadata']['basePrice'];

            if ($previousPrice !== null) {
                $this->assertGreaterThanOrEqual(
                    $previousPrice,
                    $currentPrice,
                    'Products should be sorted by price ascending'
                );
            }

            $previousPrice = $currentPrice;
        }
    }

    #[Test]
    public function sortByPriceDescendingOrdersCorrectly(): void
    {
        $result = $this->repository->getProducts(
            [],
            [['field' => 'metadata.basePrice', 'direction' => 'DESCENDING']],
            ['offset' => 0, 'pageSize' => 50]
        );

        $previousPrice = null;

        foreach ($result['products'] as $product) {
            $currentPrice = $product['metadata']['basePrice'];

            if ($previousPrice !== null) {
                $this->assertLessThanOrEqual(
                    $previousPrice,
                    $currentPrice,
                    'Products should be sorted by price descending'
                );
            }

            $previousPrice = $currentPrice;
        }
    }

    #[Test]
    public function sortByProviderNameOrdersAlphabetically(): void
    {
        $result = $this->repository->getProducts(
            [],
            [['field' => 'providerName', 'direction' => 'ASCENDING']],
            ['offset' => 0, 'pageSize' => 50]
        );

        $previousName = null;

        foreach ($result['products'] as $product) {
            $currentName = $product['providerName'];

            if ($previousName !== null) {
                $this->assertGreaterThanOrEqual(
                    0,
                    strcasecmp($currentName, $previousName),
                    'Products should be sorted by provider name ascending'
                );
            }

            $previousName = $currentName;
        }
    }

    // =========================================================================
    // Combined Filter and Sort Tests
    // =========================================================================

    #[Test]
    public function filterAndSortWorkTogether(): void
    {
        // Get a valid provider name first
        $allProducts = $this->repository->getProducts([], [], ['offset' => 0, 'pageSize' => 1]);
        $providerName = $allProducts['products'][0]['providerName'];

        $result = $this->repository->getProducts(
            [['field' => 'providerName', 'comparator' => 'eq', 'value' => $providerName]],
            [['field' => 'metadata.basePrice', 'direction' => 'ASCENDING']],
            ['offset' => 0, 'pageSize' => 100]
        );

        // Verify filter applied
        foreach ($result['products'] as $product) {
            $this->assertEquals($providerName, $product['providerName']);
        }

        // Verify sort applied
        $previousPrice = null;
        foreach ($result['products'] as $product) {
            if ($previousPrice !== null) {
                $this->assertGreaterThanOrEqual($previousPrice, $product['metadata']['basePrice']);
            }
            $previousPrice = $product['metadata']['basePrice'];
        }

        // Verify total count reflects filtered count
        $this->assertEquals($result['totalCount'], count($result['products']));
    }

    // =========================================================================
    // Get Product By ID Tests
    // =========================================================================

    #[Test]
    public function getProductByIdReturnsProductWhenExists(): void
    {
        // Get a valid product ID first
        $allProducts = $this->repository->getProducts([], [], ['offset' => 0, 'pageSize' => 1]);
        $productId = $allProducts['products'][0]['id'];

        $product = $this->repository->getProductById($productId);

        $this->assertNotNull($product);
        $this->assertEquals($productId, $product['id']);
    }

    #[Test]
    public function getProductByIdReturnsNullWhenNotExists(): void
    {
        $product = $this->repository->getProductById('non-existent-id-12345');

        $this->assertNull($product);
    }

    // =========================================================================
    // Provider Tests
    // =========================================================================

    #[Test]
    public function getProvidersReturnsAllProviders(): void
    {
        $providers = $this->repository->getProviders();

        $this->assertIsArray($providers);
        $this->assertGreaterThan(0, count($providers));

        foreach ($providers as $provider) {
            $this->assertArrayHasKey('id', $provider);
            $this->assertArrayHasKey('providerName', $provider);
        }
    }

    #[Test]
    public function getProviderByIdReturnsProviderWhenExists(): void
    {
        $providers = $this->repository->getProviders();
        $providerId = $providers[0]['id'];

        $provider = $this->repository->getProviderById($providerId);

        $this->assertNotNull($provider);
        $this->assertEquals($providerId, $provider['id']);
    }

    #[Test]
    public function getProviderByIdReturnsNullWhenNotExists(): void
    {
        $provider = $this->repository->getProviderById('non-existent-provider-id');

        $this->assertNull($provider);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    private function hasPositiveIncludedCount(string $subtitle): bool
    {
        if (preg_match('/^(\d+)\/\d+\s+included$/i', $subtitle, $matches)) {
            return (int) $matches[1] > 0;
        }

        return false;
    }
}
