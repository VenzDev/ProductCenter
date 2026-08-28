import Link from "next/link";

import { Button } from "@/components/ui/button";
import { buildLinkQuery, type SearchParamsInput } from "@/lib/product-query";

/**
 * Classic numbered pagination range: every page when there are few, otherwise the first,
 * the last, the current page's immediate neighbors, and an ellipsis over any gap.
 */
function paginationRange(current: number, total: number): (number | "ellipsis")[] {
  if (total <= 7) {
    return Array.from({ length: total }, (_, index) => index + 1);
  }

  const pages = new Set<number>([1, total, current]);
  if (current > 1) pages.add(current - 1);
  if (current < total) pages.add(current + 1);

  const sorted = Array.from(pages).sort((a, b) => a - b);

  const range: (number | "ellipsis")[] = [];
  sorted.forEach((page, index) => {
    if (index > 0 && page - sorted[index - 1] > 1) {
      range.push("ellipsis");
    }
    range.push(page);
  });

  return range;
}

export function ProductPagination({
  basePath,
  searchParams,
  currentPage,
  lastPage,
  dict,
}: {
  basePath: string;
  searchParams: SearchParamsInput;
  currentPage: number;
  lastPage: number;
  dict: { previous: string; next: string };
}) {
  if (lastPage <= 1) return null;

  const pageHref = (page: number) =>
    `${basePath}${buildLinkQuery(searchParams, { page: String(page) }, { keepPage: true })}`;

  return (
    <nav className="flex flex-wrap items-center justify-center gap-1.5">
      {currentPage > 1 ? (
        <Button
          variant="outline"
          size="sm"
          nativeButton={false}
          render={<Link href={pageHref(currentPage - 1)} />}
        >
          {dict.previous}
        </Button>
      ) : (
        <Button variant="outline" size="sm" disabled>
          {dict.previous}
        </Button>
      )}

      {paginationRange(currentPage, lastPage).map((page, index) =>
        page === "ellipsis" ? (
          <span key={`ellipsis-${index}`} className="px-1 text-sm text-muted-foreground">
            …
          </span>
        ) : (
          <Button
            key={page}
            variant={page === currentPage ? "default" : "outline"}
            size="sm"
            nativeButton={false}
            render={<Link href={pageHref(page)} />}
          >
            {page}
          </Button>
        ),
      )}

      {currentPage < lastPage ? (
        <Button
          variant="outline"
          size="sm"
          nativeButton={false}
          render={<Link href={pageHref(currentPage + 1)} />}
        >
          {dict.next}
        </Button>
      ) : (
        <Button variant="outline" size="sm" disabled>
          {dict.next}
        </Button>
      )}
    </nav>
  );
}
