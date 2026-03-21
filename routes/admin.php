<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin routes
|--------------------------------------------------------------------------
| Prefixed /admin, guarded by the 'cms' auth guard.
| All routes here require the tenant to be resolved first.
*/

// Auth (unauthenticated)
Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('login', [\App\Http\Controllers\Admin\Auth\LoginController::class, 'showLogin'])
        ->name('login');
    Route::post('login', [\App\Http\Controllers\Admin\Auth\LoginController::class, 'login'])
        ->name('login.attempt');
    Route::post('logout', [\App\Http\Controllers\Admin\Auth\LoginController::class, 'logout'])
        ->name('logout')
        ->middleware('auth:cms');
});

// Authenticated admin panel
Route::prefix('admin')->name('admin.')->middleware(['auth:cms'])->group(function (): void {
    Route::get('dashboard', function () {
        return inertia('Admin/Dashboard');
    })->name('dashboard');
});
