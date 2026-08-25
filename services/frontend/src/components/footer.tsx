import Link from "next/link";
import { lang } from "next/root-params";

import { getDictionary } from "@/app/[lang]/dictionaries";
import { localizedHref } from "@/i18n/config";

export async function Footer() {
  const [dict, locale] = await Promise.all([getDictionary(), lang()]);

  const footerLinks = [
    {
      heading: dict.footer.shop.heading,
      links: [
        { label: dict.footer.shop.products, href: "/products" },
        { label: dict.footer.shop.categories, href: "/categories" },
        { label: dict.footer.shop.cart, href: "/cart" },
      ],
    },
    {
      heading: dict.footer.company.heading,
      links: [
        { label: dict.footer.company.about, href: "/about" },
        { label: dict.footer.company.contact, href: "/contact" },
      ],
    },
    {
      heading: dict.footer.legal.heading,
      links: [
        { label: dict.footer.legal.privacy, href: "/privacy" },
        { label: dict.footer.legal.terms, href: "/terms" },
      ],
    },
  ];

  const socialLinks = [
    { label: dict.footer.social.instagram, href: "#" },
    { label: dict.footer.social.twitter, href: "#" },
    { label: dict.footer.social.github, href: "#" },
  ];

  return (
    <footer className="border-t">
      <div className="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:grid-cols-2 md:grid-cols-4">
        <div className="flex flex-col gap-2">
          <span className="text-lg font-semibold">{dict.common.siteName}</span>
          <p className="text-sm text-muted-foreground">
            {dict.footer.tagline.replace("{year}", String(new Date().getFullYear()))}
          </p>
        </div>
        {footerLinks.map((group) => (
          <div key={group.heading} className="flex flex-col gap-3">
            <h3 className="text-sm font-semibold">{group.heading}</h3>
            <ul className="flex flex-col gap-2">
              {group.links.map((link) => (
                <li key={link.href}>
                  <Link
                    href={localizedHref(locale, link.href)}
                    className="text-sm text-muted-foreground hover:text-foreground"
                  >
                    {link.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>
        ))}
      </div>
      <div className="border-t">
        <div className="mx-auto flex max-w-6xl justify-center gap-4 px-4 py-6">
          {socialLinks.map((social) => (
            <Link
              key={social.label}
              href={social.href}
              className="text-sm text-muted-foreground hover:text-foreground"
            >
              {social.label}
            </Link>
          ))}
        </div>
      </div>
    </footer>
  );
}
