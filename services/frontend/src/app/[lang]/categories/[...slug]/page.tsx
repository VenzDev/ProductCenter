import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { ChevronRightIcon } from "lucide-react";

import { ProductFilters } from "@/components/product-filters";
import { ProductGrid } from "@/components/product-grid";
import { ProductPagination } from "@/components/product-pagination";
import { ProductSortSelect } from "@/components/product-sort-select";
import { findCategoryPath, getCategories, getCategoryProducts } from "@/api/categories";
import { getDictionary } from "@/app/[lang]/dictionaries";
import { localizedHref } from "@/i18n/config";
import { buildBackendQuery, type SearchParamsInput } from "@/lib/product-query";

// A child category's slug is "parent-slug/child-slug" (see backend CategorySlugger), so
// the route segment is a catch-all: a plain [slug] only ever matches one path segment
// and 404s on anything under a subcategory.
export async function generateMetadata({
  params,
}: {
  params: Promise<{ slug: string[] }>;
}): Promise<Metadata> {
  const { slug } = await params;
  const path = findCategoryPath(await getCategories(), slug.join("/"));

  return path ? { title: path.category.name } : {};
}

export default async function CategoryProductsPage({
  params,
  searchParams,
}: {
  params: Promise<{ lang: string; slug: string[] }>;
  searchParams: Promise<SearchParamsInput>;
}) {
  const { lang, slug: slugParts } = await params;
  const slug = slugParts.join("/");
  const resolvedSearchParams = await searchParams;

  const [categories, response, dict] = await Promise.all([
    getCategories(),
    getCategoryProducts(slug, buildBackendQuery(resolvedSearchParams)),
    getDictionary(),
  ]);

  const path = findCategoryPath(categories, slug);

  if (!path || !response) {
    notFound();
  }

  const { category, parent } = path;
  const basePath = localizedHref(lang, `/categories/${slug}`);

  return (
    <div className="flex-1">
      <div className="mx-auto max-w-6xl px-4 py-6">
        <nav className="flex items-center gap-1 text-sm text-muted-foreground">
          <Link href={localizedHref(lang, "/")} className="hover:text-foreground">
            {dict.catalog.breadcrumbHome}
          </Link>
          {parent && (
            <>
              <ChevronRightIcon className="size-3.5" />
              <Link
                href={localizedHref(lang, `/categories/${parent.slug}`)}
                className="hover:text-foreground"
              >
                {parent.name}
              </Link>
            </>
          )}
          <ChevronRightIcon className="size-3.5" />
          <span className="text-foreground">{category.name}</span>
        </nav>

        <div className="mt-6 flex flex-col gap-8 md:flex-row">
          <ProductFilters
            basePath={basePath}
            searchParams={resolvedSearchParams}
            price={response.filters.price}
            attributes={response.filters.attributes}
            heading={category.name}
            subcategories={response.filters.subcategories.map((subcategory) => ({
              id: subcategory.id,
              name: subcategory.name,
              count: subcategory.count,
              href: localizedHref(lang, `/categories/${subcategory.slug}`),
            }))}
            dict={dict.catalog}
          />

          <div className="flex-1">
            <div className="flex flex-wrap items-center justify-between gap-4">
              <ProductSortSelect
                basePath={basePath}
                searchParams={resolvedSearchParams}
                dict={dict.catalog}
              />
              <ProductPagination
                basePath={basePath}
                searchParams={resolvedSearchParams}
                currentPage={response.meta.current_page}
                lastPage={response.meta.last_page}
                dict={dict.catalog}
              />
            </div>

            <div className="mt-6">
              <ProductGrid products={response.data} emptyMessage={dict.catalog.empty} />
            </div>

            <div className="mt-8">
              <ProductPagination
                basePath={basePath}
                searchParams={resolvedSearchParams}
                currentPage={response.meta.current_page}
                lastPage={response.meta.last_page}
                dict={dict.catalog}
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
