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

export async function fetchApiItem<T>(path: string): Promise<T | null> {
  try {
    const response = await fetch(`${BASE_URL}${path}`);
    if (!response.ok) return null;

    const { data } = (await response.json()) as { data: T };
    return data;
  } catch {
    return null;
  }
}

export async function postApi<T>(
  path: string,
  body: unknown,
): Promise<{ status: number; data: T }> {
  const response = await fetch(`${BASE_URL}${path}`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(body),
  });

  const data = (await response.json()) as T;
  return { status: response.status, data };
}
