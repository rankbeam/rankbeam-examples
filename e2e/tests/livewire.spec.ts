import { test, expect } from '@playwright/test'
import { readHead } from '../contract/head'
import { assertStaticContract, navigateAndAssertContract } from '../contract/assertContract'
import { fixtures, pageByKey } from '../contract/fixtures'
import { assertCanonicalQueryStrip, assertNoindexIsolation } from '../contract/sharedChecks'

/**
 * Livewire. The initial full-page render carries the complete crawler-visible
 * head (TagRenderer::render, so JSON-LD is tagged data-seo-schema —
 * requireSchemaMarker is true). The interesting Livewire clause is wire:navigate:
 * Livewire merges the <head> and treats <script> as a non-removable asset, so
 * JSON-LD accumulates unless torn down. This proves the (corrected,
 * location-based) cleanup snippet removes stale schema — including the case the
 * old docs snippet missed: navigating to a page that has NO schema.
 */
const MARK = { requireSchemaMarker: true }

test.describe('Livewire — Rendering Contract', () => {
  test('each page satisfies the full contract on initial (full-page) render', async ({ page, baseURL }) => {
    for (const pg of fixtures.pages) {
      await page.goto(pg.path)
      const head = await readHead(page)
      assertStaticContract(head, pg, baseURL!, 'livewire', MARK)
    }
  })

  test('wire:navigate keeps singletons fresh and does not accumulate JSON-LD', async ({ page, baseURL }) => {
    await page.goto(pageByKey('rich').path)
    assertStaticContract(await readHead(page), pageByKey('rich'), baseURL!, 'livewire-nav', MARK)

    for (const key of [...fixtures.nav_order, 'rich']) {
      await navigateAndAssertContract(page, pageByKey(key), baseURL!, 'livewire-nav', MARK)
    }
  })

  test('rich → bare tears the stale JSON-LD down (the case length<2 missed)', async ({ page, baseURL }) => {
    await page.goto(pageByKey('rich').path)
    expect((await readHead(page)).jsonld.length, 'rich page has exactly one JSON-LD').toBe(1)

    // wire:navigate to a page with NO schema. Livewire keeps the rich page's
    // <script>; the cleanup must remove it because its data-seo-url != here.
    const bareHead = await navigateAndAssertContract(page, pageByKey('bare'), baseURL!, 'livewire-teardown', MARK)
    expect(bareHead.jsonld.length, 'bare page has NO stale JSON-LD after wire:navigate').toBe(0)

    // And back to rich: its own schema reappears, exactly one.
    const richHead = await navigateAndAssertContract(page, pageByKey('rich'), baseURL!, 'livewire-teardown', MARK)
    expect(richHead.jsonld.length, 'rich page schema restored, not duplicated').toBe(1)
  })

  test('canonical is normalized: query string is stripped', async ({ page, baseURL }) => {
    await assertCanonicalQueryStrip(page, baseURL!)
  })

  test('noindex page is isolated: its canonical is its own URL, not the rich page’s', async ({ page, baseURL }) => {
    await assertNoindexIsolation(page, baseURL!)
  })
})
