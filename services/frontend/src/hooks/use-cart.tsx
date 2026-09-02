"use client";

import { useCallback, useSyncExternalStore } from "react";

// Anonymous and logged-in carts are both just this localStorage array for now.
// When checkout lands, the logged-in cart moves server-side and this becomes the
// guest cart that gets merged on login (see the cart discussion in the PR).
const STORAGE_KEY = "cart";
const CART_CHANGE_EVENT = "cart-change";

export type CartItem = {
  productId: number;
  quantity: number;
  // Snapshot taken at add-to-cart time — enough to render the drawer without a
  // round-trip. The full cart page / checkout revalidate against the backend.
  name: string;
  image: string | null;
  priceCents: number;
  currency: string;
};

// Module-level snapshot so useSyncExternalStore hands every subscriber the same
// reference until the store actually changes. Starts empty, which is also what
// the server renders — so the first client render matches and hydration is safe.
let snapshot: CartItem[] = [];

function readStorage(): CartItem[] {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    return raw ? (JSON.parse(raw) as CartItem[]) : [];
  } catch {
    return [];
  }
}

function subscribe(onChange: () => void): () => void {
  const handler = () => {
    snapshot = readStorage();
    onChange();
  };
  // Pick up whatever is already in storage for this freshly mounted subscriber,
  // plus later writes from this tab (custom event) and other tabs ("storage").
  handler();
  window.addEventListener(CART_CHANGE_EVENT, handler);
  window.addEventListener("storage", handler);
  return () => {
    window.removeEventListener(CART_CHANGE_EVENT, handler);
    window.removeEventListener("storage", handler);
  };
}

function write(next: CartItem[]): void {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(next));
  snapshot = next;
  window.dispatchEvent(new Event(CART_CHANGE_EVENT));
}

export function useCart() {
  const items = useSyncExternalStore(
    subscribe,
    () => snapshot,
    () => snapshot,
  );

  const addItem = useCallback(
    (item: Omit<CartItem, "quantity">, quantity = 1) => {
      const current = readStorage();
      const existing = current.find((entry) => entry.productId === item.productId);
      write(
        existing
          ? current.map((entry) =>
              entry.productId === item.productId
                ? { ...entry, quantity: entry.quantity + quantity }
                : entry,
            )
          : [...current, { ...item, quantity }],
      );
    },
    [],
  );

  const removeItem = useCallback((productId: number) => {
    write(readStorage().filter((entry) => entry.productId !== productId));
  }, []);

  const clear = useCallback(() => write([]), []);

  return {
    items,
    count: items.reduce((sum, entry) => sum + entry.quantity, 0),
    addItem,
    removeItem,
    clear,
  };
}
