<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\CmsMcpToken;
use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateMcpToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $rawToken = $request->bearerToken();

        if ($rawToken === null || $rawToken === '') {
            return $this->unauthorized('Missing bearer token.');
        }

        if (!app()->bound('current_company')) {
            return $this->unauthorized('No company context for this request.');
        }

        /** @var Company $company */
        $company = app('current_company');

        $hash = hash('sha256', $rawToken);

        $token = CmsMcpToken::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('token_hash', $hash)
            ->first();

        if ($token === null || !$token->isValid()) {
            return $this->unauthorized('Invalid or expired token.');
        }

        $token->update(['last_used_at' => now()]);

        app()->instance('current_mcp_token', $token);

        return $next($request);
    }

    private function unauthorized(string $message): Response
    {
        return response()->json([
            'jsonrpc' => '2.0',
            'error' => ['code' => -32001, 'message' => $message],
            'id' => null,
        ], 401);
    }
}
