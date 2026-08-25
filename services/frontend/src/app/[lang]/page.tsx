import type { Metadata } from "next";
import { HeroCarousel } from "@/components/hero-carousel";
import { NewsSection } from "@/components/news-section";
import { ProductsSection } from "@/components/products-section";
import { getDictionary, getLocale } from "@/app/[lang]/dictionaries";
import { locales, localizedHref } from "@/i18n/config";

export async function generateMetadata(): Promise<Metadata> {
  const [dict, locale] = await Promise.all([getDictionary(), getLocale()]);

  return {
    title: dict.home.meta.title,
    description: dict.home.meta.description,
    alternates: {
      canonical: localizedHref(locale, "/"),
      languages: Object.fromEntries(
        locales.map((l) => [l, localizedHref(l, "/")])
      ),
    },
  };
}

export default async function Home() {
  const dict = await getDictionary();

  return (
    <div className="flex-1">
      <HeroCarousel dict={dict.home.hero} />
      <ProductsSection />
      <NewsSection />
    </div>
  );
}
