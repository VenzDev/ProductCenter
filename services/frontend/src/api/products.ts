import { fetchApi, fetchApiItem } from "@/api/api";

export type Product = {
  id: number;
  category: { id: number; name: string | null };
  name: string;
  description: string | null;
  price_cents: number;
  currency: string;
  main_image: {
    webp_url: string;
    thumbnail_webp_url: string;
  } | null;
  // Only present on the single-product endpoint — /products and /products/latest
  // don't eager-load it, since list views only ever show main_image.
  gallery?: { webp_url: string; thumbnail_webp_url: string }[];
};

export function getLatestProducts(): Promise<Product[]> {
  return fetchApi<Product>("/api/v1/products/latest");
}

export function getProduct(id: number): Promise<Product | null> {
  return fetchApiItem<Product>(`/api/v1/products/${id}`);
}
