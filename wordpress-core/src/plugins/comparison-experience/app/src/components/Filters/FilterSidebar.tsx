import type { FilterState, CoverType, HospitalCoverTier } from '@/types';
import { COVER_TYPE_OPTIONS, HOSPITAL_COVER_TIERS } from '@/types/filters';
import { FilterGroup } from './FilterGroup';
import { RadioFilter } from './RadioFilter';
import { CheckboxFilter } from './CheckboxFilter';
import { MultiSelectFilter } from './MultiSelectFilter';
import { ProviderFilter } from './ProviderFilter';

// Treatment options - these could also be fetched from API in the future
const EXTRAS_TREATMENTS = [
  'General dental',
  'Major dental',
  'Endodontic',
  'Orthodontic',
  'Optical',
  'Physiotherapy',
  'Psychology',
  'Acupuncture',
  'Chiropractic',
  'Remedial massage',
  'Hearing aids',
  'Glucose monitor',
  'Non-PBS pharmaceuticals',
  'Podiatry',
];

const HOSPITAL_TREATMENTS = [
  'Assisted reproductive services',
  'Back neck and spine',
  'Blood',
  'Bone joint and muscle',
  'Brain and nervous system',
  'Breast surgery',
  'Cataracts',
  'Cancer',
  'Dental surgery',
  'Diabetes management',
  'Dialysis for chronic kidney failure',
  'Digestive system',
  'Ear nose and throat',
  'Eye excluding cataracts',
  'Gastrointestinal endoscopy',
  'Gynaecology',
  'Heart and vascular system',
  'Hernia and appendix',
  'Hospital psychiatric services',
  'Implantation of hearing devices',
  'Insulin pumps',
  'Joint reconstructions',
  'Joint replacements',
  'Kidney and bladder',
  'Lung and chest',
  'Male reproductive system',
  'Miscarriage and termination of pregnancy',
  'Pain management',
  'Pain management with device',
  'Palliative care',
  'Plastic and reconstructive surgery',
  'Podiatric surgery',
  'Pregnancy and birth',
  'Rehabilitation',
  'Skin',
  'Sleep studies',
  'Sterilisation',
  'Weight loss surgery',
];

interface FilterSidebarProps {
  filters: FilterState;
  onFilterChange: <K extends keyof FilterState>(key: K, value: FilterState[K]) => void;
  onReset: () => void;
}

/**
 * Sidebar containing all filter controls.
 */
export function FilterSidebar({ filters, onFilterChange, onReset }: FilterSidebarProps) {
  // Toggle handler for array filters
  const handleToggle = <K extends 'providers' | 'hospitalCover' | 'extras' | 'hospitalTreatments'>(
    key: K,
    value: FilterState[K][number]
  ) => {
    const currentArray = filters[key] as FilterState[K];
    const valueExists = currentArray.includes(value as never);

    let newArray: FilterState[K];
    if (valueExists) {
      newArray = currentArray.filter((v) => v !== value) as FilterState[K];
    } else {
      newArray = [...currentArray, value] as FilterState[K];
    }

    onFilterChange(key, newArray);
  };

  // Check if any filters are active
  const hasActiveFilters =
    filters.providers.length > 0 ||
    filters.coverType !== 'any' ||
    filters.hospitalCover.length > 0 ||
    filters.extras.length > 0 ||
    filters.hospitalTreatments.length > 0;

  return (
    <div className="bg-white rounded-lg border border-gray-200 p-4">
      {/* Header with reset button */}
      <div className="flex items-center justify-between mb-4">
        <h2 className="text-lg font-semibold text-gray-900">Filters</h2>
        {hasActiveFilters && (
          <button
            onClick={onReset}
            className="text-sm text-primary-600 hover:text-primary-700"
          >
            Reset all
          </button>
        )}
      </div>

      {/* Provider filter */}
      <FilterGroup title="Provider funds">
        <ProviderFilter
          selectedValues={filters.providers}
          onChange={(value) => handleToggle('providers', value)}
        />
      </FilterGroup>

      {/* Cover type filter */}
      <FilterGroup title="Cover type">
        <RadioFilter<CoverType>
          name="coverType"
          options={COVER_TYPE_OPTIONS}
          value={filters.coverType}
          onChange={(value) => onFilterChange('coverType', value)}
        />
      </FilterGroup>

      {/* Hospital cover filter */}
      <FilterGroup title="Hospital cover">
        <CheckboxFilter<HospitalCoverTier>
          options={HOSPITAL_COVER_TIERS.map((tier) => ({ value: tier, label: tier }))}
          selectedValues={filters.hospitalCover}
          onChange={(value) => handleToggle('hospitalCover', value)}
        />
      </FilterGroup>

      {/* Extras treatments filter */}
      <FilterGroup title="Extras treatments">
        <MultiSelectFilter
          options={EXTRAS_TREATMENTS}
          selectedValues={filters.extras}
          onChange={(value) => handleToggle('extras', value)}
          placeholder="Choose options"
          searchPlaceholder="Search"
        />
      </FilterGroup>

      {/* Hospital treatments filter */}
      <FilterGroup title="Hospital treatments">
        <MultiSelectFilter
          options={HOSPITAL_TREATMENTS}
          selectedValues={filters.hospitalTreatments}
          onChange={(value) => handleToggle('hospitalTreatments', value)}
          placeholder="Choose options"
          searchPlaceholder="Search"
        />
      </FilterGroup>
    </div>
  );
}
