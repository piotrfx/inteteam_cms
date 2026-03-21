<?php

declare(strict_types=1);

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

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt',  [SitemapController::class, 'robots'])->name('robots');

Route::get('/blog',        [PublicPostController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [PublicPostController::class, 'show'])->name('blog.show');

// Home — must come after /blog to avoid shadowing it
Route::get('/',            [PublicPageController::class, 'home'])->name('public.home');

// Catch-all for named pages (about, contact, custom slugs, etc.)
// Must be last to not shadow /admin, /blog, /sitemap.xml, /robots.txt
Route::get('/{slug}', [PublicPageController::class, 'show'])
    ->where('slug', '^(?!admin|login).*$')
    ->name('public.page');
