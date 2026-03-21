<?php

declare(strict_types=1);

namespace App\Services\Mcp;

use App\Models\CmsMcpToken;

interface McpTool
{
    /** Unique snake_case identifier — used in tools/call requests. */
    public function name(): string;

    /** One-sentence description shown to the AI. */
    public function description(): string;

    /**
     * JSON Schema for the tool's input parameters.
     *
     * @return array<string, mixed>
     */
    public function inputSchema(): array;

    /**
     * Execute the tool and return the result.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function execute(array $input, CmsMcpToken $token): array;
}
