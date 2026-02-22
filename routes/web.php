<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Resources API route
Route::get('/resources/{keyword}', [App\Http\Controllers\ResourceController::class, 'show'])
    ->where('keyword', '.*')
    ->name('resources.show');

require __DIR__.'/settings.php';
