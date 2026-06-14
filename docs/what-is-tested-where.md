# What is tested where

The Rendering Contract is proven in **two tiers**, deliberately split so the
required, every-push CI stays fast and deterministic while the slow, stack-heavy
browser matrix runs out of the hot path.

## Tier 1 — tested on **every push** (fast, required)

**Where:** `laravel-seo` → `.github/workflows/tests.yml` →
`tests/Unit/Services/RenderingContractTest.php`.

**What:** the renderer *shape* — framework-free, no browser, milliseconds. It
pins the parts of the contract that are pure `TagRenderer` behaviour:

- exactly-one-title / no double suffix; description only when present;
- the robots emit-only-when-deviating policy (+ `emit_default`);
- `canonical ≡ og:url`; no empty/null tags;
- the OG / Twitter / hreflang / JSON-LD value rules; `en-US → en_US`;
- stable Inertia head-keys + repeatable-tag disambiguation;
- cross-renderer **semantic** parity (`render()` ≡ `toArray()` ≡ `toInertiaHead()`).

This runs across PHP 8.2–8.4 × Laravel 11–13 on every push and PR. It is the
gate; if it is red, the renderer is wrong.

## Tier 2 — tested on a **schedule / on release** (slow, full matrix)

**Where:** this repo → `.github/workflows/reference-apps.yml` → `e2e/` Playwright.

**What:** the clauses that only a real DOM / real HTTP response can prove — the
ones the contract's §7 table marks **Browser (RT5)**:

- consistent canonical normalization (query-strip), self-referencing canonical,
  **noindex isolation**;
- client-navigation: exactly one of each singleton, **none stale**, JSON-LD does
  **not** accumulate, metadata-rich → bare **tears tags down**
  (Inertia head-key dedup; Livewire `wire:navigate` + the location-based
  JSON-LD teardown);
- **zero hydration warnings** and pre/post-hydration meta parity (Inertia);
- **SSR emits the full contract in the raw HTTP HTML** (JavaScript disabled);
  the default CSR-only mode is documented as non-compliant, not hidden.

It runs weekly, on `workflow_dispatch`, and on PRs that touch `apps/**`,
`packages/**`, or `e2e/**`. It is **not** triggered by package pushes, so a
change to `laravel-seo` never makes its required job wait on a browser matrix.

### Why the split

A browser + SSR matrix is inherently slower and more failure-prone (Node + PHP +
two servers + a real browser per stack) than a unit suite. Putting it in the
required path would make every renderer change wait on it and tempt
retry-to-green. Instead: the deterministic shape is the gate; the end-to-end
proof is the safety net that runs on a cadence and before a release.

| Guarantee | Tier 1 (every push) | Tier 2 (scheduled / release) |
|---|---|---|
| Renderer output shape | ✅ unit | cross-checked in real DOM |
| Robots / canonical / og / twitter / hreflang / JSON-LD values | ✅ unit | ✅ browser (each stack) |
| Canonical normalization, noindex isolation | — | ✅ browser |
| Client-nav: no dup / no stale / JSON-LD teardown | — | ✅ Inertia + Livewire |
| Hydration warnings + pre/post parity | — | ✅ Inertia |
| SSR raw-HTML crawler visibility | — | ✅ Inertia (JS disabled) |

## Pinning

- **PHP** 8.4, **Node** 22 (LTS) — pinned in the workflow.
- **Chromium** — pinned by the `@playwright/test` version in
  [`e2e/package.json`](../e2e/package.json) (`1.60.0`); CI runs
  `playwright install chromium` so the browser build matches the library.
- **nesbot/carbon** `3.11.*` — pinned in every app's `composer.json`. (Carbon
  `3.12.0` changed the `Carbon::plus()` signature and is incompatible with
  Laravel `12.62`; an un-pinned `minimum-stability: dev` install pulls it.)
