<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        /** @var string $cmsDomain */
        $cmsDomain = config('cms.domain', 'cms.inte.team');

        // Extract slug from {slug}.cms.inte.team
        $slug = null;

        if (str_ends_with($host, '.' . $cmsDomain)) {
            $slug = substr($host, 0, strlen($host) - strlen('.' . $cmsDomain));
        }

        if ($slug !== null && $slug !== '') {
            $company = Company::withoutGlobalScopes()
                ->where('slug', $slug)
                ->where('is_active', true)
                ->first();

            if ($company !== null) {
                app()->instance('current_company', $company);
            }
        }

        return $next($request);
    }
}
