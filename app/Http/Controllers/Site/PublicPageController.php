<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\CmsNavigation;
use App\Models\CmsPage;
use App\Services\BlockRendererService;
use App\Services\SeoMetaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

final class PublicPageController extends Controller
{
    public function __construct(
        private readonly BlockRendererService $blockRenderer,
        private readonly SeoMetaService $seoMeta,
    ) {}

    public function show(string $slug): Response
    {
        abort_unless(app()->bound('current_company'), 404);

        $company = app('current_company');
        $theme = $company->theme ?? 'default';
        $cacheKey = "cms:page:{$company->id}:{$slug}";

        $html = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($company, $slug, $theme): ?string {
            $page = CmsPage::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('slug', $slug)
                ->where('status', 'published')
                ->first();

            if (!$page) {
                return null;
            }

            // @phpstan-ignore argument.type
            return view("themes.{$theme}.page", [
                'company' => $company,
                'page' => $page,
                'renderedBlocks' => $this->blockRenderer->render(is_array($page->blocks) ? $page->blocks : []),
                'seo' => $this->seoMeta->forPage($page, $company),
                'nav' => $this->resolveNav($company->id),
            ])->render();
        });

        if ($html === null) {
            abort(404);
        }

        return response($html, 200)->header('Content-Type', 'text/html');
    }

    public function home(): Response|RedirectResponse
    {
        if (!app()->bound('current_company')) {
            return redirect()->route('admin.login');
        }

        $company = app('current_company');
        $cacheKey = "cms:page:{$company->id}:home";

        $html = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($company): ?string {
            $page = CmsPage::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('type', 'home')
                ->where('status', 'published')
                ->first();

            if (!$page) {
                return null;
            }

            $theme = $company->theme ?? 'default';

            // @phpstan-ignore argument.type
            return view("themes.{$theme}.page", [
                'company' => $company,
                'page' => $page,
                'renderedBlocks' => $this->blockRenderer->render(is_array($page->blocks) ? $page->blocks : []),
                'seo' => $this->seoMeta->forPage($page, $company),
                'nav' => $this->resolveNav($company->id),
            ])->render();
        });

        if ($html === null) {
            abort(404);
        }

        return response($html, 200)->header('Content-Type', 'text/html');
    }

    /** @return array{header: array, footer: array} */
    private function resolveNav(string $companyId): array
    {
        $rows = CmsNavigation::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereIn('location', ['header', 'footer'])
            ->get(['location', 'items']);

        $nav = ['header' => [], 'footer' => []];

        foreach ($rows as $row) {
            $nav[$row->location] = $row->items ?? [];
        }

        return $nav;
    }
}
