export function formatPrice(cents: number, currency: string): string {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency,
  }).format(cents / 100);
}

export function formatDate(iso: string): string {
  return new Intl.DateTimeFormat("en-US", { dateStyle: "long" }).format(
    new Date(iso)
  );
}
