"use client";

import { useState } from "react";
import Link from "next/link";
import { useParams } from "next/navigation";
import { CircleAlertIcon, TruckIcon } from "lucide-react";
import { Elements, PaymentElement, useElements, useStripe } from "@stripe/react-stripe-js";

import { Alert, AlertTitle } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Field, FieldGroup, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { checkout } from "@/api/checkout";
import { useCart } from "@/hooks/use-cart";
import { useCurrentUser } from "@/hooks/use-current-user";
import { getStripe } from "@/lib/stripe";
import { localizedHref } from "@/i18n/config";

export type CheckoutFormDict = {
  addressHeading: string;
  fullName: string;
  street: string;
  city: string;
  postalCode: string;
  country: string;
  phone: string;
  deliveryHeading: string;
  courier: string;
  paymentHeading: string;
  paymentDescription: string;
  loginPrompt: string;
  loginLink: string;
  placeOrder: string;
  payButton: string;
  payingLabel: string;
  successHeading: string;
  successMessage: string;
  genericError: string;
};

function PaymentStep({
  dict,
  onSuccess,
}: {
  dict: CheckoutFormDict;
  onSuccess: () => void;
}) {
  const stripe = useStripe();
  const elements = useElements();
  const [paying, setPaying] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handlePay() {
    if (!stripe || !elements) return;
    setError(null);
    setPaying(true);

    const { error: confirmError, paymentIntent } = await stripe.confirmPayment({
      elements,
      redirect: "if_required",
    });

    setPaying(false);

    if (confirmError) {
      setError(confirmError.message ?? dict.genericError);
      return;
    }

    if (paymentIntent?.status === "succeeded") {
      onSuccess();
    }
  }

  return (
    <div className="flex flex-col gap-3">
      <PaymentElement />
      {error && (
        <Alert variant="destructive">
          <CircleAlertIcon data-icon="inline-start" />
          <AlertTitle>{error}</AlertTitle>
        </Alert>
      )}
      <Button type="button" size="lg" disabled={paying} onClick={handlePay}>
        {paying ? dict.payingLabel : dict.payButton}
      </Button>
    </div>
  );
}

export function CheckoutForm({ dict }: { dict: CheckoutFormDict }) {
  const { user, loading } = useCurrentUser();
  const { items, clear } = useCart();
  const { lang } = useParams<{ lang: string }>();
  const [clientSecret, setClientSecret] = useState<string | null>(null);
  const [placing, setPlacing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [succeeded, setSucceeded] = useState(false);

  async function handlePlaceOrder() {
    setError(null);
    setPlacing(true);
    try {
      const { client_secret } = await checkout(
        items.map((item) => ({ productId: item.productId, quantity: item.quantity })),
      );
      setClientSecret(client_secret);
    } catch (err) {
      setError(err instanceof Error ? err.message : dict.genericError);
    } finally {
      setPlacing(false);
    }
  }

  function handlePaymentSuccess() {
    clear();
    setSucceeded(true);
  }

  if (!loading && !user) {
    return (
      <div className="flex flex-col items-center gap-4 py-16 text-center">
        <p className="text-muted-foreground">{dict.loginPrompt}</p>
        <Button nativeButton={false} render={<Link href={localizedHref(lang, "/login")} />}>
          {dict.loginLink}
        </Button>
      </div>
    );
  }

  if (succeeded) {
    return (
      <div className="flex flex-col items-center gap-2 py-16 text-center">
        <h2 className="text-xl font-semibold">{dict.successHeading}</h2>
        <p className="text-muted-foreground">{dict.successMessage}</p>
      </div>
    );
  }

  return (
    <div className="grid gap-6 lg:grid-cols-2">
      <Card>
        <CardHeader>
          <CardTitle>{dict.addressHeading}</CardTitle>
        </CardHeader>
        <CardContent>
          <FieldGroup>
            <Field>
              <FieldLabel htmlFor="fullName">{dict.fullName}</FieldLabel>
              <Input id="fullName" required />
            </Field>
            <Field>
              <FieldLabel htmlFor="street">{dict.street}</FieldLabel>
              <Input id="street" required />
            </Field>
            <Field>
              <FieldLabel htmlFor="city">{dict.city}</FieldLabel>
              <Input id="city" required />
            </Field>
            <Field>
              <FieldLabel htmlFor="postalCode">{dict.postalCode}</FieldLabel>
              <Input id="postalCode" required />
            </Field>
            <Field>
              <FieldLabel htmlFor="country">{dict.country}</FieldLabel>
              <Input id="country" required />
            </Field>
            <Field>
              <FieldLabel htmlFor="phone">{dict.phone}</FieldLabel>
              <Input id="phone" type="tel" />
            </Field>
          </FieldGroup>
        </CardContent>
      </Card>

      <div className="flex flex-col gap-6">
        <Card>
          <CardHeader>
            <CardTitle>{dict.deliveryHeading}</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="flex items-center gap-3 rounded-lg border border-primary/30 bg-primary/5 p-3">
              <TruckIcon className="size-5 text-primary" />
              <span className="text-sm font-medium">{dict.courier}</span>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>{dict.paymentHeading}</CardTitle>
            <CardDescription>{dict.paymentDescription}</CardDescription>
          </CardHeader>
          <CardContent className="flex flex-col gap-3">
            {clientSecret ? (
              <Elements stripe={getStripe()} options={{ clientSecret }}>
                <PaymentStep dict={dict} onSuccess={handlePaymentSuccess} />
              </Elements>
            ) : (
              <>
                {error && (
                  <Alert variant="destructive">
                    <CircleAlertIcon data-icon="inline-start" />
                    <AlertTitle>{error}</AlertTitle>
                  </Alert>
                )}
                <Button
                  type="button"
                  size="lg"
                  disabled={placing || items.length === 0}
                  onClick={handlePlaceOrder}
                >
                  {dict.placeOrder}
                </Button>
              </>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
