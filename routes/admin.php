<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Auth\ForgotPasswordController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\CrmSettingsController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\NavigationController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\RevisionController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StagedController;
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

// ── Logout ────────────────────────────────────────────────────────────────
Route::post('admin/logout', [LoginController::class, 'logout'])
    ->name('admin.logout')
    ->middleware('auth:cms');

// ── Authenticated admin panel ─────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware('auth:cms')->group(function (): void {

    Route::get('dashboard', function () {
        return inertia('Admin/Dashboard');
    })->name('dashboard');

    // Media
    Route::get('media', [MediaController::class, 'index'])->name('media.index');
    Route::post('media', [MediaController::class, 'store'])->name('media.store');
    Route::patch('media/{media}', [MediaController::class, 'update'])->name('media.update');
    Route::delete('media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');

    // Pages
    Route::get('pages', [PageController::class, 'index'])->name('pages.index');
    Route::get('pages/create', [PageController::class, 'create'])->name('pages.create');
    Route::post('pages', [PageController::class, 'store'])->name('pages.store');
    Route::get('pages/{page}/edit', [PageController::class, 'edit'])->name('pages.edit');
    Route::match(['PUT', 'POST'], 'pages/{page}', [PageController::class, 'update'])->name('pages.update');
    Route::delete('pages/{page}', [PageController::class, 'destroy'])->name('pages.destroy');
    Route::post('pages/{page}/publish', [PageController::class, 'publish'])->name('pages.publish');
    Route::post('pages/{page}/unpublish', [PageController::class, 'unpublish'])->name('pages.unpublish');

    // Posts
    Route::get('posts', [PostController::class, 'index'])->name('posts.index');
    Route::get('posts/create', [PostController::class, 'create'])->name('posts.create');
    Route::post('posts', [PostController::class, 'store'])->name('posts.store');
    Route::get('posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::match(['PUT', 'POST'], 'posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
    Route::post('posts/{post}/publish', [PostController::class, 'publish'])->name('posts.publish');
    Route::post('posts/{post}/unpublish', [PostController::class, 'unpublish'])->name('posts.unpublish');

    // Revisions + staged actions
    Route::get('pages/{page}/revisions', [RevisionController::class, 'pageIndex'])->name('pages.revisions');
    Route::post('pages/{page}/revisions/{revision}/restore', [RevisionController::class, 'restorePage'])->name('pages.revisions.restore');
    Route::post('pages/{page}/staged/publish', [StagedController::class, 'publishPage'])->name('pages.staged.publish');
    Route::post('pages/{page}/staged/discard', [StagedController::class, 'discardPage'])->name('pages.staged.discard');
    Route::get('pages/{page}/staged/preview', [StagedController::class, 'previewPage'])->name('pages.staged.preview');

    Route::get('posts/{post}/revisions', [RevisionController::class, 'postIndex'])->name('posts.revisions');
    Route::post('posts/{post}/revisions/{revision}/restore', [RevisionController::class, 'restorePost'])->name('posts.revisions.restore');
    Route::post('posts/{post}/staged/publish', [StagedController::class, 'publishPost'])->name('posts.staged.publish');
    Route::post('posts/{post}/staged/discard', [StagedController::class, 'discardPost'])->name('posts.staged.discard');
    Route::get('posts/{post}/staged/preview', [StagedController::class, 'previewPost'])->name('posts.staged.preview');

    // Navigation
    Route::get('navigation', [NavigationController::class, 'index'])->name('navigation.index');
    Route::post('navigation', [NavigationController::class, 'update'])->name('navigation.update');

    // Settings
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');

    // CRM Integration
    Route::get('settings/crm', [CrmSettingsController::class, 'show'])->name('settings.crm');
    Route::post('settings/crm', [CrmSettingsController::class, 'update'])->name('settings.crm.update');
    Route::post('settings/crm/test', [CrmSettingsController::class, 'testConnection'])->name('settings.crm.test');
});
