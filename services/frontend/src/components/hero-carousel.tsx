"use client";

import * as React from "react";
import Image from "next/image";
import Link from "next/link";
import { useParams } from "next/navigation";
import Autoplay from "embla-carousel-autoplay";

import {
  Carousel,
  CarouselContent,
  CarouselItem,
  type CarouselApi,
} from "@/components/ui/carousel";
import { cn } from "@/lib/utils";
import { localizedHref } from "@/i18n/config";

const SLIDE_IMAGES = [
  "/carousel/slide1.webp",
  "/carousel/slide2.webp",
  "/carousel/slide3.webp",
];

const SLIDE_HREF = "/products?filter=sale";

type HeroDict = {
  slides: { alt: string }[];
  goToSlide: string;
};

export function HeroCarousel({ dict }: { dict: HeroDict }) {
  const { lang } = useParams<{ lang: string }>();
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
          {SLIDE_IMAGES.map((image, index) => (
            <CarouselItem key={image}>
              <Link
                href={localizedHref(lang, SLIDE_HREF)}
                className="relative block aspect-2/1 overflow-hidden rounded-xl"
              >
                <Image
                  src={image}
                  alt={dict.slides[index].alt}
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
          {SLIDE_IMAGES.map((image, index) => (
            <button
              key={image}
              type="button"
              onClick={() => api?.scrollTo(index)}
              className={cn(
                "size-2 rounded-full bg-foreground/30 transition-colors",
                current === index && "bg-foreground"
              )}
            >
              <span className="sr-only">
                {dict.goToSlide.replace("{n}", String(index + 1))}
              </span>
            </button>
          ))}
        </div>
      </div>
    </Carousel>
  );
}
