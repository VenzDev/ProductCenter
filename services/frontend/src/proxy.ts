import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";

import { defaultLocale, isLocale, locales, type Locale } from "@/i18n/config";

function detectLocale(request: NextRequest): Locale {
  const preferred = request.headers
    .get("accept-language")
    ?.split(",")[0]
    ?.split("-")[0];

  return preferred && isLocale(preferred) ? preferred : defaultLocale;
}

export function proxy(request: NextRequest) {
  const { pathname } = request.nextUrl;
  const hasLocale = locales.some(
    (locale) => pathname === `/${locale}` || pathname.startsWith(`/${locale}/`)
  );
  if (hasLocale) return;

  const url = request.nextUrl.clone();
  url.pathname = `/${detectLocale(request)}${pathname}`;
  return NextResponse.redirect(url);
}

export const config = {
  // Skip API routes, health/metrics endpoints, Next.js internals, and any
  // request for a file with an extension (favicon.ico, images in /public, etc).
  matcher: ["/((?!api|health|metrics|_next|.*\\..*).*)"],
};
