import {
  fetchApi,
  fetchApiItem,
  fetchApiPaginated,
  type PaginatedResponse,
} from "@/api/api";

export type ProductAttribute = {
  key: string;
  name: string;
  value: string | number | string[] | number[];
  value_label: string | number | string[] | number[];
};

export type Product = {
  id: number;
  category: { id: number; name: string | null };
  name: string;
  description: string | null;
  price_cents: number;
  currency: string;
  attributes: ProductAttribute[];
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

export type PriceFacet = { min: number; max: number } | null;

export type AttributeFacet = {
  key: string;
  name: string;
  options: { key: string; label: string; count: number }[];
};

export type CategoryFacet = { id: number; name: string; count: number };

export type SubcategoryFacet = { id: number; slug: string; name: string; count: number };

export type ProductListFilters = {
  price: PriceFacet;
  attributes: AttributeFacet[];
};

export type SearchFilters = ProductListFilters & { categories: CategoryFacet[] };
export type CategoryListFilters = ProductListFilters & { subcategories: SubcategoryFacet[] };

export type ProductListResponse = PaginatedResponse<Product, CategoryListFilters>;
export type SearchResponse = PaginatedResponse<Product, SearchFilters>;

export function searchProducts(query: string): Promise<SearchResponse | null> {
  return fetchApiPaginated<Product, SearchFilters>(`/api/v1/products/search${query}`);
}
