export type SearchParamsInput = { [key: string]: string | string[] | undefined };

function firstValue(value: string | string[] | undefined): string | undefined {
  return Array.isArray(value) ? value[0] : value;
}

function attrValues(searchParams: SearchParamsInput, key: string): string[] {
  const raw = searchParams[`attr_${key}`];
  if (!raw) return [];
  const parts = Array.isArray(raw) ? raw : [raw];
  return parts.flatMap((part) => part.split(",")).filter(Boolean);
}

export function isAttrValueSelected(
  searchParams: SearchParamsInput,
  key: string,
  value: string,
): boolean {
  return attrValues(searchParams, key).includes(value);
}

/**
 * Builds the query string sent to the backend, translating the frontend's own, simpler
 * `attr_<key>=a,b` search params into the `attr[<key>][]=a&attr[<key>][]=b` shape the
 * Laravel API expects (Next's searchParams has no built-in support for that bracket
 * syntax, so round-tripping it directly through page links/forms would be awkward).
 */
export function buildBackendQuery(searchParams: SearchParamsInput): string {
  const params = new URLSearchParams();

  for (const name of ["q", "category_id", "price_min", "price_max", "sort", "page"]) {
    const value = firstValue(searchParams[name]);
    if (value) params.set(name, value);
  }

  for (const key of Object.keys(searchParams)) {
    if (!key.startsWith("attr_")) continue;
    const attrKey = key.slice("attr_".length);
    for (const value of attrValues(searchParams, attrKey)) {
      params.append(`attr[${attrKey}][]`, value);
    }
  }

  const query = params.toString();
  return query ? `?${query}` : "";
}

/**
 * Builds the href for one of this page's own links (facet toggles, sort, pagination):
 * starts from the current search params, applies the given changes (a null value removes
 * that param), and drops `page` unless explicitly kept — changing a filter should reset
 * pagination back to the first page.
 */
export function buildLinkQuery(
  searchParams: SearchParamsInput,
  changes: Record<string, string | null>,
  options: { keepPage?: boolean } = {},
): string {
  const params = new URLSearchParams();

  for (const [key, value] of Object.entries(searchParams)) {
    if (key === "page" && !options.keepPage) continue;
    const single = firstValue(value);
    if (single) params.set(key, single);
  }

  for (const [key, value] of Object.entries(changes)) {
    if (value === null) {
      params.delete(key);
    } else {
      params.set(key, value);
    }
  }

  const query = params.toString();
  return query ? `?${query}` : "";
}

export function toggleAttrValueQuery(
  searchParams: SearchParamsInput,
  key: string,
  value: string,
): string {
  const current = attrValues(searchParams, key);
  const next = current.includes(value)
    ? current.filter((v) => v !== value)
    : [...current, value];

  return buildLinkQuery(searchParams, {
    [`attr_${key}`]: next.length > 0 ? next.join(",") : null,
  });
}

/** Drops every filter param, keeping only `q` (a search page's own query text). */
export function clearFiltersQuery(searchParams: SearchParamsInput): string {
  const params = new URLSearchParams();
  const q = firstValue(searchParams.q);
  if (q) params.set("q", q);

  const query = params.toString();
  return query ? `?${query}` : "";
}

/**
 * Hidden-input entries for a GET form (e.g. the price range form) to preserve every
 * currently active search param except the ones the form's own fields already cover.
 */
export function preservedParamEntries(
  searchParams: SearchParamsInput,
  exclude: string[],
): [string, string][] {
  const entries: [string, string][] = [];
  for (const [key, value] of Object.entries(searchParams)) {
    if (key === "page" || exclude.includes(key)) continue;
    const single = firstValue(value);
    if (single) entries.push([key, single]);
  }
  return entries;
}
