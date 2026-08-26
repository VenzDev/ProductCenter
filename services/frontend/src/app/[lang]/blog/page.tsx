import type { Metadata } from "next";
import Image from "next/image";
import Link from "next/link";

import { Card, CardContent } from "@/components/ui/card";
import { getBlogPosts } from "@/api/blog";
import { getDictionary, getLocale } from "@/app/[lang]/dictionaries";
import { locales, localizedHref } from "@/i18n/config";
import { formatDate } from "@/lib/format";
import { excerptHtml } from "@/lib/html";

export async function generateMetadata(): Promise<Metadata> {
  const [dict, locale] = await Promise.all([getDictionary(), getLocale()]);

  return {
    title: dict.blog.meta.title,
    description: dict.blog.meta.description,
    alternates: {
      canonical: localizedHref(locale, "/blog"),
      languages: Object.fromEntries(
        locales.map((l) => [l, localizedHref(l, "/blog")])
      ),
    },
  };
}

export default async function BlogPage({
  params,
}: {
  params: Promise<{ lang: string }>;
}) {
  const { lang } = await params;
  const [posts, dict] = await Promise.all([getBlogPosts(), getDictionary()]);

  return (
    <div className="flex-1">
      <div className="mx-auto max-w-6xl px-4 py-6">
        <h1 className="text-2xl font-semibold">{dict.blog.heading}</h1>
        <p className="text-muted-foreground">{dict.blog.subtitle}</p>

        {posts.length === 0 ? (
          <p className="mt-6 text-muted-foreground">{dict.blog.empty}</p>
        ) : (
          <div className="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {posts.map((post) => (
              <Link
                key={post.id}
                href={localizedHref(lang, `/blog/${post.slug}`)}
              >
                <Card className="h-full gap-0 py-0">
                  <div className="relative aspect-video overflow-hidden rounded-t-xl bg-muted">
                    {post.preview_image && (
                      <Image
                        src={post.preview_image.thumbnail_webp_url}
                        alt={post.title}
                        fill
                        className="object-cover"
                        sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw"
                        unoptimized
                      />
                    )}
                  </div>
                  <CardContent className="flex flex-col gap-2 p-4">
                    {post.published_at && (
                      <span className="text-sm text-muted-foreground">
                        {formatDate(post.published_at)}
                      </span>
                    )}
                    <h2 className="line-clamp-2 font-semibold">
                      {post.title}
                    </h2>
                    <p className="line-clamp-3 text-sm text-muted-foreground">
                      {excerptHtml(post.content)}
                    </p>
                  </CardContent>
                </Card>
              </Link>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
