"use client";

import { CreditCardIcon, TruckIcon } from "lucide-react";

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
  paymentPlaceholder: string;
  paymentNotice: string;
  placeOrder: string;
};

export function CheckoutForm({ dict }: { dict: CheckoutFormDict }) {
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
            <div className="flex items-center justify-center gap-2 rounded-lg border border-dashed border-input py-8 text-muted-foreground">
              <CreditCardIcon className="size-5" />
              <span className="text-sm">{dict.paymentPlaceholder}</span>
            </div>
            <Button type="button" size="lg" disabled>
              {dict.placeOrder}
            </Button>
            <p className="text-center text-xs text-muted-foreground">
              {dict.paymentNotice}
            </p>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
