# rankbeam-examples

Thin reference apps that prove the **Rankbeam SEO Rendering Contract** holds in
every front-end stack — with real browsers and real SSR, not assertions about
source code.

> The contract itself lives in
> [`laravel-seo/docs/contributing/rendering-contract.md`](https://github.com/rankbeam/laravel-seo/blob/master/docs/contributing/rendering-contract.md).
> These apps assert **only** its clauses. Stack-agnostic features (the score, AI
> suggestions, redirects, the 404 monitor, JSON-LD *generation*, sitemaps) are
> server-side PHP tested once in each package's own Pest suite — never here. See
> [`docs/what-is-tested-where.md`](docs/what-is-tested-where.md).

## The apps

| App | Stack | How the head reaches the DOM | Demo-ready |
|---|---|---|---|
| [`apps/blade`](apps/blade) | Blade (server-render) | `TagRenderer::render()` in the layout | ✅ |
| [`apps/inertia-vue`](apps/inertia-vue) | Inertia + Vue | `<Head>` + `:head-key` + JSON-LD prop | ✅ |
| [`apps/inertia-react`](apps/inertia-react) | Inertia + React | `<Head>` + `head-key` + JSON-LD prop | |
| [`apps/inertia-svelte`](apps/inertia-svelte) | Inertia + Svelte | `<svelte:head>` | |
| [`apps/livewire`](apps/livewire) | Livewire | `TagRenderer::render()` + `wire:navigate` teardown | |

Each app is the **smallest thing** that renders one model page and navigates
between several: a rich **article** (every contract surface at once), a **bare**
page (the teardown target), and a **noindex** page. The `blade` and
`inertia-vue` apps are marked public-demo-ready (RT11).

## The prospect demo

[`demo/`](demo) packages the `blade` app as a **one-command, seeded prospect
demo on the released packages** (Docker Compose, core from Packagist, Pro via
its private Composer auth) — the low-friction trial that does not require
cloning sibling repos. See [`demo/README.md`](demo/README.md); it is documented
in the docs at [`/guide/demo`](https://docs.rankbeam.dev/guide/demo).

The page bodies are the **verbatim recipes** from the framework guides — the
Blade `@seo`-equivalent head, the Vue/React `<Head>` with `:head-key`, the
`<svelte:head>` block, the JSON-LD prop, and the Livewire `livewire:navigated`
cleanup. If a recipe doesn't hold up in the browser, that is a doc bug fixed in
`laravel-seo`, not a contract we weaken.

## One seeder, one source of truth

Every app seeds the **same** three pages from
[`packages/examples-shared/resources/contract-fixtures.json`](packages/examples-shared/resources/contract-fixtures.json),
and the Playwright tests assert against the **same file**. The seeder and the
expected values cannot drift, because they are the same file. The fixture
mirrors the unit fixture `richSeo()` in `RenderingContractTest`.

## Running a stack locally

Prerequisites: PHP 8.4, Composer, Node 22. The apps install the core package via
a **composer path repo** pointing at a sibling `laravel-seo` checkout (see the
source note below).

```bash
# 1. Blade (no JS build needed)
cd apps/blade
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8210      # leave running

# 2. e2e
cd ../../e2e
npm install && npx playwright install chromium
BLADE_URL=http://127.0.0.1:8210 npx playwright test --project=blade
```

Inertia stacks additionally need `npm install && npm run build` (client + SSR
bundles) and `php artisan inertia:start-ssr` for the SSR leg. Livewire needs no
JS build. See [`docs/what-is-tested-where.md`](docs/what-is-tested-where.md) and
[`.github/workflows/reference-apps.yml`](.github/workflows/reference-apps.yml)
for the exact per-stack steps and ports.

## Released packages vs. the path overlay (source toggle)

The brief is to install the **released** packages (Pro via its private Composer
auth). Until Core 3 / Pro 2 are tagged and published, that constraint cannot
resolve, so the committed apps install the **core working tree** via a path repo
(`../../../laravel-seo`) — the only thing that resolves today, and the way the
matrix actually runs green now.

This is a one-line flip on release: replace the path repo + `^3.0@dev` with
`composer require rankbeam/laravel-seo:^3.0` (and the Pro stacks with the real
private-Composer auth) and re-lock. The CI workflow checks out `laravel-seo` as
a sibling for the same reason and carries the same flip note.

These reference apps live in their **own repo**, never in any package's
distribution — so package dists stay clean without needing `export-ignore`
inside the packages (guardrail #12 is satisfied by separation).

## Findings surfaced — and fixed

Booting real apps surfaced five defects the unit fixtures never exercised. **All
are now fixed** and the whole matrix is green against the fixed core (the
laravel-seo fixes ride **Core 3.0.0-rc**, not yet pushed). Details +
before/after in [`docs/findings.md`](docs/findings.md):

1. **`SEO::resolveWithOverrides()` reset `og:type`/`twitter:card`** — fixed:
   `SEOData::fromArray()` no longer injects those defaults; the apps no longer
   need `resolveWithOverrides` at all.
2. **Computed `og:type=article` clobbered by the explicit layer** — fixed:
   `fromModel()` passes `og_type` through; a new nullable migration drops the
   `seo_meta.og_type` default. A null-`og_type` Post now resolves to `article`.
3. **No first-class hreflang resolver path** — fixed: a `getSEOAlternates()`
   hook on `HasSEO`, read by the resolver. The reference models implement it, so
   every stack's hreflang flows through the resolver (no workaround).
4. **Livewire JSON-LD cleanup missed same-URL duplicates** — fixed: the
   `livewire:navigated` snippet now keeps only the *last* script for the current
   URL, clearing stale schema *and* duplicates.
5. **The Inertia JSON-LD `<Head>` recipe rendered empty + broke SSR** — fixed:
   JSON-LD is now server-rendered from the root view's `schema` prop (raw-HTML /
   crawler-visible) and rebuilt on `router.on('navigate')`; the broken
   `v-html` / `dangerouslySetInnerHTML` / `{@html}`-in-`<Head>` paths are gone.
   The guide (`inertia-json.md`) is corrected.
