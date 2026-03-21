<?php

declare(strict_types=1);

namespace App\Services\Mcp;

/**
 * Registry for MCP tools.
 * Tools are registered at boot in AppServiceProvider — never hardcoded here.
 */
final class McpToolRegistry
{
    /** @var array<string, McpTool> */
    private static array $tools = [];

    public static function register(McpTool $tool): void
    {
        self::$tools[$tool->name()] = $tool;
    }

    public static function get(string $name): ?McpTool
    {
        return self::$tools[$name] ?? null;
    }

    /** @return array<string, McpTool> */
    public static function all(): array
    {
        return self::$tools;
    }

    public static function has(string $name): bool
    {
        return isset(self::$tools[$name]);
    }
}
