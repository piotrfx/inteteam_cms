<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Models\CmsUser;
use App\Models\Company;
use Illuminate\Support\Facades\Storage;

final class SeoMetaService
{
    /**
     * Build the SEO meta array for a CmsPage.
     *
     * @return array<string, mixed>
     */
    public function forPage(CmsPage $page, Company $company): array
    {
        $title = $page->seo_title
            ?: ($page->title . $this->titleSuffix($company));

        $description = $page->seo_description
            ?: $company->seo_meta_description
            ?: '';

        $ogImage = $page->seo_og_image_path
            ? Storage::url($page->seo_og_image_path)
            : ($company->seo_og_image_path ? Storage::url($company->seo_og_image_path) : null);

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $page->seo_canonical_url ?: url('/' . $page->slug),
            'robots' => $page->seo_robots ?: $company->seo_robots ?: 'index,follow',
            'og_type' => 'website',
            'og_image' => $ogImage,
            'json_ld' => $this->pageJsonLd($page, $company, $title, $description),
        ];
    }

    /**
     * Build the SEO meta array for a CmsPost.
     *
     * @return array<string, mixed>
     */
    public function forPost(CmsPost $post, Company $company): array
    {
        $title = $post->seo_title
            ?: ($post->title . $this->titleSuffix($company));

        $description = $post->seo_description
            ?: $post->excerpt
            ?: $company->seo_meta_description
            ?: '';

        $ogImage = $post->seo_og_image_path
            ? Storage::url($post->seo_og_image_path)
            : ($post->featured_image_path ? Storage::url($post->featured_image_path) : null);

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => url('/blog/' . $post->slug),
            'robots' => $post->seo_robots ?: $company->seo_robots ?: 'index,follow',
            'og_type' => 'article',
            'og_image' => $ogImage,
            'json_ld' => $this->postJsonLd($post, $company, $title, $description),
        ];
    }

    /**
     * Build SEO meta for the blog index.
     *
     * @return array<string, mixed>
     */
    public function forBlog(Company $company): array
    {
        $title = 'Blog' . $this->titleSuffix($company);

        return [
            'title' => $title,
            'description' => $company->seo_meta_description ?? '',
            'canonical' => url('/blog'),
            'robots' => $company->seo_robots ?: 'index,follow',
            'og_type' => 'website',
            'og_image' => $company->seo_og_image_path ? Storage::url($company->seo_og_image_path) : null,
            'json_ld' => null,
        ];
    }

    private function titleSuffix(Company $company): string
    {
        if (!empty($company->seo_title_suffix)) {
            return ' ' . $company->seo_title_suffix;
        }

        if (!empty($company->seo_site_name)) {
            return ' | ' . $company->seo_site_name;
        }

        return ' | ' . $company->name;
    }

    private function pageJsonLd(CmsPage $page, Company $company, string $title, string $description): string
    {
        // Home page → LocalBusiness schema
        if ($page->type === 'home') {
            return $this->localBusinessSchema($company);
        }

        // Contact page → ContactPage
        $schemaType = $page->seo_schema_type ?: 'WebPage';

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $schemaType,
            'name' => $title,
            'description' => $description,
            'url' => url('/' . $page->slug),
        ];

        return (string) json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function postJsonLd(CmsPost $post, Company $company, string $title, string $description): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $title,
            'description' => $description,
            'url' => url('/blog/' . $post->slug),
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
        ];

        if ($post->author instanceof CmsUser) {
            $schema['author'] = ['@type' => 'Person', 'name' => $post->author->name];
        }

        if ($post->featured_image_path) {
            $schema['image'] = Storage::url($post->featured_image_path);
        }

        return (string) json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function localBusinessSchema(Company $company): string
    {
        $schema = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $company->seo_site_name ?: $company->name,
            'url' => url('/'),
            'telephone' => $company->seo_phone ?: null,
            'priceRange' => $company->seo_price_range ?: null,
            'address' => $company->seo_address_street ? [
                '@type' => 'PostalAddress',
                'streetAddress' => $company->seo_address_street,
                'addressLocality' => $company->seo_address_city ?: null,
                'postalCode' => $company->seo_address_postcode ?: null,
                'addressCountry' => 'GB',
            ] : null,
            'openingHoursSpecification' => $this->openingHoursSchema($company),
        ]);

        return (string) json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /** @return list<array<string, mixed>>|null */
    private function openingHoursSchema(Company $company): ?array
    {
        if (empty($company->seo_opening_hours)) {
            return null;
        }

        $specs = [];
        foreach ($company->seo_opening_hours as $entry) {
            $specs[] = array_filter([
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => $entry['day'] ?? null,
                'opens' => $entry['opens'] ?? null,
                'closes' => $entry['closes'] ?? null,
            ]);
        }

        return $specs !== [] ? $specs : null; // @phpstan-ignore notIdentical.alwaysTrue
    }
}
