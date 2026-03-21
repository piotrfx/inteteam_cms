<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\CreatePostData;
use App\DTO\UpdatePostData;
use App\Models\CmsPost;
use App\Models\CmsUser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class PostService
{
    public function create(CmsUser $author, CreatePostData $data): CmsPost
    {
        $slug = Str::slug($data->slug ?: $data->title);

        $post = CmsPost::create([
            'author_id'            => $author->id,
            'title'                => $data->title,
            'slug'                 => $slug,
            'excerpt'              => $data->excerpt,
            'blocks'               => $data->blocks,
            'status'               => $data->status,
            'published_at'         => $data->status === 'published' ? now() : null,
            'featured_image_path'  => $data->featuredImagePath,
            'seo_title'            => $data->seoTitle,
            'seo_description'      => $data->seoDescription,
            'seo_og_image_path'    => $data->seoOgImagePath,
            'seo_canonical_url'    => $data->seoCanonicalUrl,
            'seo_robots'           => $data->seoRobots,
        ]);

        if ($post->status === 'published') {
            $this->bustCache($post);
        }

        return $post;
    }

    public function update(CmsPost $post, UpdatePostData $data): CmsPost
    {
        $slug = Str::slug($data->slug ?: $data->title);

        $post->update([
            'title'               => $data->title,
            'slug'                => $slug,
            'excerpt'             => $data->excerpt,
            'blocks'              => $data->blocks,
            'status'              => $data->status,
            'featured_image_path' => $data->featuredImagePath,
            'seo_title'           => $data->seoTitle,
            'seo_description'     => $data->seoDescription,
            'seo_og_image_path'   => $data->seoOgImagePath,
            'seo_canonical_url'   => $data->seoCanonicalUrl,
            'seo_robots'          => $data->seoRobots,
        ]);

        $this->bustCache($post);

        return $post->fresh() ?? $post;
    }

    public function publish(CmsPost $post): CmsPost
    {
        $post->update([
            'status'       => 'published',
            'published_at' => $post->published_at ?? now(),
        ]);

        $this->bustCache($post);

        return $post->fresh() ?? $post;
    }

    public function unpublish(CmsPost $post): CmsPost
    {
        $post->update(['status' => 'draft']);

        $this->bustCache($post);

        return $post->fresh() ?? $post;
    }

    public function delete(CmsPost $post): void
    {
        $this->bustCache($post);
        $post->delete();
    }

    private function bustCache(CmsPost $post): void
    {
        $key = "cms:post:{$post->company_id}:{$post->slug}";
        Cache::forget($key);
    }
}
