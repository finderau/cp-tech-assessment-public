import { useEffect, useCallback, useRef } from 'react';
import type { FilterState } from '@/types';
import { urlParamsToFilterState, filterStateToUrlParams } from '@/utils/filterMapping';
import { DEFAULT_FILTER_STATE } from '@/types/filters';

interface UseUrlSyncOptions {
  /** Whether to sync state to URL */
  enabled?: boolean;
  /** Debounce delay in ms before updating URL */
  debounceMs?: number;
}

interface UseUrlSyncReturn {
  /** Get initial filter state from URL */
  getInitialState: () => FilterState;
  /** Sync filter state to URL */
  syncToUrl: (state: FilterState) => void;
}

/**
 * Hook to sync filter state with URL parameters.
 * Enables shareable URLs with filter state preserved.
 */
export function useUrlSync(options: UseUrlSyncOptions = {}): UseUrlSyncReturn {
  const { enabled = true, debounceMs = 300 } = options;
  const debounceTimerRef = useRef<ReturnType<typeof setTimeout>>();

  // Cleanup debounce timer on unmount
  useEffect(() => {
    return () => {
      if (debounceTimerRef.current) {
        clearTimeout(debounceTimerRef.current);
      }
    };
  }, []);

  /**
   * Get initial filter state from current URL.
   */
  const getInitialState = useCallback((): FilterState => {
    if (typeof window === 'undefined') {
      return DEFAULT_FILTER_STATE;
    }

    const searchParams = new URLSearchParams(window.location.search);
    const urlState = urlParamsToFilterState(searchParams);

    return {
      ...DEFAULT_FILTER_STATE,
      ...urlState,
    };
  }, []);

  /**
   * Sync filter state to URL (debounced).
   */
  const syncToUrl = useCallback(
    (state: FilterState) => {
      if (!enabled || typeof window === 'undefined') {
        return;
      }

      // Clear existing timer
      if (debounceTimerRef.current) {
        clearTimeout(debounceTimerRef.current);
      }

      // Debounce URL updates
      debounceTimerRef.current = setTimeout(() => {
        const params = filterStateToUrlParams(state);
        const newUrl = new URL(window.location.href);

        // Clear existing filter params
        ['providers', 'coverType', 'hospitalCover', 'extras', 'hospitalTreatments'].forEach(
          (key) => newUrl.searchParams.delete(key)
        );

        // Set new params
        params.forEach((value, key) => {
          newUrl.searchParams.set(key, value);
        });

        // Update URL without page reload
        window.history.replaceState({}, '', newUrl.toString());
      }, debounceMs);
    },
    [enabled, debounceMs]
  );

  return {
    getInitialState,
    syncToUrl,
  };
}
