import Image from "next/image";
import Link from "next/link";
import { lang } from "next/root-params";

import { Badge } from "@/components/ui/badge";
import { Card, CardContent } from "@/components/ui/card";
import { formatPrice } from "@/lib/format";
import { localizedHref } from "@/i18n/config";
import type { Product } from "@/api/products";

export async function ProductCard({ product }: { product: Product }) {
  const imageSrc = product.main_image?.thumbnail_webp_url;
  const locale = await lang();

  return (
    <Link href={localizedHref(locale, `/products/${product.id}`)} className="block h-full">
      <Card className="h-full gap-0 py-0">
        <div className="relative aspect-square overflow-hidden rounded-t-xl bg-muted">
          {imageSrc && (
            <Image
              src={imageSrc}
              alt={product.name}
              fill
              className="object-cover"
              sizes="(min-width: 768px) 25vw, 50vw"
              unoptimized
            />
          )}
        </div>
        <CardContent className="flex h-full flex-col gap-2 p-4">
          {product.category.name && (
            <Badge variant="outline" className="w-fit">
              {product.category.name}
            </Badge>
          )}
          <h3 className="line-clamp-2 font-semibold">{product.name}</h3>
          <p className="line-clamp-3 text-sm text-muted-foreground">
            {product.description}
          </p>
          <span className="mt-auto font-semibold">
            {formatPrice(product.price_cents, product.currency)}
          </span>
        </CardContent>
      </Card>
    </Link>
  );
}
