import Link from "next/link";
import { MenuIcon } from "lucide-react";
import { lang } from "next/root-params";

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
import { getDictionary } from "@/app/[lang]/dictionaries";
import { localizedHref } from "@/i18n/config";

function MobileCategoryItem({
  category,
  locale,
}: {
  category: Category;
  locale: string;
}) {
  if (category.children.length === 0) {
    return (
      <li>
        <SheetClose
          nativeButton={false}
          render={
            <Link
              href={localizedHref(locale, `/categories/${category.slug}`)}
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
                        href={localizedHref(locale, `/categories/${child.slug}`)}
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

export async function MobileNav({ categories }: { categories: Category[] }) {
  const [dict, locale] = await Promise.all([getDictionary(), lang()]);

  return (
    <Sheet>
      <SheetTrigger
        render={<Button variant="ghost" size="icon" className="md:hidden" />}
      >
        <MenuIcon />
        <span className="sr-only">{dict.nav.openMenu}</span>
      </SheetTrigger>
      <SheetContent side="left">
        <SheetHeader>
          <SheetTitle>{dict.common.siteName}</SheetTitle>
        </SheetHeader>
        <Accordion className="px-4">
          <AccordionItem value="products">
            <AccordionTrigger>{dict.nav.products}</AccordionTrigger>
            <AccordionContent className="[&_a]:no-underline">
              <ul className="flex flex-col gap-1">
                {PRODUCT_LINKS.map((item) => (
                  <li key={item.href}>
                    <SheetClose
                      nativeButton={false}
                      render={
                        <Link
                          href={localizedHref(locale, item.href)}
                          className="block rounded-lg px-3 py-2 text-sm hover:bg-muted"
                        />
                      }
                    >
                      {dict.nav.productLinks[item.key].title}
                    </SheetClose>
                  </li>
                ))}
              </ul>
            </AccordionContent>
          </AccordionItem>
          <AccordionItem value="categories">
            <AccordionTrigger>{dict.nav.categories}</AccordionTrigger>
            <AccordionContent className="[&_a]:no-underline">
              <ul className="flex flex-col gap-1">
                {categories.map((category) => (
                  <MobileCategoryItem key={category.id} category={category} locale={locale} />
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
                href={localizedHref(locale, "/blog")}
                className="rounded-lg px-3 py-2 text-sm font-medium hover:bg-muted"
              />
            }
          >
            {dict.nav.blog}
          </SheetClose>
        </nav>
      </SheetContent>
    </Sheet>
  );
}
