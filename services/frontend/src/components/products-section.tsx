import { ProductCard } from "@/components/product-card";
import { getLatestProducts } from "@/api/products";

export async function ProductsSection() {
  const products = await getLatestProducts();

  if (products.length === 0) {
    return null;
  }

  return (
    <section className="mx-auto max-w-6xl px-4 py-6">
      <h2 className="text-2xl font-semibold">Product for you</h2>
      <p className="text-muted-foreground">
        A few picks we think you&apos;ll like.
      </p>
      <div className="mt-6 grid grid-cols-2 gap-6 md:grid-cols-4">
        {products.map((product) => (
          <ProductCard key={product.id} product={product} />
        ))}
      </div>
    </section>
  );
}
