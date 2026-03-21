<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'               => ['required', 'string', 'max:255'],
            'slug'                => ['required', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/'],
            'excerpt'             => ['nullable', 'string', 'max:500'],
            'blocks'              => ['array'],
            'status'              => ['required', 'string', 'in:draft,published'],
            'featured_image_path' => ['nullable', 'string', 'max:500'],
            'seo_title'           => ['nullable', 'string', 'max:255'],
            'seo_description'     => ['nullable', 'string', 'max:160'],
            'seo_og_image_path'   => ['nullable', 'string', 'max:500'],
            'seo_canonical_url'   => ['nullable', 'url', 'max:500'],
            'seo_robots'          => ['nullable', 'string', 'in:index,follow,noindex,nofollow'],
        ];
    }
}
