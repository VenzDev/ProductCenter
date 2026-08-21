import Image from "next/image";

import { Badge } from "@/components/ui/badge";
import { Card, CardContent } from "@/components/ui/card";
import { formatPrice } from "@/lib/format";
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
          <Card key={product.id} className="gap-0 py-0">
            <div className="relative aspect-square overflow-hidden rounded-t-xl bg-muted">
              {product.main_image && (
                <Image
                  src={product.main_image}
                  alt={product.name}
                  fill
                  className="object-cover"
                  sizes="(min-width: 768px) 25vw, 50vw"
                />
              )}
            </div>
            <CardContent className="flex flex-col gap-2 p-4">
              {product.category.name && (
                <Badge variant="outline" className="w-fit">
                  {product.category.name}
                </Badge>
              )}
              <h3 className="font-semibold">{product.name}</h3>
              <p className="text-sm text-muted-foreground">
                {product.description}
              </p>
              <span className="font-semibold">
                {formatPrice(product.price_cents, product.currency)}
              </span>
            </CardContent>
          </Card>
        ))}
      </div>
    </section>
  );
}
