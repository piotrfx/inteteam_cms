<?php

declare(strict_types=1);

namespace App\Services\Mcp\Tools;

use App\Models\CmsMcpToken;
use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Services\Mcp\McpTool;
use App\Services\RevisionService;

final class PublishStagedTool implements McpTool
{
    public function __construct(private readonly RevisionService $revisionService) {}

    public function name(): string
    {
        return 'publish_staged';
    }

    public function description(): string
    {
        return 'Publish the staged revision for a page or post, making it live immediately. Requires the publish permission (not granted by default).';
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
        if (!$token->hasPermission('publish')) {
            return ['error' => 'Publish permission required. This permission is not granted by default.'];
        }

        $type = is_string($input['content_type'] ?? null) ? $input['content_type'] : '';
        $id = is_string($input['content_id'] ?? null) ? $input['content_id'] : '';

        if ($type === 'page') {
            $content = CmsPage::find($id);
            if ($content === null) {
                return ['error' => 'Page not found.'];
            }
        } elseif ($type === 'post') {
            $content = CmsPost::find($id);
            if ($content === null) {
                return ['error' => 'Post not found.'];
            }
        } else {
            return ['error' => 'content_type must be "page" or "post".'];
        }

        if ($content->staged_revision_id === null) {
            return ['error' => 'No staged revision to publish.'];
        }

        $this->revisionService->publishStaged($content);

        return [
            'published' => true,
            'content_type' => $type,
            'content_id' => $id,
        ];
    }
}
