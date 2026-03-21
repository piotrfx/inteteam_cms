<?php

declare(strict_types=1);

namespace App\Services\Mcp\Tools;

use App\Models\CmsMcpToken;
use App\Models\CmsPage;
use App\Services\Mcp\McpTool;
use App\Services\RevisionService;

final class UpdatePageBlocksTool implements McpTool
{
    public function __construct(private readonly RevisionService $revisionService) {}

    public function name(): string
    {
        return 'update_page_blocks';
    }

    public function description(): string
    {
        return 'Stage new blocks for a page. Creates a staged revision — does NOT publish immediately. Requires write permission.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['page_id', 'blocks'],
            'properties' => [
                'page_id' => ['type' => 'string', 'description' => 'Page ULID'],
                'blocks' => [
                    'type' => 'array',
                    'description' => 'Full blocks array to stage. Each block must have id, type, and data.',
                ],
                'summary' => [
                    'type' => 'string',
                    'maxLength' => 255,
                    'description' => 'Brief description of the change (e.g. "Updated hero heading").',
                ],
            ],
        ];
    }

    public function execute(array $input, CmsMcpToken $token): array
    {
        if (!$token->hasPermission('write')) {
            return ['error' => 'Write permission required.'];
        }

        $page = CmsPage::find($input['page_id'] ?? '');

        if ($page === null) {
            return ['error' => 'Page not found.'];
        }

        $blocks = is_array($input['blocks'] ?? null) ? $input['blocks'] : [];
        $summary = is_string($input['summary'] ?? null) ? $input['summary'] : 'AI edit';

        $revision = $this->revisionService->stagePageRevision(
            $page,
            $blocks,
            $summary,
            'ai_agent',
            $token->id,
        );

        return [
            'staged' => true,
            'revision_id' => $revision->id,
            'page_id' => $page->id,
            'message' => 'Blocks staged. Use create_preview to preview, or publish_staged to publish.',
        ];
    }
}
