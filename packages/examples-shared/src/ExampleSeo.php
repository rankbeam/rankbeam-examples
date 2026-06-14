<?php

declare(strict_types=1);

namespace Rankbeam\Examples;

use Illuminate\Database\Eloquent\Model;
use Rankbeam\Seo\Data\SEOData;
use Rankbeam\Seo\Facades\SEO;
use Rankbeam\Seo\Services\TagRenderer;

/**
 * The one place every reference app turns a model into rendered SEO.
 *
 * It is a thin wrapper over the package's PUBLIC API: `SEO::resolve($model)`
 * resolves everything — including hreflang, now that the models implement the
 * first-class `getSEOAlternates()` resolver hook (laravel-seo >= 3.0). The
 * renderer output is produced with the SHIPPED recipes — `TagRenderer::render()`
 * (Blade), `toInertiaHead()` + the JSON-LD `toArray()['script']` prop (Inertia).
 *
 * (Before laravel-seo 3.0 there was no resolver path for hreflang and this had
 * to inject alternates with `SEOData::with('alternates', …)` — see git history.
 * The hook removes that workaround.)
 */
final class ExampleSeo
{
    public static function resolved(Model $model): SEOData
    {
        // Alternates now flow from the model's getSEOAlternates() hook through
        // the resolver — no augmentation needed.
        return SEO::resolve($model);
    }

    /** Blade: a complete <head> fragment. */
    public static function render(Model $model): string
    {
        return app(TagRenderer::class)->render(self::resolved($model));
    }

    /**
     * Inertia: the `<Head>` array (with stable head-keys) plus the JSON-LD
     * passed as its own page prop, exactly as the Inertia guide prescribes.
     *
     * @return array{seo: array<string, mixed>, schema: array<int, array{type: string, innerHTML: string}>, locale: ?string}
     */
    public static function forInertia(Model $model): array
    {
        $renderer = app(TagRenderer::class);
        $seo = self::resolved($model);

        return [
            'seo' => $renderer->toInertiaHead($seo),
            'schema' => $renderer->toArray($seo)['script'],
            // For the root view's <html lang> (the app owns the <html> element).
            'locale' => $seo->locale,
        ];
    }
}
