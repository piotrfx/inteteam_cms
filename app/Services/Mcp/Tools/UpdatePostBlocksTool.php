<?php

declare(strict_types=1);

namespace App\Services\Mcp\Tools;

use App\Models\CmsMcpToken;
use App\Models\CmsPost;
use App\Services\Mcp\McpTool;
use App\Services\RevisionService;

final class UpdatePostBlocksTool implements McpTool
{
    public function __construct(private readonly RevisionService $revisionService) {}

    public function name(): string
    {
        return 'update_post_blocks';
    }

    public function description(): string
    {
        return 'Stage new blocks for a blog post. Creates a staged revision — does NOT publish immediately. Requires write permission.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['post_id', 'blocks'],
            'properties' => [
                'post_id' => ['type' => 'string', 'description' => 'Post ULID'],
                'blocks' => [
                    'type' => 'array',
                    'description' => 'Full blocks array to stage.',
                ],
                'summary' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'Brief description of the change.',
                ],
            ],
        ];
    }

    public function execute(array $input, CmsMcpToken $token): array
    {
        if (!$token->hasPermission('write')) {
            return ['error' => 'Write permission required.'];
        }

        $post = CmsPost::find($input['post_id'] ?? '');

        if ($post === null) {
            return ['error' => 'Post not found.'];
        }

        $blocks = is_array($input['blocks'] ?? null) ? $input['blocks'] : [];
        $summary = is_string($input['summary'] ?? null) ? $input['summary'] : 'AI edit';

        $revision = $this->revisionService->stagePostRevision(
            $post,
            $blocks,
            $summary,
            'ai_agent',
            $token->id,
        );

        return [
            'staged' => true,
            'revision_id' => $revision->id,
            'post_id' => $post->id,
            'message' => 'Blocks staged. Use create_preview to preview, or publish_staged to publish.',
        ];
    }
}
