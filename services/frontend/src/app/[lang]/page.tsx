import { HeroCarousel } from "@/components/hero-carousel";
import { NewsSection } from "@/components/news-section";
import { ProductsSection } from "@/components/products-section";
import { getDictionary } from "@/app/[lang]/dictionaries";

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
