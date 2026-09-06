import { CheckoutForm } from "@/components/checkout/checkout-form";
import { getDictionary } from "@/app/[lang]/dictionaries";

export default async function CheckoutPage() {
  const dict = await getDictionary();

  return (
    <div className="flex-1">
      <div className="mx-auto max-w-6xl px-4 py-6">
        <h1 className="mb-6 text-2xl font-semibold">{dict.checkout.title}</h1>
        <CheckoutForm dict={dict.checkout} />
      </div>
    </div>
  );
}
