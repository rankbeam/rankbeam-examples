<?php

namespace App\Providers;

use App\Models\Post;
use App\Support\FixtureAiClient;
use Illuminate\Support\ServiceProvider;
use Rankbeam\Seo\Pro\Ai\AiClient;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AiClient::class, FixtureAiClient::class);
        config([
            'app.locale' => 'en', 'app.fallback_locale' => 'en',
            'seo.title_suffix' => '', 'seo.features.auto_create_meta' => false,
            'seo.defaults.site_name' => 'Rankbeam fixture',
            'seo.sitemap.models' => [Post::class], 'seo.audit.models' => [Post::class],
            'seo-filament.locales' => null,
            'seo-pro.ai.enabled' => true, 'seo-pro.ai.provider' => 'fixture',
            'seo-pro.ai.model' => 'offline-fixture', 'seo-pro.ai.suggestion_count' => 2,
        ]);
    }

    public function boot(): void
    {
        if (! is_file(base_path('.rankbeam-multilingual-fixture')) || ! $this->app->environment('local')) {
            throw new \RuntimeException('This fixture may run only in its generated local environment.');
        }

    }
}
