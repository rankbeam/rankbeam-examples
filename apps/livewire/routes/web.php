<?php

use App\Livewire\Index;
use App\Livewire\ShowPage;
use App\Livewire\ShowPost;
use Illuminate\Support\Facades\Route;

Route::get('/', Index::class)->name('home');
Route::get('/blog/{slug}', ShowPost::class)->name('blog.show');
Route::get('/pages/{slug}', ShowPage::class)->name('pages.show');
