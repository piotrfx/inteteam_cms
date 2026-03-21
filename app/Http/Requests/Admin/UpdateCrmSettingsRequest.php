<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateCrmSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'crm_base_url' => ['nullable', 'url', 'max:255'],
            'crm_company_id' => ['nullable', 'string', 'max:255'],
            'crm_api_key' => ['nullable', 'string', 'max:500'],
        ];
    }
}
