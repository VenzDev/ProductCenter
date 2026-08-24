const TOKEN_KEY = "auth_token";

export type AuthResponse = {
  access_token: string;
  token_type: string;
  expires_in: number;
};

export function getToken(): string | null {
  if (typeof window === "undefined") return null;
  return localStorage.getItem(TOKEN_KEY);
}

export function clearToken() {
  localStorage.removeItem(TOKEN_KEY);
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

  localStorage.setItem(TOKEN_KEY, data.access_token);
  return data as AuthResponse;
}

export function login(email: string, password: string) {
  return authRequest("/api/auth/login", { email, password });
}

export function register(name: string, email: string, password: string) {
  return authRequest("/api/auth/register", { name, email, password });
}
