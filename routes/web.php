<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Platform root — redirects to login
Route::get('/', function () {
    return redirect()->route('admin.login');
});

// Named alias so Laravel's auth middleware redirect works
Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');
