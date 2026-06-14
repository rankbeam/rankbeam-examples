import { readFileSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import { dirname, resolve } from 'node:path'

/**
 * The Playwright side of the single source of truth. This reads the SAME
 * contract-fixtures.json the PHP ContractSeeder seeds from, so the expected
 * values here can never drift from what the apps actually render. If the seeder
 * and the assertions disagree, it is because someone edited one file — and they
 * are the same file.
 */

const __dirname = dirname(fileURLToPath(import.meta.url))
const FIXTURES_PATH = resolve(
  __dirname,
  '../../packages/examples-shared/resources/contract-fixtures.json',
)

export type PageFixture = {
  key: string
  model: 'post' | 'page'
  path: string
  nav_label: string
  model_attributes: {
    title: string
  }
  seo_meta: {
    locale: string
  }
  alternates: Array<{ hreflang: string; path: string }>
  expect: {
    title: string
    description?: string
    description_present?: boolean
    canonical_path: string
    robots_present: boolean
    robots_value?: string
    og: {
      title?: string | null
      description?: string | null
      type: string
      locale: string
      site_name: string
      image?: string | null
    }
    article: {
      present: boolean
      author?: string
      section?: string
      tags?: string[]
      published_time?: string
      modified_present?: boolean
    }
    twitter: {
      card: string
      title?: string | null
      description?: string | null
      image?: string | null
      site?: string | null
      creator_present: boolean
    }
    schema: { present: boolean; type?: string; headline?: string }
    hreflang: string[]
  }
}

type Fixtures = {
  config: {
    site_name: string
    title_suffix: string
    default_robots: string
    twitter_site: string
    default_og_image: string
  }
  pages: PageFixture[]
  nav_order: string[]
}

export const fixtures: Fixtures = JSON.parse(readFileSync(FIXTURES_PATH, 'utf8'))

export function pageByKey(key: string): PageFixture {
  const p = fixtures.pages.find((p) => p.key === key)
  if (!p) throw new Error(`No contract fixture for key "${key}"`)
  return p
}

/** Absolutize a fixture path against the running app's base URL. */
export function absolute(baseURL: string, path: string): string {
  return new URL(path, baseURL).toString()
}
