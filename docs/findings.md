# Findings from building the proof matrix — and their fixes

Booting real apps against the real renderer surfaced behaviours the unit
fixtures (which construct `SEOData` directly) never exercise. **All of them are
now fixed** and the whole matrix is green against the fixed core. The package
fixes ride the planned **Core 3.0.0-rc** (a major) and are not yet pushed.

## 1. `SEO::resolveWithOverrides()` reset `og:type` / `twitter:card` — FIXED

`resolveWithOverrides($base, $overrides)` built the override with
`SEOData::fromArray()`, which defaulted `ogType='website'` and
`twitterCard='summary_large_image'` (non-null), and `merge()` is "other wins
unless null" — so overriding *any* unrelated field dragged those defaults along.
The docs' own pagination example (`['robots' => 'noindex,follow']`) would have
rewritten `og:type` to `website` on every paginated article.

**Fix (laravel-seo, Core 3.0):** `SEOData::fromArray()` no longer injects those
defaults (`ogType`/`twitterCard` fall back to `null`); the renderer still
defaults them at render time, and `SEOResolver::buildBaseConfig` seeds the base
layer, so no-value pages are unchanged. A regression test locks
`resolveWithOverrides($article, ['robots' => …])` keeping `og:type=article`. The
reference apps no longer call `resolveWithOverrides` at all.

## 2. Computed `og:type=article` was clobbered by the explicit layer — FIXED

`SEOData::fromModel()` read `ogType: $meta->og_type ?? 'website'`, and `SEOMeta`
defaulted the column to `'website'` (DB + model `$attributes`) — so a Post with
any `seo_meta` row always resolved to `og:type=website`, defeating
`SEOComputedBuilder`'s article inference.

**Fix (laravel-seo, Core 3.0):** `fromModel()` passes `og_type`/`twitter_card`
through verbatim; `SEOMeta` drops the `$attributes` defaults and the
`withDefault()` keys; a new **idempotent, irreversible migration**
(`2026_06_14_000002_make_seo_meta_og_type_nullable`) makes both columns nullable
and drops the DB default (runs on fresh-install and upgrade-from-v2, verified on
SQLite). A null-`og_type` Post now resolves to `og:type=article`.

## 3. No first-class resolver path for hreflang — FIXED (and used)

`SEOData` modelled `alternates` and the renderer emitted them, but nothing in the
resolve chain populated them, so `@seo($post)` / `SEO::forInertia($post)` never
emitted hreflang.

**Fix (laravel-seo, Core 3.0):** a `getSEOAlternates(): ?array` hook on the
`HasSEO` trait, read by a new `SEOComputedBuilder::computeAlternates()` and wired
into `fromModel()` — hreflang now flows through the resolver like the other
article fields. Documented in the Blade + Inertia guides. The reference models
implement the hook (reading the shared fixtures), so every stack's hreflang is
produced by the resolver — the `ExampleSeo` augmentation workaround is gone.

## 4. The Livewire JSON-LD cleanup recipe missed a case — FIXED

Beyond the original `length < 2` early-return (fixed during RT5), the
location-based cleanup still couldn't remove a **same-URL duplicate** Livewire
re-adds when a page is revisited (e.g. a rich→…→rich tour).

**Fix (laravel-seo guide + the Livewire app):** the `livewire:navigated` snippet
now keeps only the **last** `data-seo-schema` script whose `data-seo-url` is the
current page and removes the rest — clearing stale (other-URL) schema *and*
same-URL duplicates. Proven by the Livewire browser test.

## 5. The Inertia JSON-LD prop recipe didn't work — FIXED

The shipped recipe rendered JSON-LD inside Inertia `<Head>` via Vue `v-html` /
React `dangerouslySetInnerHTML` / Svelte `{@html}`. In the browser that produced
an **empty `<script>`** (so `JSON.parse` failed) and **broke SSR** (the render
threw, so inertia-laravel silently fell back to CSR — no meta in the raw HTML).

**Fix (laravel-seo `inertia-json.md` guide + all three Inertia apps):** render
the `schema` page prop **server-side in the root view** (after `@inertiaHead`,
tagged `data-seo-schema` + `data-seo-url`) so JSON-LD is in the raw SSR HTML and
crawler-visible, and rebuild it on `router.on('navigate')` for client visits.
The `<Head>` `:head-key` meta/link recipe is unchanged (it works). Proven by the
Inertia SSR (JS-disabled raw-bytes) + client-nav teardown tests on all three
frameworks.

## Follow-up (scoped out, not a defect)

**Full hreflang reciprocity** (fetch the `fr-FR` page and assert it points back)
needs a real locale-aware-canonical multilingual setup — a feature, not a
leftover fix. The contract's reciprocity clause is partially proven today (each
page emits its complete, self-referencing alternate set: absolute, unique,
`en-US` self == canonical, `x-default` present). A `/fr` reference page with
locale-aware canonicals is a clean future addition.
