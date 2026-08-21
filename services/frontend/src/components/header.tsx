import Link from "next/link";
import { LogInIcon, MenuIcon, ShoppingCartIcon } from "lucide-react";

import { Button } from "@/components/ui/button";
import {
  NavigationMenu,
  NavigationMenuContent,
  NavigationMenuItem,
  NavigationMenuLink,
  NavigationMenuList,
  NavigationMenuTrigger,
  navigationMenuTriggerStyle,
} from "@/components/ui/navigation-menu";
import { SearchDialog } from "@/components/search-dialog";
import {
  Sheet,
  SheetClose,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from "@/components/ui/sheet";
import { getCategories, type Category } from "@/lib/categories";

const MOBILE_LINKS = [
  { label: "Products", href: "/products" },
  { label: "Categories", href: "/categories" },
  { label: "About", href: "/about" },
];

const PRODUCT_LINKS = [
  {
    title: "New Arrivals",
    href: "/products?filter=new",
    description: "Freshly added items, updated every week.",
  },
  {
    title: "Best Sellers",
    href: "/products?filter=best-sellers",
    description: "The products our customers buy the most.",
  },
  {
    title: "On Sale",
    href: "/products?filter=sale",
    description: "Discounted items across the whole store.",
  },
  {
    title: "All Products",
    href: "/products",
    description: "Browse the entire catalog.",
  },
];

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

export async function Header() {
  const categories = await getCategories();

  return (
    <header className="sticky top-0 z-40 border-b bg-background">
      <div className="mx-auto flex h-16 max-w-6xl items-center gap-8 px-4">
        <Link href="/" className="text-lg font-semibold">
          Product Center
        </Link>
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
        <div className="ml-auto flex items-center gap-2">
          <SearchDialog />
          <Button
            variant="ghost"
            size="icon"
            nativeButton={false}
            render={<Link href="/cart" />}
          >
            <ShoppingCartIcon />
            <span className="sr-only">Cart</span>
          </Button>
          <Button
            variant="outline"
            nativeButton={false}
            render={<Link href="/login" />}
          >
            <LogInIcon data-icon="inline-start" />
            <span className="hidden sm:inline">Login</span>
          </Button>
        </div>
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
            <nav className="flex flex-col gap-1 px-4">
              {MOBILE_LINKS.map((item) => (
                <SheetClose
                  key={item.href}
                  nativeButton={false}
                  render={
                    <Link
                      href={item.href}
                      className="rounded-lg px-3 py-2 text-sm font-medium hover:bg-muted"
                    />
                  }
                >
                  {item.label}
                </SheetClose>
              ))}
            </nav>
          </SheetContent>
        </Sheet>
      </div>
    </header>
  );
}
