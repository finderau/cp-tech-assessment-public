import type { FilterState, ApiFilter, ProductsApiRequest } from '@/types';

/**
 * Convert UI filter state to API request filters.
 * 
 * Rules:
 * - providerName uses 'eq' with array value (OR logic within providers)
 * - coverType uses 'contains' comparator
 * - hospitalCover uses 'like' comparator (one filter per tier)
 * - hospitalTreatments uses 'contains' (one filter per treatment)
 * - extrasTreatments uses 'contains' (one filter per treatment)
 */
export function filterStateToApiFilters(state: FilterState): ApiFilter[] {
  const filters: ApiFilter[] = [];

  // Provider filter - array value for OR logic
  if (state.providers.length > 0) {
    filters.push({
      field: 'providerName',
      comparator: 'eq',
      value: state.providers,
    });
  }

  // Cover type filter
  if (state.coverType !== 'any') {
    filters.push({
      field: 'coverType',
      comparator: 'contains',
      value: state.coverType,
    });
  }

  // Hospital cover filter - one filter per tier (AND logic between tiers)
  for (const tier of state.hospitalCover) {
    filters.push({
      field: 'hospitalCover',
      comparator: 'like',
      value: tier,
    });
  }

  // Extras treatments filter - one filter per treatment (AND logic)
  for (const treatment of state.extras) {
    filters.push({
      field: 'extrasTreatments',
      comparator: 'contains',
      value: treatment,
    });
  }

  // Hospital treatments filter - one filter per treatment (AND logic)
  for (const treatment of state.hospitalTreatments) {
    filters.push({
      field: 'hospitalTreatments',
      comparator: 'contains',
      value: treatment,
    });
  }

  return filters;
}

/**
 * Build a full API request from filter state.
 */
export function buildApiRequest(
  state: FilterState,
  pagination?: { offset: number; pageSize: number }
): ProductsApiRequest {
  const request: ProductsApiRequest = {};

  const filters = filterStateToApiFilters(state);
  if (filters.length > 0) {
    request.filters = filters;
  }

  // Default sort by price ascending
  request.sort = [
    { field: 'metadata.basePrice', direction: 'ASCENDING' },
  ];

  if (pagination) {
    request.pagination = pagination;
  } else {
    request.pagination = { offset: 0, pageSize: 20 };
  }

  return request;
}

/**
 * Parse URL search params to filter state.
 */
export function urlParamsToFilterState(searchParams: URLSearchParams): Partial<FilterState> {
  const state: Partial<FilterState> = {};

  // Providers - comma separated
  const providers = searchParams.get('providers');
  if (providers) {
    state.providers = providers.split(',').filter(Boolean);
  }

  // Cover type
  const coverType = searchParams.get('coverType');
  if (coverType && ['Hospital', 'Extras', 'Combined'].includes(coverType)) {
    state.coverType = coverType as FilterState['coverType'];
  }

  // Hospital cover - comma separated
  const hospitalCover = searchParams.get('hospitalCover');
  if (hospitalCover) {
    state.hospitalCover = hospitalCover
      .split(',')
      .filter((t): t is FilterState['hospitalCover'][number] =>
        ['Basic', 'Bronze', 'Silver', 'Gold'].includes(t)
      );
  }

  // Extras treatments - comma separated
  const extras = searchParams.get('extras');
  if (extras) {
    state.extras = extras.split(',').filter(Boolean);
  }

  // Hospital treatments - comma separated
  const hospitalTreatments = searchParams.get('hospitalTreatments');
  if (hospitalTreatments) {
    state.hospitalTreatments = hospitalTreatments.split(',').filter(Boolean);
  }

  return state;
}

/**
 * Convert filter state to URL search params.
 */
export function filterStateToUrlParams(state: FilterState): URLSearchParams {
  const params = new URLSearchParams();

  if (state.providers.length > 0) {
    params.set('providers', state.providers.join(','));
  }

  if (state.coverType !== 'any') {
    params.set('coverType', state.coverType);
  }

  if (state.hospitalCover.length > 0) {
    params.set('hospitalCover', state.hospitalCover.join(','));
  }

  if (state.extras.length > 0) {
    params.set('extras', state.extras.join(','));
  }

  if (state.hospitalTreatments.length > 0) {
    params.set('hospitalTreatments', state.hospitalTreatments.join(','));
  }

  return params;
}
