import { useState, useCallback, useEffect } from 'react';
import type { FilterState, CoverType, HospitalCoverTier } from '@/types';
import { DEFAULT_FILTER_STATE } from '@/types/filters';
import { useUrlSync } from './useUrlSync';

type FilterKey = keyof FilterState;
type FilterValue<K extends FilterKey> = FilterState[K];

interface UseFiltersReturn {
  /** Current filter state */
  filters: FilterState;
  /** Update a specific filter */
  updateFilter: <K extends FilterKey>(key: K, value: FilterValue<K>) => void;
  /** Toggle a value in an array filter */
  toggleArrayFilter: <K extends 'providers' | 'hospitalCover' | 'extras' | 'hospitalTreatments'>(
    key: K,
    value: FilterState[K][number]
  ) => void;
  /** Reset all filters to default */
  resetFilters: () => void;
  /** Check if any filters are active */
  hasActiveFilters: boolean;
}

/**
 * Hook for managing filter state with URL synchronization.
 */
export function useFilters(): UseFiltersReturn {
  const { getInitialState, syncToUrl } = useUrlSync();
  const [filters, setFilters] = useState<FilterState>(() => getInitialState());

  // Sync to URL whenever filters change
  useEffect(() => {
    syncToUrl(filters);
  }, [filters, syncToUrl]);

  /**
   * Update a specific filter value.
   */
  const updateFilter = useCallback(<K extends FilterKey>(key: K, value: FilterValue<K>) => {
    setFilters((prev) => ({
      ...prev,
      [key]: value,
    }));
  }, []);

  /**
   * Toggle a value in an array filter (add if not present, remove if present).
   */
  const toggleArrayFilter = useCallback(
    <K extends 'providers' | 'hospitalCover' | 'extras' | 'hospitalTreatments'>(
      key: K,
      value: FilterState[K][number]
    ) => {
      setFilters((prev) => {
        const currentArray = prev[key] as FilterState[K];
        const valueExists = currentArray.includes(value as never);

        let newArray: FilterState[K];
        if (valueExists) {
          newArray = currentArray.filter((v) => v !== value) as FilterState[K];
        } else {
          newArray = [...currentArray, value] as FilterState[K];
        }

        return {
          ...prev,
          [key]: newArray,
        };
      });
    },
    []
  );

  /**
   * Reset all filters to default state.
   */
  const resetFilters = useCallback(() => {
    setFilters(DEFAULT_FILTER_STATE);
  }, []);

  /**
   * Check if any filters are active (different from defaults).
   */
  const hasActiveFilters =
    filters.providers.length > 0 ||
    filters.coverType !== 'any' ||
    filters.hospitalCover.length > 0 ||
    filters.extras.length > 0 ||
    filters.hospitalTreatments.length > 0;

  return {
    filters,
    updateFilter,
    toggleArrayFilter,
    resetFilters,
    hasActiveFilters,
  };
}

// Re-export types for convenience
export type { FilterState, CoverType, HospitalCoverTier };
