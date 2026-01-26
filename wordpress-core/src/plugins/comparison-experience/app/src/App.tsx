import { useState, useCallback } from 'react';
import { FilterSidebar } from '@/components/Filters';
import { ProductTable } from '@/components/ProductTable';
import { MobileFilterDrawer } from '@/components/Filters/MobileFilterDrawer';
import { useFilters } from '@/hooks/useFilters';
import { useProducts } from '@/hooks/useProducts';
import type { AppConfig } from '@/types/config';

interface AppProps {
  config: AppConfig;
}

function App({ config }: AppProps) {
  const [isMobileFilterOpen, setIsMobileFilterOpen] = useState(false);
  const { filters, updateFilter, resetFilters } = useFilters();
  const { products, totalCount, isLoading, error, refetch } = useProducts(filters, config);

  const handleOpenMobileFilters = useCallback(() => {
    setIsMobileFilterOpen(true);
  }, []);

  const handleCloseMobileFilters = useCallback(() => {
    setIsMobileFilterOpen(false);
  }, []);

  return (
    <div className="comparison-experience">
      {/* Mobile filter button */}
      <div className="lg:hidden mb-4">
        <button
          onClick={handleOpenMobileFilters}
          className="flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
        >
          <svg
            className="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
            />
          </svg>
          Filters
        </button>
      </div>

      <div className="flex gap-6">
        {/* Desktop sidebar */}
        <aside className="hidden lg:block w-72 flex-shrink-0">
          <FilterSidebar
            filters={filters}
            onFilterChange={updateFilter}
            onReset={resetFilters}
          />
        </aside>

        {/* Main content */}
        <main className="flex-1 min-w-0">
          {/* Results count */}
          <div className="mb-4 text-sm text-gray-600">
            {isLoading ? (
              <span>Loading products...</span>
            ) : error ? (
              <span className="text-red-600">Error loading products</span>
            ) : (
              <span>{totalCount} products found</span>
            )}
          </div>

          {/* Product table */}
          <ProductTable
            products={products}
            isLoading={isLoading}
            error={error}
            onRetry={refetch}
          />
        </main>
      </div>

      {/* Mobile filter drawer */}
      <MobileFilterDrawer
        isOpen={isMobileFilterOpen}
        onClose={handleCloseMobileFilters}
        filters={filters}
        onFilterChange={updateFilter}
        onReset={resetFilters}
      />
    </div>
  );
}

export default App;
