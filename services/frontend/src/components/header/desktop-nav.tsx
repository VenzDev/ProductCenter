import Link from "next/link";

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

function CategoryMenuItem({ category }: { category: Category }) {
  return (
    <li>
      <NavigationMenuLink
        render={<Link href={`/categories/${category.slug}`} />}
        className="text-sm font-medium"
      >
        {category.name}
      </NavigationMenuLink>
      {category.children.length > 0 && (
        <ul className="mt-1 flex flex-col gap-1 pl-3">
          {category.children.map((child) => (
            <li key={child.id}>
              <NavigationMenuLink
                render={<Link href={`/categories/${child.slug}`} />}
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

export function DesktopNav({ categories }: { categories: Category[] }) {
  return (
    <NavigationMenu className="hidden md:flex">
      <NavigationMenuList>
        <NavigationMenuItem>
          <NavigationMenuTrigger>Products</NavigationMenuTrigger>
          <NavigationMenuContent>
            <ul className="grid w-[400px] gap-2 p-2 md:grid-cols-2">
              {PRODUCT_LINKS.map((item) => (
                <ListItem key={item.href} {...item} />
              ))}
            </ul>
          </NavigationMenuContent>
        </NavigationMenuItem>
        <NavigationMenuItem>
          <NavigationMenuTrigger>Categories</NavigationMenuTrigger>
          <NavigationMenuContent>
            <ul className="grid w-[400px] gap-2 p-2 md:grid-cols-2">
              {categories.map((category) => (
                <CategoryMenuItem key={category.id} category={category} />
              ))}
            </ul>
          </NavigationMenuContent>
        </NavigationMenuItem>
        <NavigationMenuItem>
          <NavigationMenuLink
            render={<Link href="/about" />}
            className={navigationMenuTriggerStyle()}
          >
            About
          </NavigationMenuLink>
        </NavigationMenuItem>
      </NavigationMenuList>
    </NavigationMenu>
  );
}
