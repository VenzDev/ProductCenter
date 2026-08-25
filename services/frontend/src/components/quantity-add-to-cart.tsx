"use client";

import { useState } from "react";
import { MinusIcon, PlusIcon } from "lucide-react";

import { Button } from "@/components/ui/button";

type QuantityAddToCartDict = {
  decreaseQuantity: string;
  increaseQuantity: string;
  addToCart: string;
};

export function QuantityAddToCart({ dict }: { dict: QuantityAddToCartDict }) {
  const [quantity, setQuantity] = useState(1);

  return (
    <div className="flex items-center gap-3">
      <div className="flex items-center rounded-lg border border-input">
        <Button
          type="button"
          variant="ghost"
          size="icon"
          onClick={() => setQuantity((current) => Math.max(1, current - 1))}
        >
          <MinusIcon />
          <span className="sr-only">{dict.decreaseQuantity}</span>
        </Button>
        <span className="w-8 text-center text-sm font-medium">
          {quantity}
        </span>
        <Button
          type="button"
          variant="ghost"
          size="icon"
          onClick={() => setQuantity((current) => current + 1)}
        >
          <PlusIcon />
          <span className="sr-only">{dict.increaseQuantity}</span>
        </Button>
      </div>
      <Button className="flex-1" size="lg">
        {dict.addToCart}
      </Button>
    </div>
  );
}
