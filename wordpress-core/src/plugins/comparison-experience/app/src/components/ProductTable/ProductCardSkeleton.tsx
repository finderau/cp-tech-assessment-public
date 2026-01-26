/**
 * Loading skeleton for ProductCard.
 */
export function ProductCardSkeleton() {
  return (
    <div className="product-card animate-pulse">
      {/* Compare checkbox */}
      <div className="flex items-center gap-2 mb-3">
        <div className="w-4 h-4 rounded skeleton" />
        <div className="w-16 h-4 skeleton" />
      </div>

      {/* Provider logo */}
      <div className="h-12 mb-3">
        <div className="w-24 h-8 skeleton" />
      </div>

      {/* Product name */}
      <div className="space-y-2 mb-4">
        <div className="h-4 skeleton w-full" />
        <div className="h-4 skeleton w-3/4" />
      </div>

      {/* Badges */}
      <div className="flex gap-1 mb-4">
        <div className="h-5 w-16 skeleton rounded" />
        <div className="h-5 w-20 skeleton rounded" />
      </div>

      {/* Price block */}
      <div className="h-24 skeleton rounded-lg mb-4" />

      {/* CTA Button */}
      <div className="h-12 skeleton rounded-lg mb-4" />

      {/* Description */}
      <div className="space-y-2 mb-4">
        <div className="h-3 skeleton w-full" />
        <div className="h-3 skeleton w-5/6" />
      </div>

      {/* Detail sections */}
      <div className="space-y-2 mt-4 pt-4 border-t border-gray-100">
        <div className="h-10 skeleton rounded" />
        <div className="h-10 skeleton rounded" />
        <div className="h-10 skeleton rounded" />
      </div>
    </div>
  );
}
