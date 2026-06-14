<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Rankbeam\Examples\Fixtures;
use Rankbeam\Examples\Models\Page;
use Rankbeam\Examples\Models\Post;

/**
 * The whole Blade app: an index that links the three contract pages, plus a
 * single show view per model. SEO is rendered in the layout from the model.
 */
class ContentController
{
    public function index()
    {
        return view('index', ['pages' => Fixtures::pages()]);
    }

    public function post(string $slug)
    {
        $post = Post::where('slug', $slug)->firstOrFail();

        return view('show', ['model' => $post]);
    }

    public function page(string $slug)
    {
        $page = Page::where('slug', $slug)->firstOrFail();

        return view('show', ['model' => $page]);
    }
}
