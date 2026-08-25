import { ProductCard } from "@/components/product-card";
import { getLatestProducts } from "@/api/products";
import { getDictionary } from "@/app/[lang]/dictionaries";

export async function ProductsSection() {
  const [products, dict] = await Promise.all([
    getLatestProducts(),
    getDictionary(),
  ]);

  if (products.length === 0) {
    return null;
  }

  return (
    <section className="mx-auto max-w-6xl px-4 py-6">
      <h2 className="text-2xl font-semibold">{dict.home.products.heading}</h2>
      <p className="text-muted-foreground">{dict.home.products.subtitle}</p>
      <div className="mt-6 grid grid-cols-2 gap-6 md:grid-cols-4">
        {products.map((product) => (
          <ProductCard key={product.id} product={product} />
        ))}
      </div>
    </section>
  );
}
