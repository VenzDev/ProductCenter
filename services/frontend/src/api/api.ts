import { lang } from "next/root-params";

const BASE_URL = process.env.BACKEND_URL ?? "http://backend";

async function localeHeaders(): Promise<HeadersInit> {
  return { "Accept-Language": await lang() };
}

export async function fetchApi<T>(path: string): Promise<T[]> {
  try {
    const response = await fetch(`${BASE_URL}${path}`, {
      headers: await localeHeaders(),
    });
    if (!response.ok) return [];

    const { data } = (await response.json()) as { data: T[] };
    return data;
  } catch {
    return [];
  }
}

export type PaginatedResponse<T, F> = {
  data: T[];
  meta: { current_page: number; last_page: number; total: number };
  filters: F;
};

// Unlike fetchApi/fetchApiItem, keeps `meta`/`filters` alongside `data` — needed by any
// endpoint that paginates and returns facets (category browsing, product search).
export async function fetchApiPaginated<T, F>(
  path: string,
): Promise<PaginatedResponse<T, F> | null> {
  try {
    const response = await fetch(`${BASE_URL}${path}`, {
      headers: await localeHeaders(),
    });
    if (!response.ok) return null;

    return (await response.json()) as PaginatedResponse<T, F>;
  } catch {
    return null;
  }
}

export async function fetchApiItem<T>(path: string): Promise<T | null> {
  try {
    const response = await fetch(`${BASE_URL}${path}`, {
      headers: await localeHeaders(),
    });
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

export async function proxyGet<T>(
  path: string,
  headers: HeadersInit,
): Promise<{ status: number; data: T }> {
  const response = await fetch(`${BASE_URL}${path}`, { headers });
  const data = (await response.json()) as T;
  return { status: response.status, data };
}
