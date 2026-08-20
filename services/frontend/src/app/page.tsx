import { HeroCarousel } from "@/components/hero-carousel";
import { ProductsSection } from "@/components/products-section";

export default function Home() {
  return (
    <div className="flex flex-1 flex-col">
      <HeroCarousel />
      <ProductsSection />
    </div>
  );
}
