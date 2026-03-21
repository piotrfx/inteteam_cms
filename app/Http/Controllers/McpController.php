<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CmsMcpToken;
use App\Services\Mcp\McpToolRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON-RPC 2.0 endpoint for the MCP server.
 *
 * Supported methods:
 *   initialize   — server handshake, returns capabilities
 *   tools/list   — returns all available tool schemas
 *   tools/call   — executes a named tool
 */
final class McpController extends Controller
{
    private const SERVER_NAME = 'inteteam-cms';

    private const SERVER_VERSION = '1.0.0';

    public function handle(Request $request): JsonResponse
    {
        $body = $request->json()->all();

        if (!$this->isValidRpc($body)) {
            return $this->rpcError(null, -32600, 'Invalid Request.');
        }

        $id = $body['id'] ?? null;
        $method = $body['method'] ?? '';
        $params = $body['params'] ?? [];

        return match ($method) {
            'initialize' => $this->handleInitialize($id),
            'tools/list' => $this->handleToolsList($id),
            'tools/call' => $this->handleToolsCall($id, is_array($params) ? $params : []),
            default => $this->rpcError($id, -32601, "Method not found: {$method}"),
        };
    }

    private function handleInitialize(mixed $id): JsonResponse
    {
        return $this->rpcResult($id, [
            'protocolVersion' => '2024-11-05',
            'serverInfo' => [
                'name' => self::SERVER_NAME,
                'version' => self::SERVER_VERSION,
            ],
            'capabilities' => [
                'tools' => ['listChanged' => false],
            ],
        ]);
    }

    private function handleToolsList(mixed $id): JsonResponse
    {
        $tools = [];

        foreach (McpToolRegistry::all() as $tool) {
            $tools[] = [
                'name' => $tool->name(),
                'description' => $tool->description(),
                'inputSchema' => $tool->inputSchema(),
            ];
        }

        return $this->rpcResult($id, ['tools' => $tools]);
    }

    /** @param  array<string, mixed>  $params */
    private function handleToolsCall(mixed $id, array $params): JsonResponse
    {
        $name = is_string($params['name'] ?? null) ? $params['name'] : '';
        $input = is_array($params['arguments'] ?? null) ? $params['arguments'] : [];

        if (!McpToolRegistry::has($name)) {
            return $this->rpcError($id, -32601, "Tool not found: {$name}");
        }

        $tool = McpToolRegistry::get($name);
        $token = app('current_mcp_token');

        if (!$token instanceof CmsMcpToken) {
            return $this->rpcError($id, -32001, 'Token context missing.');
        }

        // Read tools require at minimum 'read' permission
        if (!$token->hasPermission('read') && !$token->hasPermission('write') && !$token->hasPermission('publish')) {
            return $this->rpcError($id, -32002, 'Permission denied: token has no valid permissions.');
        }

        try {
            $result = $tool->execute($input, $token);
        } catch (\Throwable $e) {
            return $this->rpcError($id, -32603, 'Tool execution error: ' . $e->getMessage());
        }

        // If the tool returned an error key, surface it as a non-fatal tool result
        return $this->rpcResult($id, [
            'content' => [
                ['type' => 'text', 'text' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)],
            ],
            'isError' => isset($result['error']),
        ]);
    }

    /** @param  array<string, mixed>  $result */
    private function rpcResult(mixed $id, array $result): JsonResponse
    {
        return response()->json([
            'jsonrpc' => '2.0',
            'result' => $result,
            'id' => $id,
        ]);
    }

    private function rpcError(mixed $id, int $code, string $message): JsonResponse
    {
        return response()->json([
            'jsonrpc' => '2.0',
            'error' => ['code' => $code, 'message' => $message],
            'id' => $id,
        ], $code === -32001 ? 401 : 200);
    }

    /** @param  array<string, mixed>  $body */
    private function isValidRpc(array $body): bool
    {
        return ($body['jsonrpc'] ?? '') === '2.0'
            && isset($body['method'])
            && is_string($body['method']);
    }
}
