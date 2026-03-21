<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\CmsNavigation;
use App\Models\CmsPost;
use App\Services\BlockRendererService;
use App\Services\SeoMetaService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

final class PublicPostController extends Controller
{
    public function __construct(
        private readonly BlockRendererService $blockRenderer,
        private readonly SeoMetaService $seoMeta,
    ) {}

    public function index(): Response
    {
        $company = app('current_company');
        $cacheKey = "cms:blog:{$company->id}";
        $theme = $company->theme ?? 'default';

        $html = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($company, $theme): string {
            /** @var Collection<int, CmsPost> $posts */
            $posts = CmsPost::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('status', 'published')
                ->orderByDesc('published_at')
                ->get(['id', 'title', 'slug', 'excerpt', 'featured_image_path', 'published_at']);

            // @phpstan-ignore argument.type
            return view("themes.{$theme}.blog", [
                'company' => $company,
                'posts' => $posts,
                'seo' => $this->seoMeta->forBlog($company),
                'nav' => $this->resolveNav($company->id),
            ])->render();
        });

        return response($html, 200)->header('Content-Type', 'text/html');
    }

    public function show(string $slug): Response
    {
        $company = app('current_company');
        $cacheKey = "cms:post:{$company->id}:{$slug}";
        $theme = $company->theme ?? 'default';

        $html = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($company, $slug, $theme): ?string {
            $post = CmsPost::withoutGlobalScopes()
                ->with('author:id,name')
                ->where('company_id', $company->id)
                ->where('slug', $slug)
                ->where('status', 'published')
                ->first();

            if (!$post) {
                return null;
            }

            // @phpstan-ignore argument.type
            return view("themes.{$theme}.post", [
                'company' => $company,
                'post' => $post,
                'renderedBlocks' => $this->blockRenderer->render(is_array($post->blocks) ? $post->blocks : []),
                'seo' => $this->seoMeta->forPost($post, $company),
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
