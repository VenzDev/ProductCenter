import { fetchApi, fetchApiItem } from "@/api/api";

export type BlogPost = {
  id: number;
  title: string;
  slug: string;
  content: string;
  published_at: string | null;
  preview_image: {
    webp_url: string;
    thumbnail_webp_url: string;
  } | null;
};

export function getBlogPosts(): Promise<BlogPost[]> {
  return fetchApi<BlogPost>("/api/v1/blog-posts");
}

export function getBlogPost(slug: string): Promise<BlogPost | null> {
  return fetchApiItem<BlogPost>(`/api/v1/blog-posts/${slug}`);
}
