<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Rankbeam\Seo\Traits\HasSEO;

class PostTranslation extends Model
{
    use HasSEO;

    protected $guarded = [];

    public function getUrlForSEO(): string
    {
        return url('/'.$this->locale.'/translations/'.$this->slug);
    }

    public function getContentForSEO(): string
    {
        return $this->content;
    }
}
