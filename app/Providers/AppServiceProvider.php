<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\CmsMedia;
use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Policies\CmsMediaPolicy;
use App\Policies\CmsPagePolicy;
use App\Policies\CmsPostPolicy;
use App\Services\BlockRendererService;
use App\Services\BlockTypeRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind BlockRendererService — resolves active theme from current company if bound
        $this->app->bind(BlockRendererService::class, function (): BlockRendererService {
            $company = app()->bound('current_company') ? app('current_company') : null;
            $theme   = $company?->theme ?? 'default';

            return new BlockRendererService($theme);
        });
    }

    public function boot(): void
    {
        Gate::policy(CmsMedia::class, CmsMediaPolicy::class);
        Gate::policy(CmsPage::class, CmsPagePolicy::class);
        Gate::policy(CmsPost::class, CmsPostPolicy::class);

        // ── Block type registry ───────────────────────────────────────────────
        // Adding a new block type: call register() here — never edit an enum.
        BlockTypeRegistry::register('heading',   'Heading',       'H');
        BlockTypeRegistry::register('rich_text', 'Rich Text',     '¶');
        BlockTypeRegistry::register('image',     'Image',         '🖼');
        BlockTypeRegistry::register('cta',       'Call to Action','→');
        BlockTypeRegistry::register('divider',   'Divider',       '—');
    }
}
