import Link from "next/link";
import { ShoppingCartIcon } from "lucide-react";
import { lang } from "next/root-params";

import { AuthStatus } from "@/components/auth/auth-status";
import { Button } from "@/components/ui/button";
import { SearchDialog } from "@/components/search-dialog";
import { getCategories } from "@/api/categories";
import { DesktopNav } from "@/components/header/desktop-nav";
import { MobileNav } from "@/components/header/mobile-nav";
import { LanguageSwitcher } from "@/components/header/language-switcher";
import { getDictionary } from "@/app/[lang]/dictionaries";
import { localizedHref } from "@/i18n/config";

export async function Header() {
  const [categories, dict, locale] = await Promise.all([
    getCategories(),
    getDictionary(),
    lang(),
  ]);

  return (
    <header className="sticky top-0 z-40 border-b bg-background">
      <div className="mx-auto flex h-16 max-w-6xl items-center gap-8 px-4">
        <Link href={localizedHref(locale, "/")} className="text-lg font-semibold">
          {dict.common.siteName}
        </Link>
        <DesktopNav categories={categories} />
        <div className="ml-auto flex items-center gap-2">
          <SearchDialog dict={dict.search} locale={locale} />
          <Button
            variant="ghost"
            size="icon"
            nativeButton={false}
            render={<Link href={localizedHref(locale, "/cart")} />}
          >
            <ShoppingCartIcon />
            <span className="sr-only">{dict.common.cart}</span>
          </Button>
          <LanguageSwitcher />
          <AuthStatus dict={dict.auth} />
        </div>
        <MobileNav categories={categories} />
      </div>
    </header>
  );
}
