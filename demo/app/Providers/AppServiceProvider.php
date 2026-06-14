<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Rankbeam\Examples\Models\Page;
use Rankbeam\Examples\Models\Post;
use Rankbeam\Seo\Pro\Facades\SeoPro;

/**
 * Demo overlay for the Blade reference app.
 *
 * The only difference from the reference app's empty provider: when Pro is
 * installed (the WITH_PRO build), register the seeded models as scan targets
 * so `seo-pro:scan` has something to audit. The class_exists guard keeps the
 * core-only build (no Pro) booting unchanged — SeoPro::class resolves to a
 * string at compile time and is never autoloaded when absent.
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (class_exists(SeoPro::class)) {
            SeoPro::targets()
                ->register('posts', Post::class)
                ->register('pages', Page::class);
        }
    }
}
