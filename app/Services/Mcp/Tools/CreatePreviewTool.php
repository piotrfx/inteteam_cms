<?php

declare(strict_types=1);

namespace App\Services\Mcp\Tools;

use App\Models\CmsMcpToken;
use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Services\Mcp\McpTool;
use App\Services\PreviewTokenService;

final class CreatePreviewTool implements McpTool
{
    public function __construct(private readonly PreviewTokenService $previewService) {}

    public function name(): string
    {
        return 'create_preview';
    }

    public function description(): string
    {
        return 'Create a 48-hour preview link for a staged revision. Returns a URL to review changes before publishing. Requires write permission.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['content_type', 'content_id'],
            'properties' => [
                'content_type' => ['type' => 'string', 'enum' => ['page', 'post']],
                'content_id' => ['type' => 'string', 'description' => 'Page or post ULID'],
            ],
        ];
    }

    public function execute(array $input, CmsMcpToken $token): array
    {
        if (!$token->hasPermission('write')) {
            return ['error' => 'Write permission required.'];
        }

        $type = is_string($input['content_type'] ?? null) ? $input['content_type'] : '';
        $id = is_string($input['content_id'] ?? null) ? $input['content_id'] : '';

        if ($type === 'page') {
            $content = CmsPage::find($id);
            if ($content === null) {
                return ['error' => 'Page not found.'];
            }
            if ($content->staged_revision_id === null) {
                return ['error' => 'No staged revision exists for this page.'];
            }
            $previewToken = $this->previewService->createForPage($content, 'ai_agent');
        } elseif ($type === 'post') {
            $content = CmsPost::find($id);
            if ($content === null) {
                return ['error' => 'Post not found.'];
            }
            if ($content->staged_revision_id === null) {
                return ['error' => 'No staged revision exists for this post.'];
            }
            $previewToken = $this->previewService->createForPost($content, 'ai_agent');
        } else {
            return ['error' => 'content_type must be "page" or "post".'];
        }

        return [
            'preview_url' => route('preview.show', $previewToken->token),
            'expires_at' => $previewToken->expires_at->toIso8601String(),
        ];
    }
}
