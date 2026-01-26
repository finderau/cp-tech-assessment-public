import type { AppConfig } from '@/types/config';
import type { ProductsResponse, ProductsApiRequest, ProvidersResponse } from '@/types';

/**
 * API client for the product-data REST endpoint.
 */
export class ProductApi {
  private baseUrl: string;
  private nonce: string;

  constructor(config: AppConfig) {
    this.baseUrl = config.apiBase;
    this.nonce = config.nonce;
  }

  /**
   * Get headers for API requests.
   */
  private getHeaders(): HeadersInit {
    const headers: HeadersInit = {
      'Content-Type': 'application/json',
    };

    // Add nonce header if available (WordPress authentication).
    if (this.nonce) {
      headers['X-WP-Nonce'] = this.nonce;
    }

    return headers;
  }

  /**
   * Fetch products from the API with optional filters, sort, and pagination.
   */
  async getProducts(request: ProductsApiRequest = {}): Promise<ProductsResponse> {
    const url = `${this.baseUrl}/product-data`;

    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(request),
    });

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}));
      throw new ApiError(
        errorData.message || `HTTP error ${response.status}`,
        response.status,
        errorData
      );
    }

    return response.json();
  }

  /**
   * Fetch all providers from the API.
   */
  async getProviders(): Promise<ProvidersResponse> {
    const url = `${this.baseUrl}/providers`;

    const response = await fetch(url, {
      method: 'GET',
      headers: this.getHeaders(),
    });

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}));
      throw new ApiError(
        errorData.message || `HTTP error ${response.status}`,
        response.status,
        errorData
      );
    }

    return response.json();
  }
}

/**
 * Custom error class for API errors.
 */
export class ApiError extends Error {
  constructor(
    message: string,
    public readonly status: number,
    public readonly data?: unknown
  ) {
    super(message);
    this.name = 'ApiError';
  }
}

/**
 * Create a singleton API instance.
 */
let apiInstance: ProductApi | null = null;

export function createApi(config: AppConfig): ProductApi {
  apiInstance = new ProductApi(config);
  return apiInstance;
}

export function getApi(): ProductApi {
  if (!apiInstance) {
    throw new Error('API not initialized. Call createApi first.');
  }
  return apiInstance;
}
