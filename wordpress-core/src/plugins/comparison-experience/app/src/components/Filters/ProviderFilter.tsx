import { useState, useEffect, useRef } from 'react';
import { MultiSelectFilter } from './MultiSelectFilter';

interface ProviderFilterProps {
  selectedValues: string[];
  onChange: (value: string) => void;
}

/**
 * Self-contained provider filter that fetches providers from the API.
 */
export function ProviderFilter({ selectedValues, onChange }: ProviderFilterProps) {
  const [providers, setProviders] = useState<string[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const fetchedRef = useRef(false);

  useEffect(() => {
    // Only fetch once
    if (fetchedRef.current) return;
    fetchedRef.current = true;

    const fetchProviders = async () => {
      try {
        // Get API base from the root element's data attribute or global settings
        const rootElement = document.getElementById('comparison-table-root');
        const apiBase = rootElement?.dataset.apiBase || 
          (window as unknown as { comparisonTableSettings?: { apiBase: string } }).comparisonTableSettings?.apiBase ||
          '/wp-json/api/v1';

        const response = await fetch(`${apiBase}/providers`);

        if (!response.ok) {
          throw new Error(`HTTP error ${response.status}`);
        }

        const data = await response.json();

        // Extract provider names and sort alphabetically
        const providerNames = data.providers
          .map((p: { providerName: string }) => p.providerName)
          .sort((a: string, b: string) => a.localeCompare(b));

        setProviders(providerNames);
        setIsLoading(false);
      } catch (err) {
        console.error('Failed to fetch providers:', err);
        setError('Failed to load providers');
        setIsLoading(false);
      }
    };

    fetchProviders();
  }, []);

  if (isLoading) {
    return <div className="text-sm text-gray-500">Loading providers...</div>;
  }

  if (error) {
    return <div className="text-sm text-red-500">{error}</div>;
  }

  return (
    <MultiSelectFilter
      options={providers}
      selectedValues={selectedValues}
      onChange={onChange}
      placeholder="All providers"
      searchPlaceholder="Search for a provider"
    />
  );
}
