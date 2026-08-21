const BASE_URL = process.env.BACKEND_URL ?? "http://backend";

export async function fetchApi<T>(path: string): Promise<T[]> {
  try {
    const response = await fetch(`${BASE_URL}${path}`);
    if (!response.ok) return [];

    const { data } = (await response.json()) as { data: T[] };
    return data;
  } catch {
    return [];
  }
}
