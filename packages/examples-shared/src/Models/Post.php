<?php

declare(strict_types=1);

namespace Rankbeam\Examples\Models;

use Illuminate\Database\Eloquent\Model;
use Rankbeam\Seo\Traits\HasSEO;

/**
 * A blog post — resolves to og:type=article (the class name contains "post",
 * so SEOComputedBuilder infers the article type). Exercises the article:*
 * surface of the Rendering Contract.
 */
class Post extends Model
{
    use HasSEO;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'author_name',
        'category',
        'tags',
        'published_at',
        'is_indexable',
    ];

    protected $casts = [
        'tags' => 'array',
        'published_at' => 'datetime',
        'is_indexable' => 'boolean',
    ];

    /** Canonical / og:url source. */
    public function getUrlForSEO(): string
    {
        return url("/blog/{$this->slug}");
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
     * First-class hreflang via the laravel-seo >= 3.0 resolver hook
     * (SEOComputedBuilder reads this and populates SEOData.alternates).
     *
     * @return array<int, array{hreflang: string, href: string}>|null
     */
    public function getSEOAlternates(): ?array
    {
        $alternates = \Rankbeam\Examples\Fixtures::alternatesFor('post', (string) $this->slug);

        return $alternates === [] ? null : $alternates;
    }
}
