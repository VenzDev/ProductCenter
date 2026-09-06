import { getToken } from "@/api/auth";

export type CheckoutItem = { productId: number; quantity: number };
export type CheckoutResponse = { order_id: number; client_secret: string };

export async function checkout(items: CheckoutItem[]): Promise<CheckoutResponse> {
  const token = getToken();

  const response = await fetch("/api/checkout", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    body: JSON.stringify({
      items: items.map((item) => ({ product_id: item.productId, quantity: item.quantity })),
    }),
  });

  const data = await response.json();

  if (!response.ok) {
    const errors = data.errors as Record<string, string[]> | undefined;
    const firstError = errors ? Object.values(errors)[0]?.[0] : undefined;
    throw new Error(firstError ?? data.message ?? "Something went wrong.");
  }

  return data as CheckoutResponse;
}
