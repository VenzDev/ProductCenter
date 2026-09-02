import { getDictionary } from "@/app/[lang]/dictionaries";

export default async function CartPage() {
  const dict = await getDictionary();

  return (
    <div className="flex-1">
      <div className="mx-auto max-w-6xl px-4 py-6">
        <h1 className="text-2xl font-semibold">{dict.cart.title}</h1>
        <p className="text-muted-foreground">{dict.cart.pagePlaceholder}</p>
      </div>
    </div>
  );
}
