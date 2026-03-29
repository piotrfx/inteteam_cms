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
use App\Services\Mcp\McpToolRegistry;
use App\Services\Mcp\Tools\CreatePageTool;
use App\Services\Mcp\Tools\CreatePostTool;
use App\Services\Mcp\Tools\CreatePreviewTool;
use App\Services\Mcp\Tools\DiscardStagedTool;
use App\Services\Mcp\Tools\GetNavigationTool;
use App\Services\Mcp\Tools\GetPageTool;
use App\Services\Mcp\Tools\GetPostTool;
use App\Services\Mcp\Tools\GetSiteSettingsTool;
use App\Services\Mcp\Tools\ListMediaTool;
use App\Services\Mcp\Tools\ListPagesTool;
use App\Services\Mcp\Tools\ListPostsTool;
use App\Services\Mcp\Tools\PublishStagedTool;
use App\Services\Mcp\Tools\UpdateNavigationTool;
use App\Services\Mcp\Tools\UpdatePageBlocksTool;
use App\Services\Mcp\Tools\UpdatePageSeoTool;
use App\Services\Mcp\Tools\UpdatePostBlocksTool;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind BlockRendererService — resolves active theme + CRM factory from current company if bound
        $this->app->bind(BlockRendererService::class, function (): BlockRendererService {
            $company = app()->bound('current_company') ? app('current_company') : null;
            $theme = $company !== null ? $company->theme ?? 'default' : 'default';

            return new BlockRendererService(
                theme: $theme,
                company: $company,
                crmFactory: app(CrmApiClientFactory::class),
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

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

        // ── MCP tool registry ─────────────────────────────────────────────────
        // Read tools
        McpToolRegistry::register(app(ListPagesTool::class));
        McpToolRegistry::register(app(GetPageTool::class));
        McpToolRegistry::register(app(ListPostsTool::class));
        McpToolRegistry::register(app(GetPostTool::class));
        McpToolRegistry::register(app(ListMediaTool::class));
        McpToolRegistry::register(app(GetNavigationTool::class));
        McpToolRegistry::register(app(GetSiteSettingsTool::class));

        // Write tools
        McpToolRegistry::register(app(UpdatePageBlocksTool::class));
        McpToolRegistry::register(app(UpdatePageSeoTool::class));
        McpToolRegistry::register(app(CreatePageTool::class));
        McpToolRegistry::register(app(UpdatePostBlocksTool::class));
        McpToolRegistry::register(app(CreatePostTool::class));
        McpToolRegistry::register(app(UpdateNavigationTool::class));

        // Preview / publish tools
        McpToolRegistry::register(app(CreatePreviewTool::class));
        McpToolRegistry::register(app(PublishStagedTool::class));
        McpToolRegistry::register(app(DiscardStagedTool::class));
    }
}
