<?php

use App\Models\Post;
use Illuminate\Support\Facades\Route;
use Rankbeam\Seo\I18n\ModelLocale;
use Rankbeam\Seo\Services\SEOResolver;
use Rankbeam\Seo\Services\TagRenderer;

Route::get('/', fn () => redirect('/admin'));
Route::get('/{locale}/posts/{slug}', function (string $locale, string $slug) {
    abort_unless(in_array($locale, ['en', 'it', 'tr', 'ja', 'zh_CN', 'zh_TW'], true), 404);
    $post = Post::query()->get()->first(fn (Post $post): bool => $post->getTranslation('slug', $locale, false) === $slug);
    abort_unless($post, 404);

    return ModelLocale::run($post, $locale, fn (Post $localized) => view('post', [
        'post' => $localized, 'locale' => $locale,
        'tags' => app(TagRenderer::class)->render(app(SEOResolver::class)->resolve($localized, locale: $locale)),
    ])->render());
});
