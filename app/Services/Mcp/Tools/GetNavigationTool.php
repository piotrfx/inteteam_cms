<?php

declare(strict_types=1);

namespace App\Services\Mcp\Tools;

use App\Models\CmsMcpToken;
use App\Services\Mcp\McpTool;
use App\Services\NavigationService;

final class GetNavigationTool implements McpTool
{
    public function __construct(private readonly NavigationService $navService) {}

    public function name(): string
    {
        return 'get_navigation';
    }

    public function description(): string
    {
        return 'Get the header and footer navigation menus for this website.';
    }

    public function inputSchema(): array
    {
        return ['type' => 'object', 'properties' => []];
    }

    public function execute(array $input, CmsMcpToken $token): array
    {
        return [
            'navigation' => [
                'header' => $this->navService->get($token->company_id, 'header') ?? [],
                'footer' => $this->navService->get($token->company_id, 'footer') ?? [],
            ],
        ];
    }
}
