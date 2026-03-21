<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CmsPage;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CmsPage>
 */
final class CmsPageFactory extends Factory
{
    protected $model = CmsPage::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(3, false);
        $title = rtrim($title, '.');

        return [
            'company_id' => Company::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'type' => 'custom',
            'blocks' => [],
            'status' => 'draft',
            'published_at' => null,
            'live_revision_id' => null,
            'staged_revision_id' => null,
        ];
    }

    public function published(): static
    {
        return $this->state([
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
    }

    public function draft(): static
    {
        return $this->state([
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    public function homepage(): static
    {
        return $this->state([
            'title' => 'Home',
            'slug' => 'home',
            'type' => 'home',
        ]);
    }
}
