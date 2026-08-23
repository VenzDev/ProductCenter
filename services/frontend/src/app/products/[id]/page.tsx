import Image from "next/image";
import Link from "next/link";
import { notFound } from "next/navigation";
import { ChevronRightIcon } from "lucide-react";

import { Badge } from "@/components/ui/badge";
import { ProductCard } from "@/components/product-card";
import { QuantityAddToCart } from "@/components/quantity-add-to-cart";
import { formatPrice } from "@/lib/format";
import { getLatestProducts, getProduct } from "@/api/products";

export default async function ProductPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  const product = await getProduct(Number(id));

  if (!product) {
    notFound();
  }

  const relatedProducts = (await getLatestProducts())
    .filter((related) => related.id !== product.id)
    .slice(0, 4);

  return (
    <div className="flex-1">
      <div className="mx-auto max-w-6xl px-4 py-6">
        <nav className="flex items-center gap-1 text-sm text-muted-foreground">
          <Link href="/" className="hover:text-foreground">
            Home
          </Link>
          {product.category.name && (
            <>
              <ChevronRightIcon className="size-3.5" />
              <span>{product.category.name}</span>
            </>
          )}
          <ChevronRightIcon className="size-3.5" />
          <span className="text-foreground">{product.name}</span>
        </nav>

        <div className="mt-6 grid gap-8 md:grid-cols-2">
          <div className="relative aspect-square overflow-hidden rounded-xl bg-muted">
            {product.main_image && (
              <Image
                src={product.main_image}
                alt={product.name}
                fill
                className="object-cover"
                sizes="(min-width: 768px) 50vw, 100vw"
                priority
                unoptimized
              />
            )}
          </div>

          <div className="flex flex-col gap-4">
            {product.category.name && (
              <Badge variant="outline" className="w-fit">
                {product.category.name}
              </Badge>
            )}
            <h1 className="text-2xl font-semibold sm:text-3xl">
              {product.name}
            </h1>
            <span className="text-2xl font-semibold">
              {formatPrice(product.price_cents, product.currency)}
            </span>
            {product.description && (
              <p className="text-muted-foreground">{product.description}</p>
            )}
            <div className="mt-2 border-t pt-4">
              <QuantityAddToCart />
            </div>
          </div>
        </div>
      </div>

      {relatedProducts.length > 0 && (
        <section className="mx-auto max-w-6xl px-4 py-6">
          <h2 className="text-2xl font-semibold">You might also like</h2>
          <div className="mt-6 grid grid-cols-2 gap-6 md:grid-cols-4">
            {relatedProducts.map((related) => (
              <ProductCard key={related.id} product={related} />
            ))}
          </div>
        </section>
      )}
    </div>
  );
}
