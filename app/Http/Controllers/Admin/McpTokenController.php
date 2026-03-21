<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsMcpToken;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class McpTokenController extends Controller
{
    public function index(): Response
    {
        abort_unless(auth('cms')->user()?->role === 'admin', 403);

        /** @var Company $company */
        $company = app('current_company');

        $tokens = CmsMcpToken::orderByDesc('created_at')
            ->get(['id', 'name', 'permissions', 'last_used_at', 'expires_at', 'created_at', 'revoked_at']);

        return Inertia::render('Admin/Settings/AiIntegration', [
            'tokens' => $tokens->map(fn (CmsMcpToken $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'permissions' => $t->permissions,
                'last_used_at' => $t->last_used_at?->toIso8601String(),
                'expires_at' => $t->expires_at?->toIso8601String(),
                'created_at' => $t->created_at?->toIso8601String(),
                'is_valid' => $t->isValid(),
            ])->values(),
            'mcp_endpoint' => url('/mcp/v1'),
            'new_token' => session('new_token'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth('cms')->user()?->role === 'admin', 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', 'in:read,write,publish'],
            'expires_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        /** @var Company $company */
        $company = app('current_company');

        $rawToken = 'mcpsk_' . bin2hex(random_bytes(32));
        $hash = hash('sha256', $rawToken);

        CmsMcpToken::create([
            'company_id' => $company->id,
            'name' => $validated['name'],
            'token_hash' => $hash,
            'permissions' => $validated['permissions'],
            'expires_at' => isset($validated['expires_days'])
                ? now()->addDays((int) $validated['expires_days'])
                : null,
            'created_by' => auth('cms')->id(),
            'created_at' => now(),
        ]);

        return redirect()->route('admin.settings.ai')
            ->with('new_token', $rawToken)
            ->with(['alert' => 'Token created. Copy it now — it will not be shown again.', 'type' => 'success']);
    }

    public function revoke(CmsMcpToken $token): RedirectResponse
    {
        abort_unless(auth('cms')->user()?->role === 'admin', 403);

        $token->update(['revoked_at' => now()]);

        return back()->with(['alert' => 'Token revoked.', 'type' => 'success']);
    }
}
