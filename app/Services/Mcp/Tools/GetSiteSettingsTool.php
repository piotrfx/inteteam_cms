<?php

declare(strict_types=1);

namespace App\Services\Mcp\Tools;

use App\Models\CmsMcpToken;
use App\Models\Company;
use App\Services\Mcp\McpTool;

final class GetSiteSettingsTool implements McpTool
{
    public function name(): string
    {
        return 'get_site_settings';
    }

    public function description(): string
    {
        return 'Get public site settings including name, theme, SEO defaults, and contact information.';
    }

    public function inputSchema(): array
    {
        return ['type' => 'object', 'properties' => []];
    }

    public function execute(array $input, CmsMcpToken $token): array
    {
        /** @var Company $company */
        $company = app('current_company');

        return [
            'settings' => [
                'name' => $company->name,
                'slug' => $company->slug,
                'theme' => $company->theme,
                'primary_colour' => $company->primary_colour,
                'seo_site_name' => $company->seo_site_name,
                'seo_title_suffix' => $company->seo_title_suffix,
                'seo_meta_description' => $company->seo_meta_description,
                'seo_robots' => $company->seo_robots,
                'seo_twitter_handle' => $company->seo_twitter_handle,
                'seo_address_street' => $company->seo_address_street,
                'seo_address_city' => $company->seo_address_city,
                'seo_address_postcode' => $company->seo_address_postcode,
                'seo_phone' => $company->seo_phone,
                'seo_price_range' => $company->seo_price_range,
            ],
        ];
    }
}
