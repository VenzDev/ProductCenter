import Link from "next/link";
import { CheckIcon } from "lucide-react";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { formatPrice } from "@/lib/format";
import {
  buildLinkQuery,
  clearFiltersQuery,
  isAttrValueSelected,
  preservedParamEntries,
  toggleAttrValueQuery,
  type SearchParamsInput,
} from "@/lib/product-query";
import type { AttributeFacet, CategoryFacet, PriceFacet } from "@/api/products";

export type CatalogDict = {
  filtersHeading: string;
  priceHeading: string;
  priceMinPlaceholder: string;
  priceMaxPlaceholder: string;
  applyPrice: string;
  clearFilters: string;
  sortHeading: string;
  sortRelevance: string;
  sortPriceAsc: string;
  sortPriceDesc: string;
  categoriesHeading: string;
  subcategoriesHeading: string;
};

export type SubcategoryLink = { id: number; name: string; count: number; href: string };

function stringParam(value: string | string[] | undefined): string | undefined {
  return typeof value === "string" ? value : undefined;
}

export function ProductFilters({
  basePath,
  searchParams,
  price,
  attributes,
  categories,
  heading,
  subcategories,
  dict,
}: {
  basePath: string;
  searchParams: SearchParamsInput;
  price: PriceFacet;
  attributes: AttributeFacet[];
  categories?: CategoryFacet[];
  heading?: string;
  subcategories?: SubcategoryLink[];
  dict: CatalogDict;
}) {
  const currentCategoryId = stringParam(searchParams.category_id);

  const hasActiveFilters =
    Boolean(searchParams.price_min) ||
    Boolean(searchParams.price_max) ||
    Boolean(currentCategoryId) ||
    Object.keys(searchParams).some((key) => key.startsWith("attr_") && searchParams[key]);

  return (
    <aside className="flex w-full flex-col gap-6 md:w-64">
      {heading && <h1 className="text-2xl font-semibold">{heading}</h1>}

      {subcategories && subcategories.length > 0 && (
        <div>
          <h2 className="mb-2 text-sm font-medium">{dict.subcategoriesHeading}</h2>
          <ul className="flex flex-col gap-1 text-sm">
            {subcategories.map((subcategory) => (
              <li key={subcategory.id}>
                <Link
                  href={subcategory.href}
                  className="flex items-center justify-between text-muted-foreground hover:text-foreground"
                >
                  <span>{subcategory.name}</span>
                  <span className="text-xs">{subcategory.count}</span>
                </Link>
              </li>
            ))}
          </ul>
        </div>
      )}

      <div className="flex items-center justify-between">
        <h2 className="font-semibold">{dict.filtersHeading}</h2>
        {hasActiveFilters && (
          <Link
            href={`${basePath}${clearFiltersQuery(searchParams)}`}
            className="text-sm text-muted-foreground hover:text-foreground"
          >
            {dict.clearFilters}
          </Link>
        )}
      </div>

      {categories && categories.length > 0 && (
        <div>
          <h3 className="mb-2 text-sm font-medium">{dict.categoriesHeading}</h3>
          <div className="flex flex-col gap-1 text-sm">
            {categories.map((category) => {
              const selected = currentCategoryId === String(category.id);
              return (
                <Link
                  key={category.id}
                  href={`${basePath}${buildLinkQuery(searchParams, {
                    category_id: selected ? null : String(category.id),
                  })}`}
                  className={
                    selected
                      ? "flex items-center justify-between font-semibold"
                      : "flex items-center justify-between text-muted-foreground hover:text-foreground"
                  }
                >
                  <span>{category.name}</span>
                  <span className="text-xs">{category.count}</span>
                </Link>
              );
            })}
          </div>
        </div>
      )}

      {price && (
        <div>
          <h3 className="mb-2 text-sm font-medium">{dict.priceHeading}</h3>
          {/* No currency in the facet response — the storefront only ever deals in one
              currency today (PLN), so it's hardcoded here rather than plumbed through. */}
          <p className="mb-2 text-xs text-muted-foreground">
            {formatPrice(price.min, "PLN")} – {formatPrice(price.max, "PLN")}
          </p>
          <form action={basePath} className="flex items-center gap-2">
            {preservedParamEntries(searchParams, ["price_min", "price_max"]).map(
              ([key, value]) => (
                <input key={key} type="hidden" name={key} value={value} />
              ),
            )}
            <Input
              type="number"
              name="price_min"
              placeholder={dict.priceMinPlaceholder}
              defaultValue={stringParam(searchParams.price_min) ?? ""}
              min={0}
              className="w-20"
            />
            <span className="text-muted-foreground">–</span>
            <Input
              type="number"
              name="price_max"
              placeholder={dict.priceMaxPlaceholder}
              defaultValue={stringParam(searchParams.price_max) ?? ""}
              min={0}
              className="w-20"
            />
            <Button type="submit" size="sm" variant="outline">
              {dict.applyPrice}
            </Button>
          </form>
        </div>
      )}

      {attributes.map((attribute) => (
        <div key={attribute.key}>
          <h3 className="mb-2 text-sm font-medium">{attribute.name}</h3>
          <div className="flex flex-col gap-1.5 text-sm">
            {attribute.options.map((option) => {
              const selected = isAttrValueSelected(searchParams, attribute.key, option.key);
              return (
                <Link
                  key={option.key}
                  href={`${basePath}${toggleAttrValueQuery(searchParams, attribute.key, option.key)}`}
                  className="flex items-center gap-2"
                >
                  <span
                    className={`flex size-4 shrink-0 items-center justify-center rounded border ${
                      selected ? "border-primary bg-primary text-primary-foreground" : "border-input"
                    }`}
                  >
                    {selected && <CheckIcon className="size-3" />}
                  </span>
                  <span className={selected ? "font-medium" : "text-muted-foreground"}>
                    {option.label}
                  </span>
                  <span className="ml-auto text-xs text-muted-foreground">{option.count}</span>
                </Link>
              );
            })}
          </div>
        </div>
      ))}
    </aside>
  );
}
