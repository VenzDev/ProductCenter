import Link from "next/link";

const FOOTER_LINKS = [
  {
    heading: "Shop",
    links: [
      { label: "Products", href: "/products" },
      { label: "Categories", href: "/categories" },
      { label: "Cart", href: "/cart" },
    ],
  },
  {
    heading: "Company",
    links: [
      { label: "About", href: "/about" },
      { label: "Contact", href: "/contact" },
    ],
  },
  {
    heading: "Legal",
    links: [
      { label: "Privacy Policy", href: "/privacy" },
      { label: "Terms & Conditions", href: "/terms" },
    ],
  },
];

const SOCIAL_LINKS = [
  { label: "Instagram", href: "#" },
  { label: "Twitter", href: "#" },
  { label: "GitHub", href: "#" },
];

export function Footer() {
  return (
    <footer className="border-t">
      <div className="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:grid-cols-2 md:grid-cols-4">
        <div className="flex flex-col gap-2">
          <span className="text-lg font-semibold">Product Center</span>
          <p className="text-sm text-muted-foreground">
            &copy; {new Date().getFullYear()} Product Center. All rights
            reserved.
          </p>
        </div>
        {FOOTER_LINKS.map((group) => (
          <div key={group.heading} className="flex flex-col gap-3">
            <h3 className="text-sm font-semibold">{group.heading}</h3>
            <ul className="flex flex-col gap-2">
              {group.links.map((link) => (
                <li key={link.href}>
                  <Link
                    href={link.href}
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
          {SOCIAL_LINKS.map((social) => (
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
