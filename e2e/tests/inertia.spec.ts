import { test, expect } from '@playwright/test'
import { canonical, metasByName, metasByProperty, readHead } from '../contract/head'
import {
  assertStaticContract,
  navigateAndAssertContract,
  headSignature,
  collectHydrationWarnings,
} from '../contract/assertContract'
import { fixtures, pageByKey } from '../contract/fixtures'
import { assertCanonicalQueryStrip, assertNoindexIsolation } from '../contract/sharedChecks'

/**
 * Inertia (Vue / React / Svelte). Runs once per stack project. Proves the §7
 * clauses left to RT5 for Inertia: head-key dedup across client navigation (no
 * duplicate / no stale singletons, JSON-LD does not accumulate), zero hydration
 * warnings, and — separately, with JavaScript disabled — that SSR emits the full
 * contract in the raw HTTP HTML (§5).
 *
 * JSON-LD here flows through the head-key prop recipe, so it carries no
 * data-seo-schema marker (Inertia dedups by head-key) — requireSchemaMarker is
 * intentionally false.
 */

/** The SSR base URL for the current project, set by CI once inertia:start-ssr is up. */
function ssrUrl(): string | undefined {
  const env = test.info().project.metadata?.ssrUrlEnv as string | undefined
  return env ? process.env[env] : undefined
}

test.describe('Inertia — Rendering Contract (hydrated DOM)', () => {
  test('each page satisfies the full contract after hydration, with zero warnings', async ({ page, baseURL }) => {
    // Collect warnings for the whole lifetime (one listener), so a warning on
    // any page — including ones emitted during a later visit — is caught.
    const warnings = collectHydrationWarnings(page)
    for (const pg of fixtures.pages) {
      await page.goto(pg.path)
      await page.waitForLoadState('networkidle')
      const head = await readHead(page)
      assertStaticContract(head, pg, baseURL!, 'inertia')
    }
    expect(warnings, 'zero hydration warnings across every page').toEqual([])
  })

  test('client navigation keeps one of each singleton, none stale, no JSON-LD accumulation', async ({ page, baseURL }) => {
    // Start on the rich (schema-bearing, article) page.
    await page.goto(pageByKey('rich').path)
    await page.waitForLoadState('networkidle')
    assertStaticContract(await readHead(page), pageByKey('rich'), baseURL!, 'inertia-nav')

    // Tour every page via in-app client visits, re-asserting the FULL contract
    // at each stop. A stale/duplicate tag or an accumulated schema fails here.
    for (const key of [...fixtures.nav_order, 'rich']) {
      await navigateAndAssertContract(page, pageByKey(key), baseURL!, 'inertia-nav')
    }
  })

  test('rich → bare tears the JSON-LD down (schema does not survive the visit)', async ({ page, baseURL }) => {
    await page.goto(pageByKey('rich').path)
    await page.waitForLoadState('networkidle')
    expect((await readHead(page)).jsonld.length, 'rich page has exactly one JSON-LD').toBe(1)

    const bareHead = await navigateAndAssertContract(page, pageByKey('bare'), baseURL!, 'inertia-teardown')
    expect(bareHead.jsonld.length, 'bare page has NO JSON-LD (torn down, not layered)').toBe(0)
  })

  test('canonical is normalized: query string is stripped', async ({ page, baseURL }) => {
    await assertCanonicalQueryStrip(page, baseURL!)
  })

  test('noindex page is isolated: its canonical is its own URL, not the rich page’s', async ({ page, baseURL }) => {
    await assertNoindexIsolation(page, baseURL!)
  })
})

test.describe('Inertia — SSR crawler visibility (JavaScript disabled)', () => {
  test('SSR emits the full contract in the raw HTTP HTML', async ({ browser, request }) => {
    const SSR_URL = ssrUrl()
    test.skip(!SSR_URL, 'SSR base URL not set — start inertia:start-ssr and set the *_SSR_URL env var')

    const ctx = await browser.newContext({ javaScriptEnabled: false })
    const page = await ctx.newPage()
    for (const pg of fixtures.pages) {
      const target = new URL(pg.path, SSR_URL).toString()

      // (a) JS-disabled DOM: the full contract parses from server HTML alone.
      await page.goto(target)
      assertStaticContract(await readHead(page), pg, SSR_URL!, 'inertia-ssr')

      // (b) Raw HTTP bytes: assert the resolved title is literally in the body —
      // proof it is in the response, not injected by a head manager.
      const body = await (await request.get(target)).text()
      expect(body, `[${pg.key}] resolved <title> is in the raw SSR bytes`).toContain(
        `<title>${pg.expect.title}</title>`,
      )
      if (pg.expect.schema.present) {
        // The JSON-LD (with its JSON_HEX_TAG-escaped </script>) is in the bytes too.
        expect(body.toLowerCase(), `[${pg.key}] JSON-LD is in the raw SSR bytes, </script>-escaped`).toContain(
          '\\u003c/script\\u003e',
        )
      }
    }
    await ctx.close()
  })

  test('the head is semantically identical pre- and post-hydration (every page)', async ({ browser, page }) => {
    const SSR_URL = ssrUrl()
    test.skip(!SSR_URL, 'SSR base URL not set — start inertia:start-ssr and set the *_SSR_URL env var')

    for (const pg of fixtures.pages) {
      // Pre-hydration: SSR HTML with JS disabled.
      const noJs = await browser.newContext({ javaScriptEnabled: false })
      const ssrPage = await noJs.newPage()
      await ssrPage.goto(new URL(pg.path, SSR_URL).toString())
      const ssrSig = headSignature(await readHead(ssrPage))
      await noJs.close()

      // Post-hydration: same page, JS enabled.
      await page.goto(new URL(pg.path, SSR_URL).toString())
      await page.waitForLoadState('networkidle')
      const hydratedSig = headSignature(await readHead(page))

      expect(hydratedSig, `[${pg.key}] full pre/post-hydration head parity`).toEqual(ssrSig)
    }
  })
})

test.describe('Inertia — CSR-only non-compliance (JavaScript disabled)', () => {
  test('CSR-only content has no crawler-visible SEO metadata', async ({ browser }) => {
    const env = test.info().project.metadata?.csrUrlEnv as string | undefined
    const CSR_URL = env ? process.env[env] : undefined
    test.skip(!CSR_URL, `${env ?? 'INERTIA_*_CSR_URL'} not set — optional CSR-only contract §5 check`)

    const rich = pageByKey('rich')
    const ctx = await browser.newContext({ javaScriptEnabled: false })
    try {
      const page = await ctx.newPage()
      await page.goto(new URL(rich.path, CSR_URL).toString())
      const head = await readHead(page)

      expect(head.title, '[csr-only:rich] resolved SEO title is absent from raw HTML').not.toBe(
        rich.expect.title,
      )
      expect(canonical(head), '[csr-only:rich] canonical is absent from raw HTML').toBeNull()
      expect(
        metasByName(head, 'description'),
        '[csr-only:rich] description is absent from raw HTML',
      ).toHaveLength(0)
      expect(
        metasByProperty(head, 'og:url'),
        '[csr-only:rich] Open Graph metadata is absent from raw HTML',
      ).toHaveLength(0)
      expect(
        metasByName(head, 'twitter:card'),
        '[csr-only:rich] Twitter metadata is absent from raw HTML',
      ).toHaveLength(0)
      expect(head.jsonld, '[csr-only:rich] JSON-LD is absent from raw HTML').toHaveLength(0)
    } finally {
      await ctx.close()
    }
  })
})
