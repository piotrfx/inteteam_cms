<?php

declare(strict_types=1);

namespace App\DTO;

use Illuminate\Http\Request;

final readonly class CreatePageData
{
    public function __construct(
        public string $title,
        public string $slug,
        public string $type,
        public array $blocks,
        public string $status,
        public ?string $seoTitle,
        public ?string $seoDescription,
        public ?string $seoOgImagePath,
        public ?string $seoCanonicalUrl,
        public ?string $seoRobots,
        public string $seoSchemaType,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            title: $request->string('title')->toString(),
            slug: $request->string('slug')->toString(),
            type: $request->string('type', 'custom')->toString(),
            blocks: $request->array('blocks', []),
            status: $request->string('status', 'draft')->toString(),
            seoTitle: $request->string('seo_title')->toString() ?: null,
            seoDescription: $request->string('seo_description')->toString() ?: null,
            seoOgImagePath: $request->string('seo_og_image_path')->toString() ?: null,
            seoCanonicalUrl: $request->string('seo_canonical_url')->toString() ?: null,
            seoRobots: $request->string('seo_robots')->toString() ?: null,
            seoSchemaType: $request->string('seo_schema_type', 'WebPage')->toString(),
        );
    }
}
