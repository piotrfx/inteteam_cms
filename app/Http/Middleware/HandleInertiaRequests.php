<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Company;
use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /** @return array<string, mixed> */
    public function share(Request $request): array
    {
        $company = app()->bound('current_company') ? app('current_company') : null;

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user('cms') ? [
                    'id' => $request->user('cms')->id,
                    'name' => $request->user('cms')->name,
                    'email' => $request->user('cms')->email,
                    'role' => $request->user('cms')->role,
                ] : null,
            ],
            'company' => $company instanceof Company ? [
                'id' => $company->id,
                'name' => $company->name,
                'slug' => $company->slug,
                'theme' => $company->theme,
                'primary_colour' => $company->primary_colour,
                'logo_path' => $company->logo_path,
            ] : null,
            'flash' => [
                'alert' => $request->session()->get('alert'),
                'type' => $request->session()->get('type'),
            ],
        ];
    }
}
