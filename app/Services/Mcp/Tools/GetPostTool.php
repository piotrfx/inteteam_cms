<?php

declare(strict_types=1);

namespace App\Services\Mcp\Tools;

use App\Models\CmsMcpToken;
use App\Models\CmsPost;
use App\Services\Mcp\McpTool;

final class GetPostTool implements McpTool
{
    public function name(): string
    {
        return 'get_post';
    }

    public function description(): string
    {
        return 'Get full details of a blog post including blocks, excerpt, and SEO fields. Use the post id or slug.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'string', 'description' => 'Post ULID'],
                'slug' => ['type' => 'string', 'description' => 'Post slug'],
            ],
            'oneOf' => [
                ['required' => ['id']],
                ['required' => ['slug']],
            ],
        ];
    }

    public function execute(array $input, CmsMcpToken $token): array
    {
        $post = isset($input['id'])
            ? CmsPost::find($input['id'])
            : CmsPost::where('slug', $input['slug'] ?? '')->first();

        if ($post === null) {
            return ['error' => 'Post not found.'];
        }

        return [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'status' => $post->status,
                'excerpt' => $post->excerpt,
                'blocks' => $post->blocks ?? [],
                'live_revision_id' => $post->live_revision_id,
                'staged_revision_id' => $post->staged_revision_id,
                'seo_title' => $post->seo_title,
                'seo_description' => $post->seo_description,
                'seo_robots' => $post->seo_robots,
                'featured_image_path' => $post->featured_image_path,
                'published_at' => $post->published_at?->toIso8601String(),
                'updated_at' => $post->updated_at?->toIso8601String(),
            ],
        ];
    }
}
