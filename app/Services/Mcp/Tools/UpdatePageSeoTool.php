<?php

declare(strict_types=1);

namespace App\Services\Mcp\Tools;

use App\Models\CmsMcpToken;
use App\Models\CmsPage;
use App\Services\Mcp\McpTool;

final class UpdatePageSeoTool implements McpTool
{
    public function name(): string
    {
        return 'update_page_seo';
    }

    public function description(): string
    {
        return 'Update the SEO metadata fields for a page (title, description, robots). Changes apply immediately. Requires write permission.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['page_id'],
            'properties' => [
                'page_id' => ['type' => 'string', 'description' => 'Page ULID'],
                'seo_title' => ['type' => 'string', 'maxLength' => 255, 'description' => 'SEO title tag'],
                'seo_description' => ['type' => 'string', 'maxLength' => 160, 'description' => 'Meta description'],
                'seo_robots' => ['type' => 'string', 'enum' => ['index', 'noindex'], 'description' => 'Robots directive'],
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

        $updates = array_filter([
            'seo_title' => is_string($input['seo_title'] ?? null) ? $input['seo_title'] : null,
            'seo_description' => is_string($input['seo_description'] ?? null) ? $input['seo_description'] : null,
            'seo_robots' => is_string($input['seo_robots'] ?? null) ? $input['seo_robots'] : null,
        ], fn ($v) => $v !== null);

        if (empty($updates)) {
            return ['error' => 'No SEO fields provided.'];
        }

        $page->update($updates);

        return [
            'updated' => true,
            'page_id' => $page->id,
            'fields' => array_keys($updates),
        ];
    }
}
