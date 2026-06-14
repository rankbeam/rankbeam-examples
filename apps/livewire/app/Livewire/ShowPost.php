<?php

declare(strict_types=1);

namespace App\Livewire;

use Livewire\Component;
use Rankbeam\Examples\Models\Post;

class ShowPost extends Component
{
    public Post $post;

    public function mount(string $slug): void
    {
        $this->post = Post::where('slug', $slug)->firstOrFail();
    }

    public function render()
    {
        return view('livewire.show', ['model' => $this->post])
            ->layout('layouts.app', ['seoModel' => $this->post]);
    }
}
