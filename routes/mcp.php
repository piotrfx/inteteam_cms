<?php

declare(strict_types=1);

use App\Http\Controllers\McpController;
use App\Http\Middleware\AuthenticateMcpToken;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| MCP (Model Context Protocol) routes
|--------------------------------------------------------------------------
| Single JSON-RPC 2.0 endpoint, authenticated via Bearer token (SHA-256 hash).
| ResolveTenant middleware runs first (via web stack), so current_company
| is already bound when AuthenticateMcpToken fires.
*/

Route::post('/mcp/v1', McpController::class . '@handle')
    ->name('mcp.handle')
    ->middleware([AuthenticateMcpToken::class])
    ->withoutMiddleware([HandleInertiaRequests::class])
    ->middleware('throttle:120,1');
