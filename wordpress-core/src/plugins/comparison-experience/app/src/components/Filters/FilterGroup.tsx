import { useState, ReactNode } from 'react';

interface FilterGroupProps {
  title: string;
  children: ReactNode;
  defaultExpanded?: boolean;
}

/**
 * Collapsible filter group container.
 */
export function FilterGroup({ title, children, defaultExpanded = true }: FilterGroupProps) {
  const [isExpanded, setIsExpanded] = useState(defaultExpanded);

  return (
    <div className="filter-group">
      <button
        onClick={() => setIsExpanded(!isExpanded)}
        className="filter-group-header w-full"
        aria-expanded={isExpanded}
      >
        <span className="filter-group-title">{title}</span>
        <svg
          className={`w-5 h-5 text-gray-400 transition-transform ${isExpanded ? 'rotate-180' : ''}`}
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 15l7-7 7 7" />
        </svg>
      </button>

      {isExpanded && <div className="mt-3">{children}</div>}
    </div>
  );
}
