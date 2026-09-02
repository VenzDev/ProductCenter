"use client";

import { useState } from "react";
import Image from "next/image";
import Link from "next/link";
import { useParams } from "next/navigation";
import { ShoppingCartIcon } from "lucide-react";

import { Button } from "@/components/ui/button";
import {
  Sheet,
  SheetContent,
  SheetFooter,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from "@/components/ui/sheet";
import { useCart } from "@/hooks/use-cart";
import { formatPrice } from "@/lib/format";
import { localizedHref } from "@/i18n/config";

export type CartSheetDict = {
  title: string;
  openCart: string;
  empty: string;
  goToCart: string;
};

export function CartSheet({ dict }: { dict: CartSheetDict }) {
  const { items, count } = useCart();
  const { lang } = useParams<{ lang: string }>();
  const [open, setOpen] = useState(false);

  return (
    <Sheet open={open} onOpenChange={setOpen}>
      <SheetTrigger
        render={<Button variant="ghost" size="icon" className="relative" />}
      >
        <ShoppingCartIcon />
        {count > 0 && (
          <span className="absolute -top-1 -right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] leading-none font-medium text-primary-foreground">
            {count}
          </span>
        )}
        <span className="sr-only">{dict.openCart}</span>
      </SheetTrigger>

      <SheetContent side="right" className="w-full sm:max-w-sm">
        <SheetHeader>
          <SheetTitle>{dict.title}</SheetTitle>
        </SheetHeader>

        {items.length === 0 ? (
          <p className="px-4 text-sm text-muted-foreground">{dict.empty}</p>
        ) : (
          <ul className="flex-1 divide-y overflow-y-auto px-4">
            {items.map((item) => (
              <li key={item.productId} className="flex items-center gap-3 py-3">
                <div className="relative size-14 shrink-0 overflow-hidden rounded-md bg-muted">
                  {item.image && (
                    <Image
                      src={item.image}
                      alt={item.name}
                      fill
                      className="object-cover"
                      sizes="56px"
                      unoptimized
                    />
                  )}
                </div>
                <div className="min-w-0 flex-1">
                  <p className="truncate text-sm font-medium">{item.name}</p>
                  <p className="text-xs text-muted-foreground">
                    {item.quantity} &times;{" "}
                    {formatPrice(item.priceCents, item.currency)}
                  </p>
                </div>
              </li>
            ))}
          </ul>
        )}

        <SheetFooter>
          <Button
            nativeButton={false}
            className="w-full"
            render={
              <Link
                href={localizedHref(lang, "/cart")}
                onClick={() => setOpen(false)}
              />
            }
          >
            {dict.goToCart}
          </Button>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  );
}
