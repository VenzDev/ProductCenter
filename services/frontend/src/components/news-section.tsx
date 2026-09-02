import Image from "next/image";
import Link from "next/link";
import { lang } from "next/root-params";

import { Card, CardContent } from "@/components/ui/card";
import { getBlogPosts } from "@/api/blog";
import { getDictionary } from "@/app/[lang]/dictionaries";
import { localizedHref } from "@/i18n/config";
import { formatDate } from "@/lib/format";

export async function NewsSection() {
  const [posts, dict, locale] = await Promise.all([
    getBlogPosts(),
    getDictionary(),
    lang(),
  ]);
  const latestPosts = posts.slice(0, 3);

  if (latestPosts.length === 0) {
    return null;
  }

  return (
    <section className="mx-auto max-w-6xl px-4 py-6">
      <h2 className="text-2xl font-semibold">{dict.home.news.heading}</h2>
      <p className="text-muted-foreground">{dict.home.news.subtitle}</p>
      <div className="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-3">
        {latestPosts.map((post) => (
          <Link
            key={post.id}
            href={localizedHref(locale, `/blog/${post.slug}`)}
          >
            <Card className="gap-0 py-0">
              <div className="relative aspect-video overflow-hidden rounded-t-xl bg-muted">
                {post.preview_image && (
                  <Image
                    src={post.preview_image.thumbnail_webp_url}
                    alt={post.title}
                    fill
                    className="object-cover"
                    sizes="(min-width: 640px) 33vw, 100vw"
                    unoptimized
                  />
                )}
              </div>
              <CardContent className="flex flex-col gap-1 p-4">
                <h3 className="line-clamp-2 font-semibold">{post.title}</h3>
                {post.published_at && (
                  <p className="text-sm text-muted-foreground">
                    {formatDate(post.published_at, locale)}
                  </p>
                )}
              </CardContent>
            </Card>
          </Link>
        ))}
      </div>
    </section>
  );
}
