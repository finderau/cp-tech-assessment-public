/**
 * Filter state managed by the application.
 */
export interface FilterState {
  /** Selected provider names */
  providers: string[];
  /** Cover type: 'any' | 'Hospital' | 'Extras' | 'Combined' */
  coverType: CoverType;
  /** Selected hospital cover tiers */
  hospitalCover: HospitalCoverTier[];
  /** Selected extras treatments */
  extras: string[];
  /** Selected hospital treatments */
  hospitalTreatments: string[];
}

export type CoverType = 'any' | 'Hospital' | 'Extras' | 'Combined';

export type HospitalCoverTier = 'Basic' | 'Bronze' | 'Silver' | 'Gold';

/**
 * Available filter options (populated from API or constants).
 */
export interface FilterOptions {
  providers: string[];
  hospitalCoverTiers: HospitalCoverTier[];
  extrasTreatments: string[];
  hospitalTreatments: string[];
}

/**
 * API filter object structure.
 */
export interface ApiFilter {
  field: string;
  comparator: ApiFilterComparator;
  value: string | string[] | number | boolean;
}

export type ApiFilterComparator =
  | 'eq'
  | 'gt'
  | 'gte'
  | 'lt'
  | 'lte'
  | 'like'
  | 'notlike'
  | 'contains'
  | 'notcontains'
  | 'noteq'
  | 'empty'
  | 'notempty'
  | 'in'
  | 'notin'
  | 'within';

/**
 * API sort configuration.
 */
export interface ApiSort {
  field: string;
  direction: 'ASCENDING' | 'DESCENDING';
}

/**
 * API pagination configuration.
 */
export interface ApiPagination {
  offset: number;
  pageSize: number;
}

/**
 * Full API request body structure.
 */
export interface ProductsApiRequest {
  filters?: ApiFilter[];
  sort?: ApiSort[];
  pagination?: ApiPagination;
}

/**
 * Default filter state.
 */
export const DEFAULT_FILTER_STATE: FilterState = {
  providers: [],
  coverType: 'any',
  hospitalCover: [],
  extras: [],
  hospitalTreatments: [],
};

/**
 * Available hospital cover tiers.
 */
export const HOSPITAL_COVER_TIERS: HospitalCoverTier[] = [
  'Basic',
  'Bronze',
  'Silver',
  'Gold',
];

/**
 * Cover type options for the radio filter.
 */
export const COVER_TYPE_OPTIONS: { value: CoverType; label: string }[] = [
  { value: 'any', label: 'Any' },
  { value: 'Hospital', label: 'Hospital' },
  { value: 'Extras', label: 'Extras' },
  { value: 'Combined', label: 'Combined' },
];
