import { expect, type Page } from '@playwright/test'
import {
  readHead,
  metasByName,
  metasByProperty,
  canonical,
  alternates,
  type HeadSnapshot,
} from './head'
import { fixtures, absolute, type PageFixture } from './fixtures'

const SUFFIX = fixtures.config.title_suffix // " | Rankbeam"

/**
 * Assert the FULL Rendering Contract against a single rendered <head>.
 *
 * This is deliberately value-exact: it does not merely check that tags EXIST,
 * it checks each carries the precise resolved value from the shared fixtures —
 * the difference between "the page loaded" and "the contract holds". Each block
 * cites the contract clause (docs/contributing/rendering-contract.md) it proves.
 *
 * It runs identically against:
 *   - a Blade server-render,
 *   - an Inertia page after hydration,
 *   - an Inertia SSR page with JavaScript disabled (raw HTTP HTML),
 *   - a Livewire page after wire:navigate.
 * Because the post-navigation <head> must satisfy exactly the same singleton /
 * no-stale / no-accumulation rules, re-running this after a client visit IS the
 * client-navigation proof (§3).
 */
export type ContractOpts = {
  /**
   * Require the data-seo-schema / data-seo-url markers on the JSON-LD script.
   * True for Blade & Livewire (TagRenderer::renderSchema emits them; Livewire
   * needs them for teardown). False for Inertia, whose JSON-LD goes through the
   * head-key prop recipe and is deduped by Inertia, not by the marker.
   */
  requireSchemaMarker?: boolean
}

