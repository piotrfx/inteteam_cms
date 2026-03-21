<?php

declare(strict_types=1);

namespace App\Services\Mcp\Tools;

use App\Models\CmsMcpToken;
use App\Models\CmsPage;
use App\Services\Mcp\McpTool;

final class ListPagesTool implements McpTool
{
    public function name(): string
    {
        return 'list_pages';
    }

    public function description(): string
    {
        return 'List all pages for this website. Returns id, title, slug, type, status, and last updated timestamp.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'status' => [
                    'type' => 'string',
                    'enum' => ['draft', 'published'],
                    'description' => 'Filter by status. Omit to return all pages.',
                ],
            ],
        ];
    }

    public function execute(array $input, CmsMcpToken $token): array
    {
        $query = CmsPage::query()->orderBy('title');

        if (isset($input['status']) && is_string($input['status'])) {
            $query->where('status', $input['status']);
        }

        $pages = $query->get(['id', 'title', 'slug', 'type', 'status', 'published_at', 'updated_at']);

        return [
            'pages' => $pages->map(fn (CmsPage $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'type' => $p->type,
                'status' => $p->status,
                'published_at' => $p->published_at?->toIso8601String(),
                'updated_at' => $p->updated_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }
}
