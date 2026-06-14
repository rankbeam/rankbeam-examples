import { test } from '@playwright/test'
import { readHead } from '../contract/head'
import { assertStaticContract } from '../contract/assertContract'
import { fixtures, pageByKey } from '../contract/fixtures'
import { assertCanonicalQueryStrip, assertNoindexIsolation } from '../contract/sharedChecks'

/**
 * Blade — classic server-rendered stack. Every page's <head> is in the raw HTTP
 * response, so Blade is the strongest crawler-visibility proof: the contract
 * holds with no JavaScript at all. It also owns the canonical-normalization and
 * noindex-isolation clauses the §7 table leaves to RT5.
 */
test.describe('Blade reference app — Rendering Contract', () => {
  for (const pg of fixtures.pages) {
    test(`${pg.key} page satisfies the full contract`, async ({ page, baseURL }) => {
      await page.goto(pg.path)
      const head = await readHead(page)
      assertStaticContract(head, pg, baseURL!, 'blade', { requireSchemaMarker: true })
    })
  }

  test('canonical is normalized: query string is stripped', async ({ page, baseURL }) => {
    await assertCanonicalQueryStrip(page, baseURL!)
  })

  test('noindex page is isolated: its canonical is its own URL, not the rich page’s', async ({ page, baseURL }) => {
    await assertNoindexIsolation(page, baseURL!)
  })

  test('navigating the whole site keeps every page correct (full-reload tour)', async ({ page, baseURL }) => {
    for (const key of fixtures.nav_order) {
      const pg = pageByKey(key)
      await page.goto('/')
      await page.click(`[data-testid=link-${key}]`)
      await page.waitForLoadState('load')
      const head = await readHead(page)
      assertStaticContract(head, pg, baseURL!, 'blade-tour', { requireSchemaMarker: true })
    }
  })
})
