<?php

declare(strict_types=1);

namespace App\Services\Mcp\Tools;

use App\Models\CmsMcpToken;
use App\Services\Mcp\McpTool;
use App\Services\NavigationService;

final class UpdateNavigationTool implements McpTool
{
    public function __construct(private readonly NavigationService $navService) {}

    public function name(): string
    {
        return 'update_navigation';
    }

    public function description(): string
    {
        return 'Update the header or footer navigation menu. Changes apply immediately. Requires write permission.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['location', 'items'],
            'properties' => [
                'location' => [
                    'type' => 'string',
                    'enum' => ['header', 'footer'],
                    'description' => 'Which menu to update.',
                ],
                'items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'required' => ['label', 'url'],
                        'properties' => [
                            'label' => ['type' => 'string'],
                            'url' => ['type' => 'string'],
                            'target' => ['type' => 'string', 'enum' => ['_self', '_blank']],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function execute(array $input, CmsMcpToken $token): array
    {
        if (!$token->hasPermission('write')) {
            return ['error' => 'Write permission required.'];
        }

        $location = is_string($input['location'] ?? null) ? $input['location'] : '';
        $items = is_array($input['items'] ?? null) ? $input['items'] : [];

        if (!in_array($location, ['header', 'footer'], true)) {
            return ['error' => 'location must be "header" or "footer".'];
        }

        $this->navService->save($token->company_id, $location, $items);

        return [
            'updated' => true,
            'location' => $location,
            'count' => count($items),
        ];
    }
}
