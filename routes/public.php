<?php

declare(strict_types=1);

use App\Http\Controllers\Site\CrmFormController;
use App\Http\Controllers\Site\PreviewController;
use App\Http\Controllers\Site\PreviewDiscardController;
use App\Http\Controllers\Site\PreviewPublishController;
use App\Http\Controllers\Site\PublicPageController;
use App\Http\Controllers\Site\PublicPostController;
use App\Http\Controllers\Site\SitemapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public site routes — served at {slug}.cms.inte.team
|--------------------------------------------------------------------------
| ResolveTenant middleware binds app('current_company') before these run.
| All routes abort(404) if no published content exists for the slug.
*/

// CRM form proxy — company is specified in URL so it works cross-tenant
Route::post('/forms/crm/{company}/{slug}', [CrmFormController::class, 'submit'])->name('crm.form.submit');

// Preview routes — no auth, token is the secret
Route::get('/preview/{token}', PreviewController::class)->name('preview.show');
Route::post('/preview/{token}/publish', PreviewPublishController::class)->name('preview.publish');
Route::post('/preview/{token}/discard', PreviewDiscardController::class)->name('preview.discard');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

Route::get('/blog', [PublicPostController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [PublicPostController::class, 'show'])->name('blog.show');

// Home — must come after /blog to avoid shadowing it
Route::get('/', [PublicPageController::class, 'home'])->name('public.home');

// Catch-all for named pages (about, contact, custom slugs, etc.)
// Must be last to not shadow /admin, /blog, /sitemap.xml, /robots.txt
Route::get('/{slug}', [PublicPageController::class, 'show'])
    ->where('slug', '^(?!admin|login).*$')
    ->name('public.page');
