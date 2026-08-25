// Plain-text preview of rich-text HTML, for card teasers and <meta description>
// — never rendered as HTML itself, so no sanitization is needed here.
export function excerptHtml(html: string, maxLength = 160): string {
  const text = html
    .replace(/<[^>]*>/g, " ")
    .replace(/\s+/g, " ")
    .trim();

  return text.length > maxLength
    ? `${text.slice(0, maxLength).trimEnd()}…`
    : text;
}
