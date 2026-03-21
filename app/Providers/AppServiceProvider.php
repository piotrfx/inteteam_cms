<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\CmsMedia;
use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Policies\CmsMediaPolicy;
use App\Policies\CmsPagePolicy;
use App\Policies\CmsPostPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(CmsMedia::class, CmsMediaPolicy::class);
        Gate::policy(CmsPage::class, CmsPagePolicy::class);
        Gate::policy(CmsPost::class, CmsPostPolicy::class);
    }
}
