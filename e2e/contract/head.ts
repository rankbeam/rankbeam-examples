import type { Page } from '@playwright/test'

/**
 * A decoded, structured snapshot of the document <head>, read from the REAL
 * DOM (post-hydration for Inertia, post-render for Blade/Livewire, or from raw
 * SSR HTML when the context has JavaScript disabled).
 *
 * Reading via the DOM means the browser has already decoded HTML-attribute
 * entities, so assertions compare semantic values — never raw bytes — exactly
 * as the Rendering Contract requires (§2 "decoded semantic values, not bytes").
 */
export type HeadSnapshot = {
  /** Number of <title> elements (the contract requires exactly one). */
  titleCount: number
  title: string | null
  /** Every <meta> carrying name|property + content. */
  metas: Array<{ name: string | null; property: string | null; content: string | null }>
  links: Array<{ rel: string | null; hreflang: string | null; href: string | null }>
  /**
   * Each JSON-LD <script> the package emitted: its raw text (the verbatim
   * source, NOT JSON.parsed — so the </script>-safety check can inspect the
   * bytes the browser actually received) and its data-seo-url marker.
   */
  jsonld: Array<{ raw: string; seoUrl: string | null; seoSchema: boolean }>
  htmlLang: string | null
  /** Count of script[data-seo-schema] — used for the accumulation check. */
  seoSchemaCount: number
}

export async function readHead(page: Page): Promise<HeadSnapshot> {
  return page.evaluate(() => {
    const titles = Array.from(document.querySelectorAll('head title'))
    const metas = Array.from(document.querySelectorAll('head meta')).map((m) => ({
      name: m.getAttribute('name'),
      property: m.getAttribute('property'),
      content: m.getAttribute('content'),
    }))
    const links = Array.from(document.querySelectorAll('head link')).map((l) => ({
      rel: l.getAttribute('rel'),
      hreflang: l.getAttribute('hreflang'),
      href: l.getAttribute('href'),
    }))
    const scripts = Array.from(
      document.querySelectorAll('head script[type="application/ld+json"]'),
    )
    return {
      titleCount: titles.length,
      title: titles.length ? (titles[0].textContent ?? '') : null,
      metas,
      links,
      jsonld: scripts.map((s) => ({
        raw: s.textContent ?? '',
        seoUrl: s.getAttribute('data-seo-url'),
        seoSchema: s.hasAttribute('data-seo-schema'),
      })),
      htmlLang: document.documentElement.getAttribute('lang'),
      seoSchemaCount: document.querySelectorAll('head script[data-seo-schema]').length,
    }
  })
}

/** All meta contents for a name= key (e.g. the repeatable robots is singleton, twitter:* singletons). */
export function metasByName(h: HeadSnapshot, name: string): string[] {
  return h.metas.filter((m) => m.name === name).map((m) => m.content ?? '')
}

/** All meta contents for a property= key (og:*, article:tag is repeatable). */
export function metasByProperty(h: HeadSnapshot, property: string): string[] {
  return h.metas.filter((m) => m.property === property).map((m) => m.content ?? '')
}

export function canonical(h: HeadSnapshot): string | null {
  const c = h.links.find((l) => l.rel === 'canonical')
  return c ? c.href : null
}

export function alternates(h: HeadSnapshot): Array<{ hreflang: string; href: string }> {
  return h.links
    .filter((l) => l.rel === 'alternate' && l.hreflang)
    .map((l) => ({ hreflang: l.hreflang as string, href: l.href ?? '' }))
}
