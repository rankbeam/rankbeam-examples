<?php

declare(strict_types=1);

namespace Rankbeam\Examples\Models;

use Illuminate\Database\Eloquent\Model;
use Rankbeam\Seo\Traits\HasSEO;

/**
 * A static page — resolves to og:type=website (the class name is not one the
 * computed builder maps to an article/product/etc.). Used for the "bare"
 * teardown target and the "noindex" page, so the matrix proves article:* is
 * never fabricated on a non-article page.
 */
class Page extends Model
{
    use HasSEO;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'is_indexable',
    ];

    protected $casts = [
        'is_indexable' => 'boolean',
    ];

    /** Canonical / og:url source. */
    public function getUrlForSEO(): string
    {
        return url("/pages/{$this->slug}");
    }

    public function getSEOTitle(): ?string
    {
        return $this->title;
    }

    public function getSEODescription(): ?string
    {
        return $this->excerpt;
    }

    public function getContentForSEO(): string
    {
        return (string) $this->content;
    }

    /**
     * First-class hreflang via the laravel-seo >= 3.0 resolver hook.
     *
     * @return array<int, array{hreflang: string, href: string}>|null
     */
    public function getSEOAlternates(): ?array
    {
        $alternates = \Rankbeam\Examples\Fixtures::alternatesFor('page', (string) $this->slug);

        return $alternates === [] ? null : $alternates;
    }
}
