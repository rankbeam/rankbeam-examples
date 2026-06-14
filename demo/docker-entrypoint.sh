#!/bin/sh
# Prepare and serve the Rankbeam SEO demo. Idempotent: safe to re-run.
set -e

cd /app

[ -f .env ] || cp .env.example .env
php artisan key:generate --force
touch database/database.sqlite

# Pro ships its migrations publish-only; publish them before migrating so the
# scan/redirect/404 tables exist when Pro is installed (no-op without Pro).
if php artisan list --raw 2>/dev/null | grep -q '^seo-pro:scan'; then
    php artisan vendor:publish --tag=seo-pro-migrations --force >/dev/null 2>&1 || true
fi

php artisan migrate --force --seed

# When Pro is present, show the audit a prospect comes to see: a health check
# and a first full scan over the seeded pages.
if php artisan list --raw 2>/dev/null | grep -q '^seo:doctor'; then
    echo "── seo:doctor ──────────────────────────────────────────"
    php artisan seo:doctor || true
    echo "── seo-pro:scan (seeded pages) ─────────────────────────"
    php artisan seo-pro:scan --sync || true
    php artisan seo-pro:scan-status || true
fi

echo
echo "Rankbeam demo ready → http://localhost:8080"
exec php artisan serve --host=0.0.0.0 --port=8080
