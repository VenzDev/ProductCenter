import Link from "next/link";
import { MenuIcon } from "lucide-react";

import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion";
import { Button } from "@/components/ui/button";
import {
  Sheet,
  SheetClose,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from "@/components/ui/sheet";
import { type Category } from "@/api/categories";
import { PRODUCT_LINKS } from "@/components/header/product-links";

function MobileCategoryItem({ category }: { category: Category }) {
  if (category.children.length === 0) {
    return (
      <li>
        <SheetClose
          nativeButton={false}
          render={
            <Link
              href={`/categories/${category.slug}`}
              className="block rounded-lg px-3 py-2 text-sm hover:bg-muted"
            />
          }
        >
          {category.name}
        </SheetClose>
      </li>
    );
  }

  return (
    <li>
      <Accordion>
        <AccordionItem value={category.slug} className="border-none">
          <AccordionTrigger className="px-3 py-2">
            {category.name}
          </AccordionTrigger>
          <AccordionContent className="pl-3 [&_a]:no-underline">
            <ul className="flex flex-col gap-1">
              {category.children.map((child) => (
                <li key={child.id}>
                  <SheetClose
                    nativeButton={false}
                    render={
                      <Link
                        href={`/categories/${child.slug}`}
                        className="block rounded-lg px-3 py-2 text-sm text-muted-foreground hover:bg-muted"
                      />
                    }
                  >
                    {child.name}
                  </SheetClose>
                </li>
              ))}
            </ul>
          </AccordionContent>
        </AccordionItem>
      </Accordion>
    </li>
  );
}

export function MobileNav({ categories }: { categories: Category[] }) {
  return (
    <Sheet>
      <SheetTrigger
        render={<Button variant="ghost" size="icon" className="md:hidden" />}
      >
        <MenuIcon />
        <span className="sr-only">Open menu</span>
      </SheetTrigger>
      <SheetContent side="left">
        <SheetHeader>
          <SheetTitle>Product Center</SheetTitle>
        </SheetHeader>
        <Accordion className="px-4">
          <AccordionItem value="products">
            <AccordionTrigger>Products</AccordionTrigger>
            <AccordionContent className="[&_a]:no-underline">
              <ul className="flex flex-col gap-1">
                {PRODUCT_LINKS.map((item) => (
                  <li key={item.href}>
                    <SheetClose
                      nativeButton={false}
                      render={
                        <Link
                          href={item.href}
                          className="block rounded-lg px-3 py-2 text-sm hover:bg-muted"
                        />
                      }
                    >
                      {item.title}
                    </SheetClose>
                  </li>
                ))}
              </ul>
            </AccordionContent>
          </AccordionItem>
          <AccordionItem value="categories">
            <AccordionTrigger>Categories</AccordionTrigger>
            <AccordionContent className="[&_a]:no-underline">
              <ul className="flex flex-col gap-1">
                {categories.map((category) => (
                  <MobileCategoryItem key={category.id} category={category} />
                ))}
              </ul>
            </AccordionContent>
          </AccordionItem>
        </Accordion>
        <nav className="flex flex-col gap-1 px-4">
          <SheetClose
            nativeButton={false}
            render={
              <Link
                href="/about"
                className="rounded-lg px-3 py-2 text-sm font-medium hover:bg-muted"
              />
            }
          >
            About
          </SheetClose>
        </nav>
      </SheetContent>
    </Sheet>
  );
}
