// Server-only — read directly by layout/page metadata, robots.ts, and sitemap.ts,
// none of which run in the browser, so this doesn't need the NEXT_PUBLIC_ prefix
// (same convention as BACKEND_URL in src/api/api.ts).
export const siteUrl = process.env.SITE_URL ?? "http://localhost:3000";
