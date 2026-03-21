<?php

declare(strict_types=1);

namespace App\Services\Mcp\Tools;

use App\DTO\CreatePostData;
use App\Models\CmsMcpToken;
use App\Models\CmsUser;
use App\Services\Mcp\McpTool;
use App\Services\PostService;

final class CreatePostTool implements McpTool
{
    public function __construct(private readonly PostService $postService) {}

    public function name(): string
    {
        return 'create_post';
    }

    public function description(): string
    {
        return 'Create a new blog post as a draft. Requires write permission.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['title', 'slug'],
            'properties' => [
                'title' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Post title'],
                'slug' => ['type' => 'string', 'maxLength' => 255, 'description' => 'URL slug'],
                'excerpt' => ['type' => 'string', 'description' => 'Short summary shown in listings.'],
                'blocks' => ['type' => 'array',  'description' => 'Initial blocks. Defaults to empty.'],
                'seo_title' => ['type' => 'string', 'maxLength' => 255],
                'seo_description' => ['type' => 'string', 'maxLength' => 160],
            ],
        ];
    }

    public function execute(array $input, CmsMcpToken $token): array
    {
        if (!$token->hasPermission('write')) {
            return ['error' => 'Write permission required.'];
        }

        $creator = CmsUser::find($token->created_by);

        if ($creator === null) {
            return ['error' => 'Token has no associated user.'];
        }

        $data = new CreatePostData(
            title: is_string($input['title'] ?? null) ? $input['title'] : '',
            slug: is_string($input['slug'] ?? null) ? $input['slug'] : '',
            excerpt: is_string($input['excerpt'] ?? null) ? $input['excerpt'] : null,
            blocks: is_array($input['blocks'] ?? null) ? $input['blocks'] : [],
            status: 'draft',
            featuredImagePath: null,
            seoTitle: is_string($input['seo_title'] ?? null) ? $input['seo_title'] : null,
            seoDescription: is_string($input['seo_description'] ?? null) ? $input['seo_description'] : null,
            seoOgImagePath: null,
            seoCanonicalUrl: null,
            seoRobots: 'index',
        );

        $post = $this->postService->create($creator, $data);

        return [
            'created' => true,
            'post_id' => $post->id,
            'slug' => $post->slug,
            'status' => $post->status,
        ];
    }
}
