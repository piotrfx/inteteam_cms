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
use App\Services\CrmApiClientFactory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind BlockRendererService — resolves active theme + CRM factory from current company if bound
        $this->app->bind(BlockRendererService::class, function (): BlockRendererService {
            $company = app()->bound('current_company') ? app('current_company') : null;
            $theme = $company?->theme ?? 'default';

            return new BlockRendererService(
                theme: $theme,
                company: $company,
                crmFactory: app(CrmApiClientFactory::class),
            );
        });
    }

    public function boot(): void
    {
        Gate::policy(CmsMedia::class, CmsMediaPolicy::class);
        Gate::policy(CmsPage::class, CmsPagePolicy::class);
        Gate::policy(CmsPost::class, CmsPostPolicy::class);

        // ── Block type registry ───────────────────────────────────────────────
        // Adding a new block type: call register() here — never edit an enum.
        // Local block types (always available)
        BlockTypeRegistry::register('heading', 'Heading', 'H');
        BlockTypeRegistry::register('rich_text', 'Rich Text', '¶');
        BlockTypeRegistry::register('image', 'Image', '🖼');
        BlockTypeRegistry::register('cta', 'Call to Action', '→');
        BlockTypeRegistry::register('divider', 'Divider', '—');

        // CRM block types (shown in editor only when CRM is connected — checked at render time)
        BlockTypeRegistry::register('gallery', 'Gallery', '🖼', crm: true);
        BlockTypeRegistry::register('storefront', 'Storefront', '🛒', crm: true);
        BlockTypeRegistry::register('crm_form', 'Embedded Form', '📋', crm: true);
        BlockTypeRegistry::register('business_updates', 'Business Updates', '📢', crm: true);
    }
}
