<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CmsMedia;
use App\Models\CmsUser;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CmsMedia>
 */
final class CmsMediaFactory extends Factory
{
    protected $model = CmsMedia::class;

    public function definition(): array
    {
        $ulid = Str::ulid()->toString();

        return [
            'company_id' => Company::factory(),
            'uploaded_by' => CmsUser::factory(),
            'filename' => $this->faker->word() . '.jpg',
            'path' => "media/company/{$ulid}.jpg",
            'disk' => 'local',
            'mime_type' => 'image/jpeg',
            'size_bytes' => $this->faker->numberBetween(50_000, 2_000_000),
            'width' => 1200,
            'height' => 800,
            'alt_text' => null,
            'caption' => null,
        ];
    }
}
