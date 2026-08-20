"use client";

import * as React from "react";
import Link from "next/link";
import Autoplay from "embla-carousel-autoplay";

import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  Carousel,
  CarouselContent,
  CarouselItem,
  type CarouselApi,
} from "@/components/ui/carousel";
import { cn } from "@/lib/utils";

const SLIDES = [
  {
    badge: "New Arrivals",
    title: "New Season, New Style",
    description: "Explore the latest arrivals, updated every week.",
    cta: "Shop New Arrivals",
    href: "/products?filter=new",
  },
  {
    badge: "Sale",
    title: "Up to 30% Off",
    description: "Selected items across the store, for a limited time.",
    cta: "Shop Sale",
    href: "/products?filter=sale",
  },
  {
    badge: "Shipping",
    title: "Free Shipping",
    description: "On all orders over $50, no code needed.",
    cta: "Browse Products",
    href: "/products",
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
      plugins={[Autoplay({ delay: 50000 })]}
      className="w-full min-w-0"
    >
      <div className="relative mx-auto max-w-6xl px-4 pt-8">
        <CarouselContent>
          {SLIDES.map((slide) => (
            <CarouselItem key={slide.title}>
              <div className="flex h-64 flex-col justify-center gap-3 rounded-xl bg-muted px-8 md:h-80">
                <Badge>{slide.badge}</Badge>
                <h2 className="text-2xl font-semibold md:text-4xl">
                  {slide.title}
                </h2>
                <p className="text-muted-foreground md:text-lg">
                  {slide.description}
                </p>
                <Button
                  nativeButton={false}
                  render={<Link href={slide.href} />}
                  className="w-fit"
                >
                  {slide.cta}
                </Button>
              </div>
            </CarouselItem>
          ))}
        </CarouselContent>
        <div className="absolute inset-x-0 bottom-4 flex justify-center gap-2">
          {SLIDES.map((slide, index) => (
            <button
              key={slide.title}
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
