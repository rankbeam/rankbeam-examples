<?php

use App\Http\Controllers\ContentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ContentController::class, 'index'])->name('home');
Route::get('/blog/{slug}', [ContentController::class, 'post'])->name('blog.show');
Route::get('/pages/{slug}', [ContentController::class, 'page'])->name('pages.show');
