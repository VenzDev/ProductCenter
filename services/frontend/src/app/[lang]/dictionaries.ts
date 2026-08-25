import { notFound } from "next/navigation";
import { lang } from "next/root-params";

import { isLocale } from "@/i18n/config";

const dictionaries = {
  en: () => import("@/dictionaries/en.json").then((module) => module.default),
  pl: () => import("@/dictionaries/pl.json").then((module) => module.default),
};

export async function getDictionary() {
  const locale = await lang();
  if (!isLocale(locale)) notFound();
  return dictionaries[locale]();
}
