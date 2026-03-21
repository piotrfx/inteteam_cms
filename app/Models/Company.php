<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string|null $seo_robots
 * @property string|null $seo_site_name
 * @property string|null $seo_meta_description
 * @property string|null $seo_og_image_path
 * @property string|null $seo_title_suffix
 * @property string|null $seo_address_street
 * @property string|null $seo_address_city
 * @property string|null $seo_address_postcode
 * @property string|null $seo_phone
 * @property string|null $seo_price_range
 * @property array|null $seo_opening_hours
 */
final class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'logo_path',
        'favicon_path',
        'crm_base_url',
        'crm_company_id',
        'crm_api_key',
        'seo_site_name',
        'seo_title_suffix',
        'seo_meta_description',
        'seo_og_image_path',
        'seo_twitter_handle',
        'seo_google_verification',
        'seo_robots',
        'seo_address_street',
        'seo_address_city',
        'seo_address_postcode',
        'seo_phone',
        'seo_opening_hours',
        'seo_price_range',
        'primary_colour',
        'theme',
        'settings',
        'plan',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'seo_opening_hours' => 'array',
            'settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(CmsUser::class);
    }

    public function hasCrmConnection(): bool
    {
        return filled($this->crm_base_url)
            && filled($this->crm_company_id)
            && filled($this->crm_api_key);
    }
}
