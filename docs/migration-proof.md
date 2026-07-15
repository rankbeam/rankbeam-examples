# Migration proof — a reproducible WordPress import

This is a reproducible, non-proprietary migration you can run yourself. It uses
a committed, anonymized WordPress export and shows the three properties that make
a migration safe to trust: a **dry run writes nothing**, the real import is
**idempotent**, and every source row is **accounted for** — matched, url-only,
or skipped with a stated reason.

> **Self-reported evidence.** The transcript below was produced by running the
> command against the committed fixture. The exact same run is pinned as an
> assertion in the core suite
> ([`tests/Feature/WordPressCsvCorpusImportTest.php`](https://github.com/rankbeam/laravel-seo/blob/master/tests/Feature/WordPressCsvCorpusImportTest.php)),
> so the behaviour behind these numbers is pinned by a test — the transcript below
> is a manual capture of one such run. For the
> full operational procedure on a live site, see the
> [WordPress → Rankbeam migration runbook](https://docs.rankbeam.dev/guide/wordpress-migration-runbook).

## The fixture

[`tests/Fixtures/wordpress/anonymized-export.csv`](https://github.com/rankbeam/laravel-seo/blob/master/tests/Fixtures/wordpress/anonymized-export.csv)
in the core repo — a realistic 11-row export excerpt on the `acme.test` domain.
It deliberately includes the awkward cases a real export has: a cross-domain
canonical, an over-length title, multibyte text (`ñ`, `日本語`), a multi-keyword
row, blank fields, a row with no URL, a row with a wrong column count, a blank
line, and a page that matches no model.

## Reproduce it

Against ten host models seeded as `post-0001 … post-0010` (matching the export
slugs), matching WordPress rows to your model by slug:

```bash
# 1. Preview — writes nothing
php artisan seo:import-from wordpress-csv \
  --file=tests/Fixtures/wordpress/anonymized-export.csv \
  --model="App\Models\Post" --match-by=slug --dry-run

# 2. Apply
php artisan seo:import-from wordpress-csv \
  --file=tests/Fixtures/wordpress/anonymized-export.csv \
  --model="App\Models\Post" --match-by=slug

# 3. Re-run — proves idempotency
php artisan seo:import-from wordpress-csv \
  --file=tests/Fixtures/wordpress/anonymized-export.csv \
  --model="App\Models\Post" --match-by=slug
```

## The captured result

```text
===DRY-RUN===
Importing from WordPress (CSV export) (CSV file `tests/Fixtures/wordpress/anonymized-export.csv`)
Target locale: en
DRY RUN — no changes will be written.

+-----------+------+
| Outcome   | Rows |
+-----------+------+
| Created   | 8    |
| Updated   | 0    |
| Unchanged | 0    |
| Skipped   | 3    |
| Scanned   | 11   |
+-----------+------+
11 source row(s) scanned — 8 created, 0 updated, 0 unchanged, 3 skipped.

Verification report
  8 matched (8 created, 0 updated, 0 unchanged), 1 url-only, 2 other skipped.
  1 field(s) truncated to fit, 0 field(s) not imported (no Core 3 column).

Truncated to fit Core 3 columns (review these):
  • title: 1 value(s)

Skipped rows by reason:
  • url-only (no Post matched slug "old-promo"): 1
  • malformed row (missing url): 1
  • malformed row (column count does not match the header): 1

Dry run — nothing was written. Re-run without --dry-run to apply.

===APPLY===
(Identical to the dry-run table above, without the "DRY RUN" line: 8 created,
0 updated, 0 unchanged, 3 skipped.)

===RE-RUN (idempotency)===
+-----------+------+
| Outcome   | Rows |
+-----------+------+
| Created   | 0    |
| Updated   | 0    |
| Unchanged | 8    |
| Skipped   | 3    |
| Scanned   | 11   |
+-----------+------+
11 source row(s) scanned — 0 created, 0 updated, 8 unchanged, 3 skipped.

Verification report
  8 matched (0 created, 0 updated, 8 unchanged), 1 url-only, 2 other skipped.
```

## What it proves

- **A dry run is inert.** It prints the full verification report — 8 rows would
  be created — but writes nothing. You sign off on the numbers before anything
  changes.
- **The import is idempotent.** The second real run creates **0** and reports
  **8 unchanged**. Re-running is safe; it never duplicates a row and (by default)
  only fills empty fields, so it can't clobber metadata you have already edited.
- **Nothing is silently dropped.** Of 11 scanned rows: 8 matched a model, 1 was
  **url-only** (`old-promo` matched no model — your worklist for a redirect), and
  2 were skipped as malformed with a stated reason (missing URL, wrong column
  count). The over-length title is reported as **truncated to fit**, not quietly
  cut.
- **Redirects are handed off, not guessed.** Add `--redirects-csv=…` and
  cross-path canonicals (e.g. `/pricing`, `/promo`, `/status`) are written to a
  CSV in a fixed shape for import into Rankbeam Pro, where every rule is
  validated against loops and unsafe targets.

The same command shape works for a live WordPress database (`--connection=`) and
for the `yoast` / `rank-math` / `ralphjsmit` sources — see the
[migration guides](https://docs.rankbeam.dev/guide/migrate-from-wordpress).
