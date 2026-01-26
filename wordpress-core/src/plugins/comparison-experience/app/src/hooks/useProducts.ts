import { useState, useEffect, useCallback, useRef } from 'react';
import type { Product, FilterState, AppConfig } from '@/types';
import { ProductApi, ApiError } from '@/services/api';
import { buildApiRequest } from '@/utils/filterMapping';

interface UseProductsReturn {
  /** Fetched products */
  products: Product[];
  /** Total count of products matching filters */
  totalCount: number;
  /** Loading state */
  isLoading: boolean;
  /** Error message if fetch failed */
  error: string | null;
  /** Manually trigger a refetch */
  refetch: () => void;
}

/**
 * Hook for fetching products from the API based on filter state.
 * Automatically refetches when filters change.
 */
export function useProducts(filters: FilterState, config: AppConfig): UseProductsReturn {
  const [products, setProducts] = useState<Product[]>([]);
  const [totalCount, setTotalCount] = useState(0);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Keep API instance in a ref to avoid recreating on every render
  const apiRef = useRef<ProductApi | null>(null);
  if (!apiRef.current) {
    apiRef.current = new ProductApi(config);
  }

  // Track current request to handle race conditions
  const requestIdRef = useRef(0);

  /**
   * Fetch products from the API.
   */
  const fetchProducts = useCallback(async () => {
    const currentRequestId = ++requestIdRef.current;
    setIsLoading(true);
    setError(null);

    try {
      const request = buildApiRequest(filters);
      const response = await apiRef.current!.getProducts(request);

      // Only update state if this is still the current request
      if (currentRequestId === requestIdRef.current) {
        setProducts(response.products);
        setTotalCount(response.totalCount);
        setIsLoading(false);
      }
    } catch (err) {
      // Only update state if this is still the current request
      if (currentRequestId === requestIdRef.current) {
        if (err instanceof ApiError) {
          setError(`Failed to load products: ${err.message}`);
        } else if (err instanceof Error) {
          setError(`Failed to load products: ${err.message}`);
        } else {
          setError('Failed to load products. Please try again.');
        }
        setIsLoading(false);
      }
    }
  }, [filters]);

  // Fetch products when filters change
  useEffect(() => {
    fetchProducts();
  }, [fetchProducts]);

  /**
   * Manual refetch function.
   */
  const refetch = useCallback(() => {
    fetchProducts();
  }, [fetchProducts]);

  return {
    products,
    totalCount,
    isLoading,
    error,
    refetch,
  };
}
