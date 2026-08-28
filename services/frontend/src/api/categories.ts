import { fetchApi, fetchApiPaginated } from "@/api/api";
import type { CategoryListFilters, Product, ProductListResponse } from "@/api/products";

export type Category = {
  id: number;
  name: string;
  slug: string;
  children: Category[];
};

export function getCategories(): Promise<Category[]> {
  return fetchApi<Category>("/api/v1/categories");
}

export type CategoryPath = { category: Category; parent: Category | null };

// The tree is capped at two levels (see backend CategoryController), so a match is
// either a root category itself or a direct child of one — never deeper. Returning the
// parent alongside lets a child category's page render a full "Home > Parent > Child"
// breadcrumb instead of skipping straight from Home to the child.
export function findCategoryPath(
  categories: Category[],
  slug: string,
): CategoryPath | null {
  for (const category of categories) {
    if (category.slug === slug) return { category, parent: null };
    const child = category.children.find((c) => c.slug === slug);
    if (child) return { category: child, parent: category };
  }
  return null;
}

export function getCategoryProducts(
  slug: string,
  query: string,
): Promise<ProductListResponse | null> {
  return fetchApiPaginated<Product, CategoryListFilters>(
    `/api/v1/categories/${slug}/products${query}`,
  );
}
