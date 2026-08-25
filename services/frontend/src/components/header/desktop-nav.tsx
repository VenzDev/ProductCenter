import Link from "next/link";
import { lang } from "next/root-params";

import {
  NavigationMenu,
  NavigationMenuContent,
  NavigationMenuItem,
  NavigationMenuLink,
  NavigationMenuList,
  NavigationMenuTrigger,
  navigationMenuTriggerStyle,
} from "@/components/ui/navigation-menu";
import { type Category } from "@/api/categories";
import { PRODUCT_LINKS } from "@/components/header/product-links";
import { getDictionary } from "@/app/[lang]/dictionaries";
import { localizedHref } from "@/i18n/config";

function CategoryMenuItem({
  category,
  locale,
}: {
  category: Category;
  locale: string;
}) {
  return (
    <li>
      <NavigationMenuLink
        render={<Link href={localizedHref(locale, `/categories/${category.slug}`)} />}
        className="text-sm font-medium"
      >
        {category.name}
      </NavigationMenuLink>
      {category.children.length > 0 && (
        <ul className="mt-1 flex flex-col gap-1 pl-3">
          {category.children.map((child) => (
            <li key={child.id}>
              <NavigationMenuLink
                render={<Link href={localizedHref(locale, `/categories/${child.slug}`)} />}
                className="text-muted-foreground text-sm"
              >
                {child.name}
              </NavigationMenuLink>
            </li>
          ))}
        </ul>
      )}
    </li>
  );
}

function ListItem({
  title,
  href,
  description,
}: {
  title: string;
  href: string;
  description?: string;
}) {
  return (
    <li>
      <NavigationMenuLink render={<Link href={href} />}>
        <div className="flex flex-col gap-1">
          <div className="text-sm font-medium">{title}</div>
          {description && (
            <p className="text-muted-foreground line-clamp-2 text-sm">
              {description}
            </p>
          )}
        </div>
      </NavigationMenuLink>
    </li>
  );
}

export async function DesktopNav({ categories }: { categories: Category[] }) {
  const [dict, locale] = await Promise.all([getDictionary(), lang()]);

  return (
    <NavigationMenu className="hidden md:flex">
      <NavigationMenuList>
        <NavigationMenuItem>
          <NavigationMenuTrigger>{dict.nav.products}</NavigationMenuTrigger>
          <NavigationMenuContent>
            <ul className="grid w-[400px] gap-2 p-2 md:grid-cols-2">
              {PRODUCT_LINKS.map((item) => (
                <ListItem
                  key={item.href}
                  href={localizedHref(locale, item.href)}
                  {...dict.nav.productLinks[item.key]}
                />
              ))}
            </ul>
          </NavigationMenuContent>
        </NavigationMenuItem>
        <NavigationMenuItem>
          <NavigationMenuTrigger>{dict.nav.categories}</NavigationMenuTrigger>
          <NavigationMenuContent>
            <ul className="grid w-[400px] gap-2 p-2 md:grid-cols-2">
              {categories.map((category) => (
                <CategoryMenuItem key={category.id} category={category} locale={locale} />
              ))}
            </ul>
          </NavigationMenuContent>
        </NavigationMenuItem>
        <NavigationMenuItem>
          <NavigationMenuLink
            render={<Link href={localizedHref(locale, "/about")} />}
            className={navigationMenuTriggerStyle()}
          >
            {dict.nav.about}
          </NavigationMenuLink>
        </NavigationMenuItem>
      </NavigationMenuList>
    </NavigationMenu>
  );
}
