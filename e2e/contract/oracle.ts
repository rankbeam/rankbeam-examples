import { expect } from '@playwright/test'
import { fixtures } from './fixtures'

/**
 * Re-derive transformed/defaulted expectations from fixture inputs. These
 * checks intentionally do not trust the fixture's expect block as the oracle.
 */
export function assertFixtureOracle(): void {
  for (const pg of fixtures.pages) {
    const label = `[oracle:${pg.key}]`
    const expectedTitle = `${pg.model_attributes.title}${fixtures.config.title_suffix}`
    const expectedLocale = pg.seo_meta.locale.replace(/-/g, '_')
    const sourceUrl = new URL(pg.path, 'https://oracle.rankbeam.test')
    const canonicalUrl = new URL(pg.expect.canonical_path, sourceUrl)

    expect(pg.expect.title, `${label} title is base title plus one suffix`).toBe(expectedTitle)
    expect(
      pg.expect.title.split(fixtures.config.title_suffix).length - 1,
      `${label} title suffix appears exactly once`,
    ).toBe(1)
    expect(pg.expect.og.locale, `${label} og:locale converts hyphens to underscores`).toBe(
      expectedLocale,
    )
    expect(canonicalUrl.search, `${label} canonical has no query string`).toBe('')
    expect(
      canonicalUrl.pathname,
      `${label} canonical path is the source path without its query string`,
    ).toBe(sourceUrl.pathname)
  }
}
