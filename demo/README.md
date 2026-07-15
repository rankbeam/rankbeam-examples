# Rankbeam SEO — runnable demo

A one-command, seeded Laravel app that shows Rankbeam working end-to-end on the
**released** packages — no path repos, no sibling checkouts to clone.

It reuses the [`apps/blade`](../apps/blade) reference app verbatim (the same
shared models, seeder, and contract fixtures the test matrix uses) and only
swaps the package source: instead of a local path repo, it pulls
`rankbeam/laravel-seo` from Packagist, and — when you supply a license —
`rankbeam/laravel-seo-pro` from its private Composer repository.

## What you'll see

**Core (free, MIT) — runs for anyone, no license:**

- Real pages whose `<head>` is fully rendered by the resolver — title,
  description, canonical, Open Graph, Twitter Cards, and a linked JSON-LD schema
  graph.
- `/sitemap.xml` generated from the registered sources.
- The layered resolver filling fallbacks for pages with no explicit meta.

**Pro (with a license) — the technical-SEO audit, headless:**

- `seo:doctor` — a green install health check.
- `seo-pro:scan` over the seeded pages, then `seo-pro:scan-status` showing
  severity-ranked issues and the 0–100 score.

The full **Filament dashboard** (live scan progress, issue browsing, redirect
manager, 404 monitor) is the hosted read-only demo — see
[the demo guide](https://docs.rankbeam.dev/guide/demo).

## Run it (core)

```bash
cd demo
docker compose up --build
```

Then open <http://localhost:8080>. View source on any page to see the rendered
meta; visit `/sitemap.xml` for the generated sitemap. That's the whole free
core, installed from Packagist.

## Run it (core + Pro)

Pro is licensed per project and distributed through a private Composer
repository. Pass your license e-mail and key via `COMPOSER_AUTH` (a BuildKit
secret — it never lands in an image layer) and build with the Pro arg:

```bash
export COMPOSER_AUTH='{"http-basic":{"laravel-seo-pro.composer.sh":{"username":"you@example.com","password":"YOUR-LICENSE-KEY"}}}'
WITH_PRO=true docker compose up --build
```

On boot the container publishes the Pro migrations, runs `seo:doctor`, and does
a first `seo-pro:scan` over the seeded pages — the scan summary and score print
in the compose logs before the server starts.

## The one-line flip (dev ↔ released)

The reference apps install the **core working tree** via a path repo so the
contract matrix can run green today, before Core 3 / Pro 2 are tagged. This demo
is that same app with the source flipped to released packages — the entire diff
is the `require`/`repositories` block:

| | Reference app (`apps/blade/composer.json`) | This demo (`composer.released.json`) |
|---|---|---|
| core | `"rankbeam/laravel-seo": "^3.0@dev"` via path repo | `"rankbeam/laravel-seo": "^3.0"` from Packagist |
| Pro | — | `"rankbeam/laravel-seo-pro": "^2.0"` via the private repo (`composer.pro.json`) |

> **Status:** both packages are published, so this demo builds today. The free
> core (`rankbeam/laravel-seo`) is on
> [Packagist](https://packagist.org/packages/rankbeam/laravel-seo) — the `^3.0`
> constraint resolves to the current 3.x release — and Pro (`^2.0`) resolves to
> the current 2.x from its private Composer repository once you supply a license.
> The `core` build needs nothing but Docker; the Pro build additionally needs a
> license in `COMPOSER_AUTH`.

## Notes

- **No host pollution.** Everything runs inside the container against a
  throwaway SQLite database; nothing is installed on your machine but Docker.
- **Out of package artifacts.** This demo lives in the `rankbeam-examples` repo,
  never inside any distributed package — so package dists stay clean by
  separation (guardrail #12).
- **Compatible versions.** The packages support PHP 8.2+ and Laravel 11–13
  (Pro adds Filament 4–5 for its dashboard); this demo pins Laravel 12 and
  `nesbot/carbon 3.11.*` for a deterministic build.
- **A future `composer create-project rankbeam/demo`** can be cut from this same
  directory now that the packages are public; the Docker path stays the
  zero-setup option in the meantime.
