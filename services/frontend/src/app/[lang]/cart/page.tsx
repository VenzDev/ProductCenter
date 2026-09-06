import { CartPageContent } from "@/components/cart/cart-page-content";
import { getDictionary } from "@/app/[lang]/dictionaries";

export default async function CartPage() {
  const dict = await getDictionary();

  return (
    <div className="flex-1">
      <div className="mx-auto max-w-6xl px-4 py-6">
        <h1 className="mb-6 text-2xl font-semibold">{dict.cart.title}</h1>
        <CartPageContent dict={dict.cart} />
      </div>
    </div>
  );
}
