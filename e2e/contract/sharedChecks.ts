import { expect, type Page } from '@playwright/test'
import { absolute, pageByKey } from './fixtures'
import { canonical, readHead } from './head'

export async function assertCanonicalQueryStrip(page: Page, baseURL: string): Promise<void> {
  const rich = pageByKey('rich')
  await page.goto(`${rich.path}?utm_source=newsletter&ref=tweet`)
  const head = await readHead(page)

  expect(canonical(head), 'canonical drops the query string').toBe(
    absolute(baseURL, rich.expect.canonical_path),
  )
  const ogUrl = head.metas.find((m) => m.property === 'og:url')?.content
  expect(ogUrl, 'og:url matches the normalized canonical').toBe(canonical(head))
}

export async function assertNoindexIsolation(page: Page, baseURL: string): Promise<void> {
  const rich = pageByKey('rich')
  const noindex = pageByKey('noindex')
  await page.goto(noindex.path)
  const head = await readHead(page)

  expect(canonical(head), 'noindex canonical is self-referencing').toBe(
    absolute(baseURL, noindex.expect.canonical_path),
  )
  expect(canonical(head), 'noindex page does NOT inherit the rich page canonical').not.toBe(
    absolute(baseURL, rich.expect.canonical_path),
  )
}
