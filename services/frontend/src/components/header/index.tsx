import Link from "next/link";
import { ShoppingCartIcon } from "lucide-react";

import { AuthStatus } from "@/components/auth/auth-status";
import { Button } from "@/components/ui/button";
import { SearchDialog } from "@/components/search-dialog";
import { getCategories } from "@/api/categories";
import { DesktopNav } from "@/components/header/desktop-nav";
import { MobileNav } from "@/components/header/mobile-nav";

export async function Header() {
  const categories = await getCategories();

  return (
    <header className="sticky top-0 z-40 border-b bg-background">
      <div className="mx-auto flex h-16 max-w-6xl items-center gap-8 px-4">
        <Link href="/" className="text-lg font-semibold">
          Product Center
        </Link>
        <DesktopNav categories={categories} />
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
          <AuthStatus />
        </div>
        <MobileNav categories={categories} />
      </div>
    </header>
  );
}
