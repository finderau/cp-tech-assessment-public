import { useState } from 'react';
import type { Product } from '@/types';
import { Badge } from '@/components/common';
import { DetailSection } from './DetailSection';

interface ProductCardProps {
  product: Product;
}

export function ProductCard({ product }: ProductCardProps) {
  const [expandedSections, setExpandedSections] = useState<Set<string>>(new Set());

  const toggleSection = (title: string) => {
    setExpandedSections((prev) => {
      const next = new Set(prev);
      if (next.has(title)) {
        next.delete(title);
      } else {
        next.add(title);
      }
      return next;
    });
  };

  // Format price
  const formattedPrice = new Intl.NumberFormat('en-AU', {
    style: 'currency',
    currency: 'AUD',
  }).format(product.metadata.basePrice);

  return (
    <div className="product-card">
      {/* Compare checkbox - disabled for MVP */}
      <div className="flex items-center gap-2 mb-3">
        <input
          type="checkbox"
          disabled
          className="w-4 h-4 rounded border-gray-300 opacity-50 cursor-not-allowed"
        />
        <span className="text-sm text-gray-500">Compare</span>
      </div>

      {/* Provider logo */}
      <div className="h-12 mb-3 flex items-center">
        {product.productImage ? (
          <img
            src={product.productImage.replace('s3://', 'https://')}
            alt={product.providerName}
            className="max-h-full max-w-full object-contain"
          />
        ) : (
          <span className="text-sm text-gray-400">{product.providerName}</span>
        )}
      </div>

      {/* Product name */}
      <h3 className="font-semibold text-gray-900 mb-2 line-clamp-2 min-h-[2.5rem]">
        {product.productName}
      </h3>

      {/* Badges */}
      <div className="flex flex-wrap gap-1 mb-4">
        {product.badges.map((badge, index) => (
          <Badge
            key={index}
            type={badge.type}
            text={badge.text}
            tooltip={badge.tooltip}
          />
        ))}
      </div>

      {/* Price block */}
      <div className="price-block mb-4">
        <div className="price-amount">{formattedPrice}</div>
        <div className="price-period flex items-center justify-center gap-1">
          per week
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
          </svg>
        </div>
      </div>

      {/* CTA Button */}
      <a
        href={product.redirectUrl}
        target="_blank"
        rel="noopener noreferrer"
        className="cta-button mb-4 block text-center"
      >
        GO TO SITE
      </a>

      {/* Description */}
      {product.description && (
        <p className="text-sm text-gray-600 mb-4 flex items-start gap-2">
          <span className="line-clamp-3">{product.description}</span>
          <button className="flex-shrink-0 text-gray-400 hover:text-gray-600">
            <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path
                fillRule="evenodd"
                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                clipRule="evenodd"
              />
            </svg>
          </button>
        </p>
      )}

      {/* Detail sections */}
      <div className="mt-auto">
        {product.details.map((section) => (
          <DetailSection
            key={section.title}
            section={section}
            isExpanded={expandedSections.has(section.title)}
            onToggle={() => toggleSection(section.title)}
          />
        ))}
      </div>
    </div>
  );
}
