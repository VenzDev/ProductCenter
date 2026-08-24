const TOKEN_KEY = "auth_token";
export const AUTH_CHANGE_EVENT = "auth-change";

export type AuthResponse = {
  access_token: string;
  token_type: string;
  expires_in: number;
};

export type User = {
  id: number;
  name: string;
  email: string;
};

export function getToken(): string | null {
  if (typeof window === "undefined") return null;
  return localStorage.getItem(TOKEN_KEY);
}

function setToken(token: string) {
  localStorage.setItem(TOKEN_KEY, token);
  window.dispatchEvent(new Event(AUTH_CHANGE_EVENT));
}

export function clearToken() {
  localStorage.removeItem(TOKEN_KEY);
  window.dispatchEvent(new Event(AUTH_CHANGE_EVENT));
}

export async function getCurrentUser(): Promise<User | null> {
  const token = getToken();
  if (!token) return null;

  const response = await fetch("/api/auth/me", {
    headers: { Authorization: `Bearer ${token}` },
  });

  if (!response.ok) {
    if (response.status === 401) clearToken();
    return null;
  }

  return (await response.json()) as User;
}

async function authRequest(path: string, body: unknown): Promise<AuthResponse> {
  const response = await fetch(path, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(body),
  });

  const data = await response.json();

  if (!response.ok) {
    const errors = data.errors as Record<string, string[]> | undefined;
    const firstError = errors ? Object.values(errors)[0]?.[0] : undefined;
    throw new Error(firstError ?? data.message ?? "Something went wrong.");
  }

  setToken(data.access_token);
  return data as AuthResponse;
}

export function login(email: string, password: string) {
  return authRequest("/api/auth/login", { email, password });
}

export function register(name: string, email: string, password: string) {
  return authRequest("/api/auth/register", { name, email, password });
}
