import { ProductCard } from "@/components/product-card";
import type { Product } from "@/api/products";

export function ProductGrid({
  products,
  emptyMessage,
}: {
  products: Product[];
  emptyMessage: string;
}) {
  if (products.length === 0) {
    return <p className="text-muted-foreground">{emptyMessage}</p>;
  }

  return (
    <div className="grid grid-cols-2 gap-6 md:grid-cols-3">
      {products.map((product) => (
        <ProductCard key={product.id} product={product} />
      ))}
    </div>
  );
}
