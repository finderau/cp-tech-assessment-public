import type { ProductDetailSection } from '@/types';
import { AttributeRow } from './AttributeRow';

interface DetailSectionProps {
  section: ProductDetailSection;
  isExpanded: boolean;
  onToggle: () => void;
}

/**
 * Collapsible detail section for Extras, Hospital, or Ambulance.
 */
export function DetailSection({ section, isExpanded, onToggle }: DetailSectionProps) {
  // Get section-specific styling
  const getSectionBadgeColor = () => {
    switch (section.title) {
      case 'Extras':
        return 'bg-blue-100 text-blue-800';
      case 'Hospital':
        return 'bg-purple-100 text-purple-800';
      case 'Ambulance':
        return 'bg-green-100 text-green-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  return (
    <div className="detail-section">
      {/* Header - clickable */}
      <button
        onClick={onToggle}
        className="detail-section-header w-full text-left"
        aria-expanded={isExpanded}
      >
        <div className="flex items-center gap-2">
          <span className={`px-2 py-0.5 rounded text-xs font-medium ${getSectionBadgeColor()}`}>
            {section.title}
          </span>
          <span className="text-sm text-gray-500">{section.subtitle}</span>
        </div>
        <svg
          className={`w-5 h-5 text-gray-400 transition-transform ${isExpanded ? 'rotate-180' : ''}`}
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
        </svg>
      </button>

      {/* Content - collapsible */}
      {isExpanded && (
        <div className="detail-section-content">
          <div className="divide-y divide-gray-100">
            {section.attributes.map((attribute, index) => (
              <AttributeRow key={index} attribute={attribute} />
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
