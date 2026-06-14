<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Rankbeam\Examples\ExampleSeo;
use Rankbeam\Examples\Models\Page;
use Rankbeam\Examples\Models\Post;

class ContentController
{
    public function index(): Response
    {
        return Inertia::render('Home', ['title' => 'Rankbeam SEO — Inertia reference app']);
    }

    public function post(string $slug): Response
    {
        return $this->renderModel(Post::where('slug', $slug)->firstOrFail());
    }

    public function page(string $slug): Response
    {
        return $this->renderModel(Page::where('slug', $slug)->firstOrFail());
    }

    private function renderModel(Post|Page $model): Response
    {
        // forInertia() = SEO::forInertia(...) shape (head-key'd meta/link) plus
        // the JSON-LD passed as its own prop, exactly as the Inertia guide says.
        $payload = ExampleSeo::forInertia($model);

        return Inertia::render('Content', [
            'seo' => $payload['seo'],
            'schema' => $payload['schema'],
            'locale' => $payload['locale'],
            'content' => [
                'title' => $model->getSEOTitle(),
                'excerpt' => $model->getSEODescription(),
            ],
        ]);
    }
}
