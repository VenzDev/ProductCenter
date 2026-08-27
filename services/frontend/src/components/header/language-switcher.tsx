"use client";

import Link from "next/link";
import { usePathname, useParams } from "next/navigation";
import { GlobeIcon } from "lucide-react";

import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { locales, localizedHref, type Locale } from "@/i18n/config";

// Language names are shown as autonyms (each in its own language), not translated,
// so a user can recognize their language regardless of the site's current locale.
const LOCALE_LABELS: Record<Locale, string> = {
  en: "English",
  pl: "Polski",
  de: "Deutsch",
  fr: "Français",
  it: "Italiano",
};

export function LanguageSwitcher() {
  const { lang } = useParams<{ lang: Locale }>();
  const pathname = usePathname();
  const rest = pathname.startsWith(`/${lang}`) ? pathname.slice(lang.length + 1) : pathname;

  return (
    <DropdownMenu>
      <DropdownMenuTrigger
        render={<Button variant="ghost" size="icon" className="cursor-pointer" />}
      >
        <GlobeIcon />
        <span className="sr-only">{LOCALE_LABELS[lang]}</span>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        {locales.map((locale) => (
          <DropdownMenuItem
            key={locale}
            className="cursor-pointer text-sm"
            disabled={locale === lang}
            render={<Link href={localizedHref(locale, rest || "/")} />}
          >
            {LOCALE_LABELS[locale]}
          </DropdownMenuItem>
        ))}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
