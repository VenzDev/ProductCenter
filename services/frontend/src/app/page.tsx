import { HeroCarousel } from "@/components/hero-carousel";
import { NewsSection } from "@/components/news-section";
import { ProductsSection } from "@/components/products-section";

export default function Home() {
  return (
    <div className="flex-1">
      <HeroCarousel />
      <ProductsSection />
      <NewsSection />
    </div>
  );
}
