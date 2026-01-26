/**
 * Product data structure from the product-data API.
 */
export interface Product {
  id: string;
  productImage: string;
  productName: string;
  providerName: string;
  redirectUrl: string;
  description: string;
  rewards: ProductRewards;
  metadata: ProductMetadata;
  badges: ProductBadge[];
  details: ProductDetailSection[];
  brokerData: BrokerData;
}

export interface ProductRewards {
  campaignName: string;
  infoboxHeadline: string;
  infoboxDescription: string;
  infoboxTCsLink: string;
  infoboxTCsText: string;
  expiryText: string;
  highlightText: string;
}

export interface ProductMetadata {
  basePrice: number;
  hideBehindFullMarket: number;
  hideRedirectCta: number;
}

export interface ProductBadge {
  type: 'basic' | 'promoted' | string;
  text: string;
  tooltip?: string;
}

export interface ProductDetailSection {
  title: 'Extras' | 'Hospital' | 'Ambulance' | string;
  subtitle: string;
  attributes: ProductAttribute[];
}

export interface ProductAttribute {
  name: string;
  value?: string;
  isAvailable?: boolean | 'restricted';
  tooltip?: string;
  metadata?: AttributeMetadata[];
}

export interface AttributeMetadata {
  name: string;
  value: string;
}

export interface BrokerData {
  shouldShowBrokerCta: boolean;
  brokerRedirectUrl: string;
  brokerCtaText: string;
}

/**
 * API response structure for product-data endpoint.
 */
export interface ProductsResponse {
  products: Product[];
  totalCount: number;
  offset: number;
  pageSize: number;
}

/**
 * Provider data structure.
 */
export interface Provider {
  id: string;
  providerName: string;
}

/**
 * API response structure for providers endpoint.
 */
export interface ProvidersResponse {
  providers: Provider[];
  totalCount: number;
}
