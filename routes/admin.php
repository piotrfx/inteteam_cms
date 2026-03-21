<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Auth\ForgotPasswordController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin routes
|--------------------------------------------------------------------------
*/

// ── Guest-only auth routes ────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware('guest:cms')->group(function (): void {
    Route::get('login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('login', [LoginController::class, 'login'])->name('login.attempt');

    Route::get('forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])
        ->name('password.request');
    Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])
        ->name('password.email');

    Route::get('reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])
        ->name('password.reset');
    Route::post('reset-password', [ResetPasswordController::class, 'reset'])
        ->name('password.update');
});

// ── Logout (auth only) ────────────────────────────────────────────────────
Route::post('admin/logout', [LoginController::class, 'logout'])
    ->name('admin.logout')
    ->middleware('auth:cms');

// ── Authenticated admin panel ─────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware('auth:cms')->group(function (): void {
    Route::get('dashboard', function () {
        return inertia('Admin/Dashboard');
    })->name('dashboard');
});
