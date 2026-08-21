export type Category = {
  id: number;
  name: string;
  slug: string;
  children: Category[];
};

export async function getCategories(): Promise<Category[]> {
  const baseUrl = process.env.BACKEND_URL ?? "http://backend";

  try {
    const response = await fetch(`${baseUrl}/api/v1/categories`);
    if (!response.ok) return [];

    const { data } = (await response.json()) as { data: Category[] };
    return data;
  } catch {
    return [];
  }
}
