<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CmsPost;
use App\Models\CmsUser;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CmsPost>
 */
final class CmsPostFactory extends Factory
{
    protected $model = CmsPost::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(5, false);
        $title = rtrim($title, '.');

        return [
            'company_id' => Company::factory(),
            'author_id' => CmsUser::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => $this->faker->paragraph(),
            'blocks' => [],
            'status' => 'draft',
            'published_at' => null,
            'featured_image_path' => null,
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

    public function scheduled(): static
    {
        return $this->state([
            'status' => 'scheduled',
            'published_at' => now()->addWeek(),
        ]);
    }
}
