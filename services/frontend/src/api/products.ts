import { fetchApi } from "@/api/api";

export type Product = {
  id: number;
  category: { id: number; name: string | null };
  name: string;
  description: string | null;
  price_cents: number;
  currency: string;
  main_image: string | null;
};

export function getLatestProducts(): Promise<Product[]> {
  return fetchApi<Product>("/api/v1/products/latest");
}
