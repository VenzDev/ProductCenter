import type { Metadata } from "next";
import Image from "next/image";
import Link from "next/link";
import { notFound } from "next/navigation";
import { ChevronRightIcon } from "lucide-react";
import DOMPurify from "isomorphic-dompurify";

import { getBlogPost } from "@/api/blog";
import { getDictionary } from "@/app/[lang]/dictionaries";
import { localizedHref } from "@/i18n/config";
import { formatDate } from "@/lib/format";
import { excerptHtml } from "@/lib/html";

export async function generateMetadata({
  params,
}: {
  params: Promise<{ slug: string }>;
}): Promise<Metadata> {
  const { slug } = await params;
  const post = await getBlogPost(slug);

  if (!post) return {};

  return {
    title: post.title,
    description: excerptHtml(post.content),
    openGraph: post.preview_image
      ? { images: [post.preview_image.webp_url] }
      : undefined,
  };
}

export default async function BlogPostPage({
  params,
}: {
  params: Promise<{ lang: string; slug: string }>;
}) {
  const { lang, slug } = await params;
  const post = await getBlogPost(slug);

  if (!post) {
    notFound();
  }

  const dict = await getDictionary();

  return (
    <div className="flex-1">
      <div className="mx-auto max-w-3xl px-4 py-6">
        <nav className="flex items-center gap-1 text-sm text-muted-foreground">
          <Link
            href={localizedHref(lang, "/")}
            className="hover:text-foreground"
          >
            {dict.blog.breadcrumbHome}
          </Link>
          <ChevronRightIcon className="size-3.5" />
          <Link
            href={localizedHref(lang, "/blog")}
            className="hover:text-foreground"
          >
            {dict.blog.heading}
          </Link>
          <ChevronRightIcon className="size-3.5" />
          <span className="text-foreground">{post.title}</span>
        </nav>

        <h1 className="mt-4 text-2xl font-semibold sm:text-3xl">
          {post.title}
        </h1>
        {post.published_at && (
          <p className="mt-1 text-sm text-muted-foreground">
            {formatDate(post.published_at)}
          </p>
        )}

        {post.preview_image && (
          <div className="relative mt-6 aspect-video overflow-hidden rounded-xl bg-muted">
            <Image
              src={post.preview_image.webp_url}
              alt={post.title}
              fill
              className="object-cover"
              sizes="(min-width: 768px) 768px, 100vw"
              priority
              unoptimized
            />
          </div>
        )}

        <div
          className="prose prose-neutral dark:prose-invert mt-6 max-w-none"
          dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(post.content) }}
        />
      </div>
    </div>
  );
}
