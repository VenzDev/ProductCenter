import { fetchApi, fetchApiItem } from "@/api/api";

export type Product = {
  id: number;
  category: { id: number; name: string | null };
  name: string;
  description: string | null;
  price_cents: number;
  currency: string;
  main_image: {
    original_url: string;
    webp_url: string;
    thumbnail_webp_url: string;
  } | null;
};

export function getLatestProducts(): Promise<Product[]> {
  return fetchApi<Product>("/api/v1/products/latest");
}

export function getProduct(id: number): Promise<Product | null> {
  return fetchApiItem<Product>(`/api/v1/products/${id}`);
}
