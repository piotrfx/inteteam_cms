<?php

declare(strict_types=1);

namespace App\Services\Mcp\Tools;

use App\Models\CmsMcpToken;
use App\Models\CmsPost;
use App\Services\Mcp\McpTool;

final class ListPostsTool implements McpTool
{
    public function name(): string
    {
        return 'list_posts';
    }

    public function description(): string
    {
        return 'List all blog posts for this website. Returns id, title, slug, status, excerpt, and published date.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'status' => [
                    'type' => 'string',
                    'enum' => ['draft', 'published', 'scheduled'],
                    'description' => 'Filter by status. Omit to return all posts.',
                ],
                'limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 100,
                    'description' => 'Max number of posts to return. Defaults to 50.',
                ],
            ],
        ];
    }

    public function execute(array $input, CmsMcpToken $token): array
    {
        $query = CmsPost::query()->orderByDesc('published_at')->orderByDesc('created_at');

        if (isset($input['status']) && is_string($input['status'])) {
            $query->where('status', $input['status']);
        }

        $limit = is_int($input['limit'] ?? null) ? min($input['limit'], 100) : 50;
        $posts = $query->limit($limit)->get(['id', 'title', 'slug', 'status', 'excerpt', 'published_at', 'updated_at']);

        return [
            'posts' => $posts->map(fn (CmsPost $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'status' => $p->status,
                'excerpt' => $p->excerpt,
                'published_at' => $p->published_at?->toIso8601String(),
                'updated_at' => $p->updated_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }
}
