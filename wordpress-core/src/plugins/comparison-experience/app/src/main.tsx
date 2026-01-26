import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import App from './App';
import './styles/index.css';

// Get the root element
const rootElement = document.getElementById('comparison-table-root');

if (!rootElement) {
  console.error('Comparison Table: Root element not found');
} else {
  // Extract configuration from data attributes or global settings
  const apiBase = rootElement.dataset.apiBase || 
    (window as unknown as { comparisonTableSettings?: { apiBase: string } }).comparisonTableSettings?.apiBase ||
    '/wp-json/api/v1';

  const nonce = rootElement.dataset.nonce ||
    (window as unknown as { comparisonTableSettings?: { nonce: string } }).comparisonTableSettings?.nonce ||
    '';

  const config = {
    apiBase,
    nonce,
  };

  createRoot(rootElement).render(
    <StrictMode>
      <App config={config} />
    </StrictMode>
  );
}
