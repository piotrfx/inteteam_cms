<?php

declare(strict_types=1);

namespace App\Services\Mcp\Tools;

use App\DTO\CreatePageData;
use App\Models\CmsMcpToken;
use App\Models\CmsUser;
use App\Services\Mcp\McpTool;
use App\Services\PageService;

final class CreatePageTool implements McpTool
{
    public function __construct(private readonly PageService $pageService) {}

    public function name(): string
    {
        return 'create_page';
    }

    public function description(): string
    {
        return 'Create a new page as a draft. Requires write permission.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['title', 'slug'],
            'properties' => [
                'title' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Page title'],
                'slug' => ['type' => 'string', 'maxLength' => 255, 'description' => 'URL slug'],
                'type' => ['type' => 'string', 'description' => 'Page type. Defaults to "custom".'],
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

        try {
            $data = new CreatePageData(
                title: is_string($input['title'] ?? null) ? $input['title'] : '',
                slug: is_string($input['slug'] ?? null) ? $input['slug'] : '',
                type: is_string($input['type'] ?? null) ? $input['type'] : 'custom',
                blocks: is_array($input['blocks'] ?? null) ? $input['blocks'] : [],
                status: 'draft',
                seoTitle: is_string($input['seo_title'] ?? null) ? $input['seo_title'] : null,
                seoDescription: is_string($input['seo_description'] ?? null) ? $input['seo_description'] : null,
                seoOgImagePath: null,
                seoCanonicalUrl: null,
                seoRobots: 'index',
                seoSchemaType: 'WebPage',
            );

            $page = $this->pageService->create($creator, $data);
        } catch (\DomainException $e) {
            return ['error' => $e->getMessage()];
        }

        return [
            'created' => true,
            'page_id' => $page->id,
            'slug' => $page->slug,
            'status' => $page->status,
        ];
    }
}
