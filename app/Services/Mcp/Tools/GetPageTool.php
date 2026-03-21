<?php

declare(strict_types=1);

namespace App\Services\Mcp\Tools;

use App\Models\CmsMcpToken;
use App\Models\CmsPage;
use App\Services\Mcp\McpTool;

final class GetPageTool implements McpTool
{
    public function name(): string
    {
        return 'get_page';
    }

    public function description(): string
    {
        return 'Get full details of a page including blocks and SEO fields. Use the page id or slug.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'string', 'description' => 'Page ULID'],
                'slug' => ['type' => 'string', 'description' => 'Page slug'],
            ],
            'oneOf' => [
                ['required' => ['id']],
                ['required' => ['slug']],
            ],
        ];
    }

    public function execute(array $input, CmsMcpToken $token): array
    {
        $page = isset($input['id'])
            ? CmsPage::find(is_string($input['id']) ? $input['id'] : '')
            : CmsPage::where('slug', is_string($input['slug'] ?? null) ? $input['slug'] : '')->first();

        if ($page === null) {
            return ['error' => 'Page not found.'];
        }

        return [
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'type' => $page->type,
                'status' => $page->status,
                'blocks' => $page->blocks ?? [],
                'live_revision_id' => $page->live_revision_id,
                'staged_revision_id' => $page->staged_revision_id,
                'seo_title' => $page->seo_title,
                'seo_description' => $page->seo_description,
                'seo_robots' => $page->seo_robots,
                'published_at' => $page->published_at?->toIso8601String(),
                'updated_at' => $page->updated_at?->toIso8601String(),
            ],
        ];
    }
}
