import type { Metadata } from "next";
import { SearchIcon } from "lucide-react";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { ProductFilters } from "@/components/product-filters";
import { ProductGrid } from "@/components/product-grid";
import { ProductPagination } from "@/components/product-pagination";
import { ProductSortSelect } from "@/components/product-sort-select";
import { searchProducts } from "@/api/products";
import { getDictionary } from "@/app/[lang]/dictionaries";
import { localizedHref } from "@/i18n/config";
import { buildBackendQuery, type SearchParamsInput } from "@/lib/product-query";

function queryParam(searchParams: SearchParamsInput): string {
  const value = searchParams.q;
  return (typeof value === "string" ? value : "").trim();
}

export async function generateMetadata({
  searchParams,
}: {
  searchParams: Promise<SearchParamsInput>;
}): Promise<Metadata> {
  const query = queryParam(await searchParams);
  const dict = await getDictionary();

  return {
    title: query ? dict.search.heading.replace("{query}", query) : dict.search.promptHeading,
  };
}

export default async function SearchPage({
  params,
  searchParams,
}: {
  params: Promise<{ lang: string }>;
  searchParams: Promise<SearchParamsInput>;
}) {
  const { lang } = await params;
  const resolvedSearchParams = await searchParams;
  const dict = await getDictionary();

  const query = queryParam(resolvedSearchParams);
  const basePath = localizedHref(lang, "/search");

  const response = query ? await searchProducts(buildBackendQuery(resolvedSearchParams)) : null;

  return (
    <div className="flex-1">
      <div className="mx-auto max-w-6xl px-4 py-6">
        <form action={basePath} className="flex gap-2">
          <Input
            type="search"
            name="q"
            placeholder={dict.search.placeholder}
            defaultValue={query}
            className="max-w-md"
          />
          <Button type="submit" size="icon" variant="outline">
            <SearchIcon />
          </Button>
        </form>

        {!query ? (
          <div className="mt-8">
            <h1 className="text-xl font-semibold">{dict.search.promptHeading}</h1>
            <p className="text-muted-foreground">{dict.search.promptSubtitle}</p>
          </div>
        ) : (
          <>
            <h1 className="mt-6 text-xl font-semibold">
              {dict.search.heading.replace("{query}", query)}
            </h1>

            {!response ? (
              <p className="mt-6 text-muted-foreground">{dict.catalog.empty}</p>
            ) : (
              <div className="mt-6 flex flex-col gap-8 md:flex-row">
                <ProductFilters
                  basePath={basePath}
                  searchParams={resolvedSearchParams}
                  price={response.filters.price}
                  attributes={response.filters.attributes}
                  categories={response.filters.categories}
                  dict={dict.catalog}
                />

                <div className="flex-1">
                  <div className="flex flex-wrap items-center justify-between gap-4">
                    <ProductSortSelect
                      basePath={basePath}
                      searchParams={resolvedSearchParams}
                      showRelevanceSort
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
            )}
          </>
        )}
      </div>
    </div>
  );
}
