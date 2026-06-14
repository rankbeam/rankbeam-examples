# Codex adversarial review of the e2e assertions

Run at the RT5 gate (the brief: *"does it actually prove the contract, or just
that the page loads?"*). Codex (GPT, `codex-cli 0.139.0`) reviewed
`e2e/contract/assertContract.ts`, `head.ts`, `fixtures.ts`, and the three specs
against the contract spec. Verdict before fixes: **Partial** on all five stacks.
Its single most important finding was correct and material, and is now fixed.

## Disposition

| # | Finding | Severity | Disposition |
|---|---|---|---|
| 1 | Absent fields made **no negative assertion** — a manager that keeps a rich page's `og:title`/`og:image`/`twitter:*` on the bare page would pass (the teardown clause §3 was not really proven). | Critical | **Fixed.** Every package-owned singleton is now `expectValueOrAbsent` — exact value, or asserted absent (`null` in the fixture). The bare/noindex fixtures carry their full resolved inventory. Verified green on Blade. |
| 2 | `og:description` / `twitter:description` unchecked; `twitter:image` not value-exact. | High | **Fixed** by the same value-or-absence pass + expanded fixtures. |
| 6 | `article:published_time` presence-only; `article:modified_time` unchecked. | Medium | **Fixed.** `published_time` is now exact; `modified_time` asserted present + ISO (its value is the non-deterministic `updated_at`). |
| 10 | SSR leg proved the no-JS **DOM**, not the **raw bytes**. | Medium | **Fixed.** The SSR test now also fetches the response with the request API and asserts the resolved `<title>` and the `</script>`-escaped JSON-LD are literally in the body. |
| 12 | Tag ownership (§2: replace package tags without deleting app tags) untested. | Medium | **Fixed.** Every layout carries an app-owned `<meta name="app-sentinel">`; `assertStaticContract` asserts it survives every render and client visit. |
| 13 | Navigation waited only for the title; a late stale-tag insert could pass. | Low | **Fixed.** `navigateAndAssertContract` now polls until two consecutive head snapshots are identical (`waitForStableHead`). |
| 14 | `</script>` XSS check used decoded `textContent`, not raw-byte proof of `JSON_HEX_TAG`. | Low | **Fixed.** Now also asserts the raw payload contains `</script>` (the specific hex escape). |
| 5 | Pre/post-hydration parity ran for `rich` only and compared `<meta>` only. | High | **Fixed.** Parity now compares the full `headSignature` (title, `<html lang>`, meta, links, JSON-LD) for **every** page; warnings collected for the whole lifetime. |
| 3 | `head-key` not asserted in the DOM. | High | **Partly addressed / by design.** Inertia commonly does not leave `head-key` in the DOM; the browser proves head-key dedup *works* via the no-duplication assertion after every client visit (exactly one of each singleton). The key *values* are pinned by the unit `RenderingContractTest`. Documented, not hard-asserted on a possibly-absent attribute. |
| 4 | Canonical normalization / noindex isolation only in the Blade spec. | High | **Accepted.** `assertStaticContract` asserts self-referencing, absolute, query-stripped canonical on **every** page in **every** stack; the explicit `?utm` query-strip + cross-page isolation tests are Blade-specific because only a server read of the request query exercises them. A future pass can add a goto-with-query case to the Inertia/Livewire specs. |
| 7 | hreflang non-self hrefs absolute-only; no French page for reciprocity. | Medium | **Deferred (tracked).** Langs are exact and the self-referencing `en-US` href is exact; full reciprocity needs a localized `/fr/...` page + fixture. Noted as RT5 follow-up. |
| 8 | "Planned" clauses (`og:image:width/height/alt`, `twitter:image:alt`, `og:locale:alternate`) must not be asserted. | — | **Confirmed correct** — none are asserted. |
| 9 | Shared fixture weakens independence (seeder + tests read one file). | Medium | **Accepted with mitigation.** The seeder consumes `model_attributes`/`seo_meta`; the tests consume the separate `expect` block — outputs are not echoed back as inputs. Independent invariants (suffix-count, canonical≡og:url, absolute URLs, article gating, JSON parse, value-or-absence) hold regardless of the fixture. A hard-coded oracle for a couple of transformed values is a possible future hardening. |
| 11 | CSR-only non-compliance documented, not executed. | Medium | **Accepted** — §5 requires it be *documented*, which it is; an optional no-SSR response test is left as a hook. |

## The fix that mattered

> "Make every contract-owned singleton an unconditional exact value-or-absence
> assertion, then run rich → bare navigation against that complete key
> inventory." — Codex

Done. Before, the bare page only checked that *its* values existed; now it
checks that the rich page's `og:title`, `og:image`, `twitter:title/description/
image`, and JSON-LD are **gone**, by asserting the bare page's own exact values
(or absence). This is what turns the navigation tour from "the new page loaded"
into "the old page was torn down."
