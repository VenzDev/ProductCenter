import { HeroCarousel } from "@/components/hero-carousel";

export default function Home() {
  return (
    <div className="flex flex-1 flex-col">
      <HeroCarousel />
      <div className="flex flex-1 flex-col items-center justify-center gap-4">
        <h1 className="text-2xl font-semibold">Product Center</h1>
      </div>
    </div>
  );
}
