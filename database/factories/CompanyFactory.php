<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
final class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        $name = $this->faker->company() . ' Repairs';

        return [
            'name' => $name,
            'slug' => $this->faker->unique()->slug(2),
            'domain' => null,
            'logo_path' => null,
            'favicon_path' => null,
            'crm_base_url' => null,
            'crm_company_id' => null,
            'crm_api_key' => null,
            'seo_site_name' => $name,
            'seo_title_suffix' => '| ' . $name,
            'seo_meta_description' => $this->faker->sentence(),
            'primary_colour' => '#6366f1',
            'theme' => 'default',
            'plan' => 'starter',
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function withDomain(string $domain): static
    {
        return $this->state(['domain' => $domain]);
    }
}
