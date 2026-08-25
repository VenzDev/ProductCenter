import type { MetadataRoute } from "next";
import { locales } from "@/i18n/config";
import { siteUrl } from "@/lib/site";

// Only the home page for now — the other routes (login, product detail) aren't
// meaningful/stable enough to index yet.
export default function sitemap(): MetadataRoute.Sitemap {
  const languages = Object.fromEntries(
    locales.map((locale) => [locale, `${siteUrl}/${locale}`])
  );

  return locales.map((locale) => ({
    url: `${siteUrl}/${locale}`,
    alternates: { languages },
  }));
}
