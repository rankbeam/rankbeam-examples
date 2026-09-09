<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Rankbeam\Seo\Traits\HasSEO;
use Spatie\Translatable\HasTranslations;

class Post extends Model
{
    use HasSEO;
    use HasTranslations;

    protected $guarded = [];

    public array $translatable = ['title', 'slug', 'content'];

    public function getUrlForSEO(): string
    {
        return url('/'.$this->getLocale().'/posts/'.rawurlencode($this->slug));
    }

    public function getContentForSEO(): string
    {
        return $this->content;
    }

    public function getSEOAlternates(): array
    {
        $alternates = [];
        foreach ($this->getTranslations('slug') as $locale => $slug) {
            $alternates[] = ['hreflang' => str_replace('_', '-', $locale), 'href' => url('/'.$locale.'/posts/'.rawurlencode($slug))];
        }

        return $alternates;
    }
}