export function assertStaticContract(
  head: HeadSnapshot,
  pg: PageFixture,
  baseURL: string,
  label = '',
  opts: ContractOpts = {},
): void {
  const e = pg.expect
  const where = label ? `${label}:${pg.key}` : pg.key
  const m = (s: string) => `[${where}] ${s}`

  // ── §1 Title — exactly one, resolved, suffix applied EXACTLY once ──────────
  expect(head.titleCount, m('exactly one <title>')).toBe(1)
  expect(head.title, m('resolved title value')).toBe(e.title)
  const suffixCount = head.title ? head.title.split(SUFFIX).length - 1 : 0
  expect(suffixCount, m('title suffix applied exactly once (no double-suffix)')).toBe(1)

  // ── §1 Meta description — present only when one resolved ────────────────────
  const desc = metasByName(head, 'description')
  if (e.description_present === false) {
    expect(desc, m('no meta description on a bare page (no empty tag)')).toHaveLength(0)
  } else {
    expect(desc, m('exactly one meta description')).toHaveLength(1)
    if (e.description) expect(desc[0], m('description value')).toBe(e.description)
  }

  // ── §1/§2 Canonical — one, absolute, self-referencing, query-stripped ───────
  const canonLinks = head.links.filter((l) => l.rel === 'canonical')
  expect(canonLinks, m('exactly one rel=canonical')).toHaveLength(1)
  const canon = canonical(head)
  expect(canon, m('canonical is the absolute self-URL')).toBe(absolute(baseURL, e.canonical_path))
  expect(canon, m('canonical is absolute http(s)')).toMatch(/^https?:\/\//)
  expect(canon, m('canonical carries no query string (normalization)')).not.toContain('?')

  // ── §2 canonical ≡ og:url — HARD invariant (disagreement is a failure) ──────
  const ogUrl = metasByProperty(head, 'og:url')
  expect(ogUrl, m('exactly one og:url')).toHaveLength(1)
  expect(ogUrl[0], m('canonical ≡ og:url (hard invariant)')).toBe(canon)

  // ── §1 Robots — absent at the site default, verbatim when deviating ─────────
  const robots = metasByName(head, 'robots')
  if (e.robots_present) {
    expect(robots, m('robots present on a deviating (noindex) page')).toHaveLength(1)
    expect(robots[0], m('robots emitted verbatim')).toBe(e.robots_value)
  } else {
    expect(robots, m('robots ABSENT when equal to the site default (no redundant index,follow)')).toHaveLength(0)
  }

  // ── §1 Open Graph — every singleton: exact value OR asserted ABSENT ─────────
  // (A `null` in the fixture means "must not be present" — this is what proves
  //  a metadata-rich → bare transition tears stale og:* down, §3.)
  expectOne(metasByProperty(head, 'og:type'), 'og:type', e.og.type, m)
  expectOne(metasByProperty(head, 'og:site_name'), 'og:site_name', e.og.site_name, m)
  expectOne(metasByProperty(head, 'og:locale'), 'og:locale (en-US → en_US)', e.og.locale, m)
  expectValueOrAbsent(metasByProperty(head, 'og:title'), 'og:title', e.og.title, baseURL, m)
  expectValueOrAbsent(metasByProperty(head, 'og:description'), 'og:description', e.og.description, baseURL, m)
  expectValueOrAbsent(metasByProperty(head, 'og:image'), 'og:image', e.og.image, baseURL, m, true)
  for (const img of metasByProperty(head, 'og:image')) {
    expect(img, m('og:image absolute')).toMatch(/^https?:\/\//)
  }

  // ── §1 article:* — only when og:type=article AND real; never fabricated ─────
  const ARTICLE_KEYS = [
    'article:published_time',
    'article:modified_time',
    'article:author',
    'article:section',
    'article:tag',
  ]
  if (e.article.present) {
    expect(metasByProperty(head, 'og:type'), m('article:* requires og:type=article')).toEqual(['article'])
    if (e.article.author) expectOne(metasByProperty(head, 'article:author'), 'article:author', e.article.author, m)
    if (e.article.section) expectOne(metasByProperty(head, 'article:section'), 'article:section', e.article.section, m)
    if (e.article.tags) {
      expect(metasByProperty(head, 'article:tag'), m('article:tag set, order preserved')).toEqual(e.article.tags)
    }
    if (e.article.published_time) {
      // Exact, not just ISO-shaped — a wrong date must fail.
      expectOne(metasByProperty(head, 'article:published_time'), 'article:published_time', e.article.published_time, m)
    }
    if (e.article.modified_present) {
      // modified_time is the row's updated_at (non-deterministic) — assert
      // presence + ISO shape, not an exact value.
      const mod = metasByProperty(head, 'article:modified_time')
      expect(mod, m('article:modified_time present')).toHaveLength(1)
      expect(mod[0], m('article:modified_time is ISO-8601')).toMatch(/^\d{4}-\d{2}-\d{2}T/)
    }
  } else {
    for (const k of ARTICLE_KEYS) {
      expect(metasByProperty(head, k), m(`NO ${k} on a non-article page (never fabricated)`)).toHaveLength(0)
    }
  }

  // ── §1 Twitter Cards — every singleton value-or-absent; site/creator independent ─
  expectOne(metasByName(head, 'twitter:card'), 'twitter:card', e.twitter.card, m)
  expectValueOrAbsent(metasByName(head, 'twitter:title'), 'twitter:title', e.twitter.title, baseURL, m)
  expectValueOrAbsent(metasByName(head, 'twitter:description'), 'twitter:description', e.twitter.description, baseURL, m)
  expectValueOrAbsent(metasByName(head, 'twitter:image'), 'twitter:image', e.twitter.image, baseURL, m, true)
  expectValueOrAbsent(metasByName(head, 'twitter:site'), 'twitter:site', e.twitter.site, baseURL, m)
  if (e.twitter.creator_present === false) {
    expect(metasByName(head, 'twitter:creator'), m('twitter:creator NOT fabricated from twitter:site (independent)')).toHaveLength(0)
  }
  for (const ti of metasByName(head, 'twitter:image')) {
    expect(ti, m('twitter:image absolute')).toMatch(/^https?:\/\//)
  }

  // ── §1 hreflang — absolute, unique by lang, self-referencing ────────────────
  const alts = alternates(head)
  expect(alts.map((a) => a.hreflang), m('hreflang langs (unique, ordered)')).toEqual(e.hreflang)
  expect(new Set(alts.map((a) => a.hreflang)).size, m('hreflang unique by lang')).toBe(alts.length)
  for (const a of alts) {
    expect(a.href, m(`hreflang ${a.hreflang} absolute`)).toMatch(/^https?:\/\//)
  }
  if (e.hreflang.includes('en-US')) {
    const self = alts.find((a) => a.hreflang === 'en-US')
    expect(self?.href, m('en-US alternate is reciprocal / self-referencing (== canonical)')).toBe(canon)
  }

  // ── §1 Per-page JSON-LD — parseable, </script>-safe, no accumulation ────────
  if (e.schema.present) {
    // Exactly one JSON-LD block — the no-accumulation invariant across every
    // stack (Inertia dedups by head-key; Livewire tears down by data-seo-url).
    expect(head.jsonld.length, m('exactly one JSON-LD script (no accumulation)')).toBe(1)
    const block = head.jsonld[0]
    if (opts.requireSchemaMarker) {
      expect(block.seoSchema, m('JSON-LD tagged data-seo-schema (teardown hook present)')).toBe(true)
      expect(block.seoUrl, m('JSON-LD data-seo-url == canonical')).toBe(canon)
    }
    // </script>-safety: the bytes the browser received must NOT contain a real
    // closing tag — JSON_HEX_TAG encodes it as </script>.
    expect(block.raw.toLowerCase(), m('JSON-LD has no literal </script> (stored-XSS guard)')).not.toContain('</script>')
    const parsed = JSON.parse(block.raw)
    if (e.schema.type) expect(parsed['@type'], m('JSON-LD @type')).toBe(e.schema.type)
    if (e.schema.headline) expect(parsed.headline, m('JSON-LD headline round-trips')).toBe(e.schema.headline)
    // The escape is reversible (lossless) AND specifically JSON_HEX_TAG: the
    // value decodes back to the dangerous literal (not stripped), and the bytes
    // the browser received carry the </script> hex escape — proving
    // the closing tag was escaped, not merely missing.
    if (typeof parsed.description === 'string' && parsed.description.includes('</script>')) {
      expect(parsed.description, m('JSON-LD value round-trips through the escape')).toContain('</script>')
      expect(block.raw.toLowerCase(), m('JSON-LD </script> is JSON_HEX_TAG-escaped')).toContain('\\u003c/script\\u003e')
    }
  } else {
    expect(head.jsonld.length, m('NO JSON-LD on a schema-less page (torn down, not layered)')).toBe(0)
  }

  // ── §2 No empty/null tags ever reach the DOM ────────────────────────────────
  // Scope to package-emitted metas (name= or property=). The app-owned
  // <meta charset> / <meta http-equiv> carry no `content` and are §6
  // out-of-scope — they are not the renderer's tags.
  for (const meta of head.metas.filter((x) => x.name || x.property)) {
    const key = meta.name ?? meta.property ?? '(meta)'
    expect(meta.content ?? '', m(`no empty content on meta ${key}`)).not.toBe('')
  }
  for (const link of head.links) {
    expect(link.href ?? '', m(`no empty href on link rel=${link.rel}`)).not.toBe('')
  }

  // ── §1 <html lang> parity with the resolved locale (en_US → en-US) ──────────
  expect(head.htmlLang, m('<html lang> parity with resolved locale')).toBe(e.og.locale.replace('_', '-'))

  // ── §2 Tag ownership — a client renderer replaces package-owned tags WITHOUT
  //     deleting unrelated app-owned tags. Every layout carries an app-owned
  //     <meta name="app-sentinel"> the renderer never touches; it must survive
  //     every render and every client visit.
  expect(metasByName(head, 'app-sentinel'), m('app-owned sentinel preserved (tag ownership)')).toEqual([
    'rankbeam-examples',
  ])
}

function expectOne(values: string[], key: string, expected: string, m: (s: string) => string): void {
  expect(values, m(`exactly one ${key}`)).toHaveLength(1)
  expect(values[0], m(`${key} value`)).toBe(expected)
}

/**
 * Every package-owned singleton is asserted to its EXACT value, or — when the
 * fixture says `null` — asserted ABSENT. This is the load-bearing assertion for
 * the teardown clause (§3): on a bare page, a stale `og:title` / `twitter:image`
 * from the previous page is a *wrong value*, so it fails the exact-value check;
 * a tag that should be gone entirely is caught by the `null` → length-0 check.
 * `undefined` means the fixture does not model the field — skip it.
 */
function expectValueOrAbsent(
  values: string[],
  key: string,
  expected: string | null | undefined,
  baseURL: string,
  m: (s: string) => string,
  isUrl = false,
): void {
  if (expected === null) {
    expect(values, m(`${key} ABSENT (torn down / never fabricated)`)).toHaveLength(0)
    return
  }
  if (expected === undefined) return
  const want = isUrl && !/^https?:\/\//.test(expected) ? absolute(baseURL, expected) : expected
  expect(values, m(`exactly one ${key}`)).toHaveLength(1)
  expect(values[0], m(`${key} value`)).toBe(want)
}

/**
 * Click the in-app nav link to `toPg`, wait for the SPA visit to settle, then
 * re-assert the FULL contract. A passing assertion here proves the §3 client-nav
 * guarantees: exactly one of each singleton, none stale, JSON-LD torn down (not
 * accumulated), and a metadata-rich → bare transition strips the extra tags.
 */
export async function navigateAndAssertContract(
  page: Page,
  toPg: PageFixture,
  baseURL: string,
  label: string,
  opts: ContractOpts = {},
): Promise<HeadSnapshot> {
  await Promise.all([
    page.waitForFunction((t) => document.title === t, toPg.expect.title, { timeout: 10_000 }),
    page.click(`[data-testid=nav-${toPg.key}]`),
  ])
  // The title can update before the rest of the head settles, and a buggy
  // manager might insert a stale tag a tick later. Poll until two consecutive
  // head snapshots are identical, so the assertion runs on a quiesced head —
  // this also catches late stale-tag insertion (it would never quiesce wrong).
  const head = await waitForStableHead(page)
  assertStaticContract(head, toPg, baseURL, label, opts)
  return head
}

/** Read the head repeatedly until two consecutive reads are identical. */
async function waitForStableHead(page: Page): Promise<HeadSnapshot> {
  let prev = ''
  let head = await readHead(page)
  for (let i = 0; i < 12; i++) {
    const cur = JSON.stringify(head)
    if (cur === prev) return head
    prev = cur
    await page.waitForTimeout(75)
    head = await readHead(page)
  }
  return head
}

/**
 * A normalized, order-independent signature of the ENTIRE head — title,
 * <html lang>, every package meta, every link, and the JSON-LD payloads. Used
 * to assert pre/post-hydration parity over the whole contract surface, not just
 * <meta> (so a title/canonical/alternate/JSON-LD hydration drift is caught too).
 */
export function headSignature(head: HeadSnapshot): {
  title: string | null
  htmlLang: string | null
  metas: string[]
  links: string[]
  jsonld: string[]
} {
  return {
    title: head.title,
    htmlLang: head.htmlLang,
    metas: head.metas
      .filter((x) => x.name || x.property)
      .map((x) => `${x.name ?? x.property}=${x.content}`)
      .sort(),
    links: head.links.map((l) => `${l.rel}|${l.hreflang ?? ''}|${l.href}`).sort(),
    jsonld: head.jsonld.map((b) => b.raw).sort(),
  }
}

/** Collect console messages that look like framework hydration warnings. */
export function collectHydrationWarnings(page: Page): string[] {
  const warnings: string[] = []
  page.on('console', (msg) => {
    if (msg.type() !== 'warning' && msg.type() !== 'error') return
    const text = msg.text().toLowerCase()
    if (
      text.includes('hydrat') ||
      text.includes('did not match') ||
      text.includes('mismatch') ||
      text.includes('server html') ||
      text.includes('text content does not match')
    ) {
      warnings.push(msg.text())
    }
  })
  return warnings
}
