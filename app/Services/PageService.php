<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\CreatePageData;
use App\DTO\UpdatePageData;
use App\Models\CmsPage;
use App\Models\CmsUser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class PageService
{
    /** Fixed-type pages: only one per company is allowed. */
    private const UNIQUE_TYPES = ['home', 'about', 'contact', 'privacy', 'terms'];

    public function create(CmsUser $creator, CreatePageData $data): CmsPage
    {
        $this->assertTypeUnique($data->type);

        $slug = $this->resolveSlug($data->type, $data->slug);

        $page = CmsPage::create([
            'title' => $data->title,
            'slug' => $slug,
            'type' => $data->type,
            'blocks' => $data->blocks,
            'status' => $data->status,
            'published_at' => $data->status === 'published' ? now() : null,
            'seo_title' => $data->seoTitle,
            'seo_description' => $data->seoDescription,
            'seo_og_image_path' => $data->seoOgImagePath,
            'seo_canonical_url' => $data->seoCanonicalUrl,
            'seo_robots' => $data->seoRobots,
            'seo_schema_type' => $data->seoSchemaType,
            'created_by' => $creator->id,
        ]);

        if ($page->status === 'published') {
            $this->bustCache($page);
        }

        return $page;
    }

    public function update(CmsPage $page, UpdatePageData $data): CmsPage
    {
        $slug = $this->resolveSlug($page->type, $data->slug);

        $page->update([
            'title' => $data->title,
            'slug' => $slug,
            'blocks' => $data->blocks,
            'status' => $data->status,
            'seo_title' => $data->seoTitle,
            'seo_description' => $data->seoDescription,
            'seo_og_image_path' => $data->seoOgImagePath,
            'seo_canonical_url' => $data->seoCanonicalUrl,
            'seo_robots' => $data->seoRobots,
            'seo_schema_type' => $data->seoSchemaType,
        ]);

        $this->bustCache($page);

        return $page->fresh() ?? $page;
    }

    public function publish(CmsPage $page): CmsPage
    {
        $page->update([
            'status' => 'published',
            'published_at' => $page->published_at ?? now(),
        ]);

        $this->bustCache($page);

        return $page->fresh() ?? $page;
    }

    public function unpublish(CmsPage $page): CmsPage
    {
        $page->update(['status' => 'draft']);

        $this->bustCache($page);

        return $page->fresh() ?? $page;
    }

    public function delete(CmsPage $page): void
    {
        $this->bustCache($page);
        $page->delete();
    }

    public function findBySlug(string $companyId, string $slug): ?CmsPage
    {
        return CmsPage::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('slug', $slug)
            ->where('status', 'published')
            ->first();
    }

    public function findHome(string $companyId): ?CmsPage
    {
        return CmsPage::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('type', 'home')
            ->where('status', 'published')
            ->first();
    }

    private function assertTypeUnique(string $type): void
    {
        if (! in_array($type, self::UNIQUE_TYPES, true)) {
            return;
        }

        $exists = CmsPage::where('type', $type)->exists();

        if ($exists) {
            throw new \DomainException("A page of type '{$type}' already exists for this company.");
        }
    }

    private function resolveSlug(string $type, string $rawSlug): string
    {
        return match ($type) {
            'home'    => 'home',
            'about'   => 'about',
            'contact' => 'contact',
            'privacy' => 'privacy-policy',
            'terms'   => 'terms-and-conditions',
            default   => Str::slug($rawSlug),
        };
    }

    private function bustCache(CmsPage $page): void
    {
        $key = "cms:page:{$page->company_id}:{$page->slug}";
        Cache::forget($key);
    }
}
