<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fallback tenant resolution from the authenticated user's company.
 * Runs after auth middleware — covers localhost/non-subdomain dev access.
 */
final class ResolveTenantFromAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!app()->bound('current_company')) {
            $user = auth('cms')->user();

            if ($user !== null) {
                $company = Company::withoutGlobalScopes()
                    ->where('id', $user->company_id)
                    ->where('is_active', true)
                    ->first();

                if ($company !== null) {
                    app()->instance('current_company', $company);
                }
            }
        }

        return $next($request);
    }
}
