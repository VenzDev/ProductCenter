"use client";

import * as React from "react";
import Image from "next/image";
import Link from "next/link";
import Autoplay from "embla-carousel-autoplay";

import {
  Carousel,
  CarouselContent,
  CarouselItem,
  type CarouselApi,
} from "@/components/ui/carousel";
import { cn } from "@/lib/utils";

const SLIDES = [
  {
    image: "/carousel/slide1.webp",
    alt: "Kids Fashion — extra 50% off, shop now",
    href: "/products?filter=sale",
  },
  {
    image: "/carousel/slide2.webp",
    alt: "Sale on Sale — 25% off everything, shop now",
    href: "/products?filter=sale",
  },
  {
    image: "/carousel/slide3.webp",
    alt: "Mother's Day Sale — up to 30% off, shop now",
    href: "/products?filter=sale",
  },
];

export function HeroCarousel() {
  const [api, setApi] = React.useState<CarouselApi>();
  const [current, setCurrent] = React.useState(0);

  React.useEffect(() => {
    if (!api) return;
    // eslint-disable-next-line react-hooks/set-state-in-effect -- initial sync with embla's external state before subscribing
    setCurrent(api.selectedScrollSnap());
    api.on("select", () => setCurrent(api.selectedScrollSnap()));
  }, [api]);

  return (
    <Carousel
      setApi={setApi}
      opts={{ loop: true }}
      plugins={[Autoplay({ delay: 3000 })]}
      className="w-full min-w-0"
    >
      <div className="relative mx-auto max-w-6xl px-4 py-6">
        <CarouselContent>
          {SLIDES.map((slide, index) => (
            <CarouselItem key={slide.image}>
              <Link
                href={slide.href}
                className="relative block aspect-2/1 overflow-hidden rounded-xl"
              >
                <Image
                  src={slide.image}
                  alt={slide.alt}
                  fill
                  priority={index === 0}
                  className="object-cover"
                  sizes="(min-width: 1152px) 1120px, 100vw"
                />
              </Link>
            </CarouselItem>
          ))}
        </CarouselContent>
        <div className="absolute inset-x-0 bottom-12 flex justify-center gap-2">
          {SLIDES.map((slide, index) => (
            <button
              key={slide.image}
              type="button"
              onClick={() => api?.scrollTo(index)}
              className={cn(
                "size-2 rounded-full bg-foreground/30 transition-colors",
                current === index && "bg-foreground"
              )}
            >
              <span className="sr-only">Go to slide {index + 1}</span>
            </button>
          ))}
        </div>
      </div>
    </Carousel>
  );
}
