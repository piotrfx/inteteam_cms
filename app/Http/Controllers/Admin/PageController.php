<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\DTO\CreatePageData;
use App\DTO\UpdatePageData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePageRequest;
use App\Http\Requests\Admin\UpdatePageRequest;
use App\Models\CmsPage;
use App\Services\PageService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class PageController extends Controller
{
    public function __construct(private readonly PageService $pageService) {}

    public function index(): Response
    {
        abort_unless(auth('cms')->user()?->can('viewAny', CmsPage::class), 403);

        $pages = CmsPage::orderByDesc('created_at')
            ->get(['id', 'title', 'slug', 'type', 'status', 'published_at', 'created_at']);

        return Inertia::render('Admin/Pages/Index', [
            'pages' => $pages,
        ]);
    }

    public function create(): Response
    {
        abort_unless(auth('cms')->user()?->can('create', CmsPage::class), 403);

        return Inertia::render('Admin/Pages/Create');
    }

    public function store(StorePageRequest $request): RedirectResponse
    {
        abort_unless(auth('cms')->user()?->can('create', CmsPage::class), 403);

        try {
            $page = $this->pageService->create(
                creator: auth('cms')->user(),
                data: CreatePageData::fromRequest($request),
            );
        } catch (\DomainException $e) {
            return back()->withErrors(['type' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.pages.edit', $page->id)
            ->with(['alert' => 'Page created.', 'type' => 'success']);
    }

    public function edit(CmsPage $page): Response
    {
        abort_unless(auth('cms')->user()?->can('update', $page), 403);

        return Inertia::render('Admin/Pages/Edit', [
            'page' => $page,
        ]);
    }

    public function update(UpdatePageRequest $request, CmsPage $page): RedirectResponse
    {
        abort_unless(auth('cms')->user()?->can('update', $page), 403);

        $this->pageService->update($page, UpdatePageData::fromRequest($request));

        return back()->with(['alert' => 'Page saved.', 'type' => 'success']);
    }

    public function destroy(CmsPage $page): RedirectResponse
    {
        abort_unless(auth('cms')->user()?->can('delete', $page), 403);

        $this->pageService->delete($page);

        return redirect()->route('admin.pages.index')
            ->with(['alert' => 'Page deleted.', 'type' => 'success']);
    }

    public function publish(CmsPage $page): RedirectResponse
    {
        abort_unless(auth('cms')->user()?->can('publish', $page), 403);

        $this->pageService->publish($page);

        return back()->with(['alert' => 'Page published.', 'type' => 'success']);
    }

    public function unpublish(CmsPage $page): RedirectResponse
    {
        abort_unless(auth('cms')->user()?->can('publish', $page), 403);

        $this->pageService->unpublish($page);

        return back()->with(['alert' => 'Page unpublished.', 'type' => 'success']);
    }
}
