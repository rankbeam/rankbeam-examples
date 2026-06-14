<?php

declare(strict_types=1);

namespace App\Livewire;

use Livewire\Component;
use Rankbeam\Examples\Fixtures;

class Index extends Component
{
    public function render()
    {
        return view('livewire.index', ['pages' => Fixtures::pages()])
            ->layout('layouts.app');
    }
}
