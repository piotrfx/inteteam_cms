<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Platform root — redirects to login
Route::get('/', function () {
    return redirect()->route('admin.login');
});
