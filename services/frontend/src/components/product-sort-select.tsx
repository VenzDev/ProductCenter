import { Button } from "@/components/ui/button";
import { preservedParamEntries, type SearchParamsInput } from "@/lib/product-query";
import type { CatalogDict } from "@/components/product-filters";

function stringParam(value: string | string[] | undefined): string | undefined {
  return typeof value === "string" ? value : undefined;
}

export function ProductSortSelect({
  basePath,
  searchParams,
  showRelevanceSort = false,
  dict,
}: {
  basePath: string;
  searchParams: SearchParamsInput;
  showRelevanceSort?: boolean;
  dict: CatalogDict;
}) {
  const currentSort = stringParam(searchParams.sort) ?? "relevance";

  return (
    <form action={basePath} className="flex items-center gap-2">
      <label htmlFor="sort" className="text-sm text-muted-foreground">
        {dict.sortHeading}
      </label>
      {preservedParamEntries(searchParams, ["sort"]).map(([key, value]) => (
        <input key={key} type="hidden" name={key} value={value} />
      ))}
      <select
        id="sort"
        name="sort"
        defaultValue={currentSort}
        className="h-8 min-w-0 rounded-lg border border-input bg-transparent px-2.5 text-sm outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 dark:bg-input/30"
      >
        {showRelevanceSort && <option value="relevance">{dict.sortRelevance}</option>}
        <option value="price_asc">{dict.sortPriceAsc}</option>
        <option value="price_desc">{dict.sortPriceDesc}</option>
      </select>
      <Button type="submit" size="sm" variant="outline">
        {dict.applyPrice}
      </Button>
    </form>
  );
}
