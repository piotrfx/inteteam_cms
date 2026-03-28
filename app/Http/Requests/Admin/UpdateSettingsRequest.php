<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'primary_colour' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme' => ['nullable', 'string', 'max:50'],
            'seo_site_name' => ['nullable', 'string', 'max:255'],
            'seo_title_suffix' => ['nullable', 'string', 'max:100'],
            'seo_meta_description' => ['nullable', 'string', 'max:160'],
            'seo_robots' => ['nullable', 'string', Rule::in(['index,follow', 'noindex,nofollow'])],
            'seo_google_verification' => ['nullable', 'string', 'max:255'],
            'seo_twitter_handle' => ['nullable', 'string', 'max:50'],
            'seo_address_street' => ['nullable', 'string', 'max:255'],
            'seo_address_city' => ['nullable', 'string', 'max:100'],
            'seo_address_postcode' => ['nullable', 'string', 'max:20'],
            'seo_phone' => ['nullable', 'string', 'max:30'],
            'seo_price_range' => ['nullable', 'string', 'max:10'],
        ];
    }
}
