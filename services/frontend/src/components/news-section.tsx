import Image from "next/image";

import { Card, CardContent } from "@/components/ui/card";

const POSTS = [
  {
    title: "How We Pick Products for the Store",
    author: "Product Center Team",
  },
  {
    title: "A Look Behind Our Fulfillment Process",
    author: "Product Center Team",
  },
  {
    title: "Tips for Getting the Most from Sale Season",
    author: "Product Center Team",
  },
];

export function NewsSection() {
  return (
    <section className="mx-auto max-w-6xl px-4 py-12">
      <h2 className="text-2xl font-semibold">News</h2>
      <p className="text-muted-foreground">
        Updates and stories from the team.
      </p>
      <div className="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-3">
        {POSTS.map((post) => (
          <Card key={post.title} className="gap-0 py-0">
            <div className="relative aspect-video overflow-hidden rounded-t-xl bg-muted">
              <Image
                src="/blog/blog.webp"
                alt={post.title}
                fill
                className="object-cover"
                sizes="(min-width: 640px) 33vw, 100vw"
              />
            </div>
            <CardContent className="flex flex-col gap-1 p-4">
              <h3 className="line-clamp-2 font-semibold">{post.title}</h3>
              <p className="text-sm text-muted-foreground">{post.author}</p>
            </CardContent>
          </Card>
        ))}
      </div>
    </section>
  );
}
