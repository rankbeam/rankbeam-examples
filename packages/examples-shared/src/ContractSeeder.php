<?php

declare(strict_types=1);

namespace Rankbeam\Examples;

use Illuminate\Database\Seeder;
use Rankbeam\Examples\Models\Page;
use Rankbeam\Examples\Models\Post;

/**
 * Seeds the three canonical contract pages identically for every stack from the
 * shared fixtures: a rich article, a bare teardown target, and a noindex page.
 *
 * Every reference app's DatabaseSeeder calls this — "one seeder" across the
 * whole matrix (the RT5 brief). Re-runnable: it truncates first so a re-seed is
 * deterministic.
 */
final class ContractSeeder extends Seeder
{
    public function run(): void
    {
        // Deterministic re-seed.
        \Rankbeam\Seo\Models\SEOMeta::query()->delete();
        Post::query()->delete();
        Page::query()->delete();

        foreach (Fixtures::pages() as $page) {
            $attrs = $page['model_attributes'];

            $model = $page['model'] === 'post'
                ? Post::create($attrs)
                : Page::create($attrs);

            // Write the explicit seo_meta row (locale, og/twitter overrides,
            // robots, JSON-LD). saveSEO stores one row keyed by the locale
            // value, which is exactly what SEOData::fromModel reads back.
            $meta = $page['seo_meta'] ?? [];
            $locale = $meta['locale'] ?? 'en';
            unset($meta['locale']);

            // Drop any auto-created placeholder row so exactly ONE seo_meta row
            // exists (keyed by the fixture locale), regardless of the app's
            // seo.features.auto_create_meta setting. SEOData::fromModel reads a
            // single row, so an ambiguous second row would break resolution.
            $model->seoMeta()->delete();
            $model->saveSEO($meta, $locale);
        }
    }
}
