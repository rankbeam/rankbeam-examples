# Generated output — what Rankbeam actually renders

This page shows the real output Rankbeam produces: the `<head>` a page renders,
and the linked JSON-LD graph a crawler follows. Nothing here is hand-written for
the docs — the head is captured from the reference fixture through the real
`TagRenderer`, and the graph is captured from a live production page.

## The `<head>` a page renders

These are the exact tags `TagRenderer::render()` emits for the shared
["rich article" contract fixture](../packages/examples-shared/resources/contract-fixtures.json)
(the same fixture the [reference apps](../README.md) seed and the
[`e2e/`](../e2e) Playwright tests assert against a real browser DOM — so those values are asserted, not hand-checked; the snippet below is a manual capture from that fixture). App-owned tags
(`<meta charset>`, viewport) are omitted; these are the tags the resolver owns.

```html
<title>How Canonical URLs Work | Rankbeam</title>
<meta name="description" content="A practical guide to canonical tags.">
<link rel="canonical" href="https://rankbeam.test/blog/canonical-urls">
<meta property="article:tag" content="canonical">
<meta property="article:tag" content="seo">
<meta property="og:title" content="How Canonical URLs Work">
<meta property="og:description" content="A practical guide to canonical tags.">
<meta property="og:image" content="https://images.rankbeam.test/og/canonical.jpg">
<meta property="og:url" content="https://rankbeam.test/blog/canonical-urls">
<meta property="og:type" content="article">
<meta property="og:site_name" content="Rankbeam">
<meta property="og:locale" content="en_US">
<meta property="article:published_time" content="2026-06-01T10:00:00+00:00">
<meta property="article:modified_time" content="2026-06-02T08:30:00+00:00">
<meta property="article:author" content="Jane Doe">
<meta property="article:section" content="SEO">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="How Canonical URLs Work">
<meta name="twitter:description" content="A practical guide to canonical tags.">
<meta name="twitter:image" content="https://images.rankbeam.test/og/canonical.jpg">
<meta name="twitter:site" content="@rankbeam">
<link rel="alternate" hreflang="en-US" href="https://rankbeam.test/blog/canonical-urls">
<link rel="alternate" hreflang="fr-FR" href="https://rankbeam.test/fr/blog/canonical-urls">
<link rel="alternate" hreflang="x-default" href="https://rankbeam.test/blog/canonical-urls">
<script type="application/ld+json" data-seo-schema data-seo-url="https://rankbeam.test/blog/canonical-urls">{
    "@context": "https://schema.org",
    "@type": "Article",
    "headline": "How Canonical URLs Work",
    "author": {
        "@type": "Person",
        "name": "Jane Doe"
    },
    "description": "Canonicals, \u0022quotes\u0022 \u0026 \u003C/script\u003E end-tag XSS-safety check"
}</script>
```

