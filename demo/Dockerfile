# Rankbeam SEO — prospect demo image.
#
# Stands up the Blade reference app (reused verbatim, not re-authored) against
# the RELEASED packages: core from Packagist, and — when WITH_PRO=true — Pro
# from its private Composer repo. Seeds the shared fixtures and serves.
#
# Build context is the rankbeam-examples repo root (see docker-compose.yml), so
# the app source and the shared package are both reachable.
#
#   docker build -f demo/Dockerfile -t rankbeam-demo ..        # core only
#   docker build -f demo/Dockerfile --build-arg WITH_PRO=true \
#     --secret id=composer_auth,src=auth.json -t rankbeam-demo ..

FROM php:8.4-cli AS app

ARG WITH_PRO=false

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# The shared models + seeder + fixtures live beside the app as a path package
# (kept local — only the Rankbeam packages flip to released versions).
COPY packages/examples-shared/ /shared/examples-shared/

# The Blade reference app, used verbatim.
COPY apps/blade/ /app/

# Start clean: drop the reference app's committed env, throwaway SQLite db,
# any vendored deps, the path-repo lockfile, and cached package manifests, so
# the released-package install + a fresh seed are what actually run.
RUN rm -rf /app/.env /app/database/database.sqlite /app/vendor \
    /app/composer.lock /app/bootstrap/cache/*.php

# Demo-only overlays: the released composer manifest (the "flip"), the demo env
# (port 8080), and an AppServiceProvider that registers Pro scan targets *only
# when Pro is present*.
COPY demo/composer.released.json /app/composer.released.json
COPY demo/composer.pro.json /app/composer.pro.json
COPY demo/.env.example /app/.env.example
COPY demo/app/Providers/AppServiceProvider.php /app/app/Providers/AppServiceProvider.php
COPY demo/docker-entrypoint.sh /usr/local/bin/demo-entrypoint
RUN chmod +x /usr/local/bin/demo-entrypoint

# Pick the manifest, drop the path-repo lockfile, and install. The Pro build
# reads private-Composer credentials from the mounted secret (auth.json), so
# the license key never lands in an image layer.
RUN --mount=type=secret,id=composer_auth,target=/root/.composer/auth.json,required=false \
    if [ "$WITH_PRO" = "true" ]; then cp composer.pro.json composer.json; else cp composer.released.json composer.json; fi \
    && rm -f composer.lock \
    && composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader \
    && composer clear-cache

ENV APP_ENV=local \
    APP_DEBUG=true \
    DB_CONNECTION=sqlite

EXPOSE 8080

ENTRYPOINT ["demo-entrypoint"]
