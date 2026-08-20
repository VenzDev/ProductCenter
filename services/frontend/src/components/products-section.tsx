import { Badge } from "@/components/ui/badge";
import { Card, CardContent } from "@/components/ui/card";

type Product = {
  name: string;
  category: string;
  description: string;
  inStock: boolean;
  price?: number;
  originalPrice?: number;
};

const PRODUCTS: Product[] = [
  {
    name: "Wireless Headphones",
    category: "Electronics",
    description: "Noise-cancelling, 30-hour battery.",
    inStock: true,
    price: 129.99,
  },
  {
    name: "Running Shoes",
    category: "Clothing",
    description: "Lightweight everyday trainers.",
    inStock: true,
    price: 69.99,
    originalPrice: 89.99,
  },
  {
    name: "Ceramic Mug Set",
    category: "Home & Garden",
    description: "Set of four, dishwasher safe.",
    inStock: false,
  },
  {
    name: "Leather Wallet",
    category: "Accessories",
    description: "Full-grain leather, slim profile.",
    inStock: true,
    price: 49.99,
  },
];

function ProductPrice({ product }: { product: Product }) {
  if (!product.inStock) {
    return <Badge variant="secondary">Out of stock</Badge>;
  }

  if (product.originalPrice) {
    const percentOff = Math.round(
      (1 - product.price! / product.originalPrice) * 100
    );

    return (
      <div className="flex items-center gap-2">
        <Badge variant="destructive" className="gap-1">
          <span className="line-through">${product.originalPrice}</span>
          <span>{percentOff}% off</span>
        </Badge>
        <span className="font-semibold">${product.price}</span>
      </div>
    );
  }

  return <span className="font-semibold">${product.price}</span>;
}

export function ProductsSection() {
  return (
    <section className="mx-auto max-w-6xl px-4 py-12">
      <h2 className="text-2xl font-semibold">Product for you</h2>
      <p className="text-muted-foreground">
        A few picks we think you&apos;ll like.
      </p>
      <div className="mt-6 grid grid-cols-2 gap-6 md:grid-cols-4">
        {PRODUCTS.map((product) => (
          <Card key={product.name} className="gap-0 py-0">
            <div className="aspect-square rounded-t-xl bg-muted" />
            <CardContent className="flex flex-col gap-2 p-4">
              <Badge variant="outline" className="w-fit">
                {product.category}
              </Badge>
              <h3 className="font-semibold">{product.name}</h3>
              <p className="text-sm text-muted-foreground">
                {product.description}
              </p>
              <ProductPrice product={product} />
            </CardContent>
          </Card>
        ))}
      </div>
    </section>
  );
}
