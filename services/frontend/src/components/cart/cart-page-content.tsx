"use client";

import Image from "next/image";
import Link from "next/link";
import { useParams } from "next/navigation";
import { MinusIcon, PlusIcon, Trash2Icon } from "lucide-react";

import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Separator } from "@/components/ui/separator";
import { useCart } from "@/hooks/use-cart";
import { formatPrice } from "@/lib/format";
import { localizedHref } from "@/i18n/config";

export type CartPageDict = {
  empty: string;
  continueShopping: string;
  decreaseQuantity: string;
  increaseQuantity: string;
  remove: string;
  redeemHeading: string;
  redeemPlaceholder: string;
  redeemApply: string;
  subtotal: string;
  total: string;
  checkout: string;
};

export function CartPageContent({ dict }: { dict: CartPageDict }) {
  const { items, removeItem, updateQuantity } = useCart();
  const { lang } = useParams<{ lang: string }>();

  if (items.length === 0) {
    return (
      <div className="flex flex-col items-center gap-4 py-16 text-center">
        <p className="text-muted-foreground">{dict.empty}</p>
        <Button
          nativeButton={false}
          render={<Link href={localizedHref(lang, "/products")} />}
        >
          {dict.continueShopping}
        </Button>
      </div>
    );
  }

  const subtotalCents = items.reduce(
    (sum, item) => sum + item.priceCents * item.quantity,
    0,
  );
  const currency = items[0].currency;

  return (
    <div className="grid gap-6 lg:grid-cols-3">
      <ul className="divide-y lg:col-span-2">
        {items.map((item) => (
          <li key={item.productId} className="flex items-center gap-4 py-4">
            <div className="relative size-20 shrink-0 overflow-hidden rounded-md bg-muted">
              {item.image && (
                <Image
                  src={item.image}
                  alt={item.name}
                  fill
                  className="object-cover"
                  sizes="80px"
                  unoptimized
                />
              )}
            </div>
            <div className="min-w-0 flex-1">
              <p className="truncate font-medium">{item.name}</p>
              <p className="text-sm text-muted-foreground">
                {formatPrice(item.priceCents, item.currency)}
              </p>
            </div>
            <div className="flex items-center rounded-lg border border-input">
              <Button
                type="button"
                variant="ghost"
                size="icon"
                onClick={() =>
                  updateQuantity(item.productId, item.quantity - 1)
                }
              >
                <MinusIcon />
                <span className="sr-only">{dict.decreaseQuantity}</span>
              </Button>
              <span className="w-8 text-center text-sm font-medium">
                {item.quantity}
              </span>
              <Button
                type="button"
                variant="ghost"
                size="icon"
                onClick={() =>
                  updateQuantity(item.productId, item.quantity + 1)
                }
              >
                <PlusIcon />
                <span className="sr-only">{dict.increaseQuantity}</span>
              </Button>
            </div>
            <p className="w-20 shrink-0 text-right font-medium">
              {formatPrice(item.priceCents * item.quantity, item.currency)}
            </p>
            <Button
              type="button"
              variant="ghost"
              size="icon"
              onClick={() => removeItem(item.productId)}
            >
              <Trash2Icon />
              <span className="sr-only">{dict.remove}</span>
            </Button>
          </li>
        ))}
      </ul>

      <div className="flex flex-col gap-6">
        <Card>
          <CardHeader>
            <CardTitle>{dict.redeemHeading}</CardTitle>
          </CardHeader>
          <CardContent className="flex gap-2">
            <Input placeholder={dict.redeemPlaceholder} />
            <Button type="button" variant="outline">
              {dict.redeemApply}
            </Button>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="flex flex-col gap-3">
            <div className="flex justify-between text-sm">
              <span className="text-muted-foreground">{dict.subtotal}</span>
              <span>{formatPrice(subtotalCents, currency)}</span>
            </div>
            <Separator />
            <div className="flex justify-between font-medium">
              <span>{dict.total}</span>
              <span>{formatPrice(subtotalCents, currency)}</span>
            </div>
          </CardContent>
          <CardFooter>
            <Button
              nativeButton={false}
              className="w-full"
              size="lg"
              render={<Link href={localizedHref(lang, "/checkout")} />}
            >
              {dict.checkout}
            </Button>
          </CardFooter>
        </Card>
      </div>
    </div>
  );
}
