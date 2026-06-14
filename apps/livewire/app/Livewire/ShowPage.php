<?php

declare(strict_types=1);

namespace App\Livewire;

use Livewire\Component;
use Rankbeam\Examples\Models\Page;

class ShowPage extends Component
{
    public Page $page;

    public function mount(string $slug): void
    {
        $this->page = Page::where('slug', $slug)->firstOrFail();
    }

    public function render()
    {
        return view('livewire.show', ['model' => $this->page])
            ->layout('layouts.app', ['seoModel' => $this->page]);
    }
}
