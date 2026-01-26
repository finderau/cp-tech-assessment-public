/**
 * Application configuration passed from WordPress or data attributes.
 */
export interface AppConfig {
  /** Base URL for the REST API */
  apiBase: string;
  /** WordPress REST API nonce for authentication */
  nonce: string;
}
