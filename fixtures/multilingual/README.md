# Local multilingual editor fixture

Real Lara Zeus / Spatie integrations for Filament 4 and 5, with an English
operator UI and English, Italian, Turkish, Japanese, Simplified Chinese and
Traditional Chinese content. This is an isolated test application, not a
production starter. It requires local source checkouts of core, Filament and
licensed Pro; it never calls an AI provider. Proposals come from a deterministic
local client and are recorded under `storage/app/ai-calls.jsonl`.

## Prepare

Use PHP 8.4 with SQLite, intl and GD, Composer, Python 3, and Node with
Playwright for the browser journey. The generated manifest records source
commits and whether each checkout was dirty. Use clean source commits and a
new destination for release evidence; do not update a previously mirrored
path package and assume its bytes changed.

```sh
python fixtures/multilingual/prepare.py --major 4 --destination ../editor-fixture4 --core ../laravel-seo --filament ../laravel-seo-filament --pro ../laravel-seo-pro
cd ../editor-fixture4
composer install --prefer-dist
php artisan package:discover
php artisan vendor:publish --tag=seo-pro-migrations
php artisan migrate --seed
php artisan filament:assets
php vendor/bin/phpunit
php artisan serve --host=127.0.0.1 --port=8244
```

Repeat with `--major 5`, a different destination, and port 8245. Core 3.20.0,
Filament adapter 1.12.0 and Pro 2.40.1 are candidate aliases for the supplied
source trees. The fixture pins Filament 4.13.1 / plugin 1.0.4 or Filament
5.8.1 / plugin 2.0.1. Keep each generated `composer.lock` with your evidence.

Log in as `editor@example.test` / `Local-fixture-only-123!`. These are public
fixture credentials, not production credentials. The app refuses to boot
without its generated marker or outside the local environment.

## Browser journey

Install Playwright in a local tool directory or use an existing installation.
Set `NODE_PATH` if needed. Optionally set `PLAYWRIGHT_EXECUTABLE_PATH` to an
installed browser, `RANKBEAM_FIXTURE_URL` to a loopback URL and
`RANKBEAM_FIXTURE_OUTPUT` to an artifact directory. From the examples repo:

```sh
node fixtures/multilingual/browser.cjs 4
node fixtures/multilingual/browser.cjs 5
```

The script checks rapid draft switches, no pre-save writes, offline AI
suggestion/apply, save/reload, record isolation, keyboard access and reflow at
1440, 390 and 320 CSS pixels. Screenshots are lossless PNGs at 2× density.
Before repeating it, run `php reset-browser.php` inside that generated app;
this restores only the seeded first post's metadata. Never point this fixture
at a production database.

The PHPUnit journey covers create/edit/clear, validation and retry, uploads,
concurrent schema, explicit tabs, related translation rows, custom hooks,
Unicode URLs, canonical/HTML/OG/hreflang agreement, and sitemap/llms links.
It rolls back its database changes. Test-only `fixture.*` configuration
switches exercise variations in the same resource.

No workflow is added: these licensed-Pro integration checks run locally.
Offline proposals are evidence of integration behavior, not provider output
quality or native-language approval. Actual image/PDF rendering has a separate
rendering matrix; these journeys do not claim native editorial review.