Four things worth reading closely, because each is a rule the
[Rendering Contract](https://github.com/rankbeam/laravel-seo/blob/master/docs/contributing/rendering-contract.md)
pins:

- **No `robots` tag.** The page's resolved robots is `index,follow`, which equals
  the site default, so the renderer suppresses it rather than emit a redundant
  directive. A page that deviates (`noindex,nofollow`) emits it verbatim.
- **`og:url` equals the canonical**, by construction — they are never allowed to
  disagree.
- **`en-US` became `en_US`** for `og:locale` (Open Graph uses the underscore
  form) while `hreflang` keeps the hyphen form.
- **The JSON-LD is escaped, not sanitised.** The description contains `"`,
  `&`, and `</script>`; the renderer emits them as the JSON hex escapes `\u0022`,
  `\u0026`, and `\u003C/script\u003E`, so a literal
  `</script>` never appears in the payload to break out of the `<script>` element, and
  a JSON parser decodes them back to the original characters.

### The same page with nothing to say

The "bare" fixture — a page with only a title and no explicit metadata — shows
the floor the resolver falls back to: no description, no `robots`, no
`article:*`, `og:type` back to `website`, and the site's default social image.

```html
<title>Bare Minimum | Rankbeam</title>
<link rel="canonical" href="https://rankbeam.test/pages/bare-minimum">
<meta property="og:title" content="Bare Minimum | Rankbeam">
<meta property="og:image" content="https://rankbeam.test/images/og-default.jpg">
<meta property="og:url" content="https://rankbeam.test/pages/bare-minimum">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Rankbeam">
<meta property="og:locale" content="en_US">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Bare Minimum | Rankbeam">
<meta name="twitter:image" content="https://rankbeam.test/images/og-default.jpg">
<meta name="twitter:site" content="@rankbeam">
```

## The linked JSON-LD graph a crawler follows

A page's individual `Article` schema is only half the story. Across a site,
Rankbeam's `SchemaGraph` emits Organization / WebSite / WebPage nodes that
cross-reference each other — and each other page — by stable `@id`, so a crawler
resolves one entity instead of re-deriving it per page.

Below is the graph from a **live production page**
([`blog.rankbeam.dev/posts/canonical-urls-in-laravel`](https://blog.rankbeam.dev/posts/canonical-urls-in-laravel)),
captured on 2026-07-15. Note that `Organization`, the `WebSite` publisher, the
`WebPage` `about`, and the `BlogPosting` author/publisher all point at the single
`https://rankbeam.dev/#organization` `@id` — one Rankbeam entity, shared across
`rankbeam.dev` and `blog.rankbeam.dev`.

```json
[
    {
        "@context": "https://schema.org",
        "@id": "https://rankbeam.dev/#organization",
        "@type": "Organization",
        "name": "Rankbeam",
        "url": "https://rankbeam.dev/",
        "logo": "https://rankbeam.dev/assets/brand/mark-color-512.png",
        "sameAs": [
            "https://github.com/rankbeam",
            "https://packagist.org/packages/rankbeam/laravel-seo",
            "https://www.linkedin.com/company/rankbeam/"
        ]
    },
    {
        "@context": "https://schema.org",
        "@id": "https://blog.rankbeam.dev#website",
        "@type": "WebSite",
        "name": "Rankbeam Blog",
        "url": "https://blog.rankbeam.dev",
        "publisher": { "@id": "https://rankbeam.dev/#organization" }
    },
    {
        "@context": "https://schema.org",
        "@id": "https://blog.rankbeam.dev/posts/canonical-urls-in-laravel#webpage",
        "@type": "WebPage",
        "name": "Canonical URLs in Laravel: derive by default, override on purpose | Rankbeam Blog",
        "url": "https://blog.rankbeam.dev/posts/canonical-urls-in-laravel",
        "datePublished": "2026-07-07T09:00:00+00:00",
        "dateModified": "2026-07-15T12:42:08+00:00",
        "isPartOf": { "@id": "https://blog.rankbeam.dev#website" },
        "about": { "@id": "https://rankbeam.dev/#organization" }
    },
    {
        "@context": "https://schema.org",
        "@type": "BlogPosting",
        "headline": "Canonical URLs in Laravel: derive by default, override on purpose",
        "author": { "@id": "https://rankbeam.dev/#organization" },
        "publisher": { "@id": "https://rankbeam.dev/#organization" },
        "datePublished": "2026-07-07T09:00:00+00:00",
        "dateModified": "2026-07-15T12:42:08+00:00",
        "mainEntityOfPage": {
            "@type": "WebPage",
            "@id": "https://blog.rankbeam.dev/posts/canonical-urls-in-laravel"
        }
    },
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            { "@type": "ListItem", "position": 1, "name": "Home", "item": "https://blog.rankbeam.dev" },
            { "@type": "ListItem", "position": 2, "name": "Technical SEO", "item": "https://blog.rankbeam.dev/category/technical-seo" },
            { "@type": "ListItem", "position": 3, "name": "Canonical URLs in Laravel: derive by default, override on purpose", "item": "https://blog.rankbeam.dev/posts/canonical-urls-in-laravel" }
        ]
    }
]
```

*(Some verbose fields — image objects, the WebPage description — are trimmed here
for readability; the full graph is on the live page.)*

This graph is built by the free MIT core's `SchemaGraph` plus the typed
`ArticleSchema` / `BreadcrumbSchema` builders. The blog wires them together in
its controller and reuses the site's Organization `@id` through
`config('seo.schema.organization.id')` — the same mechanism any Rankbeam app can
use to share one Organization across subdomains.

## Reproduce it

- **The head:** run any [reference app](../README.md) (start with the
  [Blade app](../apps/blade)) or the one-command [demo](../demo); view source on
  a page. The [`e2e/`](../e2e) suite asserts these values against a real browser.
- **The graph:** `curl https://blog.rankbeam.dev/posts/canonical-urls-in-laravel`
  and read the `application/ld+json` block, or paste the URL into the
  [Schema.org validator](https://validator.schema.org) or Google's
  [Rich Results Test](https://search.google.com/test/rich-results).
