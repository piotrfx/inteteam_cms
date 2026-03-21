<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\NavigationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class NavigationController extends Controller
{
    public function __construct(private readonly NavigationService $navigationService) {}

    public function index(): Response
    {
        abort_unless(auth('cms')->user()?->role === 'admin', 403);

        $companyId = app('current_company')->id;

        return Inertia::render('Admin/Navigation/Index', [
            'header' => $this->navigationService->get($companyId, 'header') ?? [],
            'footer' => $this->navigationService->get($companyId, 'footer') ?? [],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless(auth('cms')->user()?->role === 'admin', 403);

        $validated = $request->validate([
            'location'        => ['required', 'string', 'in:header,footer'],
            'items'           => ['required', 'array'],
            'items.*.label'   => ['required', 'string', 'max:100'],
            'items.*.url'     => ['required', 'string', 'max:500'],
            'items.*.target'  => ['nullable', 'string', 'in:_self,_blank'],
        ]);

        $companyId = app('current_company')->id;

        $this->navigationService->save(
            $companyId,
            $validated['location'],
            $validated['items'],
        );

        return back()->with(['alert' => 'Navigation saved.', 'type' => 'success']);
    }
}
