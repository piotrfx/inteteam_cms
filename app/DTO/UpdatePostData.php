<?php

declare(strict_types=1);

namespace App\DTO;

use Illuminate\Http\Request;

final readonly class UpdatePostData
{
    public function __construct(
        public string $title,
        public string $slug,
        public ?string $excerpt,
        public array $blocks,
        public string $status,
        public ?string $featuredImagePath,
        public ?string $seoTitle,
        public ?string $seoDescription,
        public ?string $seoOgImagePath,
        public ?string $seoCanonicalUrl,
        public ?string $seoRobots,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            title: $request->string('title')->toString(),
            slug: $request->string('slug')->toString(),
            excerpt: $request->string('excerpt')->toString() ?: null,
            blocks: (array) $request->input('blocks', []),
            status: $request->string('status', 'draft')->toString(),
            featuredImagePath: $request->string('featured_image_path')->toString() ?: null,
            seoTitle: $request->string('seo_title')->toString() ?: null,
            seoDescription: $request->string('seo_description')->toString() ?: null,
            seoOgImagePath: $request->string('seo_og_image_path')->toString() ?: null,
            seoCanonicalUrl: $request->string('seo_canonical_url')->toString() ?: null,
            seoRobots: $request->string('seo_robots')->toString() ?: null,
        );
    }
}
