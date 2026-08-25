import Image from "next/image";

import { Card, CardContent } from "@/components/ui/card";
import { getDictionary } from "@/app/[lang]/dictionaries";

export async function NewsSection() {
  const dict = await getDictionary();

  return (
    <section className="mx-auto max-w-6xl px-4 py-6">
      <h2 className="text-2xl font-semibold">{dict.home.news.heading}</h2>
      <p className="text-muted-foreground">{dict.home.news.subtitle}</p>
      <div className="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-3">
        {dict.home.news.posts.map((title) => (
          <Card key={title} className="gap-0 py-0">
            <div className="relative aspect-video overflow-hidden rounded-t-xl bg-muted">
              <Image
                src="/blog/blog.webp"
                alt={title}
                fill
                className="object-cover"
                sizes="(min-width: 640px) 33vw, 100vw"
              />
            </div>
            <CardContent className="flex flex-col gap-1 p-4">
              <h3 className="line-clamp-2 font-semibold">{title}</h3>
              <p className="text-sm text-muted-foreground">{dict.home.news.author}</p>
            </CardContent>
          </Card>
        ))}
      </div>
    </section>
  );
}
