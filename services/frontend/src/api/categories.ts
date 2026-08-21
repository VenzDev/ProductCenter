import { fetchApi } from "@/api/api";

export type Category = {
  id: number;
  name: string;
  slug: string;
  children: Category[];
};

export function getCategories(): Promise<Category[]> {
  return fetchApi<Category>("/api/v1/categories");
}
